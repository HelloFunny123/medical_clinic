<?php
declare(strict_types=1);
// One configuration for the entire application. Private overrides are not committed.
$configuration = [
    'host' => getenv('CLINIC_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('CLINIC_DB_PORT') ?: '3306',
    'name' => getenv('CLINIC_DB_NAME') ?: 'wellnest_lab2',
    'user' => getenv('CLINIC_DB_USER') ?: 'root',
    'password' => getenv('CLINIC_DB_PASSWORD') ?: '',
    'socket' => getenv('CLINIC_DB_SOCKET') ?: '',
];
if (is_file(__DIR__ . '/local.php')) $configuration = array_replace($configuration, require __DIR__ . '/local.php');
foreach(['host','port','name','user','password','socket'] as $key) {
    $environment=getenv('CLINIC_DB_'.strtoupper($key));
    if($environment!==false)$configuration[$key]=$environment;
}
return $configuration;
