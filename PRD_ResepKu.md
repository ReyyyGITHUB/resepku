# 📄 Product Requirements Document (PRD)
## ResepKu — Platform Berbagi Resep Masakan Indonesia

**Versi:** 1.0.0  
**Tanggal:** 20 Januari 2026  
**Status:** Draft  
**Dibuat oleh:** Tim Produk ResepKu

---

## 1. Ringkasan Produk

### 1.1 Deskripsi
**ResepKu** adalah platform web berbagi resep masakan yang memungkinkan siapa saja untuk menemukan, membuat, dan berbagi resep masakan. Pengguna dapat berinteraksi satu sama lain melalui fitur sosial seperti following/follower, komentar, like, dan rating bintang.

### 1.2 Tujuan Produk
- Menyediakan platform terpusat untuk berbagi resep masakan Indonesia dan internasional
- Membangun komunitas memasak yang aktif dan saling menginspirasi
- Memudahkan pengguna menemukan resep berdasarkan bahan, kategori, dan tingkat kesulitan

### 1.3 Target Pengguna
- **Segmen Utama:** Umum (semua kalangan), dari ibu rumah tangga hingga anak muda
- **Karakteristik:** Pengguna internet aktif, tertarik memasak, ingin berbagi atau mencari resep

---

## 2. Fitur & Modul

### 2.1 Autentikasi & Akun

| Fitur | Deskripsi | Prioritas |
|-------|-----------|-----------|
| Register | Daftar dengan nama, email, kata sandi | 🔴 Tinggi |
| Login | Masuk dengan email & kata sandi | 🔴 Tinggi |
| Lupa Kata Sandi | Reset via link email | 🔴 Tinggi |
| Logout | Keluar dari sesi | 🔴 Tinggi |
| Edit Profil | Ubah nama, foto profil, bio | 🟡 Sedang |

**Role Pengguna:**
- `pengguna` — Pengguna umum yang terdaftar
- `admin` — Pengelola platform dengan akses panel admin

---

### 2.2 Manajemen Resep

| Fitur | Deskripsi | Prioritas |
|-------|-----------|-----------|
| Buat Resep | Form lengkap pembuatan resep | 🔴 Tinggi |
| Edit Resep | Ubah resep milik sendiri | 🔴 Tinggi |
| Hapus Resep | Hapus resep milik sendiri | 🔴 Tinggi |
| Lihat Detail Resep | Halaman detail lengkap resep | 🔴 Tinggi |
| Upload Foto Resep | Unggah gambar utama resep | 🔴 Tinggi |
| Tag Kategori | Label kategori (ayam, vegetarian, dll) | 🟡 Sedang |
| Tingkat Kesulitan | Label mudah / sedang / sulit | 🟡 Sedang |
| Alat & Bahan | Daftar peralatan masak yang dibutuhkan | 🟡 Sedang |
| Langkah Memasak | Instruksi langkah per langkah | 🔴 Tinggi |
| Waktu Memasak | Estimasi durasi memasak (menit) | 🟡 Sedang |
| Jumlah Porsi | Berapa porsi yang dihasilkan | 🟡 Sedang |

**Kolom tambahan yang perlu ditambahkan ke tabel `recipes`:**
```sql
ALTER TABLE `recipes` ADD `foto_resep` VARCHAR(255) NULL;
ALTER TABLE `recipes` ADD `kategori` VARCHAR(100) NULL;
ALTER TABLE `recipes` ADD `tingkat_kesulitan` ENUM('mudah','sedang','sulit') NOT NULL DEFAULT 'sedang';
ALTER TABLE `recipes` ADD `pengguna_id` BIGINT UNSIGNED NOT NULL;
```

---

### 2.3 Fitur Sosial

| Fitur | Deskripsi | Prioritas |
|-------|-----------|-----------|
| Like Resep | Suka/tidak suka pada resep | 🔴 Tinggi |
| Komentar Resep | Tulis & lihat komentar di resep | 🔴 Tinggi |
| Rating Bintang | Beri rating 1–5 bintang pada resep | 🔴 Tinggi |
| Favorit Resep | Simpan resep ke daftar favorit | 🟡 Sedang |
| Share Resep | Bagikan link resep ke media sosial | 🟡 Sedang |
| Following / Follower | Ikuti pengguna lain | 🔴 Tinggi |
| Lihat Profil Orang Lain | Kunjungi halaman profil pengguna lain | 🔴 Tinggi |
| Feed Resep | Tampilkan resep dari orang yang diikuti | 🟡 Sedang |

