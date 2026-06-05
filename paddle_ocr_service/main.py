"""
PaddleOCR Microservice for Laravel Scan Module
================================================
Drop-in replacement for Tesseract OCR.
Runs as a local HTTP service on port 8001.
Loads PaddleOCR model ONCE at startup for fast per-request inference.

Uses PaddleOCR 3.x (PaddleX-based) with paddlepaddle 3.0.0.

Endpoints:
    POST /ocr          - Extract text from an uploaded image
    GET  /health       - Health check

Response format matches what the Laravel ScanController expects:
    {
        "success": true,
        "raw_text": "...",
        "confidence": 82.5,
        "word_count": 210,
        "boxes": [...]            # optional debug data
    }
"""

import io
import os
import sys
import time
import logging
import traceback
from contextlib import asynccontextmanager

# Must be set BEFORE importing paddle / paddleocr
os.environ["PADDLE_PDX_DISABLE_MODEL_SOURCE_CHECK"] = "True"

import numpy as np
from PIL import Image, ImageEnhance, ImageOps, ImageStat
from fastapi import FastAPI, File, UploadFile, Form, HTTPException
from fastapi.responses import JSONResponse

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[logging.StreamHandler(sys.stdout)],
)
logger = logging.getLogger("paddle_ocr_service")

# ---------------------------------------------------------------------------
# Global model holders – loaded once in lifespan
# ---------------------------------------------------------------------------
baseline_ocr_engine = None
fast_ocr_engine = None

# Detector rollout controls (calibration mode)
FAST_DET_ENABLED = os.environ.get("PADDLE_OCR_FAST_DET_ENABLED", "true").lower() in ("1", "true", "yes", "on")
BASELINE_DET_MODEL = os.environ.get("PADDLE_OCR_BASELINE_DET_MODEL", "PP-OCRv5_server_det")
FAST_DET_MODEL = os.environ.get("PADDLE_OCR_FAST_DET_MODEL", "PP-OCRv4_mobile_det")
REC_MODEL_NAME = os.environ.get("PADDLE_OCR_REC_MODEL", "en_PP-OCRv5_mobile_rec")

# Guarded fallback thresholds (initial calibration candidates)
FAST_GATE_MIN_CONFIDENCE = float(os.environ.get("PADDLE_OCR_FAST_GATE_MIN_CONFIDENCE", "55.0"))
FAST_GATE_MIN_WORDS = int(os.environ.get("PADDLE_OCR_FAST_GATE_MIN_WORDS", "60"))
FAST_GATE_MIN_BOXES = int(os.environ.get("PADDLE_OCR_FAST_GATE_MIN_BOXES", "20"))

# Keep fallback enabled at all times for this rollout.
FAST_FALLBACK_ALWAYS_ON = True


def _build_engine(det_model_name: str):
    from paddleocr import PaddleOCR

    return PaddleOCR(
        lang="en",
        text_detection_model_name=det_model_name,
        text_recognition_model_name=REC_MODEL_NAME,
        use_doc_orientation_classify=False,
        use_doc_unwarping=False,
        use_textline_orientation=False,
        text_det_box_thresh=0.5,
        text_det_thresh=0.3,
    )


def _load_model():
    """Load PaddleOCR model. Called once at startup."""
    global baseline_ocr_engine, fast_ocr_engine

    logger.info("Loading baseline PaddleOCR model (det=%s, rec=%s)", BASELINE_DET_MODEL, REC_MODEL_NAME)
    baseline_ocr_engine = _build_engine(BASELINE_DET_MODEL)
    logger.info("Baseline PaddleOCR model loaded successfully.")

    fast_ocr_engine = None
    if FAST_DET_ENABLED:
        try:
            logger.info("Loading fast detector model (det=%s, rec=%s)", FAST_DET_MODEL, REC_MODEL_NAME)
            fast_ocr_engine = _build_engine(FAST_DET_MODEL)
            logger.info("Fast detector model loaded successfully.")
        except Exception as e:
            # Stay in baseline-only mode if fast detector is unavailable.
            fast_ocr_engine = None
            logger.warning("Fast detector initialization failed; using baseline only: %s", e)


