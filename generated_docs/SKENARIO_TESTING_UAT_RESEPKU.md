# Skenario Testing UAT ResepKu

Dokumen ini berisi skenario testing atau UAT (User Acceptance Test) untuk membantu customer memverifikasi bahwa sistem berjalan sesuai kebutuhan.

Cara pakai:
- jalankan satu per satu
- tandai `Lulus` atau `Gagal`
- jika gagal, catat hasil aktualnya

## 1. Informasi UAT

Kolom yang disarankan saat dipakai:
- ID Test
- Nama Skenario
- Langkah Uji
- Hasil yang Diharapkan
- Status: Lulus / Gagal
- Catatan

## 2. Skenario Auth

### AUTH-01 Login berhasil sebagai user

Tujuan:
- memastikan user aktif bisa login

Langkah:
1. Buka halaman login
2. Isi email user valid
3. Isi password yang sesuai
4. Klik `Login`

Hasil yang diharapkan:
- login berhasil
- user masuk ke halaman home
- session login terbentuk

### AUTH-02 Login gagal dengan password salah

Langkah:
1. Buka login
2. Isi email valid
3. Isi password salah
4. Klik `Login`

Hasil yang diharapkan:
- login gagal
- tampil pesan error
- user tetap di halaman login

### AUTH-03 Login gagal untuk akun nonaktif

Langkah:
1. Gunakan akun status `nonaktif`
2. Isi email dan password benar
3. Klik `Login`

Hasil yang diharapkan:
- login ditolak
- muncul informasi bahwa akun nonaktif

### AUTH-04 Login as Guest berhasil

Langkah:
1. Buka login
2. Klik `Login as Guest`

Hasil yang diharapkan:
- user masuk ke home
- sistem masuk dalam mode guest

### AUTH-05 Register berhasil

Langkah:
1. Buka halaman register
2. Isi nama valid
3. Isi email baru
4. Isi password minimal 8 karakter
5. Isi konfirmasi password yang sama
6. Submit

Hasil yang diharapkan:
- akun baru berhasil dibuat
- user diarahkan ke login

### AUTH-06 Register gagal karena email duplikat

Langkah:
1. Buka register
2. Isi email yang sudah terdaftar
3. Submit

Hasil yang diharapkan:
- registrasi gagal
- muncul pesan email sudah dipakai

### AUTH-07 Lupa password kirim link

Langkah:
1. Buka halaman lupa password
2. Isi email valid
3. Klik kirim

Hasil yang diharapkan:
- sistem menampilkan pesan bahwa link reset dikirim jika email terdaftar

## 3. Skenario Guest Mode

### GUEST-01 Guest bisa membuka home

Hasil yang diharapkan:
- halaman home tampil normal
- katalog resep terlihat

### GUEST-02 Guest bisa membuka detail resep

Hasil yang diharapkan:
- detail resep tampil lengkap
- bahan, alat, langkah, komentar bisa dibaca

### GUEST-03 Guest tidak bisa like

Langkah:
1. Masuk sebagai guest
2. Buka detail resep
3. Klik `Like`

Hasil yang diharapkan:
- guest diminta login atau diarahkan ke login
- like tidak tersimpan

### GUEST-04 Guest tidak bisa favorite / komentar / rating / follow

Hasil yang diharapkan:
- semua aksi sosial ditolak untuk guest

## 4. Skenario Resep

### RESEP-01 User berhasil membuat resep

Langkah:
1. Login sebagai user
2. Buka `Add Recipe`
3. Isi semua field wajib
4. Isi minimal 1 bahan
5. Isi minimal 1 alat
6. Klik simpan

Hasil yang diharapkan:
- resep berhasil dibuat
- user diarahkan ke detail resep baru

### RESEP-02 Gagal membuat resep jika field wajib kosong

Hasil yang diharapkan:
- sistem menolak simpan
- tampil pesan validasi

### RESEP-03 Gagal membuat resep jika waktu memasak bukan angka valid

Hasil yang diharapkan:
- sistem menolak simpan

### RESEP-04 Gagal membuat resep jika tidak ada bahan

Hasil yang diharapkan:
- sistem menolak simpan

### RESEP-05 User bisa melihat resep miliknya di My Recipes

Hasil yang diharapkan:
- resep tampil di daftar My Recipes

### RESEP-06 User bisa edit resep miliknya sendiri

Hasil yang diharapkan:
- perubahan berhasil disimpan
- detail resep menampilkan data baru

### RESEP-07 User tidak bisa edit resep orang lain

Hasil yang diharapkan:
- akses ditolak atau aksi gagal

### RESEP-08 User bisa hapus resep miliknya

Hasil yang diharapkan:
- resep terhapus dari My Recipes

## 5. Skenario Search dan Filter

### SEARCH-01 Cari resep berdasarkan nama

Langkah:
1. Buka search
2. Masukkan kata kunci resep

Hasil yang diharapkan:
- hasil yang relevan tampil

### SEARCH-02 Filter kategori

Hasil yang diharapkan:
- hanya resep kategori sesuai yang tampil

### SEARCH-03 Filter difficulty

Hasil yang diharapkan:
- hanya resep dengan tingkat kesulitan yang dipilih yang tampil

