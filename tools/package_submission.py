#!/usr/bin/env python3
"""Package the complete lab source/report without runtime sessions or QA records."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root=Path(__file__).resolve().parents[1]
folder=root/'submission'
folder.mkdir(exist_ok=True)
target=folder/'LAB_1_3_Medical_Clinic_Huynh_Duc_Phat_2452940.zip'
required=[
    'public/index.php','public/css/style.css','public/js/script.js',
    'pages/item_details.php','components/header.php','components/footer.php',
    'report/LAB_1_3_Report_Huynh_Duc_Phat_2452940.pdf',
    'report/LAB_1_3_Report_Huynh_Duc_Phat_2452940.docx',
    'report/screenshots/01-project-structure.png',
    'report/screenshots/02-homepage-desktop.png',
    'report/screenshots/03-homepage-mobile.png',
    'report/screenshots/05-item-details.png',
    'report/screenshots/06-javascript-interaction.png',
]
for name in required:
    if not (root/name).is_file():raise SystemExit('Missing required file: '+name)
with ZipFile(target,'w',compression=ZIP_DEFLATED,compresslevel=7) as archive:
    for source in sorted(root.rglob('*')):
        relative=source.relative_to(root)
        if not source.is_file():continue
        if any(part in {'submission','.git','__pycache__','node_modules'} for part in relative.parts):continue
        if relative.parts[:2]==('storage','sessions') or source.name=='.DS_Store':continue
        if relative.parts[0]=='storage' and source.name not in {'.htaccess','.gitignore'}:continue
        archive.write(source,Path('medical_clinic')/relative)
with ZipFile(target) as archive:
    assert archive.testzip() is None
    names=set(archive.namelist())
    assert all('medical_clinic/'+name in names for name in required)
print(f'Submission archive verified: {target} ({target.stat().st_size:,} bytes)')
