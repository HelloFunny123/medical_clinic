<?php
declare(strict_types=1);

// Run with either PHP 8.0+ CLI. Test data never touches the project's runtime DB.
$testDirectory = sys_get_temp_dir() . '/wellnest-test-' . bin2hex(random_bytes(6));
putenv('CLINIC_STORAGE_DIR=' . $testDirectory);
$_SERVER['SCRIPT_NAME'] = '/medical_clinic/pages/appointment.php';
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/appointments.php';
$passed = 0;
function check(bool $condition, string $label): void {
    global $passed;
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    $passed++; echo 'PASS: ' . $label . PHP_EOL;
}
try {
    check(count(services()) === 6, 'Exactly six services are seeded');
    check(count(services()) === 6, 'Repeated initialization does not duplicate services');
    check(find_service('1')['name'] === 'General Consultation', 'Service details use the catalog database');
    check(find_service('0') === null && find_service('999') === null && find_service(['1']) === null && find_service('1 OR 1=1') === null, 'Invalid IDs and SQL injection are rejected');
    check(base_url('public/index.php') === '/medical_clinic/public/index.php', 'Subdirectory links work under XAMPP');
    check(e('<script>"') === '&lt;script&gt;&quot;', 'Untrusted HTML is escaped');
    $date = new DateTimeImmutable('tomorrow');
    while ($date->format('w') === '0') $date = $date->modify('+1 day');
    $valid = ['service_id' => '1', 'full_name' => 'Huỳnh Đức Phát', 'email' => 'demo@example.com', 'preferred_date' => $date->format('Y-m-d'), 'preferred_time' => '09:00', 'consent' => '1'];
    [$errors, $values, $service] = validate_appointment($valid);
    check($errors === [], 'Valid Unicode name and valid future request pass server validation');
    $cases = [
        ['service_id', '999', 'Unknown service rejected'],
        ['service_id', ['1'], 'Array-shaped service rejected'],
        ['full_name', '<script>alert(1)</script>', 'Markup in name rejected'],
        ['email', 'invalid', 'Invalid email rejected'],
        ['preferred_date', '2026-02-31', 'Impossible date rejected'],
        ['preferred_date', date('Y-m-d'), 'Today rejected'],
        ['preferred_date', (new DateTimeImmutable('+91 days'))->format('Y-m-d'), 'Date beyond 90-day window rejected'],
        ['preferred_date', (new DateTimeImmutable('next Sunday'))->format('Y-m-d'), 'Sunday rejected'],
        ['preferred_time', '03:00', 'Unsupported preferred time rejected'],
        ['consent', '', 'Missing demonstration consent rejected'],
    ];
    foreach ($cases as [$field, $value, $label]) {
        $input = $valid; $input[$field] = $value;
        [$invalid] = validate_appointment($input);
        check(isset($invalid[$field]), $label);
    }
    $key = bin2hex(random_bytes(24));
    $request = save_appointment($values, $service, $key);
    check(strpos($request['reference'], 'WN-') === 0, 'Valid request persists with a reference');
    $repeat = save_appointment($values, $service, $key);
    check($request === $repeat && (int) db()->query('SELECT COUNT(*) FROM appointment_requests')->fetchColumn() === 1, 'Repeated submission is idempotent');
    check((int) $request['quoted_price'] === 250000, 'Price is derived from the service database');
    $foreignKeyRejected = false;
    try { db()->exec("INSERT INTO appointment_requests (reference,request_key,service_id,full_name,email,preferred_date,preferred_time,quoted_price,created_at) VALUES ('X','X',999,'Name','x@example.com','2026-12-01','09:00',1,'now')"); } catch (PDOException $exception) { $foreignKeyRejected = true; }
    check($foreignKeyRejected, 'Database foreign keys reject orphan requests');
    echo "\nAll $passed backend checks passed.\n";
} finally {
    session_write_close();
    foreach (glob($testDirectory . '/sessions/*') ?: [] as $file) unlink($file);
    if (is_dir($testDirectory . '/sessions')) rmdir($testDirectory . '/sessions');
    foreach (glob($testDirectory . '/*') ?: [] as $file) unlink($file);
    if (is_dir($testDirectory)) rmdir($testDirectory);
}
