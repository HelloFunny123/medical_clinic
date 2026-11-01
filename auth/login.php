<?php
require_once __DIR__.'/../app/bootstrap.php';
if(current_user()) redirect(current_user()['role']==='admin'?'admin/index.php':'pages/dashboard.php');
$errors=[];$email='';$next=input_text($_GET,'next',input_text($_POST,'next'));
if($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();$email=strtolower(input_text($_POST,'email'));$password=is_string($_POST['password']??null)?$_POST['password']:'';
    $identifier=hash('sha256',$email.'|'.($_SERVER['REMOTE_ADDR']??''));
    $attempts=(int)query('SELECT COUNT(*) FROM login_attempts WHERE identifier=? AND attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)',[$identifier])->fetchColumn();
    if($attempts>=6) {$errors['form']='Too many attempts. Please try again in 15 minutes.';http_response_code(429);}
    else {
        $user=filter_var($email,FILTER_VALIDATE_EMAIL)?query('SELECT * FROM users WHERE email=? AND is_active=1',[$email])->fetch():false;
        $dummy='$2y$10$JXJDFOz1PmfIS9VHaN/b6.2aMiH5Skg.A5QqB0w7ZgjRY2PVUyQle';
        $verified=strlen($password)<=72&&!str_contains($password,"\0")&&password_verify($password,$user['password_hash']??$dummy);
        if(!$user||!$verified) {query('INSERT INTO login_attempts(identifier) VALUES (?)',[$identifier]);$errors['form']='The email or password is incorrect.';http_response_code(422);}
        else {query('DELETE FROM login_attempts WHERE identifier=?',[$identifier]);login_user($user);flash('Welcome back, '.$user['full_name'].'.');redirect(safe_next($next,$user));}
    }
}
$pageTitle='Sign in | Wellnest Medical Clinic';$authMode='login';require __DIR__.'/../components/head.php';require __DIR__.'/../components/header.php';
?>
<main id="main-content" class="auth-main"><div class="container"><div class="auth-layout"><section class="auth-story"><span class="eyebrow">A FAMILIAR PLACE TO BEGIN</span><h1>A little care.<br>A space for <em>you.</em></h1><p>Sign in to plan your visits, keep your requests in one place, and take the next step at your own pace.</p><div class="auth-photo"><img src="<?=e(base_url('public/images/hero-doctor.jpg'))?>" alt="Welcoming healthcare professional wearing a white coat" width="1100" height="800"><span><?=icon('heart')?> Your care journey, all together.</span></div></section><section class="auth-panel"><span class="auth-panel-icon"><?=icon('shield')?></span><h2>Welcome back.</h2><p>Sign in to your Wellnest account.</p><?php display_flash(); ?><?php if(isset($_GET['logged_out'])): ?><div class="alert alert-success" role="status">You have been signed out. Your session has ended.</div><?php endif; ?><?php if($errors): ?><div class="alert alert-danger" role="alert"><?=e($errors['form'])?></div><?php endif; ?><form method="post" action="<?=e(base_url('auth/login.php'))?>" class="auth-form"><?=csrf_field()?><input type="hidden" name="next" value="<?=e($next)?>"><div class="mb-4"><label for="email" class="form-label">Email address</label><input class="form-control" id="email" name="email" type="email" value="<?=e($email)?>" maxlength="190" placeholder="you@example.com" autocomplete="username" required></div><div class="mb-4"><label for="password" class="form-label">Password</label><div class="password-field"><input class="form-control" type="password" id="password" name="password" maxlength="72" placeholder="Your password" autocomplete="current-password" required><button class="password-toggle" type="button" data-toggle-password="password" aria-label="Show password" aria-pressed="false"><?=icon('eye')?></button></div></div><button class="btn btn-primary btn-lg w-100" type="submit">Sign in <?=icon('arrow')?></button></form><p class="auth-switch">New to Wellnest? <a href="<?=e(base_url('auth/register.php'))?>">Create an account</a></p><div class="auth-demo-note"><?=icon('shield')?><span>A student project. Use example details only.<br>Demo account instructions are in the project README.</span></div></section></div></div></main>
<?php require __DIR__.'/../components/footer.php'; ?>
