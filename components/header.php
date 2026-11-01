<div class="announcement"><div class="container"><span><?= icon('sun') ?> A little care goes a long way.</span><span class="announcement-note">Medical Clinic · Student project</span></div></div>
<header class="site-header">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand brand" href="<?= e(base_url('public/index.php')) ?>" aria-label="Wellnest Medical Clinic home"><span class="brand-mark"><?= icon('plus') ?></span><span>wellnest<span class="brand-dot">.</span><small>MEDICAL CLINIC</small></span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link <?= ($activePage ?? '') === 'home' ? 'active' : '' ?>" <?= ($activePage ?? '') === 'home' ? 'aria-current="page"' : '' ?> href="<?= e(base_url('public/index.php')) ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?= ($activePage ?? '') === 'details' ? 'active' : '' ?>" href="<?= e(base_url('public/index.php#services')) ?>">Items <span class="nav-detail">/ Services</span></a></li>
                    <li class="nav-item"><a class="nav-link" data-search-link href="<?= e(base_url('public/index.php#search')) ?>">Search</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(base_url('public/index.php#contact')) ?>">Contact</a></li>
                </ul>
                <div class="nav-account">
                <?php $navigationUser=current_user(); if($navigationUser): ?><a class="account-link" href="<?=e(base_url($navigationUser['role']==='admin'?'admin/index.php':'pages/dashboard.php'))?>"><span class="account-avatar"><?=e(initials($navigationUser['full_name']))?></span><?= $navigationUser['role']==='admin'?'Admin portal':'My care' ?></a><?php else: ?><a class="sign-in-link" href="<?=e(base_url('auth/login.php'))?>">Sign in</a><?php endif; ?>
                <a class="btn btn-primary nav-book" href="<?= e(base_url('pages/appointment.php')) ?>">Book a visit <?= icon('arrow-up') ?></a>
                </div>
            </div>
        </div>
    </nav>
</header>
