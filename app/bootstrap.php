<?php
declare(strict_types=1);
ini_set('display_errors','0');ini_set('log_errors','1');error_reporting(E_ALL);
date_default_timezone_set('Asia/Ho_Chi_Minh');
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function input_text(array $input,string $key,string $default=''): string { return is_string($input[$key]??null) ? trim($input[$key]) : $default; }
function base_url(string $path=''): string {
    $script=str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/public/index.php');
    $base=preg_replace('~/(public|pages|auth|admin)/[^/]+$~','',$script);
    return rtrim((string)$base,'/').'/'.ltrim($path,'/');
}
function money(int $price): string { return number_format($price,0,'.',',').' ₫'; }
function redirect(string $path): void { header('Location: '.base_url($path),true,303);exit; }
function json_response(array $data,int $status=200): void { http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit; }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function require_csrf(): void { if(!hash_equals(csrf_token(),input_text($_POST,'csrf'))) render_error(403,'Your form needs a fresh start.','Refresh the page and try again. No changes were saved.'); }
function flash(string $message,string $type='success'): void { $_SESSION['flash'][]=['message'=>$message,'type'=>$type]; }
function display_flash(): void { foreach($_SESSION['flash']??[] as $notice) echo '<div class="alert alert-'.($notice['type']==='error'?'danger':'success').'" role="status">'.e($notice['message']).'</div>';unset($_SESSION['flash']); }
function initials(string $name): string { $parts=preg_split('/\s+/u',trim($name));return mb_strtoupper(mb_substr($parts[0]??'W',0,1).mb_substr($parts[count($parts)-1]??'',0,1)); }
function render_error(int $status,string $title,string $message): void {
    http_response_code($status);$pageTitle=$title.' | Wellnest';
    require dirname(__DIR__).'/components/head.php';require dirname(__DIR__).'/components/header.php';
    echo '<main id="main-content"><section class="container error-page"><span class="eyebrow">WELLNEST · '.e($status).'</span><h1>'.e($title).'</h1><p>'.e($message).'</p><a class="btn btn-primary" href="'.e(base_url('public/index.php')).'">Back to the clinic</a></section></main>';
    require dirname(__DIR__).'/components/footer.php';exit;
}
set_exception_handler(static function(Throwable $error): void {
    error_log('Wellnest: '.$error->getMessage());
    if(PHP_SAPI==='cli') {fwrite(STDERR,"The application could not complete the operation. Check the local server log and database configuration.\n");exit(1);}
    http_response_code(503);
    if(str_contains($_SERVER['HTTP_ACCEPT']??'','application/json')) {json_response(['ok'=>false,'errors'=>['form'=>'We could not complete your request. Please try again shortly.']],503);}
    echo '<!doctype html><html lang="en"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Temporarily unavailable | Wellnest</title><body style="font:16px/1.8 system-ui;background:#fcfcf8;color:#183c34;padding:10%;max-width:850px"><h1>A little pause in care.</h1><p>We cannot load this page right now. Please try again shortly.</p><a href="'.e(base_url('public/index.php')).'">Try the homepage again</a></body></html>';
});
if(session_status()===PHP_SESSION_NONE) {
    $sessionDirectory=(getenv('CLINIC_STORAGE_DIR')?:dirname(__DIR__).'/storage').'/sessions';
    if(!is_dir($sessionDirectory)&&!mkdir($sessionDirectory,0770,true)&&!is_dir($sessionDirectory))throw new RuntimeException('Session storage unavailable.');
    ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.gc_maxlifetime','7200');
    session_save_path($sessionDirectory);session_name('wellnest_lab2_session');
    session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','path'=>rtrim(base_url(),'/').'/' ]);
    session_start();
    if(isset($_SESSION['user_id']) && time()-($_SESSION['last_activity']??0)>7200) {$_SESSION=[];session_regenerate_id(true);}
    if(isset($_SESSION['user_id']))$_SESSION['last_activity']=time();
}
header('X-Content-Type-Options: nosniff');header('Referrer-Policy: strict-origin-when-cross-origin');header('X-Frame-Options: SAMEORIGIN');header('Cache-Control: no-store');
require_once __DIR__.'/database.php';require_once __DIR__.'/auth.php';
function icon(string $name, string $class = ''): string {
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'edit' => '<path d="m16 3 5 5-12 12H4v-5L16 3Z"/><path d="m13 6 5 5"/>',
        'trash' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
        'logout' => '<path d="M9 4H4v16h5M9 12h12m-4-4 4 4-4 4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m9 3-1 3-3 1-2 3 2 2-1 3 3 2 2 4h4l2-4 3-2-1-3 2-2-2-3-3-1-1-3H9Z"/>',
        'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-up' => '<path d="M7 17 17 7M7 7h10v10"/>',
        'search' => '<circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18m-13 5h2m4 0h2"/>',
        'location' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
        'phone' => '<path d="M7 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-3l-5-2-2 2a14 14 0 0 1-7-7l2-2-2-5Z"/>',
        'shield' => '<path d="m12 3 8 4v5c0 5-8 9-8 9s-8-4-8-9V7l8-4Z"/><path d="m8 12 3 3 5-6"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1 1m12 12 1 1M5 19l1-1M18 6l1-1"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m3 10v-3a6 6 0 0 0-3-5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
    ];
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['plus']) . '</svg>';
}
