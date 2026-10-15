# Deployment FastTopup Store (Laravel 8)

Panduan singkat menaruh toko di server produksi (Linux + Nginx + PHP-FPM).
Sesuaikan path `/var/www/FastTopup-store` dan domain `FastTopup.store`.

## 1. Persiapan

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Isi `.env` produksi (lihat komentar di `.env.example`):

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://FastTopup.store
ASSET_VERSION=1            # naikkan tiap deploy aset
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
```

## 2. Database & aset

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Setiap kali kode berubah: `php artisan optimize:clear` lalu cache ulang
> (`config:cache`, `route:cache`, `view:cache`).

## 3. Izin folder

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 4. Queue worker (email notifikasi)

Pakai Supervisor — contoh ada di `deploy/supervisor-FastTopup.conf`:

```bash
sudo cp deploy/supervisor-FastTopup.conf /etc/supervisor/conf.d/FastTopup.conf
sudo supervisorctl reread && sudo supervisorctl update
```

## 5. Scheduler (batas waktu pembayaran)

Command `payments:expire` membatalkan pesanan kedaluwarsa & mengembalikan stok
tanpa menunggu halaman dibuka. Tambahkan satu baris cron:

```bash
crontab -e
# * * * * * cd /var/www/FastTopup-store && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Nginx

```nginx
server {
    listen 80;
    server_name FastTopup.store;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name FastTopup.store;
    root /var/www/FastTopup-store/public;

    ssl_certificate     /etc/letsencrypt/live/FastTopup.store/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/FastTopup.store/privkey.pem;

    index index.php;
    charset utf-8;

    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Aset statis: cache lama, tapi hormati ?v= (cache busting).
    location ~* \.(css|js|svg|png|jpg|jpeg|webp|ico|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, max-age=2592000, immutable";
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

> Aplikasi sudah mengirim header keamanan sendiri (CSP, X-Frame-Options, HSTS)
> lewat middleware `SecurityHeaders`, jadi Nginx tidak wajib menambahkannya.
> Di belakang proxy/CDN, `TrustProxies` sudah mempercayai `X-Forwarded-*`.

## 7. Pemeriksaan pasca-deploy

- `GET /up` mengembalikan `200 OK` (untuk uptime monitor).
- Buka `/sitemap.xml` & `/robots.txt` memastikan URL benar.
- Kirim satu pesanan uji, pastikan email notifikasi terkirim (queue jalan).
- Cek `/invoice/{kode}` mengirim header `X-Robots-Tag`/`noindex`.

