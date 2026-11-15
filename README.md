# ShopeePay & QRIS Payment Gateway (CodeIgniter 4 + Vue 3)

Sistem gerbang pembayaran mandiri (*self-hosted payment gateway*) modern berbasis **CodeIgniter 4** dan **Vue 3 (Composition API)**. Dilengkapi fitur QRIS Dinamis & Statis berstandar EMVCo, verifikasi mutasi transaksi otomatis (*real-time auto-checking*) langsung dari portal resmi ShopeePay Partner tanpa memerlukan aplikasi pihak ketiga seperti MacroDroid atau Ngrok, serta landing page interaktif bergaya **Geist / shadcn dark theme**.

---

## 🌟 Fitur Utama

- **Real-Time Auto Mutasi ShopeePay:**
  - Terhubung langsung dengan endpoint internal ShopeePay Partner Portal (`get-transaction-list`).
  - Transaksi diverifikasi secara otomatis dalam hitungan detik setelah pembeli melakukan scan dan pembayaran.
  - **100% Mandiri**: Tanpa perlu aplikasi HP MacroDroid/SMS forwarder dan tanpa perlu tunneling Ngrok.
- **QRIS Dinamis & Statis (EMVCo Compatible):**
  - Menggunakan QRIS resmi merchant ShopeePay (`ZHAR STORE - GADGET`, NMID `ID1026637793641`).
  - Pembuatan QRIS Dinamis otomatis dengan menyuntikkan Tag 54 (Nominal Pembayaran) dan kalkulasi ulang checksum **CRC16 (CCITT-FALSE)** secara live di sisi klien (Vue 3) maupun server (PHP).
- **Checkout Modern Vue 3 (SPA - Single Page Application):**
  - Desain antarmuka minimalis terinspirasi dari **Geist / shadcn UI (Dark Theme)**.
  - Pergantian mode QRIS Dinamis (Auto Pas) dan QRIS Statis Toko Asli secara instan.
  - Input nominal fleksibel dengan *quick amount pills* (Rp 10.000, 20.000, 50.000, 100.000).
  - Status pembayaran di-polling di latar belakang tanpa reload halaman (*zero page reload*).
  - Tampilan sukses interaktif lengkap dengan animasi centang hijau dan tombol kembali.
- **Interactive Steker Landing Page:**
  - Animasi visual sambungan kabel/steker konektor berbasis scroll halus (~2x scroll natural).
  - Responsif di perangkat desktop maupun ponsel.
- **ShopeePay CPM Mode (Customer-Presented Mode):**
  - Mendukung kasir/merchant untuk scan barcode/QR dari aplikasi pembeli (kode bayar 918...).
- **Endpoint Monitoring Mutasi Live:**
  - Pantau histori mutasi masuk toko secara transparan melalui endpoint `/payment/mutasi`.

---

## 📋 Persyaratan Sistem

- **PHP:** Versi 8.2 atau lebih baru
  - Ekstensi PHP wajib:
    - `intl`
    - `mbstring`
    - `curl`
    - `json`
    - `mysqli` / `pdo_mysql`
    - `openssl`
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Web Server:** Apache (dengan modul `mod_rewrite`) atau Nginx
- **Composer:** Versi 2.x

---

## 🗄️ Template Database