def _parse_prediction(results: list) -> dict:
    """Parse PaddleOCR results into normalized OCR metrics."""
    if not results:
        return {
            "raw_text": "",
            "confidence": 0.0,
            "word_count": 0,
            "line_count": 0,
            "boxes": [],
            "box_count": 0,
        }

    page = results[0]
    rec_texts = page.get("rec_texts", [])
    rec_scores = page.get("rec_scores", [])
    dt_polys = page.get("dt_polys", [])

    boxes_detail = []
    confidences = []

    for i, text in enumerate(rec_texts):
        conf = float(rec_scores[i]) if i < len(rec_scores) else 0.0
        box = dt_polys[i].tolist() if i < len(dt_polys) and hasattr(dt_polys[i], "tolist") else (
            dt_polys[i] if i < len(dt_polys) else []
        )

        boxes_detail.append({
            "text": text.strip(),
            "confidence": round(conf * 100, 2),
            "box": box,
        })
        confidences.append(conf)

    boxes_detail.sort(key=lambda b: (
        round(min(p[1] for p in b["box"]) / 20) * 20 if b["box"] else 0,
        min(p[0] for p in b["box"]) if b["box"] else 0,
    ))

    raw_text = "\n".join(b["text"] for b in boxes_detail)
    avg_confidence = (sum(confidences) / len(confidences) * 100) if confidences else 0.0

    return {
        "raw_text": raw_text,
        "confidence": round(avg_confidence, 2),
        "word_count": len(raw_text.split()),
        "line_count": len(boxes_detail),
        "boxes": boxes_detail,
        "box_count": len(boxes_detail),
    }


def _run_engine_predict(engine, img_array) -> tuple[list, float]:
    infer_start = time.perf_counter()
    results = list(engine.predict(img_array))
    infer_ms = (time.perf_counter() - infer_start) * 1000
    return results, infer_ms


