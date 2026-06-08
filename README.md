# 自製電子商務 RESTful API 專案

### 成果網站
<a href="https://chun-hung.idv.tw" target="_blank">自製電子商務網站</a>

### 開發環境
1. Linux(Debian GNU/Linux 12)
2. Nginx 1.29.8
3. MySQL 8.4.8
4. PHP 8.4.19
5. Composer 2.9.5
6. Laravel 12.x
7. Docker 29.4.0
8. Docker Compose 5.1.2

### 安裝步驟(請先自行安裝 Laradock)
1. 下載專案
```bash
git clone git@github.com:ggz2468/shop-api.git
```
2. 切換至 laradock/ 目錄
```bash
cd laradock/
```
3. 啟動開發環境
```bash
docker compose up -d nginx mysql redis workspace php-worker
```
4. 進入 Workspace 容器內
```bash
docker compose exec --user=laradock workspace bash
```
5. 切換至 laradock/nginx/sites/ 目錄
```bash
cd laradock/nginx/sites/
```
6. 新增 Nginx 設定檔
```bash
touch shop.conf
touch shop-performance.conf
```
7. 定義 Nginx 設定檔內容
#### laradock/nginx/sites/shop.conf
```nginx
server {

    listen 80;
    listen [::]:80;

    server_name localhost shop.test;
    root /var/www/shop-api/public;
    index index.php index.html;

    location / {
        proxy_pass http://host.docker.internal:5173;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }

    location = /api {
        return 301 /api/;
    }

    location ^~ /api/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ^~ /storage/ {
        alias /var/www/shop-api/public/storage/;
        try_files $uri =404;
        access_log off;
        expires 30d;
        add_header Cache-Control "public, max-age=2592000";
    }

    location = /sanctum/csrf-cookie {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass php-upstream;
        fastcgi_index index.php;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ \.php$ {
        return 404;
    }
}
```
#### laradock/nginx/sites/shop-performance.conf
```nginx
server {

    listen 8888;
    listen [::]:8888;

    server_name localhost;
    root /var/www/shop-api/public;
    index index.php index.html;

    location = /api {
        return 301 /api/;
    }

    location ^~ /api/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /sanctum/csrf-cookie {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ^~ /storage/ {
        alias /var/www/shop-api/public/storage/;
        try_files $uri =404;
        access_log off;
        expires 30d;
        add_header Cache-Control "public, max-age=2592000";
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass php-upstream;
        fastcgi_index index.php;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        # Laravel reads .env.performance when APP_ENV=performance.
        fastcgi_param APP_ENV performance;
        include fastcgi_params;
    }

    location ~ \.php$ {
        return 404;
    }

    access_log /var/log/nginx/shop-performance_access.log;
    error_log /var/log/nginx/shop-performance_error.log;
}
```
8. 退出 Workspace 容器
```bash
exit
```
9. 重新啟動 Nginx 容器使新設定生效
```bash
docker compose restart nginx
```
10. 將壓力測試專用 Port 映射至主機:
```bash
vim docker-compose.yml
```
```yaml
nginx:
  ...
  ports:
    ...
    - "8888:8888"
```
11. 建立並啟動一個全新的 Nginx 容器
```bash
docker compose up -d nginx
```
12. 進入 Workspace 容器內
```bash
docker compose exec --user=laradock workspace bash
```
13. 切換至專案目錄
```bash
cd shop-api/
```
14. 安裝必要套件
```bash
composer install
```
15. 設定環境變數
```bash
cp .env.example .env
cp .env.performance.example .env.performance
cp .env.testing.example .env.testing
```
16. 初始化應用程式
```bash
php artisan key:generate
php artisan migrate --seed
```
17. 手動產生兩組 APP_KEY 分別寫入 .env.performance 與 .env.testing 中
```bash
php artisan key:generate --show
php artisan key:generate --show
```
18. 新增次月的 Partition 分區，並刪除過舊的 Partition 分區
```bash
php artisan app:maintain-product-view-counts-partitions
```
19. 建立 storage 軟連結
```bash
php artisan storage:link
```

### API 入口網址
<a href="http://localhost/api">http://localhost/api</a>