### SEARCH-04 Sort popular / newest

Hasil yang diharapkan:
- urutan hasil berubah sesuai sort

## 6. Skenario Sosial

### SOS-01 User bisa like resep

Langkah:
1. Login user
2. Buka detail resep
3. Klik `Like`

Hasil yang diharapkan:
- jumlah like bertambah
- tombol like berubah aktif

### SOS-02 Klik like kedua kali menjadi unlike

Hasil yang diharapkan:
- jumlah like berkurang
- status aktif hilang

### SOS-03 User bisa favorite resep

Hasil yang diharapkan:
- resep masuk ke halaman favorite

### SOS-04 Klik favorite kedua kali menghapus dari favorite

Hasil yang diharapkan:
- resep keluar dari daftar favorite

### SOS-05 User bisa memberi rating valid

Langkah:
1. Pilih rating 1 sampai 5

Hasil yang diharapkan:
- rating tersimpan
- nilai user dan rata-rata tampil sesuai

### SOS-06 User tidak bisa memberi rating di luar 1-5

Hasil yang diharapkan:
- sistem menolak input

### SOS-07 User bisa menulis komentar

Hasil yang diharapkan:
- komentar baru langsung tampil di daftar komentar

### SOS-08 Komentar kosong ditolak

Hasil yang diharapkan:
- sistem menolak komentar kosong

### SOS-09 User bisa follow user lain

Hasil yang diharapkan:
- status follow aktif
- jumlah follower target bertambah

### SOS-10 User tidak bisa follow dirinya sendiri

Hasil yang diharapkan:
- aksi ditolak

## 7. Skenario Profile

### PROF-01 User bisa melihat profile sendiri

Hasil yang diharapkan:
- nama, bio, statistik, dan resep terbaru tampil

### PROF-02 User bisa edit nama dan bio

Hasil yang diharapkan:
- data profile berhasil berubah

### PROF-03 User bisa ganti password dengan password lama yang benar

Hasil yang diharapkan:
- password baru tersimpan

### PROF-04 Ganti password gagal jika password lama salah

Hasil yang diharapkan:
- perubahan ditolak

### PROF-05 User bisa melihat profile user lain

Hasil yang diharapkan:
- profile publik tampil
- tombol follow tersedia

## 8. Skenario Pengaduan

### LAP-01 User bisa melapor resep

Langkah:
1. Login
2. Buka detail resep
3. Klik `Laporkan`
4. Pilih kategori
5. Isi catatan
6. Submit

Hasil yang diharapkan:
- laporan berhasil dibuat
- status awal `menunggu`

### LAP-02 User bisa melapor profile user lain

Hasil yang diharapkan:
- laporan berhasil dibuat

### LAP-03 User bisa melihat laporan di Pengaduan Saya

Hasil yang diharapkan:
- tiket tampil di inbox pengaduan

### LAP-04 Detail laporan menampilkan status

Hasil yang diharapkan:
- target, isi laporan, tanggal, dan status tampil

## 9. Skenario Admin

### ADM-01 Admin berhasil login ke dashboard

Hasil yang diharapkan:
- admin masuk ke dashboard admin

### ADM-02 Member biasa tidak bisa membuka panel admin

Hasil yang diharapkan:
- akses ditolak atau diarahkan keluar

### ADM-03 Admin bisa melihat statistik dashboard

Hasil yang diharapkan:
- total user, resep, komentar, likes, rating, dan laporan tampil

### ADM-04 Admin bisa filter data user

Hasil yang diharapkan:
- daftar user berubah sesuai filter

### ADM-05 Admin bisa aktif/nonaktifkan user

Hasil yang diharapkan:
- status user berubah

### ADM-06 Admin bisa menghapus user tertentu

Hasil yang diharapkan:
- user terhapus sesuai aturan sistem

### ADM-07 Admin bisa melihat semua resep

Hasil yang diharapkan:
- daftar resep admin tampil lengkap

### ADM-08 Admin bisa menghapus resep

Hasil yang diharapkan:
- resep hilang dari daftar

### ADM-09 Admin bisa melihat semua pengaduan

Hasil yang diharapkan:
- daftar pengaduan tampil lengkap

### ADM-10 Admin bisa mengubah status pengaduan menjadi selesai

Hasil yang diharapkan:
- status tiket berubah ke `selesai`

### ADM-11 Admin bisa mengubah status pengaduan menjadi ditolak

Hasil yang diharapkan:
- status tiket berubah ke `ditolak`

## 10. Skenario Keamanan Dasar

### SEC-01 Form tanpa token valid ditolak

Hasil yang diharapkan:
- sistem tidak memproses aksi

### SEC-02 Halaman privat tanpa login ditolak

Hasil yang diharapkan:
- user diarahkan ke login

### SEC-03 Aksi edit/hapus data orang lain ditolak

Hasil yang diharapkan:
- sistem menolak aksi yang bukan hak user

## 11. Saran Penggunaan Dokumen Ini

Dokumen ini paling cocok dipakai saat:
- sesi demo dengan customer
- proses UAT
- checklist approval sebelum go-live
- acuan regresi setelah revisi fitur
