# Cloud Computing Project

## Deskripsi
Proyek ini merupakan tugas mata kuliah Cloud Computing yang mengintegrasikan aplikasi Inventory Management dan Point of Sale (POS) menggunakan Docker dan Docker Compose.

## Teknologi yang Digunakan
- PHP
- MySQL 8.0
- Apache
- Docker
- Docker Compose

## Struktur Proyek
- `inventory-management-system-main/` — Aplikasi Inventory Management.
- `POS-PHP-main/` — Aplikasi Point of Sale (POS).
- `db-init/` — File SQL untuk inisialisasi database.
- `Dockerfile` — Konfigurasi image aplikasi PHP dan Apache.
- `docker-compose.yml` — Konfigurasi layanan aplikasi dan database.

## Fitur dan Integrasi
- Menjalankan aplikasi Inventory Management dan POS dalam container Docker.
- Menggunakan MySQL sebagai database.
- Menghubungkan transaksi POS dengan produk Inventory melalui SKU.
- Mengurangi stok Inventory ketika transaksi POS berhasil.
- Menyimpan dan menampilkan riwayat transaksi POS.

## Cara Menjalankan Proyek

### Persyaratan
- Docker Desktop telah terinstal dan berjalan.
- Docker Compose tersedia melalui Docker Desktop.

### Langkah menjalankan
1. Clone atau unduh repository ini.
2. Buka terminal di folder utama proyek.
3. Pastikan konfigurasi lingkungan database sudah sesuai.
4. Jalankan perintah:

   ```bash
   docker compose up -d --build
   ```

5. Buka aplikasi melalui browser:

   - Inventory Management: http://localhost:8081
   - Point of Sale (POS): http://localhost:8082

## Database
Proyek menggunakan MySQL 8.0 dengan dua database:
- `inventory_management`
- `DB_Pos`

File inisialisasi database tersedia di folder `db-init/`.

## Tujuan Proyek
Menerapkan konsep containerization menggunakan Docker serta mengintegrasikan dua aplikasi berbasis PHP dengan database MySQL.

## Status
Proyek dibuat untuk memenuhi tugas mata kuliah Cloud Computing.

## Anggota Kelompok 5
Azzahra Nabila Diarsa (240602048)
Anisa Salsa Faradila (240602055)
Nadia Nur Lailatus Salwa (240602060)
