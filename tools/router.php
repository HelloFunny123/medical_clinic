<?php
// Safe router for PHP's development server; Apache uses .htaccess instead.
$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (preg_match('~^/(app|database|storage|tests|tools|report|submission)(/|$)~', $requestPath) || strpos($requestPath, '..') !== false || preg_match('~/(\.[^/]+)(/|$)~', $requestPath) || $requestPath === '/README.md') {
    http_response_code(403); echo 'Forbidden'; return true;
}
return false;
