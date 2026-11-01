#!/usr/bin/env python3
"""Use an isolated, disposable MariaDB database for meaningful backend checks."""
import os,secrets,subprocess,tempfile,shutil
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
MYSQL='/Applications/XAMPP/xamppfiles/bin/mysql'
PHP='/Applications/XAMPP/xamppfiles/bin/php'
name='wellnest_lab2_test_'+secrets.token_hex(5)
storage=tempfile.mkdtemp(prefix='wellnest-backend-')
command=[MYSQL,'--socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock','-u','root']
try:
 subprocess.run(command,input=(ROOT/'database/lab2.sql').read_text().replace('wellnest_lab2',name),text=True,check=True)
 environment=os.environ|{'CLINIC_DB_NAME':name,'CLINIC_DB_USER':'root','CLINIC_DB_PASSWORD':'','CLINIC_DB_SOCKET':'/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock','CLINIC_STORAGE_DIR':storage}
 subprocess.run([PHP,str(ROOT/'tests/backend.php')],env=environment,cwd=ROOT,check=True)
finally:
 subprocess.run(command,input=f'DROP DATABASE IF EXISTS `{name}`;',text=True,check=True)
 shutil.rmtree(storage)
