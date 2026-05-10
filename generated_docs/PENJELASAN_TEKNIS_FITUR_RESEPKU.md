# Penjelasan Teknis Fitur ResepKu

Dokumen ini menjelaskan fitur-fitur ResepKu dengan bahasa sederhana, tapi tetap menyebut logika dan istilah teknis yang dipakai di sistem.

Target pembaca:
- customer non-teknis
- project owner
- tim yang butuh mengerti logika sistem tanpa membaca source code

## 1. Gambaran Teknis Umum

Secara teknis, aplikasi ResepKu adalah web app berbasis:
- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL
- Penyimpanan file: folder `uploads/`

Logika kerjanya sederhana:
1. User membuka halaman.
2. User mengirim input lewat form atau tombol aksi.
3. Backend PHP menerima request.
4. Sistem memvalidasi data.
5. Jika valid, sistem membaca atau menulis ke database.
6. Sistem menampilkan hasil ke halaman berikutnya atau mengembalikan respons aksi.

Istilah teknis penting:
- `request`: permintaan dari browser ke server
- `response`: jawaban dari server ke browser
- `session`: penyimpanan status login user
- `validation`: pengecekan apakah input valid
- `database query`: perintah baca/tulis data ke MySQL
- `redirect`: memindahkan user ke halaman lain setelah proses selesai
- `CSRF token`: token keamanan untuk memastikan form dikirim dari halaman resmi aplikasi

## 2. Struktur Data Utama

Beberapa tabel inti yang dipakai:
- `pengguna`: data akun user dan admin
- `recipes`: data resep
- `bahan_resep`: daftar bahan per resep
- `peralatan_resep`: daftar alat per resep
- `likes`: data like user ke resep
- `favorite`: data resep yang disimpan user
- `ratings`: data rating user ke resep
- `komentar`: komentar pada resep
- `following`: relasi follow antar user
- `cs`: data pengaduan atau laporan
- `password_resets`: token reset password

## 3. Penjelasan Per Fitur

### 3.1 Login

Tujuan:
- memasukkan user ke sistem sesuai hak aksesnya

Logika sederhananya:
1. User isi email dan password.
2. Sistem cek dulu `CSRF token`.
3. Sistem validasi format email dan pastikan password tidak kosong.
4. Sistem cari data user di tabel `pengguna` berdasarkan email.
5. Jika email tidak ditemukan, login gagal.
6. Jika password tidak cocok, login gagal.
7. Jika status user `nonaktif`, login ditolak.
8. Jika lolos, sistem membuat `session login`.
9. Jika role user `admin`, user diarahkan ke panel admin.
10. Jika role user `pengguna`, user diarahkan ke home.

Istilah teknis:
- `session`: dipakai untuk menyimpan bahwa user sudah login
- `role`: penanda hak akses, misalnya `admin` atau `pengguna`
- `redirect`: setelah sukses, sistem memindahkan user ke halaman yang sesuai

Bahasa awam:
- sistem mencocokkan email dan password dengan data akun di database
- kalau cocok dan akun aktif, user dianggap resmi masuk

### 3.2 Login as Guest

Tujuan:
- memberi akses lihat-lihat aplikasi tanpa harus daftar dulu

Logika:
1. User klik `Login as Guest`.
2. Sistem tidak membaca tabel user.
3. Sistem hanya membuat penanda `guest_mode` di `session`.
4. User masuk ke halaman home sebagai guest.
5. Beberapa fitur dibatasi.

Guest bisa:
- lihat home
- cari resep
- buka detail resep
- baca komentar

Guest tidak bisa:
- like
- favorite
- rating
- komentar
- follow
- lapor
- tambah resep

Istilah teknis:
- `guest mode`: mode baca-saja
- `session flag`: penanda di sesi bahwa user sedang masuk sebagai tamu

### 3.3 Register

Tujuan:
- membuat akun baru

Logika:
1. User isi nama, email, password, konfirmasi password.
2. Sistem cek `CSRF token`.
3. Sistem validasi:
- nama wajib diisi
- email harus valid
- password minimal 8 karakter
- konfirmasi password harus sama
4. Sistem mencoba menyimpan data ke tabel `pengguna`.
5. Jika email sudah dipakai, database menolak data duplikat.
6. Jika berhasil, sistem tampilkan pesan sukses lalu arahkan ke login.

