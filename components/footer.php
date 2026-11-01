<footer class="site-footer" id="contact">
    <div class="container">
        <div class="footer-main">
            <div class="footer-brand"><a class="brand" href="<?= e(base_url('public/index.php')) ?>"><span class="brand-mark"><?= icon('plus') ?></span><span>wellnest<span class="brand-dot">.</span><small>MEDICAL CLINIC</small></span></a><p>A healthier tomorrow starts<br>with a little care today.</p><span class="demo-label">An illustrative clinic. A real learning journey.</span></div>
            <div><h2>Explore</h2><a href="<?= e(base_url('public/index.php#services')) ?>">Our services</a><a href="<?= e(base_url('public/index.php#approach')) ?>">Our approach</a><a href="<?= e(base_url('pages/appointment.php')) ?>">Request a visit</a></div>
            <div><h2>Get in touch</h2><a href="mailto:hello@wellnest.example"><?= icon('mail') ?> hello@wellnest.example</a><p><?= icon('location') ?> Ho Chi Minh City, Vietnam</p><p class="footer-note">Example contact details for this project.</p></div>
            <div><h2>Visit planning</h2><p>Monday – Saturday</p><p>09:00 – 17:00 <span class="footer-note">(GMT+7)</span></p><p class="footer-note">Demo appointment requests only.<br>No clinical services are provided.</p></div>
        </div>
        <div class="footer-bottom"><p>&copy; <?= date('Y') ?> Wellnest Medical Clinic. All rights reserved.</p><p>Made with care by Huỳnh Đức Phát · 2452940</p></div>
    </div>
</footer>
<div class="toast-region" aria-live="polite" aria-atomic="true"><div class="feedback-toast" id="feedback-toast" hidden><span class="toast-symbol"><?= icon('check') ?></span><span id="toast-message"></span><button type="button" class="icon-button" id="dismiss-toast" aria-label="Dismiss notification"><?= icon('close') ?></button></div></div>
<script src="<?= e(base_url('public/vendor/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(base_url('public/js/script.js')) ?>"></script>
<script src="<?= e(base_url('public/js/account.js')) ?>"></script>
</body>
</html>
