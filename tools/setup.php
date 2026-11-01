<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only.'); }
$xamppAcl = in_array('--xampp-acl', $argv, true);
$storage = dirname(__DIR__) . '/storage';
foreach ([$storage, $storage . '/sessions'] as $directory) {
    if (!is_dir($directory)) mkdir($directory, 0770, true);
}
require __DIR__ . '/../app/bootstrap.php';
db();
if ($xamppAcl) {
    if (PHP_OS_FAMILY !== 'Darwin') throw new RuntimeException('--xampp-acl is intended for the macOS XAMPP Apache daemon account.');
    foreach ([$storage, $storage . '/sessions'] as $target) {
        $permission = is_dir($target) ? 'user:daemon allow list,add_file,search,add_subdirectory,delete_child,readattr,writeattr,readsecurity' : 'user:daemon allow read,write,readattr,writeattr,readsecurity';
        $process = proc_open(['/bin/chmod', '+a', $permission, $target], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes);
        if (!is_resource($process) || proc_close($process) !== 0) throw new RuntimeException('Could not apply the Apache account ACL.');
    }
}
echo 'Wellnest Lab 2 MySQL is ready. ' . count(services()) . ' published services are available.' . PHP_EOL;
echo $xamppAcl ? 'A macOS ACL grants only the Apache daemon account access to runtime session directories. Source and world permissions were not changed.' . PHP_EOL : 'Using current-user runtime permissions.' . PHP_EOL;
