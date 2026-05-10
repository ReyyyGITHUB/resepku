# Aturan Sistem dan Business Rules ResepKu

Dokumen ini menjelaskan aturan inti sistem yang harus dipatuhi oleh aplikasi.

Tujuan dokumen ini:
- membantu customer paham logika kerja sistem
- mencegah salah persepsi tentang perilaku fitur
- menjadi acuan ketika testing, revisi, dan approval

## 1. Aturan Umum Sistem

### 1.1 Akun

- Satu email hanya boleh dipakai oleh satu akun
- Akun harus berstatus `aktif` agar bisa login
- Akun `nonaktif` tidak boleh masuk ke sistem
- Role akun hanya dua:
- `pengguna`
- `admin`

### 1.2 Session Login

- Saat login berhasil, sistem membuat session
- Saat logout, session dihapus
- Session dipakai untuk menentukan siapa user aktif saat ini

### 1.3 Guest Mode

- Guest bisa masuk tanpa akun penuh
- Guest hanya untuk akses baca
- Guest tidak boleh melakukan aksi sosial atau pengelolaan data

## 2. Aturan Fitur Auth

### 2.1 Register

- Nama wajib diisi
- Nama maksimal 50 karakter
- Email harus valid
- Email harus unik
- Password minimal 8 karakter
- Konfirmasi password harus sama dengan password

### 2.2 Login

- Email harus ada di tabel `pengguna`
- Password harus cocok dengan data akun
- Akun harus berstatus `aktif`
- Jika role `admin`, arahkan ke admin panel
- Jika role `pengguna`, arahkan ke home

### 2.3 Reset Password

- Token reset harus valid
- Token reset harus belum dipakai
- Token reset harus belum kedaluwarsa
- Password baru minimal 8 karakter
- Konfirmasi password baru harus sama

## 3. Aturan Resep

### 3.1 Pembuatan Resep

- Hanya user login yang boleh membuat resep
- Nama resep wajib diisi
- Deskripsi wajib diisi
- Langkah memasak wajib diisi
- Kategori wajib diisi
- Tingkat kesulitan hanya boleh:
- `mudah`
- `sedang`
- `sulit`
- Waktu memasak harus angka lebih dari 0
- Porsi harus angka lebih dari 0
- Minimal harus ada 1 bahan
- Minimal harus ada 1 peralatan

### 3.2 Foto Resep

- Foto resep tidak wajib, tapi jika diupload harus valid
- Format foto yang diterima:
- JPG
- PNG
- WEBP
- Jika upload gagal, resep tidak boleh disimpan setengah jadi

### 3.3 Edit Resep

- Hanya pemilik resep yang boleh edit resep
- Data yang diedit tetap harus lolos validasi yang sama seperti tambah resep

### 3.4 Hapus Resep

- Hanya pemilik resep atau admin yang boleh menghapus resep
- User biasa tidak boleh menghapus resep milik orang lain

## 4. Aturan Fitur Sosial

### 4.1 Like

- Hanya user login yang boleh like
- Satu user hanya boleh punya satu like per resep
- Klik like kedua kali berarti unlike

### 4.2 Favorite

- Hanya user login yang boleh favorite
- Satu user hanya boleh menyimpan satu favorite per resep
- Klik favorite kedua kali berarti remove favorite

### 4.3 Rating

- Hanya user login yang boleh rating
- Nilai rating hanya boleh 1 sampai 5
- Satu user hanya memiliki satu rating aktif per resep
- Jika user memberi rating lagi, rating lama diperbarui
- Nilai rata-rata rating dihitung dari semua rating aktif

### 4.4 Komentar

- Hanya user login yang boleh mengirim komentar
- Komentar tidak boleh kosong
- Komentar akan langsung tampil setelah berhasil disimpan

### 4.5 Follow

- Hanya user login yang boleh follow
- User tidak boleh follow dirinya sendiri
- Satu relasi follow hanya boleh ada satu kali
- Klik tombol follow kedua kali berarti unfollow

## 5. Aturan Profile

### 5.1 Profile Sendiri

- User boleh melihat statistik akun sendiri
- User boleh edit nama dan bio
- Email ditampilkan sebagai referensi akun

### 5.2 Ganti Password dari Edit Profile

- Untuk ganti password, user wajib memasukkan password lama
- Password lama harus cocok
- Password baru minimal 8 karakter
- Konfirmasi password baru harus sama

### 5.3 Profile Publik

- User boleh melihat profile user lain
- Jika akun target `nonaktif`, profile publik dianggap tidak tersedia

## 6. Aturan Pencarian dan Filter

- Search mencari resep berdasarkan nama
- Filter kategori hanya menampilkan kategori yang cocok
- Filter difficulty hanya menampilkan resep sesuai level kesulitan
- Filter waktu membatasi resep berdasarkan durasi memasak
- Sort `popular` memakai indikator popularitas sistem
- Sort `newest` menampilkan resep terbaru lebih dulu
- Sort `oldest` menampilkan resep terlama lebih dulu

## 7. Aturan Pengaduan

### 7.1 Pembuatan Laporan

- Hanya user login yang boleh mengirim pengaduan
- Target pengaduan hanya bisa:
- `resep`
- `pengguna`
- Kategori laporan harus dipilih
- Catatan atau alasan laporan boleh dipakai untuk menjelaskan masalah

### 7.2 Status Pengaduan

Status laporan hanya boleh:
- `menunggu`
- `selesai`
- `ditolak`

Aturan status:
- setiap laporan baru selalu mulai dari `menunggu`
- hanya admin yang boleh mengubah status
- user biasa hanya bisa membaca status

### 7.3 Inbox Pengaduan

- User hanya boleh melihat laporan miliknya sendiri
- Admin boleh melihat semua laporan

## 8. Aturan Admin

### 8.1 Dashboard

- Dashboard hanya untuk admin
- Statistik dashboard diambil dari data aktual database

### 8.2 Kelola Pengguna

- Admin boleh mencari user berdasarkan nama atau email
- Admin boleh filter role dan status
- Admin boleh aktif/nonaktifkan user
- Admin tidak boleh sembarang menghapus akun admin tertentu

### 8.3 Kelola Resep

- Admin boleh melihat semua resep
- Admin boleh filter resep berdasarkan kategori, difficulty, dan sort
- Admin boleh menghapus resep jika diperlukan

### 8.4 Kelola Pengaduan

- Admin boleh melihat semua pengaduan
- Admin boleh filter berdasarkan status, target, kategori, dan kata kunci
- Admin boleh mengubah status menjadi `selesai` atau `ditolak`

## 9. Aturan Keamanan Dasar

- Semua form penting memakai `CSRF token`
- Halaman privat harus mengecek session login
- Halaman admin harus mengecek role admin
- Aksi kepemilikan data harus mengecek pemilik data
- Input harus divalidasi sebelum disimpan

## 10. Contoh Penjelasan ke Customer

Kalau dijelaskan secara sederhana:
- sistem punya aturan siapa boleh apa
- setiap fitur tidak hanya tampil di layar, tapi ada batasan logika di belakang
- contoh: orang belum login tidak bisa like, dan orang yang bukan pemilik resep tidak bisa edit resep

Kalau dijelaskan secara teknis ringan:
- semua fitur dijaga oleh validasi input, pengecekan session, role, dan relasi data di database
