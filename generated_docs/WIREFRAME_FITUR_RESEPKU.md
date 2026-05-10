# Wireframe Fitur ResepKu

Dokumen ini dibuat dari implementasi aplikasi yang ada saat ini.
Tujuan dokumen ini: membantu menjelaskan layar, logika, dan fitur ke customer tanpa masuk ke detail teknis coding.

## 1. Ringkasan Produk

ResepKu adalah platform berbagi resep masakan.
User bisa:
- cari resep
- lihat detail resep
- upload resep sendiri
- like, favorite, komentar, rating
- follow user lain
- kirim pengaduan
- kelola profil

Ada 3 jenis akses:
- Guest: bisa lihat-lihat resep, tapi tidak bisa aksi sosial
- User login: bisa pakai semua fitur user
- Admin: bisa masuk panel admin untuk moderasi

## 2. Struktur Menu Utama

```text
[SIDEBAR]

Logo ResepKu
Nama User / Guest
Status akun

Menu:
- Home
- Profile
- My Recipes
- Add Recipe
- Favorite
- Pengaduan Saya
- Search

Tambahan:
- Admin Panel    -> hanya muncul untuk admin
- Login / Logout -> tergantung status user
```

## 3. Wireframe Per Halaman

### 3.1 Login

```text
+--------------------------------------------------+
|                  LOGIN PAGE                      |
+--------------------------------------------------+
| Logo ResepKu                                     |
| Welcome                                          |
| [Email________________________]                  |
| [Password_____________________]                  |
| [Login]                                          |
| Forgot password?                                 |
| [Login as Guest]                                 |
| Belum punya akun? Create one                     |
+--------------------------------------------------+
```

Logika:
- Login user biasa masuk ke Home
- Login admin langsung masuk ke Admin Panel
- Login as Guest masuk ke Home dalam mode guest
- Guest bisa browsing, tapi tidak bisa like/favorite/rating/follow/komen/lapor

### 3.2 Register

```text
+--------------------------------------------------+
|                REGISTER PAGE                     |
+--------------------------------------------------+
| Logo ResepKu                                     |
| Create your account                              |
| [Name_________________________]                  |
| [Email________________________]                  |
| [Password_____________________]                  |
| [Confirm Password_____________]                  |
| [Register Account]                              |
+--------------------------------------------------+
```

Logika:
- Nama wajib
- Email harus valid dan unik
- Password minimal 8 karakter
- Setelah sukses, user diarahkan ke login

### 3.3 Lupa Password

```text
+--------------------------------------------------+
|               FORGOT PASSWORD                    |
+--------------------------------------------------+
| [Email________________________]                  |
| [Kirim Link Reset]                              |
| Kembali ke login                                |
+--------------------------------------------------+
```

Logika:
- User input email
- Sistem kirim link reset ke email jika akun valid dan aktif
- Reset dilakukan lewat halaman token khusus

### 3.4 Home

```text
+--------------------+---------------------------------------------+
| SIDEBAR            | TOPBAR                                      |
| - Home             | [Search Recipes.................] [Add] [CS]|
| - Profile          +---------------------------------------------+
| - My Recipes       | HERO                                        |
| - Add Recipe       | "Learn, Cook, & Eat Your Food"              |
| - Favorite         | [Food] [Salad] [Dessert] [Drinks]           |
| - Pengaduan Saya   | [Difficulty] [Time] [Sort] [Apply] [Clear]  |
| - Search           +---------------------------------------------+
|                    | GRID RESEP                                  |
|                    | [Card Resep] [Card Resep] [Card Resep]      |
|                    | [Card Resep] [Card Resep] [Card Resep]      |
+--------------------+---------------------------------------------+
```

Fitur:
- search resep
- filter kategori
- filter kesulitan
- filter waktu masak
- sorting newest / popular / oldest
- masuk ke detail resep
- tombol cepat ke tambah resep
- tombol cepat ke pengaduan

Logika:
- Default home menampilkan katalog resep
- Jika filter aktif, hasil menyesuaikan query
- Guest tetap bisa buka detail resep

### 3.5 Search

```text
+--------------------------------------------------+
|                SEARCH PAGE                       |
+--------------------------------------------------+
| [Search Recipes.................]                |
| [All] [Food] [Salad] [Dessert] [Drinks]         |
| [Difficulty] [Sort] [Apply] [Clear]             |
| Result count                                     |
|                                                  |
| Hasil pencarian:                                 |
| [Card Resep] [Card Resep] [Card Resep]          |
+--------------------------------------------------+
```

