<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only.');}
require __DIR__.'/../app/bootstrap.php';
// Only records created by the browser test's explicit, reserved QA identifiers.
db()->beginTransaction();
try {
    $verificationPath=dirname(__DIR__).'/report/lab2/verification.json';
    $verification=is_file($verificationPath)?json_decode(file_get_contents($verificationPath),true):[];
    $nativeReference=$verification['qa_native_reference']??'';
    if(is_string($nativeReference)&&preg_match('/^WN-[A-F0-9]{10}$/D',$nativeReference)) {
        $nativeId=query('SELECT id FROM appointment_requests WHERE reference=?',[$nativeReference])->fetchColumn();
        if($nativeId){query("DELETE FROM audit_logs WHERE entity='appointment' AND entity_id=?",[$nativeId]);query('DELETE FROM appointment_requests WHERE id=?',[$nativeId]);}
    }
    $rateEmail=$verification['qa_rate_email']??'';
    if(is_string($rateEmail)&&preg_match('/^limit-[0-9]+@example\.com$/D',$rateEmail))foreach(['127.0.0.1','::1'] as $address)query('DELETE FROM login_attempts WHERE identifier=?',[hash('sha256',$rateEmail.'|'.$address)]);
    query("DELETE FROM audit_logs WHERE user_id IN(SELECT id FROM users WHERE email LIKE 'qa-lab2-%@example.com') OR (entity='service' AND description LIKE '%QA Wellness Consultation%') OR (entity='appointment' AND entity_id IN(SELECT id FROM appointment_requests WHERE user_id IN(SELECT id FROM users WHERE email LIKE 'qa-lab2-%@example.com')))");
    query("DELETE FROM appointment_requests WHERE user_id IN(SELECT id FROM users WHERE email LIKE 'qa-lab2-%@example.com')");
    query("DELETE FROM users WHERE email LIKE 'qa-lab2-%@example.com'");
    query("DELETE FROM services WHERE name LIKE 'QA Wellness Consultation%'");
    db()->commit();echo 'Removed reserved browser-QA records only. Sample clinic data retained.'.PHP_EOL;
} catch(Throwable $error){db()->rollBack();throw $error;}
