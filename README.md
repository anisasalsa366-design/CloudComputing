# Cloud Computing Project — Inventory & Point of Sale

## Deskripsi

Proyek ini merupakan tugas mata kuliah Cloud Computing yang mengintegrasikan aplikasi **Inventory Management** dan **Point of Sale (POS)** menggunakan Docker dan Docker Compose.

Sistem menghubungkan data produk Inventory dengan aplikasi POS melalui SKU dan Inventory API. Integrasi ini mendukung validasi stok serta sinkronisasi data produk ketika terjadi perubahan pada sistem terkait.

## Teknologi yang Digunakan

- PHP
- MySQL 8.0
- Apache
- Docker dan Docker Compose
- REST API
- phpMyAdmin

## Struktur Proyek

```text
CloudComputing/
├── inventory-management-system-main/
├── POS-PHP-main/
├── inventory-api/
├── db-init/
├── Dockerfile
├── docker-compose.yml
├── set_transaction.php
└── README.md
```

## Fitur Utama

- Menjalankan aplikasi Inventory Management dan POS menggunakan container Docker.
- Mengelola data produk dan stok Inventory.
- Mengelola item dan transaksi penjualan POS.
- Menghubungkan produk Inventory dan item POS berdasarkan SKU.
- Menyediakan Inventory API untuk mengakses data produk.
- Memvalidasi ketersediaan stok sebelum transaksi diproses.
- Mendukung sinkronisasi data produk Inventory ke POS.
- Menyimpan transaksi dan detail transaksi POS.
- Menggunakan phpMyAdmin untuk membantu pengelolaan database.

## Layanan dan Port

| Layanan | Port | URL |
|---|---:|---|
| Inventory Management | 8081 | http://localhost:8081 |
| Point of Sale (POS) | 8082 | http://localhost:8082 |
| phpMyAdmin | 8083 | http://localhost:8083 |
| Inventory API | 8084 | http://localhost:8084 |
| MySQL | 3307 | `localhost:3307` |

Alamat tersebut digunakan ketika layanan berjalan secara lokal dengan konfigurasi port yang sesuai.

## Persyaratan

Pastikan perangkat telah memiliki:

- Docker Desktop yang sudah terinstal dan berjalan.
- Docker Compose.
- Git, jika ingin melakukan clone repository.

## Cara Menjalankan Proyek

1. Clone atau unduh repository.
2. Buka terminal pada direktori utama proyek yang berisi `docker-compose.yml`.
3. Periksa konfigurasi environment dan koneksi database. Pastikan password dan token disimpan dengan aman, bukan menggunakan kredensial yang dipublikasikan.
4. Bangun image dan jalankan seluruh layanan:

   ```bash
   docker compose up -d --build
   ```

5. Periksa status container:

   ```bash
   docker compose ps
   ```

6. Buka layanan melalui browser:

   - Inventory Management: http://localhost:8081
   - POS: http://localhost:8082
   - phpMyAdmin: http://localhost:8083
   - Inventory API: http://localhost:8084

> **Catatan:** Perintah di atas mengasumsikan konfigurasi Docker Compose, file environment, dan file inisialisasi database yang diperlukan sudah tersedia. Ikuti konfigurasi yang disertakan dalam repository.

## Database

Proyek menggunakan MySQL 8.0 dengan dua database utama:

- `inventory_management` — menyimpan data produk dan stok Inventory.
- `DB_Pos` — menyimpan data item, akun, dan transaksi POS.

Tabel utama pada database POS meliputi:

- `items`
- `transactions`
- `transaction_details`
- `accounts`
- `roles`

File inisialisasi database berada di direktori `db-init/`, jika disertakan dalam repository.

## Alur Integrasi

1. Data produk dikelola melalui aplikasi Inventory Management.
2. Produk dikenali menggunakan SKU sebagai penghubung antara Inventory dan POS.
3. Inventory API menyediakan endpoint untuk mengakses data produk.
4. Data produk dapat disinkronkan ke POS melalui mekanisme sinkronisasi yang tersedia.
5. POS memeriksa ketersediaan stok sesuai implementasi integrasi sebelum transaksi diproses.
6. Transaksi dan detail penjualan disimpan dalam database POS.

Keberhasilan proses sinkronisasi dan pembaruan stok bergantung pada konfigurasi API, koneksi database, dan kesesuaian SKU.

## Pengujian dan Troubleshooting

### Memeriksa status container

```bash
docker compose ps
```

### Melihat log POS

```bash
docker logs cloudcomputing-pos
```

### Melihat log Inventory API

```bash
docker logs cloudcomputing-inventory-api
```

### Memeriksa sintaks PHP pada handler transaksi

```bash
docker exec cloudcomputing-pos php -l /var/www/html/src/server/setter/set_transaction.php
```

Jika transaksi atau sinkronisasi gagal, periksa:

- Status container dan log aplikasi.
- Koneksi database Inventory dan POS.
- Kesesuaian SKU pada kedua database.
- Konfigurasi URL API dan autentikasi sinkronisasi.
- Ketersediaan stok dan struktur tabel yang digunakan.

## Tujuan Proyek

Proyek ini bertujuan menerapkan konsep containerization menggunakan Docker serta mengintegrasikan aplikasi PHP, database MySQL, dan REST API dalam sebuah sistem Inventory dan Point of Sale.

## Status Proyek

Fitur dasar aplikasi dan integrasi API telah diuji dalam lingkungan pengembangan. Pengujian lanjutan tetap diperlukan untuk memastikan konsistensi stok, penanganan transaksi gagal, autentikasi API, serta perilaku sistem ketika container atau layanan database mengalami gangguan.

## Anggota Kelompok 5

1. Azzahra Nabila Diarsa (240602048)
2. Anisa Salsa Faradila (240602055)
3. Nadia Nur Lailatus Salwa (240602060)