Fitur:
- pencarian berdasarkan nama resep
- filter kategori
- filter difficulty
- sort newest / popular

Logika:
- Jika belum ada query/filter, halaman mengajak user mulai mencari
- Jika hasil kosong, tampil empty state dan tombol reset filter

### 3.6 Detail Resep

```text
+------------------------------------------------------------------+
| BACK                                                             |
+------------------------------------------------------------------+
| FOTO RESEP                   | INFO RESEP                        |
|                              | Judul                             |
|                              | Author                            |
|                              | Waktu | Porsi | Difficulty | Rate |
|                              | Deskripsi singkat                 |
|                              | [Like] [Favorite] [Rate] [Share]  |
|                              | [Laporkan]                        |
+------------------------------------------------------------------+
| DESCRIPTION                                                      |
| Penjelasan resep                                                 |
+------------------------------------------------------------------+
| BAHAN                     | PERALATAN                            |
| - item                    | - item                               |
+------------------------------------------------------------------+
| LANGKAH MEMASAK                                                |
| 1. ...                                                          |
| 2. ...                                                          |
+------------------------------------------------------------------+
| KOMENTAR                                                         |
| [Textarea komentar.................] [Kirim]                    |
| List komentar                                                   |
+------------------------------------------------------------------+
| RESEP TERKAIT                                                    |
| [Recipe mini card] [Recipe mini card] [Recipe mini card]        |
+------------------------------------------------------------------+
```

Fitur:
- lihat isi resep lengkap
- lihat author
- like resep
- favorite resep
- rating 1 sampai 5
- share link resep
- laporkan resep
- baca dan kirim komentar
- lihat resep terkait

Logika:
- Guest bisa baca detail dan komentar, tapi tidak bisa aksi sosial
- User login bisa like/favorite/rating/komentar/lapor
- Semua aksi sosial bersifat langsung update
- Rating tersimpan per user per resep
- Favorite dan like bersifat toggle

### 3.7 Profile Sendiri

```text
+------------------------------------------------------------------+
| PROFILE HEADER                                                   |
| Foto Profil | Nama | Badge role                                  |
| Bio                                                           |
| Recipe count | Follower | Following                              |
| [Edit Profile]                                                 |
+------------------------------------------------------------------+
| RECENT RECIPES                                                  |
| [Card resep user] [Card resep user] [Card resep user]          |
+------------------------------------------------------------------+
| COMMUNITY / SUGGESTED ACCOUNT                                   |
| akun yang disarankan untuk dilihat/follow                       |
+------------------------------------------------------------------+
```

Fitur:
- lihat profil sendiri
- lihat statistik akun
- lihat resep terbaru milik sendiri
- masuk ke edit profile

### 3.8 Profile User Lain

```text
+------------------------------------------------------------------+
| PUBLIC PROFILE                                                   |
| Foto Profil | Nama | Role                                        |
| Bio                                                              |
| Recipe count | Follower | Following                               |
| [Follow / Following]  [Laporkan]                                 |
+------------------------------------------------------------------+
| RECENT RECIPES                                                    |
| daftar resep user tersebut                                        |
+------------------------------------------------------------------+
```

Fitur:
- lihat profil user lain
- follow / unfollow
- laporkan user
- buka resep milik user tersebut

Logika:
- User tidak bisa follow dirinya sendiri
- Jika akun user nonaktif, profil publik dianggap tidak tersedia

### 3.9 Edit Profile

```text
+--------------------------------------------------+
|                EDIT PROFILE                      |
+--------------------------------------------------+
| Ringkasan profil                                 |
| Nama                                             |
| Email (readonly)                                 |
| Bio                                              |
|                                                  |
| Ganti Password (accordion)                       |
| - Password sekarang                              |
| - Password baru                                  |
| - Konfirmasi password baru                       |
|                                                  |
| [Cancel] [Simpan Perubahan]                      |
+--------------------------------------------------+
```

Logika:
- Nama dan bio bisa diubah
- Email tampil baca saja
- Password hanya berubah jika user isi section password
- Verifikasi password lama dibutuhkan untuk ganti password

### 3.10 Add Recipe