Istilah teknis:
- `unique email`: satu email hanya boleh dipakai oleh satu akun
- `insert`: proses menambah data baru ke database

Bahasa awam:
- register itu proses membuat baris akun baru di database

### 3.4 Lupa Password

Tujuan:
- membantu user yang lupa password

Logika:
1. User masukkan email.
2. Sistem cek format email dan `CSRF token`.
3. Sistem cari user berdasarkan email.
4. Jika akun ada dan aktif, sistem membuat `token reset`.
5. Token disimpan di tabel `password_resets`.
6. Sistem kirim email berisi link reset.
7. User klik link tersebut untuk ganti password.

Istilah teknis:
- `token`: kode unik sementara
- `expired`: token punya masa berlaku
- `SMTP`: jalur teknis untuk mengirim email

Bahasa awam:
- sistem membuat tiket reset sementara lalu mengirim link aman ke email user

### 3.5 Reset Password

Tujuan:
- mengganti password dengan token yang valid

Logika:
1. User membuka link reset yang membawa `token`.
2. Sistem cocokkan token ke tabel `password_resets`.
3. Sistem cek apakah token masih berlaku dan belum dipakai.
4. User isi password baru dan konfirmasi password baru.
5. Sistem validasi panjang password dan kecocokan konfirmasi.
6. Jika valid, password di akun user diubah.
7. Token reset ditandai sudah dipakai.
8. User diarahkan kembali ke login.

Istilah teknis:
- `one-time token`: token sekali pakai
- `update`: proses mengubah data yang sudah ada

### 3.6 Home / Katalog Resep

Tujuan:
- menampilkan daftar resep utama

Logika:
1. Sistem membaca parameter filter dari URL, misalnya:
- kata kunci
- kategori
- tingkat kesulitan
- waktu masak
- sorting
2. Sistem mengambil data resep dari database.
3. Sistem menerapkan filter yang diminta.
4. Sistem menampilkan hasil ke grid resep.

Istilah teknis:
- `query parameter`: nilai filter yang dikirim lewat URL
- `sorting`: pengurutan data, misalnya terbaru atau populer
- `filtering`: menyaring data sesuai kebutuhan

Bahasa awam:
- halaman home sebenarnya adalah katalog resep yang bisa dipersempit dengan filter

### 3.7 Search

Tujuan:
- mempermudah user mencari resep tertentu

Logika:
1. User mengetik nama resep.
2. Sistem mengambil nilai pencarian dari URL.
3. Sistem mencari resep yang judulnya cocok atau mendekati kata kunci.
4. Filter tambahan seperti kategori dan difficulty tetap ikut dipakai.
5. Hasil ditampilkan beserta jumlah hasilnya.

Istilah teknis:
- `search query`: kata kunci yang dicari
- `result count`: jumlah hasil pencarian

### 3.8 Detail Resep

Tujuan:
- menampilkan isi resep secara lengkap

Data yang ditampilkan:
- judul resep
- foto
- author
- waktu masak
- porsi
- tingkat kesulitan
- deskripsi
- bahan
- peralatan
- langkah memasak
- komentar
- resep terkait
- statistik sosial

Logika:
1. Halaman menerima `id resep`.
2. Sistem membaca detail resep dari tabel `recipes`.
3. Sistem membaca bahan dari `bahan_resep`.
4. Sistem membaca alat dari `peralatan_resep`.
5. Sistem membaca komentar dari `komentar`.
6. Sistem membaca status sosial user terhadap resep itu:
- apakah sudah like
- apakah sudah favorite
- rating user berapa
7. Sistem menghitung angka ringkasan seperti jumlah like dan rata-rata rating.

Istilah teknis:
- `relasi data`: satu resep terhubung ke beberapa tabel lain
- `aggregate`: hasil hitung seperti total like atau rata-rata rating

Bahasa awam:
- halaman detail itu menggabungkan beberapa sumber data, bukan cuma satu tabel

### 3.9 Tambah Resep

Tujuan:
- memungkinkan user membuat resep baru

Logika:
1. User login dulu.
2. User isi form:
- nama resep
- deskripsi
- langkah memasak
- waktu
- porsi
- kategori
- tingkat kesulitan
- foto
- bahan
- peralatan
3. Sistem validasi semua data penting.
4. Sistem validasi file foto:
- hanya tipe tertentu
- file harus berhasil diupload
5. Sistem simpan data utama ke tabel `recipes`.
6. Sistem ambil `ID resep` yang baru dibuat.
7. Sistem simpan bahan ke tabel `bahan_resep`.
8. Sistem simpan alat ke tabel `peralatan_resep`.
9. Jika semua berhasil, user diarahkan ke detail resep.

