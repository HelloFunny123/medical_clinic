<?php
declare(strict_types=1);

function appointment_date_limits(): array {
    $today = new DateTimeImmutable('today');
    return [$today->modify('+1 day')->format('Y-m-d'), $today->modify('+90 days')->format('Y-m-d')];
}
function validate_appointment(array $input): array {
    $errors = [];
    $values = [];
    foreach (['service_id', 'full_name', 'email', 'preferred_date', 'preferred_time', 'consent'] as $field) {
        $values[$field] = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
    }
    $service = find_service($values['service_id']);
    if (!$service) $errors['service_id'] = 'Please choose an available service.';
    if (mb_strlen($values['full_name']) < 2 || mb_strlen($values['full_name']) > 100 || preg_match('/[\x00-\x1F\x7F<>]/u', $values['full_name'])) $errors['full_name'] = 'Please enter a name between 2 and 100 characters, without markup.';
    if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    [$minimum, $maximum] = appointment_date_limits();
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $values['preferred_date']);
    if (!$date || $date->format('Y-m-d') !== $values['preferred_date'] || $values['preferred_date'] < $minimum || $values['preferred_date'] > $maximum) {
        $errors['preferred_date'] = 'Choose a date from tomorrow through the next 90 days.';
    } elseif ($date->format('w') === '0') {
        $errors['preferred_date'] = 'Please choose Monday through Saturday; the clinic is closed on Sundays.';
    }
    if (!in_array($values['preferred_time'], ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'], true)) $errors['preferred_time'] = 'Please select one of the available preferred times.';
    if ($values['consent'] !== '1') $errors['consent'] = 'Please acknowledge that this is a demonstration request.';
    return [$errors, $values, $service];
}
function save_appointment(array $values, array $service, string $requestKey): array {
    $pdo = db();
    // A unique request key makes a repeated submit safe, including concurrent retries.
    $reference = 'WN-' . strtoupper(bin2hex(random_bytes(5)));
    $statement = $pdo->prepare('INSERT OR IGNORE INTO appointment_requests (reference, request_key, service_id, full_name, email, preferred_date, preferred_time, quoted_price, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $statement->execute([$reference, $requestKey, $service['id'], $values['full_name'], $values['email'], $values['preferred_date'], $values['preferred_time'], $service['price'], date(DATE_ATOM)]);
    $find = $pdo->prepare('SELECT reference, service_id, preferred_date, preferred_time, quoted_price FROM appointment_requests WHERE request_key = ?');
    $find->execute([$requestKey]);
    $result = $find->fetch();
    if (!$result) throw new RuntimeException('The request could not be saved.');
    $result['service_name'] = find_service((string) $result['service_id'])['name'];
    return $result;
}
