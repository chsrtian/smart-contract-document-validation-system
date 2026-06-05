from pathlib import Path
from PIL import Image
from paddleocr import PaddleOCR

root = Path(r'c:\Users\PC\final_capstone\screenshots')
files = sorted(root.glob('*.png'))
ocr = PaddleOCR(use_angle_cls=False, lang='en', show_log=False)
for path in files:
    img = Image.open(path)
    w, h = img.size
    crop = img.crop((0, 0, w, int(h * 0.35)))
    temp = root / '_tmp_crop.png'
    crop.save(temp)
    try:
        result = ocr.ocr(str(temp), cls=False)
        lines = []
        if result and result[0]:
            for item in result[0]:
                text = item[1][0].strip()
                conf = item[1][1]
                if conf >= 0.75 and len(text) >= 4:
                    lines.append(text)
        uniq = []
        for line in lines:
            if line not in uniq:
                uniq.append(line)
        print(f'FILE: {path.name}')
        print('TEXT:', ' | '.join(uniq[:10]))
    except Exception as e:
        print(f'FILE: {path.name}')
        print('TEXT: OCR_ERROR', e)
    print('-' * 80)
if temp.exists():
    temp.unlink()