File skema template database telah disediakan pada berkas [`schema.sql`](file:///d:/Downloads/payment-gateway(shop)/schema.sql).

### Struktur Tabel:

1. **`orders`**: Menyimpan histori transaksi dan status pesanan toko.
   ```sql
   CREATE TABLE `orders` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `order_id` varchar(50) NOT NULL,
     `produk` varchar(255) DEFAULT 'Topup / Pesanan Toko',
     `total_bayar` int(11) NOT NULL DEFAULT 0,
     `status` varchar(20) NOT NULL DEFAULT 'pending', -- pending, paid, expired, cancelled
     `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
     `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
     PRIMARY KEY (`id`),
     UNIQUE KEY `order_id` (`order_id`)
   );
   ```

2. **`merchant_settings`**: Menyimpan konfigurasi akun toko dan token Shopee Partner secara aman (tidak terekspos ke repositori git).
   ```sql
   CREATE TABLE `merchant_settings` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `key_name` varchar(100) NOT NULL,
     `key_value` text DEFAULT NULL,
     `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     PRIMARY KEY (`id`),
     UNIQUE KEY `key_name` (`key_name`)
   );
   ```

---

## 🚀 Panduan Instalasi Lokal (Localhost)

### 1. Clone Repositori
```bash
git clone https://github.com/id0s/pembayaran-purujekuto.biz.id.git
cd pembayaran-purujekuto.biz.id
```

### 2. Pasang Dependensi Composer
```bash
composer install
```

### 3. Buat dan Impor Database
Buat database baru di MySQL (misal bernama `orders`), lalu impor file `schema.sql`:
```bash
mysql -u root -p orders < schema.sql
```

### 4. Konfigurasi Environment (`.env`)
Salin file `env.example` menjadi `.env`:
```bash
cp env.example .env
```
Buka file `.env` dan sesuaikan koneksi database serta base URL:
```ini
app.baseURL = 'http://localhost:8080/'
CI_ENVIRONMENT = development

database.default.hostname = 127.0.0.1
database.default.database = orders
database.default.username = root
database.default.password = 
database.default.DBDriver = MySQLi
database.default.port = 3306

# Identitas Toko ShopeePay
SHOPEEPAY_MERCHANT_NAME = "Zhar store"
SHOPEEPAY_STORE_ID = "24157627"
SHOPEEPAY_MERCHANT_ID = "25388964"
```

### 5. Masukkan Token Shopee Partner
Masukkan token Shopee Partner ke tabel `merchant_settings` di database MySQL atau langsung ke `.env`:
```sql
UPDATE merchant_settings 
SET key_value = 'B:TOKEN_ANDA_DISINI' 
WHERE key_name = 'shopee_token';
```

### 6. Jalankan Server Development
```bash
php spark serve --port 8080
```
Akses di browser:
- **Landing Page:** [http://localhost:8080/](http://localhost:8080/)
- **Halaman Checkout:** [http://localhost:8080/checkout](http://localhost:8080/checkout)
- **Monitoring Mutasi:** [http://localhost:8080/payment/mutasi](http://localhost:8080/payment/mutasi)

---

## 🔑 Cara Mendapatkan Token Shopee Partner

Token session Shopee Partner digunakan untuk melakukan pengecekan mutasi secara langsung. Token ini memiliki masa aktif sangat panjang (**~1.000 hari / 3 tahun**):

1. Buka browser dan login ke dashboard merchant [Shopee Partner Web](https://partner.shopee.co.id/).
2. Tekan tombol `F12` (atau klik kanan -> **Inspect**) untuk membuka **Developer Tools**.
3. Pilih tab **Network**, lalu aktifkan filter **Fetch/XHR**.
4. Buka menu **Riwayat Transaksi** / **Daftar Transaksi** di portal Shopee Partner.
5. Cari network request yang bernama **`get-transaction-list`**.
6. Klik request tersebut, pilih tab **Payload** / **Request Body**, lalu navigasikan ke:
   ```json
   {
     "data": {
       "metadata": {
         "token": "B:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx..."
       }
     }
   }
   ```
7. Salin seluruh string token tersebut (string yang diawali dengan huruf **`B:`**).
8. Simpan token tersebut ke database `merchant_settings` kolom `shopee_token`.

> [!NOTE]
> Jangan menekan tombol **"Keluar / Logout"** di browser pada dashboard Shopee Partner agar sesi token tersebut tetap aktif hingga masa berlakunya selesai (Agustus 2029).

---

## 🌐 Panduan Deployment ke Production (VPS / Cloud / cPanel)

### A. Struktur Document Root (Sangat Penting)
Untuk alasan keamanan CodeIgniter 4, **Web Server WAJIB mengarahkan Document Root langsung ke folder `/public`**, bukan ke root folder repositori.

```
/var/www/payment-gateway/
├── app/
├── public/          <---- ARAHKAN DOCUMENT ROOT KE SINI
│   ├── index.php
│   └── .htaccess
├── system/
├── writable/
├── .env
```

---

### B. Konfigurasi Nginx (Ubuntu / Debian VPS)

Buat file konfigurasi server block di `/etc/nginx/sites-available/payment-gateway`:

```nginx
server {
    listen 80;
    server_name bayar.domainanda.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name bayar.domainanda.com;

    # SSL Certificate (Let's Encrypt / Certbot)
    ssl_certificate /etc/letsencrypt/live/bayar.domainanda.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/bayar.domainanda.com/privkey.pem;

    root /var/www/payment-gateway/public;
    index index.php index.html;

    charset utf-8;

    # Log
    access_log /var/log/nginx/payment_access.log;
    error_log  /var/log/nginx/payment_error.log;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Blokir akses ke file sensitif
    location ~ /\.(env|git|htaccess) {
        deny all;
    }
}
```

Aktifkan konfigurasi dan restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/payment-gateway /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

### C. Konfigurasi Apache VirtualHost

Jika menggunakan Apache, pastikan modul `mod_rewrite` dan `mod_headers` aktif:
```bash
sudo a2enmod rewrite headers
```

Contoh konfigurasi VirtualHost (`/etc/apache2/sites-available/payment-gateway.conf`):
```apache
<VirtualHost *:80>
    ServerName bayar.domainanda.com
    DocumentRoot /var/www/payment-gateway/public

    <Directory /var/www/payment-gateway/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/payment_error.log
    CustomLog ${APACHE_LOG_DIR}/payment_access.log combined
