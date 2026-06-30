<?php
// CORS 支持
if (isset($_GET['cors'])) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
}

// POST：接收并丢弃数据（用于上传测速）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = fopen('php://input', 'rb');
    while (!feof($input)) {
        fread($input, 8192);
    }
    fclose($input);
    header('HTTP/1.1 200 OK');
    header('Content-Length: 0');
    exit;
}

// GET：用于 Ping（请求一个空内容）
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('HTTP/1.1 200 OK');
    header('Content-Length: 0');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    exit;
}
