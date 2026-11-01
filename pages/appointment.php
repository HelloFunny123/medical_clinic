<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/appointments.php';
$allServices = services();
$errors = [];
$values = ['service_id' => is_string($_GET['service'] ?? null) ? $_GET['service'] : '', 'full_name' => '', 'email' => '', 'preferred_date' => '', 'preferred_time' => '', 'consent' => ''];
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
$confirmation = null;
if ($isPost) {
    [$errors, $values, $selectedService] = validate_appointment($_POST);
    $token = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
    $requestKey = is_string($_POST['request_key'] ?? null) ? $_POST['request_key'] : '';
    if (!hash_equals(csrf_token(), $token) || !in_array($requestKey, $_SESSION['request_keys'] ?? [], true)) {
        $errors['form'] = 'Your form has expired. Refresh the page and try again.';
        http_response_code(403);
    }
    if (!$errors) {
        try {
            $confirmation = save_appointment($values, $selectedService, $requestKey);
            $_SESSION['last_request'] = $confirmation;
            if (!$isJson) { header('Location: ' . base_url('pages/appointment.php?success=1'), true, 303); exit; }
        } catch (Throwable $exception) {
            error_log('Wellnest appointment error: ' . $exception->getMessage());
            $errors['form'] = 'Your request could not be saved. Please try again in a moment.';
            http_response_code(503);
        }
    } elseif (http_response_code() !== 403) http_response_code(422);
    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($errors ? ['ok' => false, 'errors' => $errors] : ['ok' => true, 'confirmation' => $confirmation], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
}
if (!$isPost && isset($_GET['success'])) $confirmation = $_SESSION['last_request'] ?? null;
$requestKey = bin2hex(random_bytes(24));
$_SESSION['request_keys'] = array_slice(array_merge($_SESSION['request_keys'] ?? [], [$requestKey]), -20);
[$minimumDate, $maximumDate] = appointment_date_limits();
$pageTitle = 'Request a Visit | Wellnest Medical Clinic';
$activePage = 'appointment';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/header.php';
?>
<main id="main-content" class="appointment-main"><div class="container"><nav aria-label="Breadcrumb" class="breadcrumb-nav"><ol><li><a href="<?= e(base_url('public/index.php')) ?>">Home</a></li><li><?= icon('chevron') ?><span aria-current="page">Request a visit</span></li></ol></nav><div class="row g-5"><section class="col-lg-5 appointment-intro"><span class="eyebrow">ONE SMALL STEP FOR YOURSELF</span><h1>Let’s make time<br>for your health.</h1><p>Choose your care and a time that suits your day. We’ll save your preferences as a demo appointment request.</p><div class="booking-info"><span><?= icon('calendar') ?></span><div><h2>A request, then a conversation</h2><p>A preferred time is a request, not a confirmed appointment.</p></div></div><div class="booking-info"><span><?= icon('shield') ?></span><div><h2>A little clarity before you start</h2><p>This is a student project. Use example contact details; no email is sent and no real appointment is booked.</p></div></div><div class="booking-info"><span><?= icon('clock') ?></span><div><h2>Plan ahead, at your pace</h2><p>Monday – Saturday, 09:00 – 17:00.<br>All dates and times use GMT+7.</p></div></div></section><section class="col-lg-7"><div class="appointment-panel">
    <div id="booking-success" class="booking-success" tabindex="-1" <?= !$confirmation ? 'hidden' : '' ?>><span class="success-icon"><?= icon('check') ?></span><span class="eyebrow">A LITTLE STEP, TAKEN</span><h2>Your demo request is saved.</h2><p>This is a request only. No real appointment is booked and no email will be sent.</p><dl class="confirmation-details"><div><dt>Reference</dt><dd id="confirmation-reference"><?= e($confirmation['reference'] ?? '') ?></dd></div><div><dt>Service</dt><dd id="confirmation-service"><?= e($confirmation['service_name'] ?? '') ?></dd></div><div><dt>Preferred date</dt><dd id="confirmation-date"><?= e($confirmation['preferred_date'] ?? '') ?></dd></div><div><dt>Preferred time (GMT+7)</dt><dd id="confirmation-time"><?= e($confirmation['preferred_time'] ?? '') ?></dd></div><div><dt>Consultation fee</dt><dd id="confirmation-price"><?= isset($confirmation['quoted_price']) ? money((int) $confirmation['quoted_price']) : '' ?></dd></div></dl><a class="btn btn-primary" href="<?= e(base_url('public/index.php#services')) ?>">Back to our services <?= icon('arrow') ?></a></div>
    <form method="post" action="<?= e(base_url('pages/appointment.php')) ?>" id="appointment-form" <?= $confirmation ? 'hidden' : '' ?>><h2>Your visit, your way.</h2><p class="form-subtitle">All fields are required. Please use example details.</p><div id="form-errors" class="alert alert-danger" role="alert" tabindex="-1" <?= !$errors ? 'hidden' : '' ?>><?php if ($errors): ?><strong>Please check your request.</strong><ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul><?php endif; ?></div><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="request_key" value="<?= e($requestKey) ?>">
        <div class="mb-4"><label for="service-id" class="form-label">Which service can we help you with?</label><select class="form-select" name="service_id" id="service-id" required><option value="">Choose your service</option><?php foreach ($allServices as $item): ?><option value="<?= e($item['id']) ?>" data-price="<?= e($item['price']) ?>" <?= (string) $item['id'] === $values['service_id'] ? 'selected' : '' ?>><?= e($item['name']) ?> — <?= money((int) $item['price']) ?></option><?php endforeach; ?></select></div>
        <div class="row g-3 mb-4"><div class="col-sm-6"><label for="full-name" class="form-label">Full name</label><input class="form-control" type="text" id="full-name" name="full_name" minlength="2" maxlength="100" placeholder="e.g. Alex Nguyen" autocomplete="name" value="<?= e($values['full_name']) ?>" required></div><div class="col-sm-6"><label for="email" class="form-label">Email address</label><input class="form-control" type="email" id="email" name="email" maxlength="254" placeholder="e.g. alex@example.com" autocomplete="email" value="<?= e($values['email']) ?>" required></div></div>
        <div class="row g-3 mb-4"><div class="col-sm-6"><label for="preferred-date" class="form-label">Preferred date</label><input class="form-control" type="date" id="preferred-date" name="preferred_date" min="<?= e($minimumDate) ?>" max="<?= e($maximumDate) ?>" aria-describedby="date-hint" value="<?= e($values['preferred_date']) ?>" required><small id="date-hint" class="field-hint">Tomorrow to 90 days ahead; no Sundays.</small></div><div class="col-sm-6"><label for="preferred-time" class="form-label">Preferred time</label><select class="form-select" name="preferred_time" id="preferred-time" required><option value="">Choose a time</option><?php foreach (['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'] as $time): ?><option value="<?= $time ?>" <?= $time === $values['preferred_time'] ? 'selected' : '' ?>><?= $time ?> (GMT+7)</option><?php endforeach; ?></select></div></div>
        <div class="booking-price"><span>Consultation fee</span><strong id="booking-price" aria-live="polite"><?php $chosen = find_service($values['service_id']); echo $chosen ? money((int) $chosen['price']) : 'Select a service'; ?></strong></div><div class="form-check mb-4"><input class="form-check-input" type="checkbox" id="consent" name="consent" value="1" <?= $values['consent'] === '1' ? 'checked' : '' ?> required><label class="form-check-label" for="consent">I understand this is a student demonstration and does not book a real medical appointment.</label></div><button type="submit" class="btn btn-primary btn-lg w-100" id="submit-request">Send demo request <?= icon('arrow-up') ?></button><p class="form-footnote">No payment is collected. Your request stays in this project’s local database.</p>
    </form>
    </div></section></div></div></main>
<?php require_once __DIR__ . '/../components/footer.php'; ?>
