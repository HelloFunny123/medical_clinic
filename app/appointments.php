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
    if (strlen($values['email']) > 190 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address (up to 190 characters).';
    [$minimum, $maximum] = appointment_date_limits();
    $date = preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $values['preferred_date'])
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $values['preferred_date']) : false;
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
    $user = require_login();
    $pdo->beginTransaction();
    try {
        $existing=query('SELECT reference, service_id, service_name, preferred_date, TIME_FORMAT(preferred_time,"%H:%i") AS preferred_time, quoted_price FROM appointment_requests WHERE request_key=? AND user_id=?',[$requestKey,$user['id']])->fetch();
        if($existing) {$pdo->commit();return $existing;}
        $fresh=query('SELECT * FROM services WHERE id=? AND is_active=1 FOR UPDATE',[$service['id']])->fetch();
        if(!$fresh)throw new DomainException('This service is no longer available.');
        query('INSERT INTO appointment_requests (reference,request_key,user_id,service_id,service_name,full_name,email,preferred_date,preferred_time,quoted_price) VALUES (?,?,?,?,?,?,?,?,?,?)',[$reference,$requestKey,$user['id'],$fresh['id'],$fresh['name'],$values['full_name'],$values['email'],$values['preferred_date'],$values['preferred_time'],$fresh['price']]);
        audit('appointment.requested','appointment',(int)$pdo->lastInsertId(),'Requested a visit: '.$fresh['name']);
        $result=['reference'=>$reference,'service_id'=>$fresh['id'],'service_name'=>$fresh['name'],'preferred_date'=>$values['preferred_date'],'preferred_time'=>$values['preferred_time'],'quoted_price'=>$fresh['price']];
        $pdo->commit();return $result;
    } catch(Throwable $error) {if($pdo->inTransaction())$pdo->rollBack();throw $error;}
}

function change_appointment_status(int $id,string $next,int $version,array $user): void {
    db()->beginTransaction();
    try {
        $appointment=query('SELECT * FROM appointment_requests WHERE id=? FOR UPDATE',[$id])->fetch();
        if(!$appointment || ($user['role']!=='admin'&&(int)$appointment['user_id']!==(int)$user['id'])) throw new DomainException('This request is not available to your account.');
        $allowed=['requested'=>['confirmed','cancelled'],'confirmed'=>['completed','cancelled'],'completed'=>[],'cancelled'=>[]];
        if($user['role']!=='admin'&&$next!=='cancelled')throw new DomainException('Patients can only cancel their own pending or confirmed requests.');
        if((int)$appointment['version']!==$version||!in_array($next,$allowed[$appointment['status']],true))throw new DomainException('The request changed or that status transition is not available. Refresh and try again.');
        query('UPDATE appointment_requests SET status=?,version=version+1 WHERE id=?',[$next,$id]);
        audit('appointment.'.$next,'appointment',$id,'Updated '.$appointment['reference'].' to '.$next.'.');
        db()->commit();
    } catch(Throwable $error) {if(db()->inTransaction())db()->rollBack();throw $error;}
}