</VirtualHost>
```

---

### D. Konfigurasi di cPanel / Shared Hosting

1. Upload seluruh file proyek ke folder luar `public_html`, misalnya di `/home/username/payment-gateway/`.
2. Pindahkan seluruh isi folder `public/` ke dalam `public_html/`.
3. Buka file `public_html/index.php`, sesuaikan path menuju `Paths.php`:
   ```php
   require FCPATH . '../payment-gateway/app/Config/Paths.php';
   // atau sesuaikan lokasi path relatifnya
   ```
4. Buat database di cPanel MySQL Wizard, impor `schema.sql`.
5. Edit file `.env` di luar `public_html` dengan kredensial database cPanel Anda.

---

### E. Izin Direktori (File Permissions)

Pastikan direktori `writable/` memiliki hak akses tulis untuk user web server (`www-data` atau `apache`):
```bash
sudo chown -R www-data:www-data /var/www/payment-gateway/writable
sudo chmod -R 775 /var/www/payment-gateway/writable
```

### F. Mode Production di `.env`
Di server live, ubah environment menjadi production agar mode debug dimatikan:
```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://bayar.domainanda.com/'
```

---

## 📡 Referensi API Endpoint

| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/` | Landing page interaktif sambungan steker |
| `GET` | `/checkout` | Halaman checkout pembayaran QRIS Vue 3 |
| `GET` | `/payment/pay?amount=20000` | Redirect checkout dari toko eksternal dengan nominal dinamis |
| `POST` | `/payment/update-amount` | Update nominal transaksi aktif secara dinamis (`{"order_id": "...", "amount": 25000}`) |
| `GET` | `/payment/check-status/:order_id` | Pengecekan status pembayaran & auto-mutasi real-time ShopeePay |
| `GET` | `/payment/mutasi` | Endpoint JSON untuk melihat daftar mutasi ShopeePay live |
| `POST` | `/payment/cpm-pay` | Pembayaran model scan kode bayar pembeli (918...) |
| `POST` | `/api/notif` | Webhook konfirmasi pembayaran alternatif |

---

## 🔒 Keamanan

1. **Token Rahasia:** Token `SHOPEEPAY_TOKEN` tersimpan di database lokal / `.env` dan tidak pernah diunggah ke repositori git (sudah terdaftar di `.gitignore`).
2. **CSRF Protection:** Dikonfigurasi dengan selektif di `app/Config/Filters.php` sehingga endpoint API AJAX tetap aman dan dapat dipanggil dengan mulus oleh klien Vue 3.
3. **Validasi EMVCo:** Perhitungan QRIS dinamis menggunakan algoritma CRC16 standar resmi ASPI/BI untuk mencegah kesalahan transfer pembeli.

---

## 📄 Lisensi

Proyek ini berada di bawah lisensi MIT. Silakan gunakan dan kembangkan sesuai kebutuhan toko Anda.
