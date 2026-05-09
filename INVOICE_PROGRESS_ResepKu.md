# Invoice Progress Fitur ResepKu

**Berdasarkan PRD:** `PRD_ResepKu.md`  
**Tanggal pengecekan:** 9 Mei 2026  
**Metode cek:** mencocokkan PRD dengan file proyek, halaman PHP, API, dan struktur database.

---

## Ringkasan Invoice

| Keterangan | Jumlah |
|---|---:|
| Total item dicek | 72 |
| ✅ Selesai | 62 |
| ⚠️ Sebagian | 9 |
| ❌ Belum selesai | 1 |

> Catatan: status ini berdasarkan pengecekan kode/static review, belum termasuk testing manual semua flow di browser.

---

## 1. Autentikasi & Akun

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ✅ | Register | Ada `auth/register.php` dengan validasi form dan insert user. |
| ⚠️ | Register aman dengan hash password | Form ada, tapi password masih disimpan langsung, belum `password_hash()`. |
| ✅ | Login | Ada `auth/login.php`, session user, validasi status akun. |
| ⚠️ | Login aman dengan `password_verify()` | Login ada, tapi masih membandingkan password plaintext. |
| ✅ | Lupa Kata Sandi | Ada `auth/lupa-sandi.php`, `auth/reset-sandi.php`, token reset sekali pakai, dan pengiriman email via Gmail SMTP vanilla PHP. |
| ✅ | Logout | Ada `auth/logout.php`. |
| ✅ | Edit Profil | Ada `profil/edit.php` untuk edit nama, bio, dan ganti password dengan accordion. |
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
| ✅ | Komentar Resep | Ada `api/komentar.php`, form komentar di detail, dan daftar komentar. |
| ✅ | Rating Bintang | Ada `api/rating.php`, fungsi upsert rating, tombol rate di detail. |
| ✅ | Favorit Resep | Ada `api/favorite.php`, halaman `resep/favorite.php`, toggle dan daftar favorit. |
| ✅ | Share Resep | Tombol share di detail menyalin link ke clipboard. |
| ✅ | Following / Follower | Ada `api/follow.php`, toggle follow/unfollow, dan tombol follow di public profile. |
| ✅ | Lihat Profil Orang Lain | Public profile bisa dibuka via `profil/?id=...` dan menerima aksi follow. |
| ⚠️ | Feed Resep dari Following | Home sudah memprioritaskan resep akun yang di-follow, tapi belum feed khusus yang hanya berisi following. |

---

## 4. Pencarian & Filter

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ✅ | Cari Resep | Ada `cari.php`, query `q` mencari nama resep. |
| ✅ | Filter Kategori | Ada filter kategori di home/search dan query repository. |
| ✅ | Filter Kesulitan | Ada filter difficulty di home/search. |
| ✅ | Sort Terpopuler | Ada sort `popular` berdasarkan like dan rating. |
| ✅ | Sort Terbaru | Default sort `newest` berdasarkan tanggal posting. |

---

## 5. Profil Pengguna

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ✅ | Halaman Profil Sendiri | Ada `profil/index.php`. |
| ✅ | Halaman Profil Orang Lain | Public profile tersedia via `profil/?id=...`. |
| ✅ | Daftar Resep Pengguna | Profil dan `resep/myresep.php` menampilkan resep user. |
| ✅ | Statistik Profil | Ada jumlah resep, follower, following. |
| ✅ | Daftar Favorit | Ada `resep/favorite.php`. |
| ✅ | Edit data profil | Ada `profil/edit.php`; tombol edit di profile menuju route khusus edit. |

---

## 6. Laporan / Customer Support

> Catatan: implementasi yang ada sekarang masih berupa reporting + inbox/admin handling. Scope CS ke depan diarahkan ke chatbot AI untuk tanya resep.

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ⚠️ | Tabel Laporan CS | Tabel `cs` dipakai untuk reporting, tapi belum jadi modul CS/chatbot mandiri. |
| ⚠️ | Laporkan Resep | Ada modal laporan di detail resep dan `api/report.php`, masih bagian reporting admin. |
| ⚠️ | Laporkan Pengguna | Ada modal laporan di profil publik dan `api/report.php`, masih bagian reporting admin. |
| ⚠️ | Status Laporan untuk user | Ada halaman `profil/laporan.php`, tapi masih inbox reporting, bukan CS AI. |
| ⚠️ | Proses status laporan | Ada `admin/laporan.php` untuk ubah status, tapi ini belum scope CS final. |
| ❌ | Implementasi CS AI Chatbot | Belum ada chatbot AI untuk bantu tanya resep, rekomendasi, dan navigasi konten. |

---

## 7. Panel Admin

| Status | Fitur | Bukti / Catatan |
|---|---|---|
| ✅ | Dashboard Admin | Ada `admin/index.php` berisi statistik dan ringkasan data terbaru. |
| ✅ | Kelola Pengguna | Ada `admin/pengguna.php` dengan filter, toggle status, dan hapus pengguna non-admin. |
| ✅ | Kelola Resep | Ada `admin/resep.php` dengan filter dan hapus resep. |
| ✅ | Kelola Laporan CS | Ada `admin/laporan.php` dengan filter dan aksi ubah status. |
| ✅ | Statistik Konten Admin | Dashboard menampilkan total pengguna, resep, komentar, likes, rating, dan laporan menunggu. |
| ✅ | Middleware akses admin | Ada `requireAdmin()` di `config/helpers.php`. |

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
| ✅ | `password_resets` | Ada di `resepku.sql` dan tersedia skrip `sql/create_password_resets.sql` untuk setup tabel reset password. |
| ⚠️ | Seeder akun | Data contoh di `resepku.sql` masih memakai password plaintext/dummy hash. |

---

## 9. Keamanan

| Status | Aspek | Bukti / Catatan |
|---|---|---|
| ✅ | SQL Injection | Mayoritas query memakai PDO prepared statements. |
| ✅ | XSS | Output banyak memakai helper `e()`/escape. |
| ✅ | CSRF | Ada helper CSRF dan diterapkan di banyak form/API POST. |
| ✅ | Session | Ada `session_regenerate_id()` setelah login. |
| ⚠️ | Password Hash | Register dan login masih simpan/bandingkan password plaintext, belum `password_hash()` / `password_verify()`. |
| ✅ | Upload File | Upload resep validasi ekstensi/MIME dan rename file. |
| ✅ | Akses Admin | Panel admin dan guard `requireAdmin()` sudah ada. |

---

## Prioritas Pekerjaan Berikutnya

1. ⚠️ Rancang CS chatbot AI untuk bantu tanya resep dan navigasi konten.
2. ⚠️ Audit auth password: `password_hash()` saat register dan `password_verify()` saat login.
3. ⚠️ Lengkapi feed resep dari following menjadi feed khusus, bukan hanya prioritas di home.
4. ⚠️ Rapikan seed akun demo supaya format password konsisten.

---

## Kesimpulan

Pekerjaan yang sudah paling kuat ada di **CRUD resep, detail resep, upload foto, database, pencarian/filter, like, rating, favorit, edit profil, reporting admin, dan panel admin**.

Bagian yang masih tertinggal adalah **hardening password, feed following khusus, seed data demo, dan CS AI chatbot untuk tanya resep**.
