# SpeedTest Pro 部署指南

一个轻量级的网速测试工具，支持下载/上传速度测试，提供实时可视化图表显示。

## 📋 系统要求

### 服务器端
- **PHP 7.0+** （推荐 PHP 7.4+）
- **Web服务器**: Apache / Nginx / IIS
- **PHP扩展**:
  - OpenSSL（用于生成随机数据）
  - 无需其他特殊扩展

### 客户端
- **浏览器**: Chrome 80+ / Firefox 75+ / Safari 13+ / Edge 80+
- 支持 JavaScript 和 Canvas
- 建议使用现代浏览器以获得最佳性能

## 🚀 快速部署

### 方式一：直接部署到现有服务器

#### 1. 下载文件
将以下文件上传到你的Web服务器目录：

```
speedteest/
├── index.html          # 主页面
├── garbage.php         # 下载测速数据生成
├── empty.php           # 上传测速接收端
└── README.md           # 部署文档
```

#### 2. 确保PHP正确配置

检查 `php.ini` 配置文件，确保以下设置：

```ini
# 禁用输出压缩（测速必须）
zlib.output_compression = Off
output_buffering = Off

# 设置足够大的上传限制（根据需求调整）
upload_max_filesize = 50M
post_max_size = 50M

# 设置足够的执行时间
max_execution_time = 120
max_input_time = 120
```

#### 3. 访问测试
打开浏览器访问：`http://你的域名/speedteest/index.html`

---

### 方式二：使用 Docker 部署

#### 1. 创建 Dockerfile

```dockerfile
FROM php:7.4-apache

# 启用 OpenSSL 扩展
RUN docker-php-ext-install openssl

# 复制项目文件
COPY . /var/www/html/

# 设置权限
RUN chown -R www-data:www-data /var/www/html/

# 配置 PHP
RUN echo "zlib.output_compression = Off" >> /usr/local/etc/php/php.ini \
    && echo "output_buffering = Off" >> /usr/local/etc/php/php.ini \
    && echo "upload_max_filesize = 50M" >> /usr/local/etc/php/php.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/php.ini

EXPOSE 80
```

#### 2. 构建并运行

```bash
# 构建镜像
docker build -t speedtest-pro .

# 运行容器
docker run -d -p 8080:80 --name speedtest speedtest-pro

# 访问测试
# http://localhost:8080
```

---

### 方式三：使用 Nginx + PHP-FPM

#### 1. Nginx 配置示例

```nginx
server {
    listen 80;
    server_name speedtest.yourdomain.com;
    root /var/www/speedteest;
    index index.html;

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # 测速优化配置
        fastcgi_read_timeout 120;
        fastcgi_send_timeout 120;
    }

    # 静态文件
    location ~* \.(html|css|js)$ {
        expires 1h;
        add_header Cache-Control "public";
    }

    # 禁用缓存（测速文件）
    location ~ \.php$ {
        add_header Cache-Control "no-store, no-cache, must-revalidate";
    }
}
```

#### 2. 重启服务

```bash
sudo systemctl restart nginx
sudo systemctl restart php7.4-fpm
```

## ⚙️ 配置说明

### 测速参数调整

编辑 `index.html` 中的 `TEST_CONFIG` 配置：

```javascript
const TEST_CONFIG = {
  threads: 4,          // 并发线程数（建议2-6）
  duration: 10000,     // 测试时长（毫秒，建议8000-15000）
  warmup: 2000,        // 预热时长（毫秒，建议1500-3000）
  adjust: 1.05,        // 补偿因子（根据实际测试微调）
  sampleInterval: 100, // 采样间隔（毫秒，建议50-200）
  smoothWindow: 5      // 平滑窗口（建议3-7）
};
```

### 服务器配置建议

根据预期带宽调整 PHP 参数：

| 预期带宽 | upload_max_filesize | post_max_size | max_execution_time |
|---------|---------------------|---------------|--------------------|
| <100 Mbps | 10M | 10M | 60 |
| 100-500 Mbps | 30M | 30M | 90 |
| >500 Mbps | 50M+ | 50M+ | 120+ |

## 📊 性能优化建议

### 1. 服务器端优化

- **禁用压缩**: 必须禁用 gzip/brotli 压缩，否则测速不准确
- **启用 HTTP/2**: 提高并发连接效率
- **足够内存**: 确保 PHP 内存限制 >= 128M
- **快速存储**: 使用 SSD 存储 PHP 文件

### 2. 网络优化

- **本地部署**: 服务器最好与测试目标在同一网络环境
- **独占带宽**: 测试时避免其他流量干扰
- **稳定连接**: 使用有线网络而非 WiFi

### 3. 客户端建议

- 使用最新浏览器版本
- 关闭其他网络应用
- 禁用浏览器扩展（可能干扰测速）

## 🔧 常见问题