**Tabel baru yang dibutuhkan:**
```sql
CREATE TABLE `likes` (
    `like_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `pengguna_id` BIGINT UNSIGNED NOT NULL,
    `resep_id` BIGINT UNSIGNED NOT NULL,
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_like` (`pengguna_id`, `resep_id`)
);

CREATE TABLE `komentar` (
    `komentar_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `pengguna_id` BIGINT UNSIGNED NOT NULL,
    `resep_id` BIGINT UNSIGNED NOT NULL,
    `isi_komentar` TEXT NOT NULL,
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `following` (
    `following_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `follower_id` BIGINT UNSIGNED NOT NULL COMMENT 'yang mengikuti',
    `following_id_user` BIGINT UNSIGNED NOT NULL COMMENT 'yang diikuti',
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_follow` (`follower_id`, `following_id_user`)
);
```

---

### 2.4 Pencarian & Filter

| Fitur | Deskripsi | Prioritas |
|-------|-----------|-----------|
| Cari Resep | Cari berdasarkan nama resep | 🔴 Tinggi |
| Filter Kategori | Filter berdasarkan tag kategori | 🟡 Sedang |
| Filter Kesulitan | Filter berdasarkan tingkat kesulitan | 🟡 Sedang |
| Filter Waktu Masak | Filter berdasarkan durasi memasak | 🟢 Rendah |
| Sort Terpopuler | Urutkan berdasarkan like / rating | 🟡 Sedang |
| Sort Terbaru | Urutkan berdasarkan tanggal posting | 🟡 Sedang |

---

### 2.5 Profil Pengguna

| Fitur | Deskripsi | Prioritas |
|-------|-----------|-----------|
| Halaman Profil Sendiri | Lihat resep, follower, following milik sendiri | 🔴 Tinggi |
| Halaman Profil Orang Lain | Kunjungi & ikuti pengguna lain | 🔴 Tinggi |
| Daftar Resep Pengguna | Semua resep yang diunggah pengguna | 🔴 Tinggi |
| Statistik Profil | Jumlah resep, follower, following | 🟡 Sedang |
| Daftar Favorit | Resep yang disimpan sebagai favorit | 🟡 Sedang |

---

### 2.6 Laporan / Customer Support (CS)

| Fitur | Deskripsi | Prioritas |
|-------|-----------|-----------|
| Laporkan Resep | Pengguna melaporkan resep yang melanggar | 🟡 Sedang |
| Laporkan Pengguna | Pengguna melaporkan akun yang melanggar | 🟡 Sedang |
| Status Laporan | Pengguna bisa lihat status laporan mereka | 🟢 Rendah |

**Status laporan:** `menunggu` → `ditolak` / `selesai`

---

### 2.7 Panel Admin

| Fitur | Deskripsi | Prioritas |
|-------|-----------|-----------|
| Dashboard | Ringkasan statistik platform | 🔴 Tinggi |
| Kelola Pengguna | Lihat, nonaktifkan, hapus akun pengguna | 🔴 Tinggi |
| Kelola Resep | Lihat, moderasi, hapus resep | 🔴 Tinggi |
| Kelola Laporan CS | Tinjau & tindak lanjuti laporan masuk | 🔴 Tinggi |
| Statistik Konten | Jumlah resep, pengguna, laporan aktif | 🟡 Sedang |

---

## 3. Struktur Database (Perbaikan & Tambahan)

### 3.1 Tabel yang Ada (dari SQL)
- `pengguna` — Data akun pengguna
- `recipes` — Data resep
- `bahan_resep` — Bahan-bahan resep
- `favorite` — Resep favorit pengguna
- `ratings` — Rating bintang resep
- `cs` — Laporan/report konten

### 3.2 Tabel Baru yang Diperlukan
- `likes` — Like pada resep
- `komentar` — Komentar pada resep
- `following` — Relasi follower/following antar pengguna
- `kategori_resep` — Master data kategori
- `peralatan_resep` — Alat masak yang dibutuhkan per resep

### 3.3 Kolom Tambahan
- `recipes`: `foto_resep`, `kategori`, `tingkat_kesulitan`, `pengguna_id`
- `pengguna`: `foto_profil`, `bio`, `role` (enum: pengguna, admin)

---

## 4. Arsitektur Teknologi

### 4.1 Stack Teknologi
| Layer | Teknologi |
|-------|-----------|
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Backend | PHP 8.x (Native / Procedural/OOP) |
| Database | MySQL 8.x |
| Web Server | Apache / Nginx |
| File Storage | Local filesystem (`/uploads/`) |

### 4.2 Struktur Folder Proyek
```
resepku/
├── index.php                  # Halaman utama / feed
├── config/
│   └── db.php                 # Koneksi MySQL (PDO)
├── auth/
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── lupa-sandi.php
├── resep/
│   ├── detail.php             # Detail resep
│   ├── buat.php               # Form buat resep
│   ├── edit.php               # Edit resep
│   └── hapus.php
├── profil/
│   ├── index.php              # Profil sendiri
│   └── lihat.php              # Profil orang lain
├── api/
│   ├── like.php               # Toggle like
│   ├── komentar.php           # CRUD komentar
│   ├── follow.php             # Toggle follow
│   ├── rating.php             # Submit rating
│   └── favorit.php            # Toggle favorit
├── admin/
│   ├── dashboard.php
│   ├── pengguna.php
│   ├── resep.php
│   └── laporan.php
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── img/
├── uploads/
│   ├── resep/
│   └── profil/
└── sql/
    └── resepku_full.sql       # SQL lengkap + migration
