<div align="center">

# 🚗 Vehicle Booking System

<img src="https://readme-typing-svg.demolab.com?font=Baloo+2&size=22&pause=1000&color=16A34A&center=true&vCenter=true&width=600&lines=Kelola+pemesanan+kendaraan+perusahaan;Persetujuan+berjenjang+2+level;Monitoring+BBM+%26+jadwal+service;Dashboard+%26+laporan+real-time" alt="Typing SVG" />

<p>
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/CodeIgniter-4.7-EF4223?style=for-the-badge&logo=codeigniter&logoColor=white" />
  <img src="https://img.shields.io/badge/Angular-21-DD0031?style=for-the-badge&logo=angular&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-8.x-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/Status-Active-16A34A?style=for-the-badge" />
</p>

Aplikasi pemesanan kendaraan perusahaan (tambang nikel) untuk memonitor kendaraan, konsumsi BBM, jadwal service, dan riwayat pemakaian — lengkap dengan alur persetujuan berjenjang (2 level).

</div>

---

## 📋 Daftar Isi

- [Tech Stack](#-tech-stack)
- [Fitur](#-fitur)
- [Struktur Folder](#-struktur-folder)
- [Instalasi & Menjalankan](#-instalasi--menjalankan)
- [Kredensial Default](#-kredensial-default)
- [Alur Bisnis / Panduan Penggunaan](#-alur-bisnis--panduan-penggunaan)
- [Skema Database](#-skema-database)
- [Daftar API Endpoint](#-daftar-api-endpoint)
- [Roadmap](#-roadmap)
- [Troubleshooting Umum](#-troubleshooting-umum)

---

## 🛠 Tech Stack

| Komponen | Teknologi | Versi |
|---|---|---|
| Backend Framework | CodeIgniter | 4.7.4 |
| Bahasa Backend | PHP | 8.3.13 |
| Database | MySQL (via Laragon) | 8.x |
| Frontend Framework | Angular (standalone components + SSR) | 21.x |
| Grafik Dashboard | Chart.js | 4.x |
| Export Laporan | PhpSpreadsheet | ^5.9 |
| Font | Baloo 2 (Google Fonts) | - |

---

## ✨ Fitur

- **Autentikasi** — login berbasis role (`admin` dan `approver`), session disimpan di browser.
- **Ganti Password Mandiri** — setiap user (admin maupun approver) dapat mengubah password akunnya sendiri melalui dropdown pada kartu profil di sidebar, dengan verifikasi password lama wajib sebelum password baru disimpan.
- **Kelola User** — halaman khusus **Admin** untuk mengelola seluruh akun (tambah, edit, hapus), mengatur role (`admin`/`approver`) beserta level approval (1 atau 2) untuk akun bertipe approver, lengkap pencarian berdasarkan nama/username.
- **Manajemen Kendaraan** — CRUD lengkap (tambah, lihat, edit, hapus), pencarian, filter tipe/kepemilikan, **foto kendaraan yang dapat disesuaikan per unit** (isi URL gambar sendiri saat tambah/edit kendaraan, dengan fallback ke foto generic per tipe jika belum diisi), serta **riwayat pemakaian** yang menampilkan jarak tempuh dan BBM terpakai per trip.
- **Manajemen Driver** — CRUD lengkap, pencarian, peringatan otomatis jika masa berlaku SIM sudah/akan habis dalam 30 hari, serta **riwayat pemakaian per driver** (daftar booking yang pernah menggunakan driver tersebut, lengkap kendaraan dan status).
- **Pemesanan Kendaraan**
  - Admin membuat pemesanan (pilih kendaraan, driver, dan 2 approver).
  - **Input nama driver bebas diketik** — jika nama belum terdaftar, sistem otomatis membuat data driver baru; jika sudah ada, otomatis tersambung ke data yang sama (dengan bantuan `<datalist>` sebagai saran).
  - **Validasi bentrok jadwal** — sistem menolak pemesanan baru/pengeditan jika kendaraan yang sama sudah dipesan (dengan status aktif) pada rentang waktu yang tumpang tindih, lengkap dengan pesan yang menyebutkan kode booking penyebab bentrok.
  - **Edit & Hapus pemesanan** — hanya dapat dilakukan selama status masih *"Menunggu Persetujuan L1"* (belum ada approver yang bertindak). Setelah disetujui/ditolak salah satu approver, data terkunci demi menjaga integritas alur persetujuan.
  - **Tandai Selesai + Log BBM & Odometer** — setelah pemesanan disetujui penuh (Level 1 & 2) dan kendaraan dikembalikan, admin menandainya sebagai *Selesai* sekaligus mencatat odometer awal/akhir dan liter BBM terisi (beserta catatan opsional). Odometer awal terisi otomatis dari catatan pemakaian terakhir kendaraan tersebut. Kendaraan otomatis kembali tersedia untuk pemesanan baru.
  - **Detail pemesanan lengkap** — modal detail menampilkan data kendaraan, driver, pemohon, riwayat approval, alasan penolakan (jika ada), serta odometer/BBM/catatan hasil "Tandai Selesai".
  - Pencarian, filter status, sorting, dan pagination pada daftar pemesanan.
- **Kalender Pemakaian Kendaraan** — halaman visual bertipe Gantt chart mingguan yang menampilkan jadwal pemakaian seluruh kendaraan sekaligus (satu baris per kendaraan, bar warna sesuai status: menunggu L1/L2, disetujui, selesai), lengkap navigasi minggu sebelumnya/berikutnya dan tombol kembali ke hari ini — memudahkan melihat sekilas kendaraan mana yang kosong pada tanggal tertentu.
- **Persetujuan Berjenjang (2 Level)** — approver Level 1 menyetujui/menolak terlebih dahulu, baru approver Level 2 bisa bertindak.
- **Dashboard** — ringkasan total kendaraan, total pemesanan, tren 7 hari terakhir, distribusi kepemilikan armada, ketersediaan armada, dan pengingat jadwal service.
- **Export Laporan Excel** — laporan pemesanan periodik (bisa difilter rentang tanggal) diunduh dalam format `.xlsx`, termasuk kolom odometer awal/akhir, jarak tempuh, BBM terisi, dan catatan selesai untuk pemesanan yang sudah rampung.
- **Log Aktivitas** — setiap aksi penting (login, ganti password, buat/ubah/hapus user & kendaraan & driver, buat/ubah/hapus/selesaikan/approve/reject pemesanan) tercatat di tabel `activity_logs`.

---

## 📁 Struktur Folder

```
vehicle-booking-system/
├── vehicle-booking/            # Backend — CodeIgniter 4
│   ├── app/
│   │   ├── Controllers/        # Auth, Users, Vehicles, Drivers, Bookings, Approvals, Reports
│   │   ├── Models/              # UsersModel, VehiclesModel, DriversModel, FuelLogsModel, dll.
│   │   ├── Database/Migrations/
│   │   ├── Filters/             # CorsFilter
│   │   └── Config/Routes.php
│   └── public/
│
└── vehicle-booking-frontend/   # Frontend — Angular
    └── src/app/
        ├── core/                # Auth, Api service, auth guard
        ├── layout/main-layout/  # Sidebar, shell utama, & modal Ganti Password
        └── pages/
            ├── login/
            ├── dashboard/
            ├── vehicles/
            ├── drivers/
            ├── bookings/
            ├── calendar/
            ├── users/
            └── approvals/
```

---

## 🚀 Instalasi & Menjalankan

### Prasyarat

- [Laragon](https://laragon.org/) (atau XAMPP) dengan PHP 8.3+ dan MySQL
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) versi LTS terbaru & npm
- Angular CLI: `npm install -g @angular/cli`

### 1. Backend (CodeIgniter 4)

```powershell
cd vehicle-booking

# Install dependency PHP
composer install

# Buat database bernama vehicle_booking_db di phpMyAdmin/HeidiSQL,
# lalu jalankan migration untuk membuat semua tabel
php spark migrate

# (Jika belum ada user admin/approver, insert manual ke tabel `users`
#  dengan password yang di-hash pakai password_hash() PHP — atau tambahkan
#  lewat halaman "Kelola User" setelah login dengan akun admin pertama)

# Jalankan server backend
php spark serve
```

Backend akan berjalan di `http://localhost:8080`.

### 2. Frontend (Angular)

Buka terminal baru:

```powershell
cd vehicle-booking-frontend

# Install dependency Node
npm install

# Jalankan development server
ng serve
```

Frontend akan berjalan di `http://localhost:4200`.

> ⚠️ **Backend dan frontend harus berjalan bersamaan** di dua terminal terpisah — frontend memanggil API ke `http://localhost:8080/api/...`. Jangan gunakan terminal yang sama untuk menjalankan command lain selagi server aktif.

---

## 🔑 Kredensial Default

| Username | Password | Role | Level Approval |
|---|---|---|---|
| `admin` | `admin123` | Admin | - |
| `spv_tambang1` | *(sesuaikan dengan yang di-set di database)* | Approver | Level 1 |
| `manager_hq` | *(sesuaikan dengan yang di-set di database)* | Approver | Level 2 |

> Password approver di atas dibuat manual saat seeding awal database dan di-hash dengan `password_hash()`. Setelah login pertama kali, setiap user (termasuk approver) disarankan segera mengganti password melalui menu **Ganti Password** pada dropdown kartu profil di sidebar.
>
> Jika lupa password dan tidak bisa login sama sekali, reset via query:
> ```sql
> UPDATE users SET password = '<hasil password_hash()>' WHERE username = 'spv_tambang1';
> ```
> atau generate hash baru lewat terminal:
> ```powershell
> php -r "echo password_hash('password_baru', PASSWORD_DEFAULT);"
> ```

---

## 📖 Alur Bisnis / Panduan Penggunaan

Sistem punya 2 peran: **Admin** (mengelola master data & pemesanan) dan **Approver** (menyetujui, terbagi 2 level).

### Alur satu siklus pemesanan kendaraan

```
1. Admin login → menu "Pemesanan" → klik "Buat Pemesanan"
   Pilih kendaraan, ketik nama driver (opsional — otomatis dibuat jika belum ada),
   pilih approver Level 1, approver Level 2, dan tanggal.

   Sistem otomatis memvalidasi:
   - Tanggal selesai harus setelah tanggal mulai
   - Kendaraan tidak sedang dipesan orang lain pada rentang waktu yang sama

   → status booking: MENUNGGU L1 (pending)

   Selama masih status ini, admin masih bisa mengedit atau menghapus pemesanan
   melalui menu "..." pada baris data.

2. Approver Level 1 login → menu "Approval"
   Melihat daftar pemesanan yang menunggu persetujuannya → klik "Setujui" atau "Tolak"
   → jika disetujui, status berubah: MENUNGGU L2 (approved_l1)
   → jika ditolak, status: DITOLAK (alur berhenti, alasan tercatat)

   Setelah tahap ini, pemesanan TIDAK BISA lagi diedit/dihapus oleh admin.

3. Approver Level 2 login → menu "Approval"
   Pemesanan baru muncul di daftarnya SETELAH Level 1 menyetujui → klik "Setujui" atau "Tolak"
   → jika disetujui, status akhir: DISETUJUI (approved_l2)
   → jika ditolak, status: DITOLAK

4. Setelah kendaraan selesai digunakan dan dikembalikan, admin membuka menu "..."
   pada pemesanan berstatus DISETUJUI dan memilih "Tandai Selesai"
   → muncul form kecil untuk mencatat odometer awal (terisi otomatis dari
     pemakaian terakhir kendaraan), odometer akhir, liter BBM terisi, dan catatan
   → status berubah: SELESAI (completed)
   → kendaraan otomatis kembali tersedia untuk pemesanan baru
   → data jarak tempuh, BBM, dan catatan tercatat dan dapat dilihat di detail
     pemesanan, riwayat pemakaian kendaraan, maupun riwayat pemakaian driver
```

### Peta Halaman

| Halaman | Fungsi | Akses |
|---|---|---|
| **Dashboard** | Ringkasan statistik & grafik operasional | Semua role |
| **Kendaraan** | Daftar kendaraan, detail, foto per unit, riwayat pemakaian (jarak tempuh & BBM) | Lihat: semua role. Tambah/Edit/Hapus: **Admin** |
| **Driver** | Daftar driver, riwayat pemakaian, peringatan masa berlaku SIM | Lihat: semua role. Tambah/Edit/Hapus: **Admin** |
| **Pemesanan** | Riwayat semua pemesanan, edit/hapus/tandai selesai, export Excel | Lihat: semua role. Kelola: **Admin** |
| **Kalender** | Visual jadwal pemakaian seluruh kendaraan per minggu (Gantt chart) | Semua role |
| **Approval** | Daftar pemesanan yang perlu disetujui user yang login | Hanya tampil untuk role **Approver** |
| **Kelola User** | Tambah/edit/hapus akun, atur role & level approval | Hanya **Admin** |
| **Ganti Password** | Ubah password akun sendiri (via dropdown kartu profil di sidebar) | Semua role |

### Contoh Uji Coba Alur Lengkap

1. Login `admin` / `admin123` → buka **Pemesanan** → buat 1 pemesanan baru, ketik nama driver bebas, pilih approver Level 1 = `spv_tambang1`, Level 2 = `manager_hq`.
2. (Opsional) Selama status masih "Menunggu L1", coba **Edit** data lewat menu "..." untuk mengubah tanggal/tujuan.
3. Coba buat pemesanan lain dengan kendaraan & rentang tanggal yang sama — sistem akan menolak dengan pesan bentrok jadwal.
4. Logout → login `spv_tambang1` → buka **Approval** → klik **Setujui** pada pemesanan tadi.
5. Logout → login `manager_hq` → buka **Approval** → pemesanan kini muncul di daftarnya → klik **Setujui**.
6. Login kembali sebagai `admin` → cek **Pemesanan**, status sudah berubah menjadi **Disetujui**. Buka menu "..." → klik **Tandai Selesai**, isi odometer akhir, liter BBM, dan catatan.
7. Buka halaman **Kendaraan**, klik **Status Kendaraan** pada kendaraan yang baru dipakai — jarak tempuh dan BBM yang dicatat akan tampil di riwayatnya.
8. Buka halaman **Driver**, klik **Riwayat** pada driver yang tadi dipakai — pemesanan yang baru saja diselesaikan akan tampil di riwayatnya.
9. Buka halaman **Kalender**, lihat bar pemesanan tadi muncul pada baris kendaraan yang bersangkutan sesuai rentang tanggalnya.
10. Sebagai `admin`, buka halaman **Kelola User** → tambah 1 akun approver baru (isi nama, username, password, pilih level) → akun tersebut langsung bisa dipakai login.
11. Klik kartu profil di pojok bawah sidebar → pilih **Ganti Password** → masukkan password lama dan password baru untuk mengubah kredensial akun yang sedang login.

### Menambahkan Foto Kendaraan

Di halaman **Kendaraan**, saat menambah atau mengedit data, isi field **URL Foto Kendaraan** dengan link gambar yang sudah ter-hosting online (misalnya dari Unsplash atau Pinterest — klik kanan gambar → *Copy image address*, pastikan link mengarah langsung ke file gambar, bukan ke halaman web). Jika dikosongkan, sistem akan menampilkan foto generic berdasarkan tipe kendaraan (angkutan orang/barang) sebagai fallback. Foto ini akan otomatis ikut tampil di kartu kendaraan maupun tabel dan detail pemesanan.

### Mengelola User & Approver

Hanya **Admin** yang dapat mengakses halaman **Kelola User**. Saat menambah user baru:
- **Role `admin`** — tidak memerlukan level approval.
- **Role `approver`** — wajib memilih **Level 1** atau **Level 2**, menentukan approver tersebut muncul di tahap persetujuan pertama atau kedua pada alur pemesanan.

Saat mengedit user, field password dapat dikosongkan jika tidak ingin mengubah password akun tersebut (password lama tetap dipertahankan).

### Export Laporan

Di halaman **Pemesanan**, isi rentang tanggal (opsional) lalu klik **Export Excel** untuk mengunduh laporan periodik dalam format `.xlsx`, lengkap dengan kolom odometer, jarak tempuh, BBM, dan catatan selesai untuk pemesanan yang berstatus Selesai.

---

## 🗄 Skema Database

Tabel utama:

| Tabel | Keterangan |
|---|---|
| `users` | Data admin & approver (kolom `role`, `level` untuk approver) |
| `vehicles` | Data kendaraan (`type`: angkutan_orang/angkutan_barang, `ownership`: milik_perusahaan/sewa, `image_url`: URL foto kendaraan, opsional) |
| `drivers` | Data driver/pengemudi, termasuk `license_expiry` untuk masa berlaku SIM (dapat ditambahkan otomatis saat membuat pemesanan) |
| `vehicle_bookings` | Data pemesanan kendaraan (`status`: pending / approved_l1 / approved_l2 / completed / rejected) |
| `booking_approvals` | Baris persetujuan per level per pemesanan (status, notes, approved_at) |
| `fuel_logs` | Catatan odometer awal/akhir, BBM terisi, dan catatan per pemesanan yang telah selesai |
| `vehicle_service_schedule` | Jadwal service kendaraan |
| `activity_logs` | Log aktivitas sistem |

> Physical Data Model (ERD) dan Activity Diagram dilampirkan terpisah sebagai bagian dari dokumentasi submission.

---

## 🔌 Daftar API Endpoint

Base URL: `http://localhost:8080/api`

| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/login` | Login user |
| POST | `/logout` | Logout user |
| POST | `/auth/change-password` | Ubah password akun sendiri. Body: `user_id`, `old_password`, `new_password` (verifikasi password lama wajib) |
| GET | `/users?role=approver` | Daftar user approver (dipakai saat memilih approver Level 1/2 pada form pemesanan) |
| GET | `/users` | Daftar seluruh user (untuk halaman Kelola User) |
| POST | `/users` | Tambah user baru — Body: `name`, `username`, `password`, `role`, `level` (khusus role `approver`) |
| PUT | `/users/{id}` | Update user — `password` opsional, dikosongkan jika tidak ingin diubah |
| DELETE | `/users/{id}` | Hapus user |
| GET | `/vehicles` | Daftar kendaraan |
| POST | `/vehicles` | Tambah kendaraan (dapat menyertakan `image_url`) |
| PUT | `/vehicles/{id}` | Update kendaraan (dapat menyertakan `image_url`) |
| DELETE | `/vehicles/{id}` | Hapus kendaraan |
| GET | `/vehicles/{id}/last-odometer` | Odometer akhir terakhir dari kendaraan (untuk default odometer awal pemakaian berikutnya) |
| GET | `/drivers` | Daftar driver |
| POST | `/drivers` | Tambah driver (juga dipanggil otomatis saat nama driver baru diketik di form pemesanan) |
| PUT | `/drivers/{id}` | Update driver |
| DELETE | `/drivers/{id}` | Hapus driver |
| GET | `/bookings` | Daftar pemesanan (termasuk `rejection_reason`, `fuel_log`, dan `vehicle_image_url` jika tersedia) |
| GET | `/bookings/{id}` | Detail pemesanan lengkap (kendaraan, driver, pemohon, riwayat approval, `fuel_log`, `rejection_reason`) |
| POST | `/bookings` | Buat pemesanan baru — memvalidasi rentang tanggal & bentrok jadwal (respons `409` jika bentrok) |
| PUT | `/bookings/{id}` | Update pemesanan — **hanya jika status masih pending**, tetap divalidasi bentrok jadwal |
| DELETE | `/bookings/{id}` | Hapus pemesanan — **hanya jika status masih pending** |
| POST | `/bookings/{id}/complete` | Menandai pemesanan selesai — **hanya jika status sudah approved_l2**. Body opsional: `odometer_start`, `odometer_end`, `fuel_liters`, `notes` |
| GET | `/approvals?approver_id={id}` | Seluruh riwayat approval milik seorang approver (pending/approved/rejected) |
| POST | `/approvals/{id}/approve` | Menyetujui pemesanan |
| POST | `/approvals/{id}/reject` | Menolak pemesanan (menyertakan `notes` alasan) |
| GET | `/reports/bookings/export` | Export laporan pemesanan ke Excel (parameter opsional `start` & `end`), termasuk kolom odometer, jarak tempuh, BBM, dan catatan selesai |

---

## 🗺 Roadmap

Fitur yang direncanakan untuk pengembangan selanjutnya:

- [x] Validasi bentrok jadwal pemesanan (double booking)
- [x] Manajemen Driver (CRUD + peringatan masa berlaku SIM)
- [x] Status "Selesai" untuk menandai kendaraan telah dikembalikan
- [x] Riwayat pemakaian per driver
- [x] Log BBM & odometer per pemakaian kendaraan
- [x] Foto kendaraan custom per unit (bukan hanya generic per tipe)
- [x] Kalender visual pemakaian kendaraan (Gantt chart mingguan)
- [x] Halaman kelola User/Approver dari UI
- [x] Ganti password mandiri untuk setiap user
- [ ] Riwayat service kendaraan (bukan hanya jadwal berikutnya)
- [ ] Riwayat aktivitas (activity log) yang dapat dilihat di UI
- [ ] Notifikasi in-app
- [ ] Optimasi tampilan mobile / PWA

---

## 🩹 Troubleshooting Umum

**CORS error di banyak halaman sekaligus**
Sering kali bukan masalah konfigurasi CORS itu sendiri, melainkan *fatal error* di sisi PHP yang membuat response gagal terkirim sebelum sempat melewati filter CORS. Periksa `writable/logs/log-<tanggal>.log` untuk pesan error sebenarnya sebelum mengubah konfigurasi CORS.

**`Namespace declaration statement has to be the very first statement`**
Terjadi ketika ada teks tidak sengaja tertinggal di atas baris `<?php`/`namespace` pada file PHP (controller maupun migration) — biasanya sisa perintah terminal yang ikut ter-paste saat menimpa file lewat PowerShell heredoc. Perbaiki dengan menimpa ulang file secara utuh dan pastikan baris pertama persis `<?php`.

**`Table 'namadb.nama_tabel' doesn't exist`**
Migration untuk tabel tersebut belum dijalankan atau gagal karena error sintaks (lihat poin di atas). Jalankan ulang `php spark migrate` setelah memastikan file migration bersih.

**`Class "App\Models\NamaModel" not found`**
File model belum dibuat, namanya tidak sama persis dengan class-nya, atau berada di folder yang salah. CodeIgniter 4 memakai autoload PSR-4 — nama file **harus** sama persis dengan nama class (mis. `FuelLogsModel.php` berisi `class FuelLogsModel extends Model`) dan berada di `app/Models/`.

**Endpoint mengembalikan data tidak lengkap (field kosong/`null`) padahal tabel lain punya datanya**
Periksa apakah method controller terkait melakukan `JOIN` ke tabel relasi (`vehicles`, `drivers`, `users`, `fuel_logs`, dsb) menggunakan query builder, atau hanya memanggil `$model->find($id)` yang cuma mengambil baris mentah dari satu tabel. Method `index()` dan `show()` pada controller yang sama seringkali perlu pola query yang identik agar hasilnya konsisten.

**Data berhasil dikirim dari frontend tapi tidak tersimpan di database (tanpa error)**
Periksa properti `$allowedFields` pada Model terkait. CodeIgniter secara diam-diam akan membuang kolom yang tidak terdaftar di `$allowedFields` saat `insert()`/`update()` dipanggil — tidak ada error yang muncul, datanya hanya tidak masuk.

**Frontend menampilkan "Cannot GET"**
Biasanya karena SSR mencoba mengakses `localStorage` di sisi server. Pastikan pengecekan `isPlatformBrowser` digunakan sebelum memanggil Web API browser di service Angular (`Auth`).

**Grafik dashboard kosong**
Pastikan elemen `<canvas>` sudah ter-render di DOM sebelum Chart.js dipanggil (gunakan `setTimeout` singkat setelah data selesai dimuat).

**`NG8004: No pipe found with name 'slice'` / `'date'` / pipe bawaan lainnya**
Karena komponen bertipe *standalone*, pipe bawaan Angular seperti `SlicePipe` atau `DatePipe` harus diimpor eksplisit dari `@angular/common` dan didaftarkan di array `imports` komponen terkait — tidak otomatis tersedia seperti pada NgModule biasa.

**Dropdown menu "..." pada baris/kartu terakhir terpotong**
Pastikan `overflow: hidden` tidak diterapkan langsung pada container yang membungkus dropdown; gunakan `border-radius` pada elemen anak (mis. baris pertama/terakhir tabel) alih-alih pada container luar. Jika baris berada di posisi paling bawah, arahkan dropdown terbuka ke atas (`bottom` bukan `top`) agar tidak keluar dari area yang terlihat.

**Booking gagal disimpan dengan pesan bentrok jadwal**
Ini bukan bug — validasi memang menolak pemesanan kendaraan yang sama pada rentang waktu yang tumpang tindih dengan pemesanan aktif lain. Pilih kendaraan lain, ubah rentang tanggal, atau selesaikan (Tandai Selesai) pemesanan lama terlebih dahulu jika kendaraan sudah tidak terpakai.

**"Tandai Selesai" gagal dengan pesan "Hanya pemesanan yang sudah disetujui penuh..."**
Booking yang bersangkutan belum berstatus `approved_l2` (belum disetujui kedua level approver). Cek status booking terlebih dahulu di tabel Pemesanan sebelum mencoba menandainya selesai.

**URL foto kendaraan tidak muncul / broken image**
Pastikan URL yang diisi mengarah **langsung ke file gambar**, bukan ke halaman web. Untuk Pinterest, gunakan *Copy image address* pada gambar yang sudah diperbesar (biasanya berformat `https://i.pinimg.com/...`), bukan link pin (`pinterest.com/pin/...`). Tes dengan membuka URL tersebut di tab baru — jika yang muncul hanya gambar (bukan tampilan situs penuh), URL tersebut valid digunakan.

**Ganti password gagal dengan pesan "Password lama tidak sesuai"**
Pastikan password lama yang diinput benar-benar sama dengan password yang sedang aktif saat ini (bukan password default jika sudah pernah diganti sebelumnya). Password baru minimal 6 karakter.

**Server backend mati sendiri**
`php spark serve` berjalan di foreground — jangan tutup terminal tempat command ini dijalankan, dan jangan pakai terminal yang sama untuk command lain.

---

<div align="center">

## 👤 Kontributor

Proyek ini dikembangkan secara mandiri sebagai sarana pembelajaran dan eksplorasi dalam membangun sistem web berbasis Fullstack (Angular & CodeIgniter).

<img src="https://readme-typing-svg.demolab.com?font=Baloo+2&size=14&pause=1500&color=64748B&center=true&vCenter=true&width=400&lines=Made+with+%E2%98%95+and+a+lot+of+debugging" alt="Footer" />

</div>
