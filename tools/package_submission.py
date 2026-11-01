#!/usr/bin/env python3
"""Package Lab 2 source, SQL, report and genuine screenshots; omit private/runtime files."""
from pathlib import Path
from zipfile import ZipFile,ZIP_DEFLATED
import json,hashlib
root=Path(__file__).resolve().parents[1];folder=root/'submission';folder.mkdir(exist_ok=True)
target=folder/'LAB_2_Medical_Clinic_Huynh_Duc_Phat_2452940.zip'
required=['public/index.php','public/css/style.css','public/js/script.js','pages/item_details.php','pages/dashboard.php','admin/index.php','admin/services.php','admin/service_form.php','admin/service_delete.php','auth/register.php','auth/login.php','auth/logout.php','config/database.php','config/local.example.php','database/lab2.sql','report/lab2/LAB_2_Report_Huynh_Duc_Phat_2452940.pdf','report/lab2/LAB_2_Report_Huynh_Duc_Phat_2452940.docx','report/lab2/screenshots/25-phpmyadmin-tables.png','report/lab2/screenshots/10-create-service-form.png','report/lab2/screenshots/12-updated-public-record.png','report/lab2/screenshots/13-delete-confirmation.png','report/lab2/screenshots/03-registration-success-patient-dashboard.png','report/lab2/screenshots/09-sidebar-cookie-compact.png']
for name in required:
 if not(root/name).is_file():raise SystemExit('Missing required deliverable: '+name)
results=json.loads((root/'report/lab2/verification.json').read_text());assert not results.get('error'),'Browser tests must pass before packaging.'
assert results['checks'] and results['responsive'] and results['accessibility'],'Verification evidence must not be empty.'
assert all(r['noHorizontalScroll'] for r in results['responsive'])
assert all(not r['violations'] for r in results['accessibility'])
assert not results['javascriptErrors'] and not results.get('assetErrors',[])
backend=json.loads((root/'report/lab2/backend-verification.json').read_text())
assert backend['passed']==len(backend['checks']) and backend['passed']>0
outage=json.loads((root/'report/lab2/error-handling-verification.json').read_text())
assert len(outage['checks'])==2 and all(r['http_status']==503 and r['generic_message'] and r['no_sensitive_details'] for r in outage['checks'])
evidence=json.loads((root/'report/lab2/evidence-index.json').read_text())
shots={p.name for p in (root/'report/lab2/screenshots').glob('*.png')}
assert shots=={f['file'] for f in evidence['figures']} and len(shots)==40,'Every original screenshot must be included in the report.'
required += ['report/lab2/evidence-index.json','report/lab2/backend-verification.json','report/lab2/error-handling-verification.json','report/lab2/verification.json','report/lab2/diagrams/relational-model.pdf','report/lab2/diagrams/relational-model.svg']
required += ['report/lab2/screenshots/'+name for name in sorted(shots)]
delivery=json.loads((root/'report/lab2/deliverables-verification.json').read_text())
assert delivery['original_screenshots']==40 and delivery['all_figures_referenced'] and delivery['schema_files_agree']
for name,metadata in delivery['artifacts'].items():
 assert hashlib.sha256((root/'report/lab2'/name).read_bytes()).hexdigest()==metadata['sha256'],'Re-verify modified report: '+name
required += ['report/lab2/deliverables-verification.json']
manifest={}
with ZipFile(target,'w',compression=ZIP_DEFLATED,compresslevel=7) as archive:
 for source in sorted(root.rglob('*')):
  relative=source.relative_to(root)
  if not source.is_file():continue
  if any(part in {'submission','.git','__pycache__','node_modules'} for part in relative.parts):continue
  if source.name in {'.DS_Store','.env','local.php'}:continue
  if relative.parts[0]=='storage' and source.name not in {'.htaccess','.gitignore'}:continue
  archive.write(source,Path('medical_clinic')/relative)
  manifest[str(relative)]={'bytes':source.stat().st_size,'sha256':hashlib.sha256(source.read_bytes()).hexdigest()}
 archive.writestr('medical_clinic/SUBMISSION_MANIFEST.json',json.dumps(manifest,indent=2,ensure_ascii=False))
with ZipFile(target) as archive:
 assert archive.testzip() is None
 names=set(archive.namelist());assert all('medical_clinic/'+name in names for name in required)
 assert 'medical_clinic/config/local.php' not in names
 assert not any('/sessions/' in n or n.endswith('.sqlite') for n in names)
 for name,metadata in manifest.items():
  data=archive.read('medical_clinic/'+name)
  assert len(data)==metadata['bytes'] and hashlib.sha256(data).hexdigest()==metadata['sha256'],name
print(f'Lab 2 submission verified: {target} ({target.stat().st_size:,} bytes)')
