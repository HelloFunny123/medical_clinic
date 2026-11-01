<?php $pageTitle = $pageTitle ?? 'Wellnest Medical Clinic | Thoughtful care, closer to you'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore Wellnest Medical Clinic’s illustrative care services, transparent consultation prices, and simple appointment request experience. A Medical Clinic semester project.">
    <meta name="theme-color" content="#176b5b">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" href="<?= e(base_url('public/images/favicon.svg')) ?>" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" onerror="this.onerror=null;this.href='<?= e(base_url('public/vendor/bootstrap.min.css')) ?>'">
    <link href="<?= e(base_url('public/vendor/bootstrap.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(base_url('public/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