Istilah teknis:
- `multipart form`: form yang bisa mengirim file
- `upload`: proses menyimpan file dari browser ke server
- `transaction`: proses simpan berantai agar data tetap konsisten

Bahasa awam:
- resep disimpan bertahap: data utama dulu, lalu bahan dan alat menyusul menggunakan ID resep yang sama

### 3.10 Edit Resep

Tujuan:
- mengubah resep yang sudah pernah dibuat

Logika umum:
1. User membuka resep miliknya.
2. Sistem cek apakah resep itu memang milik user yang sedang login.
3. User mengubah data.
4. Sistem validasi ulang seperti saat tambah resep.
5. Sistem `update` data resep.
6. Jika perlu, daftar bahan dan alat juga diperbarui.

Istilah teknis:
- `ownership check`: pengecekan kepemilikan data
- `authorization`: memastikan hanya pemilik yang boleh mengubah

### 3.11 Hapus Resep

Tujuan:
- menghapus resep milik user

Logika:
1. User klik delete dari halaman `My Recipes`.
2. Sistem cek `CSRF token`.
3. Sistem cek apakah resep itu milik user yang sedang login.
4. Jika iya, sistem hapus data resep.
5. Halaman menampilkan notifikasi berhasil.

Istilah teknis:
- `delete operation`: penghapusan data
- `access control`: pembatasan siapa yang boleh menghapus

### 3.12 My Recipes

Tujuan:
- menjadi pusat pengelolaan resep milik user sendiri

Logika:
1. Sistem membaca semua resep berdasarkan `pengguna_id` user login.
2. Sistem bisa memfilter resep user berdasarkan kata kunci dan kategori.
3. Data ditampilkan dalam bentuk daftar kartu.
4. Dari sini user bisa:
- view
- edit
- delete

Bahasa awam:
- ini semacam dashboard pribadi untuk koleksi resep user

### 3.13 Favorite

Tujuan:
- menyimpan resep yang disukai untuk dibuka lagi nanti

Logika:
1. Saat user klik tombol favorite di detail resep, sistem memanggil endpoint aksi.
2. Sistem cek apakah pasangan `user + resep` sudah ada di tabel `favorite`.
3. Jika belum ada, sistem `insert`.
4. Jika sudah ada, sistem `delete`.
5. Karena itu favorite bekerja sebagai `toggle`.
6. Halaman favorite menampilkan semua resep yang tersimpan untuk user tersebut.

Istilah teknis:
- `toggle`: satu tombol untuk dua kondisi, simpan atau batalkan simpan
- `many-to-many relation`: satu user bisa punya banyak favorit, satu resep bisa difavoritkan banyak user

### 3.14 Like

Tujuan:
- memberi sinyal cepat bahwa user menyukai resep

Logika:
1. User klik like pada detail resep.
2. Endpoint `like` menerima `recipe_id`.
3. Sistem cek apakah like dari user itu sudah ada.
4. Jika belum ada, tambahkan.
5. Jika sudah ada, hapus.
6. Sistem kembalikan jumlah like terbaru ke halaman.

Istilah teknis:
- `AJAX/API action`: aksi dikirim tanpa reload halaman penuh
- `JSON response`: format data balasan dari server

Bahasa awam:
- tombol like bekerja instan, jadi halaman tidak perlu refresh total

### 3.15 Rating

Tujuan:
- memberi nilai kualitas resep

Logika:
1. User pilih rating 1 sampai 5.
2. Sistem validasi nilainya harus di rentang tersebut.
3. Sistem cek apakah user sudah pernah memberi rating.
4. Jika belum, buat data baru.
5. Jika sudah, update rating lama.
6. Sistem hitung rata-rata rating resep setelah perubahan.

Istilah teknis:
- `upsert-like behavior`: kalau data belum ada dibuat, kalau sudah ada diperbarui
- `average rating`: rata-rata nilai semua user

### 3.16 Komentar

Tujuan:
- memberi ruang diskusi pada resep

