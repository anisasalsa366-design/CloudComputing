
# Cloud Computing Project

## Deskripsi
Proyek ini merupakan tugas mata kuliah Cloud Computing yang mengintegrasikan aplikasi Inventory Management dan Point of Sale (POS) menggunakan Docker dan Docker Compose.

Sistem ini menghubungkan transaksi penjualan POS dengan data produk Inventory melalui SKU, sehingga stok Inventory dapat diperbarui berdasarkan transaksi penjualan.

## Teknologi yang Digunakan
- PHP
- MySQL 8.0
- Apache
- Docker
- Docker Compose
- REST API
- phpMyAdmin

## Struktur Proyek
- `inventory-management-system-main/` — Aplikasi Inventory Management.
- `POS-PHP-main/` — Aplikasi Point of Sale (POS).
- `db-init/` — File SQL untuk inisialisasi database.
- `Dockerfile` — Konfigurasi image aplikasi PHP dan Apache.
- `docker-compose.yml` — Konfigurasi layanan aplikasi dan database.

## Fitur dan Integrasi
- Menjalankan aplikasi Inventory Management dan POS dalam container Docker.
- Menggunakan MySQL sebagai database.
- Mengintegrasikan transaksi POS dengan produk Inventory melalui SKU.
- Menghubungkan POS dengan Inventory API untuk memproses penjualan dan pembaruan stok.
- Memvalidasi ketersediaan stok sebelum penjualan diproses.
- Menyimpan data transaksi POS dan menyediakan riwayat transaksi.
- Menggunakan phpMyAdmin untuk mengelola database.

## Layanan dan Port
| Layanan | Port | URL |
|---|---:|---|
| Inventory Management | 8081 | http://localhost:8081 |
| Point of Sale (POS) | 8082 | http://localhost:8082 |
| phpMyAdmin | 8083 | http://localhost:8083 |
| Inventory API | 8084 | http://localhost:8084 |
| MySQL | 3307 | localhost:3307 |

## Cara Menjalankan Proyek

### Persyaratan
- Docker Desktop telah terinstal dan berjalan.
- Docker Compose tersedia melalui Docker Desktop.
- Git (opsional, untuk clone repository).

### Langkah Menjalankan

1. Clone atau unduh repository ini.
2. Buka terminal pada folder utama proyek yang berisi file `docker-compose.yml`.
3. Pastikan konfigurasi database dan environment sudah sesuai.
4. Jalankan seluruh layanan menggunakan perintah:

   ```bash
   docker compose up -d --build
   ```

5. Periksa status container:

   ```bash
   docker compose ps
   ```

6. Buka aplikasi melalui browser:

   - Inventory Management: http://localhost:8081
   - Point of Sale (POS): http://localhost:8082
   - phpMyAdmin: http://localhost:8083

   Inventory API tersedia pada port `8084`.

## Database
Proyek menggunakan MySQL 8.0 dengan dua database utama:

- `inventory_management` — Menyimpan data produk dan stok Inventory.
- `DB_Pos` — Menyimpan data item, akun, dan transaksi POS.

Tabel utama pada database POS meliputi:
- `items`
- `transactions`
- `transaction_details`
- `accounts`
- `roles`

File inisialisasi database tersedia di folder `db-init/`, jika disertakan dalam repository.

## Pengujian dan Troubleshooting

### Memeriksa Container
```bash
docker compose ps
```

### Melihat Log POS
```bash
docker logs cloudcomputing-pos
```

### Melihat Log Inventory API
```bash
docker logs cloudcomputing-inventory-api
```

### Memeriksa Sintaks PHP
```bash
docker exec cloudcomputing-pos php -l /var/www/html/src/server/setter/set_transaction.php
```

Jika transaksi gagal, periksa kesesuaian SKU antara database POS dan Inventory, ketersediaan stok, konfigurasi API, serta log container terkait sebelum mencoba transaksi kembali.

## Tujuan Proyek
Menerapkan konsep containerization menggunakan Docker serta mengintegrasikan dua aplikasi berbasis PHP dengan database MySQL melalui layanan API.

## Status Proyek
Proyek sedang dalam tahap pengembangan dan pengujian integrasi. Kesesuaian SKU, pemrosesan transaksi, serta sinkronisasi stok perlu dipastikan melalui pengujian menyeluruh.

## Anggota Kelompok 5
1. Azzahra Nabila Diarsa (240602048)
2. Anisa Salsa Faradila (240602055)
3. Nadia Nur Lailatus Salwa (240602060)
