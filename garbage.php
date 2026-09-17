<?php
// ==================== 禁用压缩 / 缓冲（测速必须） ====================
@ini_set('zlib.output_compression', 'Off');
@ini_set('output_buffering', 'Off');
@ini_set('output_handler', '');
@ini_set('implicit_flush', 'On');
@set_time_limit(0);
@ignore_user_abort(false);

// Apache 层面禁用 mod_deflate（对 .php 也生效，防止上游压缩）
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
    @apache_setenv('dont-vary', '1');
}

// ==================== CORS ====================
if (isset($_GET['cors'])) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: *');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ==================== 响应头 ====================
header('HTTP/1.1 200 OK');
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=random.dat');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Connection: keep-alive');

// ==================== 参数 ====================
$ckSize = isset($_GET['ckSize']) ? intval($_GET['ckSize']) : 8; // 默认 8MB
$ckSize = min(max($ckSize, 1), 32);                              // 1~32 MB

$chunkSize  = 1048576;             // 1 MB
$chunks     = $ckSize;
$totalBytes = $chunks * $chunkSize;

// ==================== 获取 1MB 随机数据（带缓存） ====================
function st_get_random_chunk($size) {
    // 1) 优先 APCu
    if (function_exists('apcu_fetch')) {
        $data = @apcu_fetch('st_rand_chunk_v1');
        if (is_string($data) && strlen($data) === $size) {
            return $data;
        }
    }

    // 2) 生成
    $data = false;
    if (function_exists('openssl_random_pseudo_bytes')) {
        $data = @openssl_random_pseudo_bytes($size);
    }
    if (!is_string($data) || strlen($data) !== $size) {
        $data = '';
        for ($i = 0; $i < intdiv($size, 4); $i++) {
            $data .= pack('N', mt_rand(0, 0x7FFFFFFF));
        }
    }

    // 3) 存回缓存
    if (function_exists('apcu_store')) {
        @apcu_store('st_rand_chunk_v1', $data, 300);
    } else {
        $cacheFile = sys_get_temp_dir() . '/st_rand_' . md5(__FILE__) . '.bin';
        if (!is_file($cacheFile) || filesize($cacheFile) !== $size) {
            $tmp = $cacheFile . '.tmp.' . getmypid();
            if (@file_put_contents($tmp, $data) !== false) {
                @rename($tmp, $cacheFile);
            }
        }
    }
    return $data;
}

$data = st_get_random_chunk($chunkSize);

// 预拼 4MB 大块，减少系统调用
$bigBlock = str_repeat($data, 4);
$bigLen   = $chunkSize * 4;

// ==================== Content-Length ====================
header('Content-Length: ' . $totalBytes);

// ==================== 清空所有输出缓冲 ====================
while (ob_get_level() > 0) {
    @ob_end_clean();
}

// ==================== 分块输出 ====================
$remaining = $totalBytes;
while ($remaining > 0) {
    if ($remaining >= $bigLen) {
        echo $bigBlock;
        $remaining -= $bigLen;
    } else {
        echo substr($bigBlock, 0, $remaining);
        $remaining = 0;
    }
    @flush();
    if (connection_aborted()) {
        exit;
    }
}