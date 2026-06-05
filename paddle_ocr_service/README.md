# PaddleOCR Service – Installation & Migration Guide

## Overview

This is a **drop-in OCR engine replacement**.  
PaddleOCR runs as a local Python HTTP micro-service on `http://127.0.0.1:8001`.  
Laravel sends images to the service and receives raw text back.  
**All existing field extraction, cleaning, scoring, and response formats are unchanged.**

---

## Architecture

```
Laravel ScanController::processOCR()
        │
        ▼
  ┌─────────────────────┐
  │  OCRService (PHP)   │  ── HTTP POST with image ──►  ┌──────────────────────┐
  │  app/Services/       │                                │  PaddleOCR Service   │
  │  OCRService.php      │  ◄── JSON {raw_text, conf} ── │  paddle_ocr_service/ │
  └─────────────────────┘                                │  main.py (FastAPI)   │
        │                                                └──────────────────────┘
        ▼
  Existing pipeline (unchanged):
  • enhancedTextCleaning()
  • enhancedFieldExtraction()
  • calculateConfidenceScore()
  • Same JSON response to frontend
```

---

## 1. Install Python Dependencies

```bash
# Navigate to the service directory
cd paddle_ocr_service

# Create a virtual environment (recommended)
python -m venv venv

# Activate it
# Windows:
venv\Scripts\activate
# Linux/Mac:
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt
```

> **Note:** First run of PaddleOCR will download detection & recognition models (~100 MB).  
> After that, models are cached locally and load in seconds.

### System Requirements

| Requirement       | Minimum           |
|-------------------|-------------------|
| Python            | 3.8 – 3.12       |
| RAM               | 4 GB              |
| Disk (models)     | ~200 MB           |
| GPU               | Not required      |

---

## 2. Start the Service

```bash
cd paddle_ocr_service

# With virtual environment activated:
python main.py
```

The service starts on **port 8001** by default.  
Override with `PADDLE_OCR_PORT` environment variable:

```bash
PADDLE_OCR_PORT=9000 python main.py
```

### Verify it's running

```bash
curl http://127.0.0.1:8001/health
```

Expected response:
```json
{
  "status": "ok",
  "engine": "PaddleOCR",
  "model_loaded": true,
  "version": "1.0.0"
}
```

---

## 3. Laravel Configuration

Add these to your `.env` file (already done if you followed the migration):

```dotenv
PADDLE_OCR_URL=http://127.0.0.1:8001
PADDLE_OCR_TIMEOUT=30
```

No other Laravel configuration changes are needed.

---

## 4. Files Changed

| File | Change |
|------|--------|
| `paddle_ocr_service/main.py` | **NEW** – Python FastAPI PaddleOCR service |
| `paddle_ocr_service/requirements.txt` | **NEW** – Python dependencies |
| `app/Services/OCRService.php` | **NEW** – HTTP client wrapper for PaddleOCR |
| `app/Http/Controllers/ScanController.php` | **MODIFIED** – `processOCR()` now calls `OCRService` instead of Tesseract. Added `use App\Services\OCRService` import. All field extraction methods are **unchanged**. |
| `.env` / `.env.example` | **MODIFIED** – Added `PADDLE_OCR_URL` and `PADDLE_OCR_TIMEOUT` |

### What was NOT changed

- Controllers (except the OCR call in `processOCR()`)
- Routes
- Database schema / migrations
- Field names or JSON structure
- Validation rules
- Other modules (Search, Corrections, Analytics, Documents, Admin)
- Frontend views
- `Scan` model
- `DocumentService`
- Confidence scoring formula
- Field extraction regex patterns

---

## 5. Fallback Behavior

If the PaddleOCR service is **not running or unreachable**, the system automatically falls back to **Tesseract OCR** (the previous engine). This means:

- **Zero downtime** – if PaddleOCR crashes, OCR still works via Tesseract.
- **Gradual migration** – you can stop/start the Python service at will.
- To force PaddleOCR-only (no fallback), remove the fallback block in `processOCR()`.

---

## 6. Testing Checklist

### Pre-flight

- [ ] Python 3.8+ installed
- [ ] `pip install -r requirements.txt` completed without errors
- [ ] `python main.py` starts without errors
- [ ] `curl http://127.0.0.1:8001/health` returns `"status": "ok"`
- [ ] `.env` contains `PADDLE_OCR_URL=http://127.0.0.1:8001`

### Functional Tests

- [ ] Upload a **birth certificate** → fields extracted correctly
- [ ] Upload a **death certificate** → fields extracted correctly
- [ ] Upload a **marriage certificate** → fields extracted correctly
- [ ] Confidence score is between 0–100
- [ ] `ocr_results.extracted_fields` has the same keys as before
- [ ] `ocr_results.raw_text` contains readable text
- [ ] Document saves to database successfully via `saveProcessedDocument`
- [ ] Validation score and blockchain eligibility compute correctly

### Fallback Test

- [ ] Stop the Python service → upload a document → Tesseract fallback activates
- [ ] Restart the Python service → PaddleOCR resumes normally

### Performance

- [ ] OCR response time < 3 seconds per document
- [ ] No memory leak after 50+ consecutive OCR requests

---

## 7. Sample Request & Response

### Request (from Laravel or curl)

```bash
curl -X POST http://127.0.0.1:8001/ocr \
  -F "file=@birth_certificate.jpg" \
  -F "document_type=birth_certificate"
```

### Response

```json
{
  "success": true,
  "raw_text": "Republic of the Philippines\nCERTIFICATE OF LIVE BIRTH\n...\nName: JUAN DELA CRUZ\nDate of Birth: January 15 1990\n...",
  "confidence": 87.34,
  "word_count": 215,
  "line_count": 42,
  "boxes": [
    {
      "text": "Republic of the Philippines",
      "confidence": 96.12,
      "box": [[10,20],[300,20],[300,45],[10,45]]
    }
  ],
  "processing_time_ms": 1250.3
}
```

The Laravel `processOCR()` method then feeds `raw_text` through:
1. `enhancedTextCleaning()` → cleaned text
2. `enhancedFieldExtraction()` → structured fields (same keys as before)
3. `calculateConfidenceScore()` → blended confidence

Final JSON to frontend is **identical** to the previous Tesseract output.

---

## 8. Logging Strategy

| Component | Log Channel | Key Messages |
|-----------|-------------|-------------|
| Python service | stdout (uvicorn) | Model load time, per-request timing, errors |
| `OCRService.php` | Laravel `Log` | Service health, request/response metrics |
| `ScanController` | Laravel `Log` | Engine used (paddleocr/tesseract), fallback events |

No existing log statements were removed or modified.

---

## 9. Rollback Plan

To revert to Tesseract-only:

1. In `ScanController.php`, revert the `processOCR()` method to call `extractTextWithEnhancedTesseract()` directly (the method still exists and is fully intact).
2. Remove or leave the `use App\Services\OCRService;` import (unused imports don't break PHP).
3. Stop the Python service.
4. Remove `PADDLE_OCR_URL` / `PADDLE_OCR_TIMEOUT` from `.env` (optional).

**No database changes, no migration rollbacks needed.**

---

## 10. Data Privacy

- All processing is **100% local** (localhost:8001).
- No cloud APIs are called.
- No data leaves the machine.
- PaddleOCR models are downloaded once and cached locally.
