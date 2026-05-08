# Invoice Progress Fitur ResepKu

**Berdasarkan PRD:** `PRD_ResepKu.md`  
**Tanggal pengecekan:** 8 Mei 2026  
**Metode cek:** mencocokkan PRD dengan file proyek, halaman PHP, API, dan struktur database.

---

## Ringkasan Invoice

| Keterangan | Jumlah |
|---|---:|
| Total item dicek | 55 |
| ✅ Selesai | 34 |
| ⚠️ Sebagian | 7 |
| ❌ Belum selesai | 14 |

> Catatan: status ini berdasarkan pengecekan kode/static review, belum termasuk testing manual semua flow di browser.

---

## 1. Autentikasi & Akun

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ✅ | Register | Ada `auth/register.php` dengan validasi form dan insert user. |
| ⚠️ | Register aman dengan hash password | Form ada, tapi password masih disimpan langsung, belum `password_hash()`. |
| ✅ | Login | Ada `auth/login.php`, session user, validasi status akun. |
| ⚠️ | Login aman dengan `password_verify()` | Login ada, tapi masih membandingkan password plaintext. |
| ❌ | Lupa Kata Sandi | File `auth/lupa-sandi.php` belum ada. |
| ✅ | Logout | Ada `auth/logout.php`. |
| ❌ | Edit Profil | Belum ada form edit nama, foto profil, dan bio. |
| ✅ | Role pengguna/admin di database | Kolom `role` ada di tabel `pengguna`. |
| ✅ | Session regenerate setelah login | Ada `session_regenerate_id(true)` di login. |
| ✅ | CSRF auth | Form login/register memakai token CSRF. |

---

## 2. Manajemen Resep

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ✅ | Buat Resep | Ada `resep/buat.php`. |
| ✅ | Edit Resep | Ada `resep/edit.php`. |
| ✅ | Hapus Resep | Ada aksi delete di `resep/myresep.php` dan fungsi `recipe_delete_db()`. |
| ✅ | Lihat Detail Resep | Ada `resep/detail.php`. |
| ✅ | Upload Foto Resep | Form buat/edit menerima `foto_resep`, validasi ekstensi/MIME, simpan ke `uploads/recipes`. |
| ✅ | Tag Kategori | Kolom `kategori`, input kategori, filter kategori tersedia. |
| ✅ | Tingkat Kesulitan | Kolom `tingkat_kesulitan`, select mudah/sedang/sulit tersedia. |
| ✅ | Alat & Bahan | Ada tabel `bahan_resep` dan `peralatan_resep`, detail menampilkan bahan/peralatan. |
| ✅ | Langkah Memasak | Ada kolom `langkah_resep`, form dan detail menampilkan steps. |
| ✅ | Waktu Memasak | Ada kolom `waktu_memasak`, form dan tampilan meta. |
| ✅ | Jumlah Porsi | Ada kolom `porsi`, form dan tampilan meta. |
| ✅ | Kolom tambahan PRD di `recipes` | `foto_resep`, `kategori`, `tingkat_kesulitan`, `pengguna_id` sudah ada. |

---

## 3. Fitur Sosial

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ✅ | Like Resep | Ada `api/like.php`, fungsi toggle like, tombol di detail. |
| ❌ | Komentar Resep | Tabel `komentar` ada, tapi belum ada `api/komentar.php` dan UI komentar di detail. |
| ✅ | Rating Bintang | Ada `api/rating.php`, fungsi upsert rating, tombol rate di detail. |
| ✅ | Favorit Resep | Ada `api/favorite.php`, halaman `resep/favorite.php`, toggle dan daftar favorit. |
| ✅ | Share Resep | Tombol share di detail menyalin link ke clipboard. |
| ❌ | Following / Follower | Tabel ada dan statistik dihitung, tapi belum ada API follow/unfollow dan tombol follow. |
| ❌ | Lihat Profil Orang Lain | File `profil/lihat.php` belum ada. |
| ❌ | Feed Resep dari Following | Home masih katalog umum, belum feed khusus berdasarkan akun yang diikuti. |

---

## 4. Pencarian & Filter

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ✅ | Cari Resep | Ada `cari.php`, query `q` mencari nama resep. |
| ✅ | Filter Kategori | Ada filter kategori di home/search dan query repository. |
| ✅ | Filter Kesulitan | Ada filter difficulty di home/search. |
| ⚠️ | Filter Waktu Masak | Ada di `home/index.php`, tapi belum ada di `cari.php`. |
| ✅ | Sort Terpopuler | Ada sort `popular` berdasarkan like dan rating. |
| ✅ | Sort Terbaru | Default sort `newest` berdasarkan tanggal posting. |

