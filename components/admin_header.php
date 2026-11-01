<?php
$adminUser=require_admin();$isAdminPage=true;
$sidebarCollapsed=($_COOKIE['wellnest_sidebar']??'expanded')==='collapsed';
$adminSection=$adminSection??'overview';
require __DIR__.'/head.php';
?>
<div class="admin-shell <?=$sidebarCollapsed?'sidebar-collapsed':''?>" id="admin-shell" data-cookie-path="<?=e(rtrim(base_url(),'/').'/')?>">
<button class="sidebar-overlay" id="sidebar-overlay" aria-label="Close navigation" type="button" hidden></button>
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Clinic workspace navigation">
    <a class="brand sidebar-brand" href="<?=e(base_url('admin/index.php'))?>"><span class="brand-mark"><?=icon('plus')?></span><span class="sidebar-brand-text">wellnest.<small>CLINIC WORKSPACE</small></span></a>
    <div class="sidebar-label">YOUR WORKSPACE</div>
    <nav aria-label="Administration"><ul class="sidebar-nav"><?php foreach([['overview','index.php','grid','Overview'],['services','services.php','heart','Medical services'],['appointments','appointments.php','calendar','Appointments'],['patients','patients.php','users','Patients'],['preferences','preferences.php','settings','Preferences']] as [$key,$url,$symbol,$label]): ?><li><a href="<?=e(base_url('admin/'.$url))?>" class="<?=$adminSection===$key?'active':''?>" <?=$adminSection===$key?'aria-current="page"':''?> title="<?=e($label)?>"><?=icon($symbol)?><span><?=e($label)?></span><?php if($key==='appointments'): ?><span class="sidebar-count"><?=(int)query("SELECT COUNT(*) FROM appointment_requests WHERE status='requested'")->fetchColumn()?></span><?php endif; ?></a></li><?php endforeach; ?></ul></nav>
    <div class="sidebar-bottom"><a class="sidebar-website" href="<?=e(base_url('public/index.php'))?>" title="View public website"><?=icon('arrow-up')?><span>View public website</span></a><div class="workspace-note"><?=icon('shield')?><strong>A little care, behind the scenes.</strong><p>Your services and demo requests, together in one place.</p></div><div class="sidebar-user"><span class="admin-avatar"><?=e(initials($adminUser['full_name']))?></span><div><strong><?=e($adminUser['full_name'])?></strong><small>Administrator</small></div><form method="post" action="<?=e(base_url('auth/logout.php'))?>"><?=csrf_field()?><button type="submit" aria-label="Sign out" title="Sign out"><?=icon('logout')?></button></form></div></div>
</aside>
<div class="admin-workspace"><header class="admin-topbar"><div class="admin-topbar-left"><button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-controls="admin-sidebar" aria-expanded="<?=$sidebarCollapsed?'false':'true'?>" aria-label="Toggle sidebar"><?=icon('menu')?></button><nav aria-label="Breadcrumb"><a href="<?=e(base_url('admin/index.php'))?>">Workspace</a><?=icon('chevron')?><span><?=e($adminPageLabel??'Overview')?></span></nav></div><div class="admin-topbar-right"><span class="workspace-status"><span></span> Local workspace</span><a class="topbar-avatar" href="<?=e(base_url('admin/preferences.php'))?>" aria-label="<?=e(initials($adminUser['full_name']))?> — Administrator preferences"><?=e(initials($adminUser['full_name']))?></a></div></header>
<main class="admin-main" id="main-content">
<?php display_flash(); ?>
