<?php
declare(strict_types=1);
function current_user(): ?array {
    static $loaded = false, $user;
    if ($loaded) return $user;
    $loaded = true;
    $user = isset($_SESSION['user_id']) ? (query('SELECT id,full_name,email,role,created_at FROM users WHERE id=? AND is_active=1', [(int) $_SESSION['user_id']])->fetch() ?: null) : null;
    return $user;
}
function require_login(): array {
    $user = current_user();
    if (!$user) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) json_response(['ok'=>false,'errors'=>['form'=>'Please sign in to continue.']],401);
        $next = ltrim(str_replace(base_url(), '', $_SERVER['REQUEST_URI'] ?? ''), '/');
        redirect('auth/login.php?next=' . rawurlencode($next));
    }
    return $user;
}
function require_admin(): array {
    $user = require_login();
    if ($user['role'] !== 'admin') render_error(403, 'This space is for clinic administrators.', 'Your account can access My care. Service management is reserved for administrators.');
    return $user;
}
function validate_registration(array $input): array {
    $values = ['full_name'=>input_text($input,'full_name'),'email'=>strtolower(input_text($input,'email'))];
    $password = is_string($input['password'] ?? null) ? $input['password'] : '';
    $confirmation = is_string($input['password_confirmation'] ?? null) ? $input['password_confirmation'] : '';
    $errors = [];
    if (mb_strlen($values['full_name']) < 2 || mb_strlen($values['full_name']) > 100 || preg_match('/[<>\x00-\x1F]/u',$values['full_name'])) $errors['full_name']='Enter a name from 2 to 100 characters without markup.';
    if (strlen($values['email'])>190 || !filter_var($values['email'],FILTER_VALIDATE_EMAIL)) $errors['email']='Enter a valid email address (up to 190 characters).';
    if (strlen($password)<8 || strlen($password)>72 || str_contains($password,"\0") || !preg_match('/[a-zA-Z]/',$password) || !preg_match('/[0-9]/',$password)) $errors['password']='Use 8–72 bytes, including at least one letter and one number; no null bytes.';
    if ($password !== $confirmation) $errors['password_confirmation']='Your passwords do not match.';
    if (input_text($input,'consent') !== '1') $errors['consent']='Please acknowledge the student demonstration.';
    return [$errors,$values,$password];
}
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION = ['user_id'=>(int)$user['id'],'last_activity'=>time(),'csrf'=>bin2hex(random_bytes(32))];
}
function safe_next(string $next, array $user): string {
    if (preg_match('~^(pages/(?:dashboard|appointment)\.php(?:\?service=[1-9][0-9]*)?|admin/(?:index|services|service_form|appointments|patients|preferences)\.php(?:\?id=[1-9][0-9]*)?)$~D',$next) && (!str_starts_with($next,'admin/') || $user['role']==='admin')) return $next;
    return $user['role']==='admin' ? 'admin/index.php' : 'pages/dashboard.php';
}