---

## 5. Profil Pengguna

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ✅ | Halaman Profil Sendiri | Ada `profil/index.php`. |
| ❌ | Halaman Profil Orang Lain | `profil/lihat.php` belum ada. |
| ✅ | Daftar Resep Pengguna | Profil dan `resep/myresep.php` menampilkan resep user. |
| ✅ | Statistik Profil | Ada jumlah resep, follower, following. |
| ✅ | Daftar Favorit | Ada `resep/favorite.php`. |
| ❌ | Edit data profil | Belum ada halaman/form update profil. |

---

## 6. Laporan / Customer Support

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ⚠️ | Tabel Laporan CS | Tabel `cs` sudah ada di SQL. |
| ❌ | Laporkan Resep | Belum ada form/API laporan resep. Tombol CS masih `href="#"`. |
| ❌ | Laporkan Pengguna | Belum ada form/API laporan pengguna. |
| ❌ | Status Laporan untuk user | Belum ada halaman daftar/status laporan milik user. |
| ❌ | Proses status laporan | Belum ada UI admin untuk ubah `menunggu/ditolak/selesai`. |

---

## 7. Panel Admin

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ❌ | Dashboard Admin | Folder `admin/` ada, tapi belum ada file dashboard. |
| ❌ | Kelola Pengguna | Belum ada `admin/pengguna.php`. |
| ❌ | Kelola Resep | Belum ada `admin/resep.php`. |
| ❌ | Kelola Laporan CS | Belum ada `admin/laporan.php`. |
| ❌ | Statistik Konten Admin | Belum ada halaman statistik admin. |
| ❌ | Middleware akses admin | Belum terlihat middleware cek role admin untuk folder admin. |

---

## 8. Database

| Status | Item | Bukti / Catatan |
|---|---|---|
| ✅ | `pengguna` | Ada, termasuk `foto_profil`, `bio`, `role`, `status`. |
| ✅ | `recipes` | Ada, termasuk kolom tambahan dari PRD. |
| ✅ | `bahan_resep` | Ada. |
| ✅ | `favorite` | Ada dengan unique user+resep. |
| ✅ | `ratings` | Ada dengan unique user+resep. |
| ✅ | `cs` | Ada. |
| ✅ | `likes` | Ada. |
| ✅ | `komentar` | Ada. |
| ✅ | `following` | Ada. |
| ✅ | `kategori_resep` | Ada. |
| ✅ | `peralatan_resep` | Ada. |
| ⚠️ | Seeder akun | Data contoh masih memakai password plaintext/dummy hash yang tidak konsisten dengan standar security. |

---

## 9. Keamanan

| Status | Aspek | Bukti / Catatan |
|---|---|---|
| ✅ | SQL Injection | Mayoritas query memakai PDO prepared statements. |
| ✅ | XSS | Output banyak memakai helper `e()`/escape. |
| ✅ | CSRF | Ada helper CSRF dan diterapkan di banyak form/API POST. |
| ✅ | Session | Ada `session_regenerate_id()` setelah login. |
| ❌ | Password Hash | Register belum `password_hash()`, login belum `password_verify()`. |
| ✅ | Upload File | Upload resep validasi ekstensi/MIME dan rename file. |
| ❌ | Akses Admin | Belum ada panel dan middleware admin. |

---

## Prioritas Pekerjaan Berikutnya

1. ❌ Perbaiki auth password: `password_hash()` saat register dan `password_verify()` saat login.
2. ❌ Buat `auth/lupa-sandi.php`.
3. ❌ Buat sistem komentar: API, form komentar di detail, daftar komentar.
4. ❌ Buat follow/unfollow dan `profil/lihat.php`.
5. ❌ Buat laporan CS: form lapor resep/pengguna dan status laporan.
6. ❌ Buat panel admin: dashboard, pengguna, resep, laporan.
7. ⚠️ Lengkapi edit profil dan upload foto profil.

---

## Kesimpulan

Pekerjaan yang sudah paling kuat ada di **CRUD resep, detail resep, upload foto, database, pencarian/filter, like, rating, dan favorit**.

Bagian yang paling belum selesai adalah **komentar, follow/profil orang lain, laporan CS, lupa sandi, edit profil, admin panel, dan hardening password**.