```text
+------------------------------------------------------------------+
| CREATE RECIPE                                                    |
+------------------------------------------------------------------+
| PREVIEW FOTO                 | INFORMASI INTI                    |
| [Upload foto]                | Nama resep                        |
|                              | Deskripsi                         |
|                              | Waktu memasak                     |
|                              | Porsi                             |
|                              | Kategori                          |
|                              | Tingkat kesulitan                 |
+------------------------------------------------------------------+
| BAHAN                                                            |
| [Nama bahan] [Jumlah] [Satuan] [Catatan]                         |
| [Tambah baris bahan]                                             |
+------------------------------------------------------------------+
| PERALATAN                                                        |
| [Nama peralatan]                                                 |
| [Tambah baris alat]                                              |
+------------------------------------------------------------------+
| LANGKAH MEMASAK                                                  |
| [Textarea langkah....]                                           |
| [Simpan Resep]                                                   |
+------------------------------------------------------------------+
```

Fitur:
- upload foto resep
- isi metadata resep
- isi bahan
- isi peralatan
- isi langkah memasak

Logika:
- Hanya user login
- Minimal 1 bahan dan 1 peralatan
- Waktu dan porsi harus angka valid
- Foto mendukung JPG, PNG, WEBP
- Setelah sukses, langsung masuk ke halaman detail resep

### 3.11 My Recipes

```text
+--------------------------------------------------+
|                MY RECIPES                        |
+--------------------------------------------------+
| [Search my recipes..............] [Add] [CS]     |
| Filter kategori                                  |
| Statistik: total resep / hasil tampil            |
|                                                  |
| [Card resep]                                     |
| - Judul                                          |
| - Kategori                                       |
| - Difficulty                                     |
| - Waktu / Porsi                                  |
| - Summary                                        |
| - [Edit] [View] [Delete]                         |
+--------------------------------------------------+
```

Fitur:
- lihat semua resep milik sendiri
- cari resep sendiri
- filter kategori
- edit resep
- hapus resep

Logika:
- Hapus hanya berlaku ke resep milik sendiri
- Setelah edit/hapus, ada notifikasi status

### 3.12 Favorite

```text
+--------------------------------------------------+
|                 FAVORITE                         |
+--------------------------------------------------+
| [Search favorites..............] [Add] [CS]      |
| Statistik total favorit                          |
|                                                  |
| [Card resep favorit]                             |
| - Judul                                          |
| - Author                                         |
| - Difficulty                                     |
| - Cook time / Rating / Likes                     |
| - [View] [Remove]                                |
+--------------------------------------------------+
```

Fitur:
- lihat resep yang disimpan
- buka detail resep favorit
- hapus resep dari daftar favorit

Logika:
- Urutan favorit dari yang terbaru disimpan

### 3.13 Pengaduan Saya

```text
+-----------------------------+------------------------------------+
| LIST PENGADUAN              | DETAIL PENGADUAN                   |
+-----------------------------+------------------------------------+
| [Item laporan]              | Kategori laporan                   |
| - kategori                  | Target                             |
| - target                    | Tanggal                            |
| - preview catatan           | Status                             |
| - status                    | Isi laporan                        |
| - ticket id                 |                                    |
| [Item laporan]              |                                    |
+-----------------------------+------------------------------------+
```

Fitur:
- melihat semua laporan yang pernah dikirim
- tampilan seperti inbox
- buka detail laporan
- lihat status proses laporan

Logika:
- Status laporan: `menunggu`, `selesai`, `ditolak`
- Target laporan bisa resep atau pengguna

## 4. Wireframe Admin

### 4.1 Admin Dashboard

```text
+--------------------------------------------------------------+
| ADMIN DASHBOARD                                              |
+--------------------------------------------------------------+
| Card statistik:                                              |
| [Total User] [User Aktif] [User Nonaktif] [Total Resep]      |
| [Total Komentar] [Pengaduan Menunggu] [Total Likes] [Avg Rate]|
+--------------------------------------------------------------+
| Resep terbaru      | Pengaduan terbaru                        |
| table              | table                                    |
+--------------------------------------------------------------+
| Pengguna terbaru                                            |
| table                                                       |
+--------------------------------------------------------------+
```

Fitur:
- ringkasan kondisi platform
- monitor resep terbaru
- monitor user terbaru
- monitor pengaduan terbaru

### 4.2 Kelola Pengguna

