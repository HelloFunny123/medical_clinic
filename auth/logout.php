<?php
require_once __DIR__.'/../app/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST') {header('Allow: POST');render_error(405,'Use the sign-out button.','Signing out requires an intentional action from your account.');}
require_csrf();
$_SESSION=[];$parameters=session_get_cookie_params();setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>$parameters['path'],'secure'=>$parameters['secure'],'httponly'=>true,'samesite'=>'Lax']);session_destroy();
redirect('auth/login.php?logged_out=1');
