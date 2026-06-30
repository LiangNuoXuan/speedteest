<?php
// 禁用压缩,避免测速不准
@ini_set('zlib.output_compression', 'Off');
@ini_set('output_buffering', 'Off');
@ini_set('output_handler', '');

// CORS 支持
if (isset($_GET['cors'])) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
}

// 响应头
header('HTTP/1.1 200 OK');
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=random.dat');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Connection: keep-alive');

// 动态调整数据大小（根据ckSize参数）
$ckSize = isset($_GET['ckSize']) ? intval($_GET['ckSize']) : 8; // 默认8MB
$ckSize = min(max($ckSize, 1), 32); // 限制在1-32MB之间

$chunkSize = 1048576; // 1MB
$chunks = $ckSize;
$data = openssl_random_pseudo_bytes($chunkSize);

// 设置总Content-Length
header('Content-Length: ' . ($chunks * $chunkSize));

// 分块输出，避免内存占用过大
for ($i = 0; $i < $chunks; $i++) {
    echo $data;
    flush();
    // 添加微小延迟，避免CPU占用过高
    if ($i % 2 == 0) {
        usleep(1000); // 1ms
    }
}
