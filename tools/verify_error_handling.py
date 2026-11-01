#!/usr/bin/env python3
"""Check an isolated PHP process with a missing DB; never alter local.php."""
import json
import os
import socket
import subprocess
import tempfile
import time
from datetime import datetime
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get('CLINIC_PHP', '/Applications/XAMPP/xamppfiles/bin/php')


def main():
    with tempfile.TemporaryDirectory(prefix='wellnest-outage-') as runtime:
        with socket.socket() as listener:
            listener.bind(('127.0.0.1', 0))
            port = listener.getsockname()[1]
        environment = os.environ | {
            'CLINIC_DB_NAME': 'wellnest_missing_' + os.urandom(8).hex(),
            'CLINIC_STORAGE_DIR': runtime,
        }
        with open(Path(runtime) / 'php.log', 'w') as log:
            server = subprocess.Popen(
                [PHP, '-S', f'127.0.0.1:{port}', '-t', str(ROOT), str(ROOT / 'tools/router.php')],
                env=environment, stdout=log, stderr=log,
            )
            try:
                checks = []
                for accept in ['text/html', 'application/json']:
                    for attempt in range(50):
                        try:
                            response = urlopen(Request(f'http://127.0.0.1:{port}/public/index.php', headers={'Accept': accept}), timeout=3)
                        except HTTPError as error:
                            response = error
                        except URLError:
                            if attempt == 49:
                                raise
                            time.sleep(.1)
                            continue
                        break
                    body = response.read().decode('utf-8')
                    assert response.code == 503, (accept, response.code)
                    assert not any(term in body for term in ['SQLSTATE', 'PDOException', 'Stack trace', 'wellnest_missing_', 'password', str(ROOT)])
                    if accept == 'application/json':
                        assert json.loads(body)['ok'] is False
                        assert 'application/json' in response.headers['Content-Type']
                    else:
                        assert 'We cannot load this page right now' in body
                    checks.append({'accept': accept, 'http_status': response.code, 'generic_message': True, 'no_sensitive_details': True})
                target = ROOT / 'report/lab2/error-handling-verification.json'
                target.write_text(json.dumps({'executed_at': datetime.now().astimezone().isoformat(), 'checks': checks}, indent=2))
                print('Passed: HTML and JSON database failures return generic HTTP 503 without sensitive details.')
            finally:
                server.terminate()
                server.wait(timeout=10)


if __name__ == '__main__':
    main()
