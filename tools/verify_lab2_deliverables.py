#!/usr/bin/env python3
"""Verify report evidence, PDF layout bounds and source syntax before packaging."""
import hashlib
import json
import os
import subprocess
from datetime import datetime
from pathlib import Path
from zipfile import ZipFile

import pymupdf
from docx import Document
from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'report/lab2'
STEM = 'LAB_2_Report_Huynh_Duc_Phat_2452940'


def main():
    evidence = json.loads((OUT / 'evidence-index.json').read_text())
    screenshots = list((OUT / 'screenshots').glob('*.png'))
    assert len(screenshots) == 40
    assert {p.name for p in screenshots} == {f['file'] for f in evidence['figures']}
    for screenshot in screenshots:
        with Image.open(screenshot) as image:
            assert image.width >= 320 and image.height >= 600
            image.verify()
    schema = (ROOT / 'database/schema.sql').read_text().strip()
    full_sql = (ROOT / 'database/lab2.sql').read_text()
    assert full_sql.startswith(schema), 'schema.sql and lab2.sql DDL must agree'
    pdf = OUT / (STEM + '.pdf')
    with pymupdf.open(pdf) as document:
        page_count = len(document)
        assert page_count >= 30 and len(document.get_toc()) >= 30
        full_text = '\n'.join(page.get_text() for page in document)
        for page in document:
            text = page.get_text()
            assert len(text) > 180, f'Unexpectedly sparse PDF page {page.number+1}'
            assert '\ufffd' not in text
            for block in page.get_text('blocks'):
                assert block[0] >= 25 and block[2] <= page.rect.width - 25
                assert block[1] >= 20 and block[3] <= page.rect.height - 20
        for number in range(1, 41):
            assert f'Figure {number}.' in full_text
        assert 'Contents' in document[1].get_text()
    word = OUT / (STEM + '.docx')
    doc = Document(word)
    assert len(doc.inline_shapes) == 41  # Forty screenshots and the generated ER model.
    with ZipFile(word) as archive:
        assert archive.testzip() is None
        assert sum(name.startswith('word/media/') for name in archive.namelist()) == 41
    php = os.environ.get('CLINIC_PHP', '/Applications/XAMPP/xamppfiles/bin/php')
    php_files = list(ROOT.rglob('*.php'))
    js_files = list((ROOT / 'public/js').glob('*.js')) + [ROOT / 'tests/browser.cjs']
    for source in php_files:
        subprocess.run([php, '-l', str(source)], capture_output=True, text=True, check=True)
    for source in js_files:
        subprocess.run(['node', '--check', str(source)], capture_output=True, text=True, check=True)
    result = {
        'executed_at': datetime.now().astimezone().isoformat(),
        'pdf_pages': page_count,
        'original_screenshots': len(screenshots),
        'docx_embedded_images': len(doc.inline_shapes),
        'php_syntax_checks': len(php_files),
        'javascript_syntax_checks': len(js_files),
        'schema_files_agree': True,
        'pdf_text_inside_margins': True,
        'all_figures_referenced': True,
        'artifacts': {
            p.name: {'bytes': p.stat().st_size, 'sha256': hashlib.sha256(p.read_bytes()).hexdigest()}
            for p in [pdf, word]
        },
    }
    (OUT / 'deliverables-verification.json').write_text(json.dumps(result, indent=2))
    print(json.dumps(result, indent=2))


if __name__ == '__main__':
    main()