### Q1: 测速结果显示 0 或不准确？

**解决方案**:
1. 检查 PHP 文件是否可执行
2. 确认服务器禁用了输出压缩
3. 检查浏览器 Console 是否有错误
4. 确认 CORS 配置正确

```bash
# 测试 PHP 是否工作
php -v
curl http://localhost/speedteest/garbage.php?cors=true
```

### Q2: 上传测试失败？

**解决方案**:
1. 检查 `upload_max_filesize` 和 `post_max_size` 设置
2. 确认 `empty.php` 文件存在且可访问
3. 检查防火墙是否拦截 POST 请求

```php
// 在 empty.php 中添加调试
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Upload received\n");
```

### Q3: 测速过程中断？

**解决方案**:
1. 增加 `max_execution_time` 和 `max_input_time`
2. 检查服务器超时设置
3. 降低并发线程数（threads 参数）
4. 增加测试时长（duration 参数）

### Q4: 速度跳动很大？

**解决方案**:
- 这是正常现象，系统已使用平滑处理和中位数计算
- 如果跳动异常，可能是网络不稳定或服务器资源不足
- 可适当增加 `smoothWindow` 值

### Q5: 如何自定义字体？

项目使用系统字体栈，无需下载额外字体文件，具有以下优势：
- ✅ 零依赖，无需网络连接
- ✅ 加载速度快，使用本地系统字体
- ✅ 跨平台兼容，各系统显示最佳字体

**当前字体配置**：
- 主字体：系统默认字体（Apple SF Pro / Segoe UI / Roboto 等）
- 等宽字体：SF Mono / Courier New（用于数值显示）

如需更换字体，直接修改 `index.html` 中 CSS 的 `font-family` 属性：

```css
body {
  font-family: '你的字体', -apple-system, sans-serif;
}

.gauge-value {
  font-family: '你的等宽字体', "SF Mono", monospace;
}
```

### Q6: 如何修改界面语言？

系统默认支持中文和英文，点击右上角语言按钮切换。如需添加其他语言：

```javascript
// 在 index.html 的 i18n 对象中添加
const i18n = {
  zh: { ... },
  en: { ... },
  ja: { // 日文示例
    traffic: 'データ使用量',
    download: 'ダウンロード',
    upload: 'アップロード',
    // ... 其他翻译
  }
};
```

## 📝 测试验证

部署完成后，建议进行以下验证：

### 1. 基本功能测试

```bash
# 测试下载接口
curl -I http://你的域名/garbage.php?cors=true

# 应返回:
# HTTP/1.1 200 OK
# Content-Type: application/octet-stream
# Content-Length: 8388608

# 测试上传接口
curl -X POST http://你的域名/empty.php?cors=true -d "test"

# 应返回:
# HTTP/1.1 200 OK
# Content-Length: 0
```

### 2. 性能测试

- 测试不同时段的速度差异
- 对比多个客户端的测试结果
- 监控服务器资源占用（CPU、内存、网络）

### 3. 压力测试

```bash
# 使用 ab 工具测试并发性能
ab -n 100 -c 10 http://你的域名/garbage.php?cors=true&ckSize=8

# 使用 curl 测试持续性能
for i in {1..10}; do
  time curl -s http://你的域名/garbage.php?cors=true > /dev/null
done
```

## 🌐 生产环境建议

### 1. 安全加固

```php
// 在 garbage.php 和 empty.php 中添加访问限制
if (!isset($_GET['cors'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// 添加速率限制（防止滥用）
session_start();
if ($_SESSION['test_count'] > 10) {
    header('HTTP/1.1 429 Too Many Requests');
    exit('Rate limit exceeded');
}
$_SESSION['test_count']++;
```

### 2. 日志记录

```php
// 在 PHP 文件中添加日志
function logTest($type, $clientIP) {
    $log = sprintf("[%s] %s - %s\n", date('Y-m-d H:i:s'), $clientIP, $type);
    file_put_contents('speedtest.log', $log, FILE_APPEND);
}

logTest('download', $_SERVER['REMOTE_ADDR']);
```

### 3. 监控告警

- 设置服务器资源监控（CPU >80% 告警）
- 监控测速成功率（<90% 告警）
- 记录异常测速结果

## 📞 技术支持

如遇到部署问题：

1. 查看浏览器 Console 错误信息
2. 检查 PHP 错误日志：`/var/log/php_errors.log`
3. 检查 Web服务器日志：`/var/log/apache2/error.log` 或 `/var/log/nginx/error.log`
4. 使用网络抓包工具（Wireshark）分析请求响应

---

**部署成功标志**:
- ✅ 页面正常加载，无报错
- ✅ 点击测试按钮后速度曲线实时更新
- ✅ 测试完成后显示合理速度数值
- ✅ 流量统计正常累加

祝你部署顺利！🎉