```text
+--------------------------------------------------------------+
| KELOLA PENGGUNA                                              |
+--------------------------------------------------------------+
| Filter: [Cari] [Role] [Status] [Terapkan] [Reset]            |
+--------------------------------------------------------------+
| TABLE USER                                                   |
| Nama | Email | Role | Status | Total Resep | Tgl Daftar | Aksi|
| Aksi: [Profil] [Aktifkan/Nonaktifkan] [Hapus]                |
+--------------------------------------------------------------+
```

Logika:
- Admin bisa aktif/nonaktifkan user
- Admin bisa hapus user non-admin
- Admin tidak boleh hapus akun admin tertentu

### 4.3 Kelola Resep

```text
+----------------------------------------------------------------+
| KELOLA RESEP                                                   |
+----------------------------------------------------------------+
| Filter: [Cari] [Kategori] [Kesulitan] [Sort] [Terapkan] [Reset]|
+----------------------------------------------------------------+
| TABLE RESEP                                                    |
| Judul | Author | Kategori | Difficulty | Likes | Komen | Aksi  |
| Aksi: [Lihat] [Hapus]                                          |
+----------------------------------------------------------------+
```

Logika:
- Admin bisa cari resep bermasalah
- Admin bisa lihat performa resep dari like, komentar, rating
- Admin bisa hapus resep langsung

### 4.4 Kelola Pengaduan

```text
+----------------------------------------------------------------+
| KELOLA PENGADUAN                                               |
+----------------------------------------------------------------+
| Filter: [Status] [Target] [Kategori] [Cari] [Terapkan] [Reset] |
+----------------------------------------------------------------+
| TABLE PENGADUAN                                                |
| Pelapor | Target | Kategori | Isi | Status | Tgl | Aksi        |
| Aksi: [Selesai] [Tolak]                                        |
+----------------------------------------------------------------+
```

Logika:
- Admin memproses laporan masuk
- Laporan bisa ditandai selesai atau ditolak
- Admin bisa buka target yang dilaporkan

## 5. Logika Fitur Inti

### 5.1 Mode Guest

```text
Guest boleh:
- lihat home
- cari resep
- buka detail resep
- baca komentar

Guest tidak boleh:
- like
- favorite
- rating
- komentar
- follow
- kirim pengaduan
- tambah resep
- edit profil
```

### 5.2 Interaksi Sosial

```text
LIKE
User klik Like
-> sistem toggle status like
-> counter like update

FAVORITE
User klik Favorite
-> sistem toggle status favorite
-> resep masuk/keluar dari daftar favorite

RATING
User pilih rating 1-5
-> sistem simpan rating per user
-> rata-rata rating resep update

FOLLOW
User buka profil user lain
-> klik Follow / Following
-> sistem toggle status follow
-> jumlah follower update

KOMENTAR
User tulis komentar
-> submit
-> komentar tampil di list
-> jumlah komentar update
```

### 5.3 Pengaduan

```text
User klik Laporkan pada resep / profil
-> pilih kategori laporan
-> isi catatan
-> kirim
-> sistem buat ticket
-> status awal = menunggu
-> user bisa pantau di "Pengaduan Saya"
-> admin review
-> admin ubah ke selesai / ditolak
```

### 5.4 Manajemen Resep

```text
Buat resep
-> isi form
-> upload foto
-> isi bahan
-> isi alat
-> isi langkah
-> simpan
-> tampil di detail resep dan katalog

Kelola resep pribadi
-> buka My Recipes
-> edit / view / delete
```

## 6. Nilai Jual ke Customer

Poin yang bisa dijelaskan saat presentasi:
- Platform bukan cuma katalog resep, tapi ada unsur komunitas
- Ada mode guest untuk menurunkan hambatan masuk user baru
- Ada fitur sosial lengkap: like, favorite, komentar, rating, follow
- Ada sistem pengaduan untuk moderasi konten
- Ada panel admin untuk kontrol platform
- User-generated content jelas: user bisa upload dan kelola resep sendiri
- Alur navigasi sederhana dan mudah dipahami

## 7. Kesimpulan Singkat

Secara bisnis, ResepKu punya 3 lapisan utama:
- Discovery: home, search, detail resep
- Community: profile, follow, komentar, like, favorite, rating
- Governance: pengaduan dan admin panel

Kalau dipresentasikan ke customer, saran urutan demo:
1. Login / guest mode
2. Home dan search
3. Detail resep dan aksi sosial
4. Profile dan follow
5. Add Recipe dan My Recipes
6. Pengaduan
7. Admin Panel
