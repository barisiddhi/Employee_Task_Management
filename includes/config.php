<?php
declare(strict_types=1);

// If the app is not at the web root, set this to the folder path (no trailing slash), e.g. '/Employee_task_Management'
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/Employee_task_Management');

    }

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_PATH . ($path !== '' ? '/' . $path : '');
}
