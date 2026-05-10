# Hak Akses dan Role Matrix ResepKu

Dokumen ini menjelaskan siapa bisa mengakses apa, siapa bisa melakukan aksi apa, dan batasan sistem berdasarkan role.

## 1. Role Utama

Ada 3 jenis role/akses:
- `Guest`
- `Pengguna / Member`
- `Admin`

## 2. Ringkasan Hak Akses

### 2.1 Guest

Guest adalah pengunjung yang masuk memakai `Login as Guest` atau belum punya akun aktif di sistem.

Guest bisa:
- melihat halaman login, register, lupa password
- melihat home
- mencari resep
- membuka detail resep
- membaca komentar

Guest tidak bisa:
- membuat resep
- edit profile
- like resep
- favorite resep
- memberi rating
- menulis komentar
- follow user lain
- mengirim pengaduan
- membuka halaman `My Recipes`
- membuka halaman `Favorite`
- membuka halaman `Pengaduan Saya`
- membuka panel admin

### 2.2 Pengguna / Member

Pengguna adalah akun biasa yang berhasil login.

Pengguna bisa:
- semua akses baca seperti guest
- membuat resep
- melihat dan edit profile sendiri
- melihat profil user lain
- follow / unfollow user lain
- like resep
- favorite resep
- memberi rating
- menulis komentar
- melihat resep milik sendiri
- edit resep milik sendiri
- hapus resep milik sendiri
- melihat daftar favorit
- mengirim pengaduan
- melihat status pengaduan sendiri

Pengguna tidak bisa:
- edit profile orang lain
- edit resep milik orang lain
- hapus resep milik orang lain
- mengakses panel admin
- aktif/nonaktifkan user
- menghapus user lain
- memproses pengaduan admin

### 2.3 Admin

Admin adalah akun dengan role `admin`.

Admin bisa:
- semua kemampuan pengguna biasa
- masuk ke panel admin
- melihat statistik platform
- melihat daftar semua user
- aktif/nonaktifkan user
- menghapus user tertentu
- melihat semua resep
- menghapus resep
- melihat semua pengaduan
- mengubah status pengaduan menjadi `selesai` atau `ditolak`

Admin tetap dibatasi:
- tidak semua akun admin boleh dihapus sembarangan
- aksi admin tetap harus lewat validasi form dan token keamanan

## 3. Matrix Hak Akses

| Fitur / Halaman | Guest | Pengguna | Admin |
|---|---|---|---|
| Login | Ya | Ya | Ya |
| Register | Ya | Ya | Ya |
| Lupa Password | Ya | Ya | Ya |
| Home | Ya | Ya | Ya |
| Search | Ya | Ya | Ya |
| Detail Resep | Ya | Ya | Ya |
| Baca Komentar | Ya | Ya | Ya |
| Tulis Komentar | Tidak | Ya | Ya |
| Like | Tidak | Ya | Ya |
| Favorite | Tidak | Ya | Ya |
| Rating | Tidak | Ya | Ya |
| Share | Ya | Ya | Ya |
| Lapor Resep/User | Tidak | Ya | Ya |
| Profile Sendiri | Tidak | Ya | Ya |
| Edit Profile Sendiri | Tidak | Ya | Ya |
| Lihat Profile Orang Lain | Terbatas | Ya | Ya |
| Follow / Unfollow | Tidak | Ya | Ya |
| Add Recipe | Tidak | Ya | Ya |
| My Recipes | Tidak | Ya | Ya |
| Edit Resep Sendiri | Tidak | Ya | Ya |
| Hapus Resep Sendiri | Tidak | Ya | Ya |
| Favorite List | Tidak | Ya | Ya |
| Pengaduan Saya | Tidak | Ya | Ya |
| Admin Dashboard | Tidak | Tidak | Ya |
| Kelola Pengguna | Tidak | Tidak | Ya |
| Kelola Resep | Tidak | Tidak | Ya |
| Kelola Pengaduan | Tidak | Tidak | Ya |

## 4. Aturan Hak Akses Penting

### 4.1 Resep

- Resep hanya bisa dibuat oleh user login
- Resep hanya bisa diedit oleh pemilik resep
- Resep hanya bisa dihapus oleh pemilik resep atau admin
- Guest hanya bisa membaca resep

### 4.2 Profile

- User hanya bisa edit profile miliknya sendiri
- User lain hanya bisa melihat profile publik
- Jika user nonaktif, profile publik dianggap tidak tersedia

### 4.3 Fitur Sosial

- Like, favorite, rating, komentar, dan follow hanya tersedia untuk user login
- Guest akan diarahkan untuk login jika mencoba aksi sosial
- User tidak boleh follow dirinya sendiri

### 4.4 Pengaduan

- Pengaduan hanya bisa dibuat user login
- User hanya bisa melihat laporan miliknya sendiri
- Admin bisa melihat seluruh laporan
- Hanya admin yang bisa mengubah status laporan

### 4.5 Admin

- Hanya akun dengan role `admin` yang boleh masuk panel admin
- Admin panel tidak boleh diakses member biasa
- Aksi admin seperti ubah status user, hapus resep, atau update laporan tetap memerlukan validasi keamanan

## 5. Penjelasan Teknis Sederhana

Sistem membedakan hak akses dengan dua hal utama:
- `session login`
- `role`

Logikanya:
1. Saat user login, data akun disimpan di `session`.
2. Di dalam session ada informasi seperti `id`, `name`, `email`, dan `role`.
3. Setiap halaman tertentu akan mengecek:
- apakah user sudah login
- apakah role user adalah `admin`
- apakah data yang dibuka milik user tersebut
4. Kalau tidak memenuhi syarat, sistem akan:
- redirect ke login
- menolak aksi
- atau menampilkan halaman tidak tersedia

Istilah teknis yang dipakai:
- `authentication`: memastikan siapa user yang masuk
- `authorization`: memastikan user boleh melakukan aksi tertentu
- `ownership check`: memastikan data memang milik user yang sedang login

## 6. Contoh Penjelasan ke Customer

Kalau dijelaskan singkat:
- Guest hanya bisa melihat-lihat.
- Member bisa berinteraksi dan mengelola resep sendiri.
- Admin punya hak moderasi untuk mengelola user, resep, dan laporan.

Kalau dijelaskan lebih teknis:
- sistem memakai session dan role untuk menentukan hak akses
- setiap halaman privat dan aksi penting punya pengecekan akses sebelum data diproses