```

---

## 5. Halaman & Alur Pengguna

### 5.1 Alur Pengguna Umum
```
Landing/Home → Cari Resep → Detail Resep → [Login jika belum]
                                         ↓
                              Like / Komentar / Rating / Favorit
```

### 5.2 Alur Pengguna Terdaftar
```
Login → Feed Beranda → 
  ├── Buat Resep Baru
  ├── Lihat Profil Sendiri
  │     ├── Edit Profil
  │     ├── Daftar Resep Saya
  │     └── Daftar Favorit
  ├── Cari & Kunjungi Profil Orang Lain → Follow/Unfollow
  └── Laporan Konten (CS)
```

### 5.3 Alur Admin
```
Login Admin → Dashboard →
  ├── Kelola Pengguna (ban/hapus)
  ├── Kelola Resep (moderasi/hapus)
  └── Kelola Laporan CS (terima/tolak)
```

---

## 6. Halaman yang Dibutuhkan

| No | Halaman | URL | Role |
|----|---------|-----|------|
| 1 | Beranda / Feed | `/` | Semua |
| 2 | Login | `/auth/login.php` | Guest |
| 3 | Register | `/auth/register.php` | Guest |
| 4 | Lupa Kata Sandi | `/auth/lupa-sandi.php` | Guest |
| 5 | Detail Resep | `/resep/detail.php?id=` | Semua |
| 6 | Buat Resep | `/resep/buat.php` | Login |
| 7 | Edit Resep | `/resep/edit.php?id=` | Pemilik |
| 8 | Profil Sendiri | `/profil/` | Login |
| 9 | Profil Orang Lain | `/profil/lihat.php?id=` | Semua |
| 10 | Cari Resep | `/cari.php?q=` | Semua |
| 11 | Admin Dashboard | `/admin/` | Admin |
| 12 | Admin Pengguna | `/admin/pengguna.php` | Admin |
| 13 | Admin Resep | `/admin/resep.php` | Admin |
| 14 | Admin Laporan | `/admin/laporan.php` | Admin |

---

## 7. Aturan Bisnis

1. Hanya pengguna yang **sudah login** yang bisa membuat resep, like, komentar, rating, follow, dan menyimpan favorit
2. Hanya **pemilik resep** atau **admin** yang bisa mengedit/menghapus resep
3. Satu pengguna hanya bisa memberi **1 rating per resep** (bisa diperbarui)
4. Satu pengguna hanya bisa **like 1x per resep** (toggle on/off)
5. Pengguna **tidak bisa follow diri sendiri**
6. Admin **tidak bisa dihapus** oleh admin lain
7. Laporan CS akan diproses admin dengan status: `menunggu` → `selesai` / `ditolak`

---

## 8. Keamanan

| Aspek | Implementasi |
|-------|-------------|
| SQL Injection | Gunakan PDO dengan prepared statements |
| XSS | `htmlspecialchars()` pada semua output |
| CSRF | Token CSRF pada semua form POST |
| Session | `session_regenerate_id()` setelah login |
| Password | `password_hash()` & `password_verify()` |
| Upload File | Validasi ekstensi & MIME type, rename file |
| Akses Admin | Middleware cek role sebelum masuk halaman admin |

---

## 9. Timeline Pengembangan (Estimasi)

| Fase | Scope | Estimasi |
|------|-------|----------|
| Fase 1 | Database + Auth + CRUD Resep | 1–2 minggu |
| Fase 2 | Fitur Sosial (like, komentar, rating, follow) | 1–2 minggu |
| Fase 3 | Profil, Pencarian, Filter | 1 minggu |
| Fase 4 | Admin Panel + CS | 1 minggu |
| Fase 5 | UI Polish + Testing + Deploy | 1 minggu |

---

## 10. Catatan untuk Implementasi Figma

- Desain Figma akan diimplementasikan **1:1** ke dalam HTML/CSS/JS
- Gunakan **CSS Custom Properties** (variabel) untuk konsistensi warna & typography dari Figma
- Komponen yang berulang (navbar, card resep, form) dibuat sebagai **PHP include/component**
- Kirimkan link Figma untuk memulai implementasi pixel-perfect

---

*Dokumen ini akan diperbarui seiring perkembangan proyek.*