Logika:
1. User menulis komentar.
2. Sistem validasi komentar tidak boleh kosong.
3. Sistem simpan komentar ke tabel `komentar`.
4. Sistem ambil ulang daftar komentar terbaru.
5. Sistem kirim daftar komentar itu kembali ke halaman.
6. Komentar baru langsung tampil di bagian atas atau daftar aktif.

Istilah teknis:
- `submit form asynchronously`: kirim komentar tanpa pindah halaman
- `refresh partial data`: yang diperbarui hanya area komentar

### 3.17 Follow User

Tujuan:
- membangun koneksi antar user

Logika:
1. User membuka profil user lain.
2. User klik `Follow`.
3. Sistem cek apakah relasi follow sudah ada di tabel `following`.
4. Jika belum ada, sistem membuat relasi.
5. Jika sudah ada, sistem menghapus relasi.
6. Jumlah follower diperbarui.

Aturan:
- user tidak boleh follow dirinya sendiri

Istilah teknis:
- `relationship table`: tabel khusus untuk relasi antar akun
- `toggle follow`: satu tombol untuk follow dan unfollow

### 3.18 Profile

Tujuan:
- menampilkan identitas akun dan statistik sosial

Logika:
1. Sistem membaca data user dari tabel `pengguna`.
2. Sistem menghitung:
- jumlah resep
- jumlah follower
- jumlah following
3. Sistem mengambil resep terbaru milik user.
4. Jika membuka profil orang lain, tombol follow dan laporkan ditampilkan.
5. Jika membuka profil sendiri, tombol edit profile ditampilkan.

Bahasa awam:
- halaman profil adalah gabungan data akun, statistik, dan konten milik user

### 3.19 Edit Profile

Tujuan:
- mengubah nama, bio, dan password

Logika:
1. User login.
2. User ubah nama atau bio.
3. Jika hanya ubah profil dasar, sistem cukup update data profil.
4. Jika user juga ingin ganti password:
- user wajib isi password lama
- password baru wajib valid
- konfirmasi password harus cocok
5. Jika semua benar, sistem update data akun.

Istilah teknis:
- `conditional validation`: validasi tambahan hanya aktif kalau user mau ganti password

### 3.20 Pengaduan / Laporan

Tujuan:
- memberi saluran moderasi untuk melaporkan resep atau user

Logika:
1. User klik tombol `Laporkan`.
2. Sistem membuka form laporan.
3. User pilih kategori laporan dan isi catatan.
4. Sistem validasi input.
5. Sistem simpan ke tabel `cs`.
6. Status awal otomatis `menunggu`.
7. User bisa melihat laporan itu di halaman `Pengaduan Saya`.

Jenis target laporan:
- `resep`
- `pengguna`

Status laporan:
- `menunggu`
- `selesai`
- `ditolak`

Istilah teknis:
- `ticket`: satu data laporan
- `moderation workflow`: alur moderasi dari masuk sampai selesai

### 3.21 Pengaduan Saya

Tujuan:
- menampilkan riwayat laporan yang pernah dikirim user

Logika:
1. Sistem mengambil semua laporan dari tabel `cs` milik user login.
2. Data ditampilkan seperti inbox.
3. Saat satu item dipilih, detail tiket tampil di panel kanan.
4. User bisa melihat:
- target laporan
- isi catatan
- tanggal
- status

Bahasa awam:
- ini seperti kotak masuk untuk tiket laporan milik user

## 4. Penjelasan Teknis Panel Admin

### 4.1 Admin Dashboard

Tujuan:
- memberi ringkasan kondisi platform

Logika:
1. Sistem menghitung total data penting:
- total pengguna
- pengguna aktif
- pengguna nonaktif
- total resep
- total komentar
- total likes
- rata-rata rating
- pengaduan menunggu
2. Sistem menampilkan tabel ringkas untuk resep terbaru, user terbaru, dan laporan terbaru.

Istilah teknis:
- `dashboard metrics`: angka ringkasan untuk monitoring
- `aggregate query`: query hitung total, jumlah, rata-rata

### 4.2 Kelola Pengguna

Tujuan:
- admin bisa mengontrol akun user

Logika:
1. Admin membuka daftar pengguna.
2. Sistem mendukung filter berdasarkan:
- nama/email
- role
- status
3. Admin bisa:
- buka profil user
- aktifkan/nonaktifkan user
- hapus user tertentu
4. Sistem menolak penghapusan akun admin yang tidak boleh dihapus.

Istilah teknis:
- `admin moderation`: proses kendali akun oleh admin
- `status flag`: penanda apakah akun aktif atau nonaktif

