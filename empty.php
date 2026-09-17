<?php
// ==================== 基础设置 ====================
@ini_set('zlib.output_compression', 'Off');
@ini_set('output_buffering', 'Off');
@ini_set('output_handler', '');
@ini_set('implicit_flush', 'On');

// Apache 层面禁用 mod_deflate
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
    @apache_setenv('dont-vary', '1');
}

// ==================== CORS ====================
if (isset($_GET['cors'])) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: *');
}

// ==================== OPTIONS 预检 ====================
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ==================== POST：接收并丢弃（上传测速） ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    @set_time_limit(0);
    @ignore_user_abort(false);

    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    $input = fopen('php://input', 'rb');
    if ($input) {
        while (!feof($input)) {
            $buf = fread($input, 1048576);
            if ($buf === false || $buf === '') {
                break;
            }
            if (connection_aborted()) {
                break;
            }
        }
        fclose($input);
    }

    http_response_code(200);
    header('Content-Length: 0');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    exit;
}

// ==================== GET：Ping ====================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(200);
    header('Content-Length: 0');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    exit;
}

// 其他方法
http_response_code(405);
header('Allow: GET, POST, OPTIONS');
exit;