def _evaluate_fast_fallback_gates(parsed: dict) -> list[str]:
    """Return reasons to fallback from fast detector to baseline detector."""
    reasons = []

    if parsed.get("confidence", 0.0) < FAST_GATE_MIN_CONFIDENCE:
        reasons.append("low_confidence")
    if parsed.get("word_count", 0) < FAST_GATE_MIN_WORDS:
        reasons.append("low_word_count")
    if parsed.get("box_count", 0) < FAST_GATE_MIN_BOXES:
        reasons.append("low_box_count")

    return reasons


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Load model on startup, clean up on shutdown."""
    _load_model()
    yield
    logger.info("PaddleOCR service shutting down.")


# ---------------------------------------------------------------------------
# FastAPI app
# ---------------------------------------------------------------------------
app = FastAPI(
    title="PaddleOCR Service",
    version="1.0.0",
    lifespan=lifespan,
)


# ---------------------------------------------------------------------------
# Image preprocessing helpers
# ---------------------------------------------------------------------------

# Maximum long-edge after scaling — balances OCR quality vs CPU speed.
# 1024x1536 ~= 1.6M pixels, significantly faster on CPU while still readable.
MAX_LONG_EDGE = max(1024, int(os.environ.get("PADDLE_OCR_MAX_LONG_EDGE", "1536")))

# Upload/input normalization controls (source-agnostic).
SMALL_IMAGE_LONG_EDGE = max(700, int(os.environ.get("PADDLE_OCR_SMALL_IMAGE_LONG_EDGE", "900")))
SMALL_IMAGE_UPSCALE_FACTOR = max(1.0, float(os.environ.get("PADDLE_OCR_SMALL_IMAGE_UPSCALE_FACTOR", "1.35")))
ENABLE_EXIF_TRANSPOSE = os.environ.get("PADDLE_OCR_ENABLE_EXIF_TRANSPOSE", "true").lower() in ("1", "true", "yes", "on")
ENABLE_ADAPTIVE_ENHANCEMENT = os.environ.get("PADDLE_OCR_ENABLE_ADAPTIVE_ENHANCEMENT", "true").lower() in ("1", "true", "yes", "on")

# Guarded ROI-first controls. Keep conservative defaults.
ROI_ENABLED = os.environ.get("PADDLE_OCR_ROI_ENABLED", "true").lower() in ("1", "true", "yes", "on")
ROI_SUPPORTED_TYPES = {
    t.strip()
    for t in os.environ.get("PADDLE_OCR_ROI_SUPPORTED_TYPES", "birth_certificate,death_certificate").split(",")
    if t.strip()
}
ROI_BG_THRESHOLD = int(os.environ.get("PADDLE_OCR_ROI_BG_THRESHOLD", "245"))
ROI_ROW_ACTIVATION_RATIO = float(os.environ.get("PADDLE_OCR_ROI_ROW_ACTIVATION_RATIO", "0.01"))
ROI_COL_ACTIVATION_RATIO = float(os.environ.get("PADDLE_OCR_ROI_COL_ACTIVATION_RATIO", "0.01"))
ROI_MARGIN_RATIO = float(os.environ.get("PADDLE_OCR_ROI_MARGIN_RATIO", "0.04"))
ROI_MIN_AREA_RATIO = float(os.environ.get("PADDLE_OCR_ROI_MIN_AREA_RATIO", "0.35"))
ROI_MAX_AREA_RATIO = float(os.environ.get("PADDLE_OCR_ROI_MAX_AREA_RATIO", "0.92"))

# ROI quality gates (initial calibration candidates).
ROI_MIN_CONFIDENCE = float(os.environ.get("PADDLE_OCR_ROI_MIN_CONFIDENCE", "58.0"))
ROI_MIN_WORDS = int(os.environ.get("PADDLE_OCR_ROI_MIN_WORDS", "70"))
ROI_MIN_BOXES = int(os.environ.get("PADDLE_OCR_ROI_MIN_BOXES", "25"))

# Confidence threshold to trigger second pass. Keep current default behavior.
PASS2_CONFIDENCE_THRESHOLD = float(os.environ.get("PADDLE_OCR_PASS2_THRESHOLD", "50.0"))


def _choose_enhancement_profile(gray_img: Image.Image, document_type: str) -> tuple[str, float, float, float]:
    """Choose conservative enhancement strengths based on a quick contrast proxy."""
    stddev = float(ImageStat.Stat(gray_img).stddev[0]) if gray_img.size[0] > 0 and gray_img.size[1] > 0 else 0.0

    if not ENABLE_ADAPTIVE_ENHANCEMENT:
        if document_type in ("birth_certificate", "death_certificate"):
            return "fixed", 2.0, 2.0, 1.1
        if document_type == "marriage_certificate":
            return "fixed", 1.8, 2.0, 1.1
        return "fixed", 1.5, 2.0, 1.1

    if stddev < 28.0:
        profile = "strong"
        contrast = 2.1
        sharpness = 2.1
        brightness = 1.12
    elif stddev < 45.0:
        profile = "standard"
        contrast = 1.9
        sharpness = 2.0
        brightness = 1.1
    else:
        profile = "mild"
        contrast = 1.6
        sharpness = 1.8
        brightness = 1.06

    if document_type == "marriage_certificate":
        contrast = max(1.5, contrast - 0.1)
    return profile, contrast, sharpness, brightness


def _is_roi_supported(document_type: str) -> bool:
    return ROI_ENABLED and document_type in ROI_SUPPORTED_TYPES


def _detect_content_roi(img: Image.Image) -> dict:
    """Detect a conservative certificate content bounding box; return metadata and crop box."""
    w, h = img.size
    if w <= 0 or h <= 0:
        return {
            "applied": False,
            "reason": "invalid_image_size",
            "crop_box": None,
            "area_ratio": 1.0,
        }

    gray = np.array(img.convert("L"))
    if gray.size == 0:
        return {
            "applied": False,
            "reason": "empty_image",
            "crop_box": None,
            "area_ratio": 1.0,
        }

    dark_mask = gray < ROI_BG_THRESHOLD
    min_row_pixels = max(6, int(w * ROI_ROW_ACTIVATION_RATIO))
    min_col_pixels = max(6, int(h * ROI_COL_ACTIVATION_RATIO))

    rows = np.where(dark_mask.sum(axis=1) >= min_row_pixels)[0]
    cols = np.where(dark_mask.sum(axis=0) >= min_col_pixels)[0]

    if len(rows) == 0 or len(cols) == 0:
        return {
            "applied": False,
            "reason": "no_content_projection",
            "crop_box": None,
            "area_ratio": 1.0,
        }

    y1, y2 = int(rows[0]), int(rows[-1])
    x1, x2 = int(cols[0]), int(cols[-1])

    pad_x = max(8, int((x2 - x1 + 1) * ROI_MARGIN_RATIO))
    pad_y = max(8, int((y2 - y1 + 1) * ROI_MARGIN_RATIO))

    x1 = max(0, x1 - pad_x)
    y1 = max(0, y1 - pad_y)
    x2 = min(w - 1, x2 + pad_x)
    y2 = min(h - 1, y2 + pad_y)

    crop_w = max(1, x2 - x1 + 1)
    crop_h = max(1, y2 - y1 + 1)
    area_ratio = (crop_w * crop_h) / float(w * h)

    if area_ratio < ROI_MIN_AREA_RATIO:
        return {
            "applied": False,
            "reason": "roi_too_small",
            "crop_box": [x1, y1, x2, y2],
            "area_ratio": round(area_ratio, 4),
        }

    if area_ratio > ROI_MAX_AREA_RATIO:
        return {
            "applied": False,
            "reason": "roi_not_meaningful",
            "crop_box": [x1, y1, x2, y2],
            "area_ratio": round(area_ratio, 4),
        }

    return {
        "applied": True,
        "reason": "ok",
        "crop_box": [x1, y1, x2, y2],
        "area_ratio": round(area_ratio, 4),
    }


def _evaluate_roi_quality_gates(result: dict) -> list[str]:
    """Return reasons indicating ROI OCR result is too weak and should fallback."""
    reasons = []
    if result.get("confidence", 0.0) < ROI_MIN_CONFIDENCE:
        reasons.append("roi_low_confidence")
    if result.get("word_count", 0) < ROI_MIN_WORDS:
        reasons.append("roi_low_word_count")
    if result.get("box_count", 0) < ROI_MIN_BOXES:
        reasons.append("roi_low_box_count")
    return reasons


def preprocess_image(img: Image.Image, document_type: str = "birth_certificate") -> tuple[Image.Image, dict]:
    """
    Enhance image for OCR. Uses adaptive scaling: upscale small images
    but cap at MAX_LONG_EDGE so large images do not become expensive.

    Returns a PIL Image ready for PaddleOCR and additive normalization metadata.
    """
    original_w, original_h = img.size

    if ENABLE_EXIF_TRANSPOSE:
        img = ImageOps.exif_transpose(img)

    if img.mode not in ("RGB", "L"):
        img = img.convert("RGB")

    w, h = img.size
    long_edge = max(w, h)
    profile = "standard"

    # Runtime-focused resize policy: cap oversized inputs and avoid unnecessary medium-image upscaling.
    if long_edge > MAX_LONG_EDGE:
        target = MAX_LONG_EDGE
    elif long_edge < SMALL_IMAGE_LONG_EDGE:
        target = min(int(long_edge * SMALL_IMAGE_UPSCALE_FACTOR), MAX_LONG_EDGE)
    else:
        target = long_edge

    scale = target / long_edge
    if abs(scale - 1.0) > 0.01:
        new_w, new_h = int(w * scale), int(h * scale)
        img = img.resize((new_w, new_h), Image.LANCZOS)
        direction = "up" if scale > 1.0 else "down"
        logger.info("Scaled image %dx%d -> %dx%d (%.2fx, %s)", w, h, new_w, new_h, scale, direction)

    img = img.convert("L")
    profile, contrast_strength, sharpness_strength, brightness_strength = _choose_enhancement_profile(img, document_type)

    enhancer = ImageEnhance.Contrast(img)
    img = enhancer.enhance(contrast_strength)

    enhancer = ImageEnhance.Sharpness(img)
    img = enhancer.enhance(sharpness_strength)

    enhancer = ImageEnhance.Brightness(img)
    img = enhancer.enhance(brightness_strength)

    normalized_w, normalized_h = img.size
    normalization_meta = {
        "original_size": [int(original_w), int(original_h)],
        "normalized_size": [int(normalized_w), int(normalized_h)],
        "scale_factor": round(float(scale), 4),
        "profile": profile,
        "applied": {
            "exif_transpose": ENABLE_EXIF_TRANSPOSE,
            "adaptive_enhancement": ENABLE_ADAPTIVE_ENHANCEMENT,
        },
    }

    return img.convert("RGB"), normalization_meta


# ---------------------------------------------------------------------------
# Core OCR logic
# ---------------------------------------------------------------------------

def run_paddle_ocr(img: Image.Image, document_type: str = "birth_certificate") -> dict:
    """
    Run PaddleOCR on a PIL Image and return structured results.

    In calibration rollout mode:
      - pass1 prefers fast detector when enabled and loaded
      - always-on guarded fallback reruns baseline detector when gates fail

    Returns additive metadata without breaking existing response fields.
    """
    global baseline_ocr_engine, fast_ocr_engine
    if baseline_ocr_engine is None:
        raise RuntimeError("PaddleOCR engine not initialized")

    start_total = time.perf_counter()

    preprocess_start = time.perf_counter()
    processed, normalization_meta = preprocess_image(img, document_type)
    img_array = np.array(processed)
    preprocess_ms = (time.perf_counter() - preprocess_start) * 1000

    engine_used = "baseline_detector"
    fallback_used = False
    fallback_reasons = []
    timings_extra = {}

    if FAST_DET_ENABLED and fast_ocr_engine is not None:
        fast_results, infer_fast_ms = _run_engine_predict(fast_ocr_engine, img_array)
        fast_parsed = _parse_prediction(fast_results)
        fallback_reasons = _evaluate_fast_fallback_gates(fast_parsed)

        if FAST_FALLBACK_ALWAYS_ON and fallback_reasons:
            fallback_used = True
            base_results, infer_base_ms = _run_engine_predict(baseline_ocr_engine, img_array)
            results = base_results
            infer_ms = infer_fast_ms + infer_base_ms
            timings_extra = {
                "inference_fast_pass1": round(infer_fast_ms, 1),
                "inference_fallback_baseline": round(infer_base_ms, 1),
            }
            engine_used = "baseline_detector"
            logger.info(
                "Fast detector fallback triggered reasons=%s fast_ms=%.1f base_ms=%.1f",
                fallback_reasons,
                infer_fast_ms,
                infer_base_ms,
            )
        else:
            results = fast_results
            infer_ms = infer_fast_ms
            timings_extra = {
                "inference_fast_pass1": round(infer_fast_ms, 1),
            }
            engine_used = "fast_detector"
            fallback_reasons = []
            logger.info("Fast detector accepted result in %.3fs", infer_fast_ms / 1000)
    else:
        base_results, infer_base_ms = _run_engine_predict(baseline_ocr_engine, img_array)
        results = base_results
        infer_ms = infer_base_ms
        timings_extra = {
            "inference_baseline_pass1": round(infer_base_ms, 1),
        }
        engine_used = "baseline_detector"
        logger.info("Baseline detector inference took %.3fs", infer_base_ms / 1000)

    parse_start = time.perf_counter()
    parsed = _parse_prediction(results)
    parse_ms = (time.perf_counter() - parse_start) * 1000
    total_ms = (time.perf_counter() - start_total) * 1000

    return {
        "raw_text": parsed["raw_text"],
        "confidence": parsed["confidence"],
        "word_count": parsed["word_count"],
        "line_count": parsed["line_count"],
        "boxes": parsed["boxes"],
        "box_count": parsed["box_count"],
        "engine_used": engine_used,
        "fallback_used": fallback_used,
        "fallback_reasons": fallback_reasons,
        "processing_time_ms": round(total_ms, 1),
        "normalization": {
            **normalization_meta,
            "normalization_time_ms": round(preprocess_ms, 1),
        },
        "timings_ms": {
            "preprocess": round(preprocess_ms, 1),
            "inference_pass1": round(infer_ms, 1),
            **timings_extra,
            "parse": round(parse_ms, 1),
            "total": round(total_ms, 1),
        },
    }


def run_multi_pass_ocr(img: Image.Image, document_type: str) -> dict:
    """
    Multi-pass OCR strategy:
      Pass 1 - Detector rollout path from run_paddle_ocr
      Pass 2 - Higher-contrast binary-threshold image if pass1 confidence is low
    Picks the result with higher confidence.
    """
    result1 = run_paddle_ocr(img, document_type)
    pass2_used = False
    pass2_inference_ms = 0.0

    if result1["confidence"] >= PASS2_CONFIDENCE_THRESHOLD:
        logger.info("Pass-1 confidence %.1f%% - skipping pass 2", result1["confidence"])
        result1["pass2_used"] = False
        return result1

    logger.info("Pass-1 confidence %.1f%% - running pass 2 with binarisation", result1["confidence"])
    try:
        bw_img = img.convert("L").point(lambda x: 0 if x < 140 else 255, "1").convert("RGB")

        w, h = bw_img.size
        bw_long = max(w, h)
        bw_scale = min(2.0, MAX_LONG_EDGE / bw_long) if bw_long < MAX_LONG_EDGE else 1.0
        if bw_scale > 1.01:
            bw_img = bw_img.resize((int(w * bw_scale), int(h * bw_scale)), Image.LANCZOS)

        global baseline_ocr_engine
        pass2_start = time.perf_counter()
        results2 = list(baseline_ocr_engine.predict(np.array(bw_img)))
        pass2_inference_ms = (time.perf_counter() - pass2_start) * 1000
        pass2_used = True

        if results2:
            parsed2 = _parse_prediction(results2)
            if parsed2["confidence"] > result1["confidence"]:
                logger.info(
                    "Pass-2 confidence %.1f%% > pass-1 %.1f%% - using pass 2",
                    parsed2["confidence"],
                    result1["confidence"],
                )
                return {
                    "raw_text": parsed2["raw_text"],
                    "confidence": parsed2["confidence"],
                    "word_count": parsed2["word_count"],
                    "line_count": parsed2["line_count"],
                    "boxes": parsed2["boxes"],
                    "box_count": parsed2["box_count"],
                    "engine_used": "baseline_detector",
                    "fallback_used": result1.get("fallback_used", False),
                    "fallback_reasons": result1.get("fallback_reasons", []),
                    "normalization": result1.get("normalization", {}),
                    "processing_time_ms": round(result1["processing_time_ms"] + pass2_inference_ms, 1),
                    "timings_ms": {
                        **result1.get("timings_ms", {}),
                        "inference_pass2": round(pass2_inference_ms, 1),
                        "total": round(result1["processing_time_ms"] + pass2_inference_ms, 1),
                    },
                    "pass2_used": pass2_used,
                }
    except Exception as e:
        logger.warning("Pass-2 failed: %s", e)

    if pass2_used:
        timings = result1.get("timings_ms", {})
        timings["inference_pass2"] = round(pass2_inference_ms, 1)
        timings["total"] = round(result1["processing_time_ms"] + pass2_inference_ms, 1)
        result1["timings_ms"] = timings
        result1["processing_time_ms"] = round(result1["processing_time_ms"] + pass2_inference_ms, 1)

    result1["pass2_used"] = pass2_used
    return result1


def run_roi_guarded_ocr(img: Image.Image, document_type: str) -> dict:
    """
    ROI-first OCR wrapper with strict fallback to full-page OCR.
    Keeps full-page path always available and unchanged.
    """
    roi_meta = {
        "enabled": ROI_ENABLED,
        "supported": _is_roi_supported(document_type),
        "strategy": "content_projection_certificate_area",
        "attempted": False,
        "applied": False,
        "used": False,
        "fallback_used": False,
        "fallback_reasons": [],
        "crop_box": None,
        "area_ratio": 1.0,
    }

    if not _is_roi_supported(document_type):
        full_result = run_multi_pass_ocr(img, document_type)
        roi_meta["fallback_reasons"] = ["roi_disabled_for_document_type"]
        full_result["roi"] = roi_meta
        return full_result

    roi_meta["attempted"] = True
    roi_candidate = _detect_content_roi(img)
    roi_meta["crop_box"] = roi_candidate.get("crop_box")
    roi_meta["area_ratio"] = roi_candidate.get("area_ratio", 1.0)

    if not roi_candidate.get("applied", False):
        full_result = run_multi_pass_ocr(img, document_type)
        roi_meta["fallback_used"] = True
        roi_meta["fallback_reasons"] = [roi_candidate.get("reason", "roi_not_applied")]
        full_result["roi"] = roi_meta
        return full_result

    x1, y1, x2, y2 = roi_candidate["crop_box"]
    roi_img = img.crop((x1, y1, x2 + 1, y2 + 1))
    roi_meta["applied"] = True

    roi_result = run_multi_pass_ocr(roi_img, document_type)
    weak_reasons = _evaluate_roi_quality_gates(roi_result)

    if weak_reasons:
        full_result = run_multi_pass_ocr(img, document_type)
        roi_probe_ms = roi_result.get("processing_time_ms", 0.0)
        full_ms = full_result.get("processing_time_ms", 0.0)
        combined_ms = round(full_ms + roi_probe_ms, 1)

        full_result["processing_time_ms"] = combined_ms
        timings = full_result.get("timings_ms", {})
        timings["roi_probe_total"] = round(roi_probe_ms, 1)
        timings["total"] = combined_ms
        full_result["timings_ms"] = timings

        roi_meta["fallback_used"] = True
        roi_meta["fallback_reasons"] = weak_reasons
        full_result["roi"] = roi_meta
        return full_result

    roi_meta["used"] = True
    roi_result["roi"] = roi_meta
    return roi_result


# ---------------------------------------------------------------------------
# API Endpoints
# ---------------------------------------------------------------------------

@app.get("/health")
async def health():
    return {
        "status": "ok",
        "engine": "PaddleOCR",
        "model_loaded": baseline_ocr_engine is not None,
        "fast_detector_enabled": FAST_DET_ENABLED,
        "fast_detector_loaded": fast_ocr_engine is not None,
        "baseline_detector_model": BASELINE_DET_MODEL,
        "fast_detector_model": FAST_DET_MODEL,
        "recognizer_model": REC_MODEL_NAME,
        "roi_enabled": ROI_ENABLED,
        "roi_supported_types": sorted(list(ROI_SUPPORTED_TYPES)),
        "version": "1.0.0",
    }


@app.post("/ocr")
async def ocr_endpoint(
    file: UploadFile = File(...),
    document_type: str = Form("birth_certificate"),
):
    """
    Accept an image file and return OCR results.

    Form fields:
        file          : image file (jpg, png)
        document_type : birth_certificate | death_certificate | marriage_certificate | other
    """
    if baseline_ocr_engine is None:
        raise HTTPException(status_code=503, detail="OCR engine not ready")

    allowed_types = {"image/jpeg", "image/png", "image/jpg"}
    if file.content_type and file.content_type not in allowed_types:
        return JSONResponse(
            status_code=422,
            content={
                "success": False,
                "error": f"Unsupported file type: {file.content_type}. Allowed: jpg, png",
            },
        )

    try:
        contents = await file.read()
        img = Image.open(io.BytesIO(contents))

        logger.info(
            "Processing image: %s (%dx%d, %d bytes, type=%s)",
            file.filename,
            img.width,
            img.height,
            len(contents),
            document_type,
        )

        ocr_result = run_roi_guarded_ocr(img, document_type)

        return JSONResponse(content={
            "success": True,
            "raw_text": ocr_result["raw_text"],
            "confidence": ocr_result["confidence"],
            "word_count": ocr_result["word_count"],
            "line_count": ocr_result["line_count"],
            "boxes": ocr_result["boxes"],
            "box_count": ocr_result.get("box_count", len(ocr_result.get("boxes", []))),
            "processing_time_ms": ocr_result["processing_time_ms"],
            "timings_ms": ocr_result.get("timings_ms", {}),
            "pass2_used": ocr_result.get("pass2_used", False),
            "engine_used": ocr_result.get("engine_used", "baseline_detector"),
            "fallback_used": ocr_result.get("fallback_used", False),
            "fallback_reasons": ocr_result.get("fallback_reasons", []),
            "normalization": ocr_result.get("normalization", {}),
            "roi": ocr_result.get("roi", {}),
        })

    except Exception as e:
        logger.error("OCR processing failed: %s\n%s", str(e), traceback.format_exc())
        return JSONResponse(
            status_code=500,
            content={
                "success": False,
                "error": f"OCR processing failed: {str(e)}",
            },
        )


# ---------------------------------------------------------------------------
# Entry-point
# ---------------------------------------------------------------------------
if __name__ == "__main__":
    import uvicorn

    port = int(os.environ.get("PADDLE_OCR_PORT", 8001))
    logger.info("Starting PaddleOCR service on port %d", port)
    uvicorn.run(app, host="0.0.0.0", port=port, log_level="info")