### 4.3 Kelola Resep

Tujuan:
- admin bisa moderasi konten resep

Logika:
1. Admin melihat daftar semua resep.
2. Sistem menyediakan filter berdasarkan:
- judul atau author
- kategori
- difficulty
- sorting
3. Admin bisa melihat performa resep dari:
- like
- komentar
- rating
4. Admin bisa menghapus resep.

Bahasa awam:
- admin punya meja kontrol untuk membersihkan atau meninjau resep yang bermasalah

### 4.4 Kelola Pengaduan

Tujuan:
- memproses tiket laporan dari user

Logika:
1. Admin membuka daftar pengaduan.
2. Sistem memfilter berdasarkan:
- status
- target
- kategori
- kata kunci
3. Admin membaca isi laporan.
4. Admin memutuskan status:
- `selesai`
- `ditolak`
5. Status itu disimpan ke database.

Istilah teknis:
- `triage`: memilah laporan yang masuk
- `status transition`: perubahan status dari menunggu ke selesai atau ditolak

## 5. Sistem Keamanan Dasar

### 5.1 Session

Dipakai untuk:
- menyimpan user yang sedang login
- membedakan guest, user biasa, dan admin

Kalau session tidak ada:
- halaman privat akan meminta login

### 5.2 CSRF Token

Dipakai di form dan aksi penting seperti:
- login
- register
- edit profile
- tambah/hapus data
- aksi admin

Fungsi sederhananya:
- mencegah form palsu dari luar sistem mengirim aksi berbahaya

### 5.3 Validasi Input

Sistem mengecek:
- format email
- panjang password
- field wajib
- angka valid
- file upload valid

Tujuannya:
- mencegah data rusak masuk ke database

### 5.4 Role dan Hak Akses

Ada pemisahan hak akses:
- guest
- user
- admin

Contoh:
- user biasa tidak bisa buka panel admin
- guest tidak bisa melakukan aksi sosial
- user tidak bisa sembarang mengedit resep milik orang lain

## 6. Pola Teknis yang Dipakai di Banyak Fitur

### 6.1 Toggle Pattern

Dipakai pada:
- like
- favorite
- follow

Maksudnya:
- kalau data belum ada, sistem menambahkan
- kalau data sudah ada, sistem menghapus

Ini membuat satu tombol cukup untuk dua kondisi.

### 6.2 Redirect After Success

Dipakai pada:
- login
- register
- tambah resep
- edit profile
- hapus data tertentu

Tujuannya:
- user langsung masuk ke halaman hasil proses

### 6.3 List + Detail Pattern

Dipakai pada:
- pengaduan saya
- admin pengaduan
- daftar resep

Maksudnya:
- kiri atau atas untuk daftar item
- detail ditampilkan setelah item dipilih

## 7. Penjelasan Singkat untuk Presentasi Customer

Kalau ingin menjelaskan secara cepat:

- Login: sistem mencocokkan email dan password ke database, lalu membuat sesi login.
- Register: sistem membuat akun baru setelah validasi nama, email, dan password.
- Guest mode: user bisa melihat-lihat aplikasi tanpa akun, tapi tidak bisa melakukan aksi interaktif.
- Tambah resep: data resep utama, bahan, dan alat disimpan ke tabel yang berbeda tapi saling terhubung.
- Like/Favorite/Follow: memakai sistem toggle, jadi sekali klik bisa aktif atau nonaktif.
- Komentar: komentar disimpan ke database lalu langsung ditampilkan lagi ke halaman.
- Rating: setiap user bisa memberi nilai, lalu sistem menghitung rata-rata rating resep.
- Pengaduan: user membuat tiket laporan, lalu admin memproses statusnya.
- Admin panel: admin memonitor user, resep, dan laporan dari satu tempat.

## 8. Kesimpulan

Secara teknis, ResepKu dibangun dengan pola web application yang cukup jelas:
- akun dan sesi untuk identitas user
- database relasional untuk menyimpan data yang saling terhubung
- validasi input untuk menjaga kualitas data
- endpoint aksi cepat untuk fitur sosial
- panel admin untuk moderasi

Kalau dijelaskan ke orang awam:
- aplikasi ini bukan cuma tampilan resep
- di belakangnya ada logika identitas user, pengecekan hak akses, relasi antar data, dan proses moderasi konten
