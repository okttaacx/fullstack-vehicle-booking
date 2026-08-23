<div align="center">

# 🚗 Vehicle Booking System

<img src="https://readme-typing-svg.demolab.com?font=Baloo+2&size=22&pause=1000&color=16A34A&center=true&vCenter=true&width=600&lines=Kelola+pemesanan+kendaraan+perusahaan;Persetujuan+berjenjang+2+level;Monitoring+BBM+%26+jadwal+service;Dashboard+%26+laporan+real-time" alt="Typing SVG" />

<p>
  <a href="https://github.com/okttaacx/fullstack-vehicle-booking/actions/workflows/backend-tests.yml"><img src="https://github.com/okttaacx/fullstack-vehicle-booking/actions/workflows/backend-tests.yml/badge.svg" /></a>
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/CodeIgniter-4.7-EF4223?style=for-the-badge&logo=codeigniter&logoColor=white" />
  <img src="https://img.shields.io/badge/Angular-21-DD0031?style=for-the-badge&logo=angular&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-8.x-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/Tests-25%20passing-16A34A?style=for-the-badge&logo=php&logoColor=white" />
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
- [Testing & CI](#-testing--ci)
- [Roadmap](#-roadmap)
- [Troubleshooting Umum](#-troubleshooting-umum)

---

## 🛠 Tech Stack

| Komponen | Teknologi | Versi |
|---|---|---|
| Backend Framework | CodeIgniter | 4.7.4 |
| Bahasa Backend | PHP | 8.3.13 |
| Database | MySQL (via Laragon) | 8.x |
| Testing | PHPUnit (via `codeigniter4/framework` test suite) | ^10.5 |
| CI/CD | GitHub Actions | - |
| Frontend Framework | Angular (standalone components + SSR) | 21.x |
| Grafik Dashboard | Chart.js | 4.x |
| Export Laporan | PhpSpreadsheet | ^5.9 |
| Font | Baloo 2 (Google Fonts) | - |

---

## ✨ Fitur

- **Autentikasi** — login berbasis role (`admin` dan `approver`), session disimpan di browser.
- **Notifikasi In-App** — lonceng notifikasi *floating* di sidebar dengan badge jumlah item belum ditinjau, auto-refresh setiap 30 detik. **Approver** mendapat notifikasi saat ada booking yang benar-benar sudah waktunya mereka setujui (memperhitungkan giliran level 1/2, bukan sekadar status `pending`); **Admin** mendapat notifikasi saat ada booking yang siap ditandai selesai (`approved_l2`) atau baru ditolak dalam 3 hari terakhir.
- **Ganti Password Mandiri** — setiap user (admin maupun approver) dapat mengubah password akunnya sendiri melalui dropdown pada kartu profil di sidebar, dengan verifikasi password lama wajib sebelum password baru disimpan.
- **Kelola User** — halaman khusus **Admin** untuk mengelola seluruh akun (tambah, edit, hapus), mengatur role (`admin`/`approver`) beserta level approval (1 atau 2) untuk akun bertipe approver, lengkap pencarian berdasarkan nama/username.
- **Riwayat Aktivitas (Activity Log)** — halaman khusus **Admin** yang menampilkan seluruh jejak aktivitas penting di sistem (login, ganti password, buat/ubah/hapus data, approve/reject, tandai selesai, dsb) lengkap dengan pelaku, waktu, alamat IP, serta pencarian bebas dan filter berdasarkan jenis aksi maupun rentang tanggal.
- **Manajemen Kendaraan** — CRUD lengkap (tambah, lihat, edit, hapus), pencarian, filter tipe/kepemilikan, **foto kendaraan yang dapat disesuaikan per unit** (isi URL gambar sendiri saat tambah/edit kendaraan, dengan fallback ke foto generic per tipe jika belum diisi), **riwayat pemakaian** yang menampilkan jarak tempuh dan BBM terpakai per trip, serta **riwayat service** (catat tanggal, keterangan, dan status setiap kali kendaraan diservice — bukan hanya jadwal berikutnya).
- **Manajemen Driver** — CRUD lengkap, pencarian, peringatan otomatis jika masa berlaku SIM sudah/akan habis dalam 30 hari, serta **riwayat pemakaian per driver** (daftar booking yang pernah menggunakan driver tersebut, lengkap kendaraan dan status).
- **Pemesanan Kendaraan**
  - Admin membuat pemesanan (pilih kendaraan, driver, dan 2 approver).
  - **Input nama driver bebas diketik** — jika nama belum terdaftar, sistem otomatis membuat data driver baru; jika sudah ada, otomatis tersambung ke data yang sama (dengan bantuan `<datalist>` sebagai saran).
  - **Validasi bentrok jadwal** — sistem menolak pemesanan baru/pengeditan jika kendaraan yang sama sudah dipesan (dengan status aktif) pada rentang waktu yang tumpang tindih, lengkap dengan pesan yang menyebutkan kode booking penyebab bentrok. **Diverifikasi otomatis lewat CI** (lihat [Testing & CI](#-testing--ci)).
  - **Edit & Hapus pemesanan** — hanya dapat dilakukan selama status masih *"Menunggu Persetujuan L1"* (belum ada approver yang bertindak). Setelah disetujui/ditolak salah satu approver, data terkunci demi menjaga integritas alur persetujuan.
  - **Tandai Selesai + Log BBM & Odometer** — setelah pemesanan disetujui penuh (Level 1 & 2) dan kendaraan dikembalikan, admin menandainya sebagai *Selesai* sekaligus mencatat odometer awal/akhir dan liter BBM terisi (beserta catatan opsional). Odometer awal terisi otomatis dari catatan pemakaian terakhir kendaraan tersebut. Kendaraan otomatis kembali tersedia untuk pemesanan baru.
  - **Detail pemesanan lengkap** — modal detail menampilkan data kendaraan, driver, pemohon, riwayat approval, alasan penolakan (jika ada), serta odometer/BBM/catatan hasil "Tandai Selesai".
  - Pencarian, filter status, sorting, dan pagination pada daftar pemesanan.
- **Kalender Pemakaian Kendaraan** — halaman visual bertipe Gantt chart mingguan yang menampilkan jadwal pemakaian seluruh kendaraan sekaligus (satu baris per kendaraan, bar warna sesuai status: menunggu L1/L2, disetujui, selesai), lengkap navigasi minggu sebelumnya/berikutnya dan tombol kembali ke hari ini — memudahkan melihat sekilas kendaraan mana yang kosong pada tanggal tertentu.
- **Persetujuan Berjenjang (2 Level)** — approver Level 1 menyetujui/menolak terlebih dahulu, baru approver Level 2 bisa bertindak. **Diverifikasi otomatis lewat CI** (lihat [Testing & CI](#-testing--ci)).
- **Dashboard** — ringkasan total kendaraan, total pemesanan, tren 7 hari terakhir, distribusi kepemilikan armada, ketersediaan armada, dan pengingat jadwal service (dibaca langsung dari riwayat service, bukan dari kolom tunggal yang mudah kadaluarsa).
- **Export Laporan Excel** — laporan pemesanan periodik (bisa difilter rentang tanggal) diunduh dalam format `.xlsx`, termasuk kolom odometer awal/akhir, jarak tempuh, BBM terisi, dan catatan selesai untuk pemesanan yang sudah rampung.
- **Log Aktivitas** — setiap aksi penting (login, ganti password, buat/ubah/hapus user & kendaraan & driver & catatan service, buat/ubah/hapus/selesaikan/approve/reject pemesanan) tercatat otomatis di tabel `activity_logs` dan dapat ditinjau kapan saja melalui halaman Riwayat Aktivitas.

---

## 📁 Struktur Folder

```
vehicle-booking-system/
├── .github/
│   └── workflows/
│       └── backend-tests.yml   # CI — jalankan PHPUnit otomatis di setiap push/PR
│
├── vehicle-booking/            # Backend — CodeIgniter 4
│   ├── app/
│   │   ├── Controllers/        # Auth, Users, Vehicles, Drivers, VehicleServices,
│   │   │                       # Bookings, Approvals, Reports, ActivityLogs
│   │   ├── Models/              # UsersModel, VehiclesModel, DriversModel, FuelLogsModel,
│   │   │                       # VehicleServiceScheduleModel, ActivityLogsModel, dll.
│   │   ├── Libraries/            # ActivityLogger
│   │   ├── Database/Migrations/
│   │   ├── Filters/             # CorsFilter
│   │   └── Config/Routes.php
│   ├── tests/
│   │   ├── _support/            # Trait/helper bersama antar test (mis. CreatesBookingFixtures)
│   │   └── Feature/             # Feature test lewat endpoint HTTP asli
│   │       ├── BookingConflictTest.php
│   │       └── ApprovalFlowTest.php
│   └── public/
│
└── vehicle-booking-frontend/   # Frontend — Angular
    └── src/app/
        ├── core/                # Auth, Api service, auth guard
        ├── layout/main-layout/  # Sidebar, notifikasi floating, modal Ganti Password
        └── pages/
            ├── login/
            ├── dashboard/
            ├── vehicles/
            ├── drivers/
            ├── bookings/
            ├── calendar/
            ├── users/
            ├── activity-logs/
            └── approvals/
```

---

## 🚀 Instalasi & Menjalankan

### Prasyarat

- [Laragon](https://laragon.org/) (atau XAMPP) dengan PHP 8.3+ dan MySQL
- Ekstensi PHP `sqlite3` aktif (dipakai khusus saat menjalankan test — lihat [Testing & CI](#-testing--ci))
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

2. Approver Level 1 login → notifikasi lonceng menyala jika ada booking baru yang
   perlu ditinjau → menu "Approval" → klik "Setujui" atau "Tolak"
   → jika disetujui, status berubah: MENUNGGU L2 (approved_l1)
   → jika ditolak, status: DITOLAK (alur berhenti, alasan tercatat)

   Setelah tahap ini, pemesanan TIDAK BISA lagi diedit/dihapus oleh admin.

3. Approver Level 2 login → notifikasi lonceng menyala jika ada booking yang
   sudah disetujui Level 1 dan menunggu tindakannya → menu "Approval" →
   klik "Setujui" atau "Tolak"
   → jika disetujui, status akhir: DISETUJUI (approved_l2)
   → jika ditolak, status: DITOLAK

4. Setelah kendaraan selesai digunakan dan dikembalikan, admin (yang notifikasi
   lonceng-nya menyala menandakan ada booking siap diselesaikan) membuka menu "..."
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
| **Kendaraan** | Daftar kendaraan, detail, foto per unit, riwayat pemakaian & riwayat service | Lihat: semua role. Tambah/Edit/Hapus: **Admin** |
| **Driver** | Daftar driver, riwayat pemakaian, peringatan masa berlaku SIM | Lihat: semua role. Tambah/Edit/Hapus: **Admin** |
| **Pemesanan** | Riwayat semua pemesanan, edit/hapus/tandai selesai, export Excel | Lihat: semua role. Kelola: **Admin** |
| **Kalender** | Visual jadwal pemakaian seluruh kendaraan per minggu (Gantt chart) | Semua role |
| **Approval** | Daftar pemesanan yang perlu disetujui user yang login | Hanya tampil untuk role **Approver** |
| **Kelola User** | Tambah/edit/hapus akun, atur role & level approval | Hanya **Admin** |
| **Riwayat Aktivitas** | Jejak seluruh aktivitas penting sistem, pencarian & filter aksi/tanggal | Hanya **Admin** |
| **Notifikasi** | Lonceng floating di sidebar — booking perlu ditinjau (approver) / siap diselesaikan atau baru ditolak (admin) | Admin & Approver |
| **Ganti Password** | Ubah password akun sendiri (via dropdown kartu profil di sidebar) | Semua role |

### Contoh Uji Coba Alur Lengkap

1. Login `admin` / `admin123` → buka **Pemesanan** → buat 1 pemesanan baru, ketik nama driver bebas, pilih approver Level 1 = `spv_tambang1`, Level 2 = `manager_hq`.
2. (Opsional) Selama status masih "Menunggu L1", coba **Edit** data lewat menu "..." untuk mengubah tanggal/tujuan.
3. Coba buat pemesanan lain dengan kendaraan & rentang tanggal yang sama — sistem akan menolak dengan pesan bentrok jadwal.
4. Logout → login `spv_tambang1` → perhatikan badge notifikasi di sidebar menyala → buka **Approval** → klik **Setujui** pada pemesanan tadi.
5. Logout → login `manager_hq` → badge notifikasi menyala karena booking sudah lolos Level 1 → buka **Approval** → klik **Setujui**.
6. Login kembali sebagai `admin` → badge notifikasi menyala (booking siap ditandai selesai) → cek **Pemesanan**, status sudah berubah menjadi **Disetujui**. Buka menu "..." → klik **Tandai Selesai**, isi odometer akhir, liter BBM, dan catatan.
7. Buka halaman **Kendaraan**, klik **Status Kendaraan** pada kendaraan yang baru dipakai — jarak tempuh dan BBM yang dicatat akan tampil di riwayatnya. Coba juga **Riwayat Service** untuk menambah catatan servis baru.
8. Buka halaman **Driver**, klik **Riwayat** pada driver yang tadi dipakai — pemesanan yang baru saja diselesaikan akan tampil di riwayatnya.
9. Buka halaman **Kalender**, lihat bar pemesanan tadi muncul pada baris kendaraan yang bersangkutan sesuai rentang tanggalnya.
10. Sebagai `admin`, buka halaman **Kelola User** → tambah 1 akun approver baru (isi nama, username, password, pilih level) → akun tersebut langsung bisa dipakai login.
11. Klik kartu profil di pojok bawah sidebar → pilih **Ganti Password** → masukkan password lama dan password baru untuk mengubah kredensial akun yang sedang login.
12. Buka halaman **Riwayat Aktivitas** → seluruh langkah di atas (login, buat booking, approve, tandai selesai, tambah user, ganti password, catat service) akan muncul sebagai baris log lengkap dengan pelaku dan waktunya. Coba gunakan filter jenis aksi atau rentang tanggal untuk mempersempit tampilan.

### Menambahkan Foto Kendaraan

Di halaman **Kendaraan**, saat menambah atau mengedit data, isi field **URL Foto Kendaraan** dengan link gambar yang sudah ter-hosting online (misalnya dari Unsplash atau Pinterest — klik kanan gambar → *Copy image address*, pastikan link mengarah langsung ke file gambar, bukan ke halaman web). Jika dikosongkan, sistem akan menampilkan foto generic berdasarkan tipe kendaraan (angkutan orang/barang) sebagai fallback. Foto ini akan otomatis ikut tampil di kartu kendaraan maupun tabel dan detail pemesanan.

### Mencatat Riwayat Service Kendaraan

Di halaman **Kendaraan**, menu "..." pada setiap unit memiliki opsi **Riwayat Service** — di sana Admin dapat menambah catatan servis baru (tanggal, keterangan, status: Terjadwal/Selesai/Dibatalkan), menandai catatan sebagai selesai, mengedit, atau menghapusnya. Kolom "Jadwal Service" pada tabel utama serta baris "Service Berikutnya" pada modal detail kendaraan otomatis menampilkan catatan berstatus Terjadwal dengan tanggal terdekat. Data yang sama juga menjadi dasar pengingat jadwal servis pada Dashboard.

### Mengelola User & Approver

Hanya **Admin** yang dapat mengakses halaman **Kelola User**. Saat menambah user baru:
- **Role `admin`** — tidak memerlukan level approval.
- **Role `approver`** — wajib memilih **Level 1** atau **Level 2**, menentukan approver tersebut muncul di tahap persetujuan pertama atau kedua pada alur pemesanan.

Saat mengedit user, field password dapat dikosongkan jika tidak ingin mengubah password akun tersebut (password lama tetap dipertahankan).

### Meninjau Riwayat Aktivitas

Halaman **Riwayat Aktivitas** (khusus Admin) menampilkan seluruh baris log dari tabel `activity_logs`, terurut dari yang terbaru, lengkap dengan nama pelaku, jenis aksi (berwarna sesuai kategori — hijau untuk aksi tambah/setujui, biru untuk ubah, merah untuk hapus/tolak), deskripsi detail, waktu kejadian, dan alamat IP. Gunakan kolom pencarian untuk mencari berdasarkan nama user/deskripsi, atau dropdown filter jenis aksi dan rentang tanggal untuk mempersempit hasil. Baris dengan pelaku "Sistem" menandakan aksi yang tercatat tanpa konteks user yang login (umumnya dari controller yang belum menyertakan `user_id` saat memanggil `ActivityLogger`).

### Notifikasi In-App

Ikon lonceng di bagian atas sidebar menampilkan badge merah berisi jumlah item yang perlu perhatian, dengan dropdown floating (tidak mendorong menu lain) berisi daftar singkat. Data di-refresh otomatis setiap 30 detik:
- **Approver** — hanya notifikasi booking yang **benar-benar sudah gilirannya** ditinjau (level 2 tidak akan mendapat notifikasi selama level 1 booking yang sama belum disetujui).
- **Admin** — notifikasi booking berstatus Disetujui (siap ditandai selesai) dan booking yang ditolak dalam 3 hari terakhir.

Klik salah satu item notifikasi akan mengarahkan langsung ke halaman terkait (Approval atau Pemesanan).

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
| `vehicle_service_schedule` | Riwayat & jadwal service kendaraan (`service_date`, `description`, `status`: scheduled/done/cancelled) — sumber data pengingat servis di Dashboard dan modal detail kendaraan |
| `activity_logs` | Log aktivitas sistem (`user_id`, `action`, `description`, `ip_address`, `created_at`) — diisi otomatis lewat `ActivityLogger::log()` di setiap controller |

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
| GET | `/vehicles/{id}/services` | Riwayat service kendaraan tertentu, terurut terbaru |
| POST | `/vehicle-services` | Tambah catatan service baru. Body: `vehicle_id`, `service_date`, `description`, `status` |
| PUT | `/vehicle-services/{id}` | Update catatan service (termasuk mengubah status, mis. menandai selesai) |
| DELETE | `/vehicle-services/{id}` | Hapus catatan service |
| GET | `/vehicle-services/upcoming` | Seluruh catatan service berstatus `scheduled` dari semua kendaraan, terurut tanggal terdekat (dipakai Dashboard & kolom tabel Kendaraan) |
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
| GET | `/approvals?approver_id={id}` | Seluruh riwayat approval milik seorang approver (pending/approved/rejected), termasuk flag `actionable` yang menandai apakah baris tersebut benar-benar sudah bisa ditindak (mis. level 2 baru `actionable` setelah level 1 disetujui) |
| POST | `/approvals/{id}/approve` | Menyetujui pemesanan |
| POST | `/approvals/{id}/reject` | Menolak pemesanan (menyertakan `notes` alasan) |
| GET | `/reports/bookings/export` | Export laporan pemesanan ke Excel (parameter opsional `start` & `end`), termasuk kolom odometer, jarak tempuh, BBM, dan catatan selesai |
| GET | `/activity-logs` | Daftar seluruh log aktivitas, terurut terbaru (parameter opsional `action`, `start`, `end`) |

---

## 🧪 Testing & CI

Backend punya feature test (PHPUnit) yang jalan lewat endpoint HTTP asli — bukan cuma manggil method controller secara terpisah — supaya routing, validasi, dan format response ikut tervalidasi. Test ini dijalankan **otomatis oleh GitHub Actions** setiap ada `push` atau `pull request` ke branch `main`.

### Cakupan test saat ini

| File | Yang divalidasi |
|---|---|
| `tests/Feature/BookingConflictTest.php` | Booking bentrok jadwal ditolak (`409`), booking back-to-back (jam sambung tanpa jeda) diizinkan, kendaraan berbeda dengan jadwal sama tidak dianggap bentrok, tanggal selesai lebih awal dari tanggal mulai ditolak, edit/hapus terkunci begitu status booking bukan `pending` lagi, edit tidak salah anggap bentrok dengan booking itu sendiri |
| `tests/Feature/ApprovalFlowTest.php` | Approval level 2 tidak bisa disetujui sebelum level 1, flag `actionable` berubah sesuai giliran approver, status booking naik bertahap (`pending` → `approved_l1` → `approved_l2`), reject menghentikan alur, "Tandai Selesai" hanya bisa dilakukan setelah `approved_l2`, validasi odometer akhir tidak boleh lebih kecil dari odometer awal |

Total **25 test, 43 assertion**, semuanya lulus.

### Menjalankan test secara lokal

```powershell
cd vehicle-booking
vendor\bin\phpunit
```

atau lewat spark:

```powershell
php spark test
```

### CI — GitHub Actions

Workflow `.github/workflows/backend-tests.yml` menjalankan job berikut setiap ada `push`/`pull request` yang menyentuh folder `vehicle-booking/`, atau dipicu manual lewat tab **Actions → Backend Tests → Run workflow**:

1. Checkout kode
2. Setup PHP 8.3 beserta extension `sqlite3`, `mbstring`, `intl`, `curl`, dan driver coverage Xdebug
3. `composer install`
4. Jalankan `vendor/bin/phpunit`

Kalau ada satu saja test yang gagal, GitHub akan menandai run tersebut ❌ merah dan (kalau berasal dari pull request) memblokir status check-nya — jadi perubahan yang tidak sengaja merusak fitur yang sudah divalidasi test bisa ketahuan sebelum sempat digabung ke `main`, bukan setelah dipakai.

Status build terkini selalu bisa dicek lewat badge di bagian atas README ini, atau langsung di tab [Actions](https://github.com/okttaacx/fullstack-vehicle-booking/actions) repo.

### Bagaimana test-nya bekerja

- Test jalan lewat trait bawaan CodeIgniter (`DatabaseTestTrait` + `FeatureTestTrait`), yang otomatis migrate skema ke **SQLite in-memory** (bukan MySQL) setiap kali test dijalankan — jadi database development (`vehicle_booking_db`) di MySQL **tidak pernah tersentuh** oleh test, baik secara lokal maupun di CI.
- Membutuhkan ekstensi PHP `sqlite3` aktif di `php.ini` untuk dijalankan secara lokal (lihat [Troubleshooting](#-troubleshooting-umum) kalau muncul error terkait ini). Di CI, extension ini sudah disiapkan otomatis oleh workflow.
- Data dummy (user, kendaraan) dibuat lewat helper `Tests\Support\CreatesBookingFixtures` di `tests/_support/`, dipakai bersama oleh kedua test class supaya tidak duplikasi kode.

### Belum tercakup (lihat [Roadmap](#-roadmap))

- Test untuk controller lain (Vehicles, Drivers, Users, VehicleServices, ActivityLogs, Reports).
- Frontend (Jasmine/Karma) — belum ada test maupun workflow CI untuk frontend.

---

## 🗺 Roadmap

Fitur yang sudah selesai dan yang direncanakan untuk pengembangan selanjutnya:

### Selesai

- [x] Validasi bentrok jadwal pemesanan (double booking)
- [x] Manajemen Driver (CRUD + peringatan masa berlaku SIM)
- [x] Status "Selesai" untuk menandai kendaraan telah dikembalikan
- [x] Riwayat pemakaian per driver
- [x] Log BBM & odometer per pemakaian kendaraan
- [x] Foto kendaraan custom per unit (bukan hanya generic per tipe)
- [x] Kalender visual pemakaian kendaraan (Gantt chart mingguan)
- [x] Halaman kelola User/Approver dari UI
- [x] Ganti password mandiri untuk setiap user
- [x] Riwayat aktivitas (activity log) yang dapat dilihat di UI
- [x] Riwayat service kendaraan (bukan hanya jadwal berikutnya)
- [x] Notifikasi in-app (lonceng floating, badge, auto-refresh 30 detik)
- [x] Testing awal (PHPUnit) — feature test untuk validasi bentrok jadwal dan alur approval berjenjang (25 test, lihat [Testing & CI](#-testing--ci))
- [x] **CI (GitHub Actions)** — PHPUnit dijalankan otomatis di setiap push/pull request, lengkap badge status build di README

### Direncanakan — Testing

- [ ] **Perluas cakupan backend** ke controller lain (Vehicles, Drivers, Users, VehicleServices, ActivityLogs, Reports) serta endpoint `/auth/change-password`.
- [ ] **Frontend — Jasmine/Karma** (bawaan Angular CLI lewat `ng test`): unit test untuk logic komponen yang sudah memakai signals/computed (mis. `filteredBookings`, `barStyle` di halaman Kalender, `nextServiceFor` di halaman Kendaraan), serta pengujian rendering kondisional (`@if`/`@for`) pada template.
- [ ] Tambahkan job frontend (`ng test`) ke workflow CI yang sudah ada, agar backend dan frontend tervalidasi otomatis dalam satu pipeline.
- [ ] Manfaatkan laporan code coverage (`vendor\bin\phpunit --coverage-text`) yang kini sudah bisa dijalankan berkat Xdebug aktif di CI, untuk mengidentifikasi bagian kode yang belum tercakup test.

### Direncanakan — Keamanan

- [ ] **Rate limiting pada endpoint login** — mencegah percobaan brute-force dengan membatasi jumlah percobaan gagal per IP/username dalam rentang waktu tertentu (memanfaatkan `Throttler` bawaan CodeIgniter 4).
- [ ] **Validasi & sanitasi input lebih ketat** — menerapkan `Validation` service bawaan CodeIgniter secara konsisten di seluruh controller (saat ini sebagian besar masih validasi manual per field), serta memastikan seluruh query tetap memakai Query Builder/parameter binding untuk mencegah SQL injection.
- [ ] **Autentikasi berbasis token (JWT)** — menggantikan pola login sederhana saat ini (session disimpan di browser tanpa token bertanda tangan) dengan token yang memiliki masa berlaku dan dapat diverifikasi di setiap request API, sekaligus memungkinkan penerapan middleware otorisasi per-role yang lebih ketat.
- [ ] **HTTPS & security headers** saat deployment produksi (HSTS, CSP, `X-Frame-Options`, dsb).

### Direncanakan — Lainnya

- [ ] Optimasi tampilan mobile / PWA

---

## 🩹 Troubleshooting Umum

**CORS error di banyak halaman sekaligus**
Sering kali bukan masalah konfigurasi CORS itu sendiri, melainkan *fatal error* di sisi PHP yang membuat response gagal terkirim sebelum sempat melewati filter CORS. Periksa `writable/logs/log-<tanggal>.log` untuk pesan error sebenarnya sebelum mengubah konfigurasi CORS.

**`Namespace declaration statement has to be the very first statement`**
Terjadi ketika ada teks tidak sengaja tertinggal di atas baris `<?php`/`namespace` pada file PHP (controller, migration, maupun test) — biasanya sisa perintah terminal yang ikut ter-paste saat menimpa file lewat PowerShell heredoc, atau **BOM (Byte Order Mark)** tak terlihat yang disisipkan otomatis oleh `Set-Content -Encoding UTF8` di Windows PowerShell 5.1. Perbaiki dengan menimpa ulang file secara utuh memakai `Out-File -Encoding ascii` (bukan `Set-Content -Encoding UTF8`) untuk file yang isinya murni ASCII seperti kode PHP, dan pastikan baris pertama persis `<?php`.

**`Table 'namadb.nama_tabel' doesn't exist`**
Migration untuk tabel tersebut belum dijalankan atau gagal karena error sintaks (lihat poin di atas). Jalankan ulang `php spark migrate` setelah memastikan file migration bersih.

**`Class "App\Models\NamaModel" not found`**
File model belum dibuat, namanya tidak sama persis dengan class-nya, atau berada di folder yang salah. CodeIgniter 4 memakai autoload PSR-4 — nama file **harus** sama persis dengan nama class (mis. `FuelLogsModel.php` berisi `class FuelLogsModel extends Model`) dan berada di `app/Models/`. Untuk namespace baru di folder `tests/` (mis. `Tests\Support\...`), jalankan `composer dump-autoload` agar autoloader ikut diperbarui.

**Endpoint mengembalikan data tidak lengkap (field kosong/`null`) padahal tabel lain punya datanya**
Periksa apakah method controller terkait melakukan `JOIN` ke tabel relasi (`vehicles`, `drivers`, `users`, `fuel_logs`, dsb) menggunakan query builder, atau hanya memanggil `$model->find($id)` yang cuma mengambil baris mentah dari satu tabel. Method `index()` dan `show()` pada controller yang sama seringkali perlu pola query yang identik agar hasilnya konsisten.

**Data berhasil dikirim dari frontend tapi tidak tersimpan di database (tanpa error)**
Periksa properti `$allowedFields` pada Model terkait. CodeIgniter secara diam-diam akan membuang kolom yang tidak terdaftar di `$allowedFields` saat `insert()`/`update()` dipanggil — tidak ada error yang muncul, datanya hanya tidak masuk.

**`Cannot find module './pages/nama-halaman/nama-halaman'` saat build Angular**
Route sudah didaftarkan di `app.routes.ts`, tapi file komponennya (`.ts`/`.html`/`.css`) belum dibuat di folder yang sesuai, atau nama path pada `loadComponent()` tidak sama persis dengan nama folder/file (termasuk kesalahan tulis tunggal/jamak, mis. `activity-log` vs `activity-logs`). Buat folder dan file komponennya terlebih dahulu (bisa lewat `New-Item` di PowerShell atau `ng generate component`), lalu pastikan path pada route cocok persis.

**`TS2345: Argument of type 'string' is not assignable to parameter of type 'number'`**
`currentUser()?.id` pada service `Auth` umumnya bertipe `string`, sementara sejumlah method `Api` (mis. `getApprovals(approverId: number)`) mendeklarasikan parameter bertipe `number`. Bungkus dengan `Number(...)` saat memanggil, mis. `this.api.getApprovals(Number(user.id))`.

**Frontend menampilkan "Cannot GET"**
Biasanya karena SSR mencoba mengakses `localStorage` di sisi server. Pastikan pengecekan `isPlatformBrowser` digunakan sebelum memanggil Web API browser di service Angular (`Auth`).

**Grafik dashboard kosong**
Pastikan elemen `<canvas>` sudah ter-render di DOM sebelum Chart.js dipanggil (gunakan `setTimeout` singkat setelah data selesai dimuat).

**`NG8004: No pipe found with name 'slice'` / `'date'` / pipe bawaan lainnya**
Karena komponen bertipe *standalone*, pipe bawaan Angular seperti `SlicePipe` atau `DatePipe` harus diimpor eksplisit dari `@angular/common` dan didaftarkan di array `imports` komponen terkait — tidak otomatis tersedia seperti pada NgModule biasa.

**Dropdown atau notifikasi ikut terdorong/mendorong elemen sidebar lain**
Dropdown yang menggunakan `position: absolute` akan tetap terikat pada alur tata letak parent-nya (mendorong elemen lain jika parent memiliki `overflow` tertentu). Untuk dropdown yang perlu benar-benar melayang di atas seluruh halaman (mis. notifikasi), gunakan `position: fixed` dengan koordinat dihitung manual dari `getBoundingClientRect()` elemen pemicunya melalui `ViewChild`, dan render dropdown tersebut di luar container yang membatasi (lihat implementasi lonceng notifikasi pada `main-layout`).

**Booking gagal disimpan dengan pesan bentrok jadwal**
Ini bukan bug — validasi memang menolak pemesanan kendaraan yang sama pada rentang waktu yang tumpang tindih dengan pemesanan aktif lain. Pilih kendaraan lain, ubah rentang tanggal, atau selesaikan (Tandai Selesai) pemesanan lama terlebih dahulu jika kendaraan sudah tidak terpakai.

**"Tandai Selesai" gagal dengan pesan "Hanya pemesanan yang sudah disetujui penuh..."**
Booking yang bersangkutan belum berstatus `approved_l2` (belum disetujui kedua level approver). Cek status booking terlebih dahulu di tabel Pemesanan sebelum mencoba menandainya selesai.

**URL foto kendaraan tidak muncul / broken image**
Pastikan URL yang diisi mengarah **langsung ke file gambar**, bukan ke halaman web. Untuk Pinterest, gunakan *Copy image address* pada gambar yang sudah diperbesar (biasanya berformat `https://i.pinimg.com/...`), bukan link pin (`pinterest.com/pin/...`). Tes dengan membuka URL tersebut di tab baru — jika yang muncul hanya gambar (bukan tampilan situs penuh), URL tersebut valid digunakan.

**Ganti password gagal dengan pesan "Password lama tidak sesuai"**
Pastikan password lama yang diinput benar-benar sama dengan password yang sedang aktif saat ini (bukan password default jika sudah pernah diganti sebelumnya). Password baru minimal 6 karakter.

**Kolom "pelaku" pada Riwayat Aktivitas menampilkan "Sistem" untuk beberapa baris**
Terjadi jika controller terkait memanggil `ActivityLogger::log(null, ...)` alih-alih menyertakan ID user yang sedang login (umumnya pada operasi yang belum mengirim ulang identitas user dari frontend, seperti pada beberapa aksi kendaraan/driver). Ini bukan bug fatal — log tetap tercatat, hanya kolom pelakunya kosong. Untuk melengkapi, sertakan `user_id` yang relevan pada pemanggilan `ActivityLogger::log()` di controller terkait.

**Notifikasi approver level 2 tidak muncul padahal ada booking berstatus "Menunggu L2"**
Ini justru perilaku yang benar — badge notifikasi hanya menyala untuk baris approval yang bendera `actionable`-nya `true` dari endpoint `/approvals`. Approver level 2 baru dianggap `actionable` setelah approval level 1 pada booking yang sama berstatus `approved`, mencegah notifikasi yang menyesatkan approver untuk bertindak sebelum gilirannya.

**Server backend mati sendiri**
`php spark serve` berjalan di foreground — jangan tutup terminal tempat command ini dijalankan, dan jangan pakai terminal yang sama untuk command lain.

**`could not find driver` atau test gagal total saat `vendor\bin\phpunit` dijalankan (lokal)**
Ekstensi PHP `sqlite3` belum aktif — testing framework CodeIgniter butuh ini untuk membuat database SQLite in-memory sementara. Aktifkan di `php.ini` (`extension=sqlite3`), lalu restart Laragon/terminal.

**`Failed to drop column "..." on "..." table` saat menjalankan test**
SQLite (dipakai khusus untuk lingkungan test) punya dukungan terbatas untuk `DROP COLUMN` dibanding MySQL. Kalau migration kamu memakai `dropColumn()`, migration tersebut perlu pengecualian khusus saat `ENVIRONMENT === 'testing'`, atau pertimbangkan untuk tidak menghapus kolom lewat migration terpisah pada development lanjutan.

**Warning `No code coverage driver available` saat run test**
Ini hanya warning, bukan kegagalan test — muncul karena Xdebug (atau PCOV) belum ter-install. Bisa terjadi saat menjalankan test **secara lokal** kalau Xdebug belum di-install di environment tersebut (di CI, warning ini sudah tidak muncul karena Xdebug sudah diaktifkan lewat `coverage: xdebug` pada step Setup PHP). Test tetap berjalan dan lulus seperti biasa tanpa Xdebug; extension ini baru wajib kalau ingin melihat laporan persentase baris kode yang tercakup test.

**Response `respond([...])` mengirim status code `200` walau body-nya berisi `"status": 201`/`404`/dst**
`$this->respond($data, $statusCode)` di CodeIgniter menentukan HTTP status code lewat **argumen kedua**, bukan dari isi array `$data`. Kalau argumen kedua tidak diisi, status code HTTP asli tetap `200` meskipun body JSON menyebut angka lain — ini gampang lolos dari pengecekan manual karena body-nya "kelihatan" benar. Pastikan endpoint yang seharusnya mengembalikan `201 Created`, `404 Not Found`, dsb memanggil `respond($data, $kodeStatusnya)` secara eksplisit, dan verifikasi lewat test yang memeriksa HTTP status code asli (bukan cuma isi body).

**Workflow CI tidak kepicu setelah push, padahal ada perubahan**
Workflow `backend-tests.yml` dibatasi dengan filter `paths: - 'vehicle-booking/**'` — hanya jalan otomatis kalau ada file yang berubah **di dalam folder backend**. Perubahan pada file di luar folder itu (mis. hanya mengedit workflow-nya sendiri di `.github/workflows/`, atau file di frontend) tidak akan memicu run baru secara otomatis. Untuk menjalankan tanpa menunggu perubahan di folder backend, gunakan trigger manual lewat tab **Actions → Backend Tests → Run workflow** (tersedia karena workflow ini juga mendaftarkan event `workflow_dispatch`).

**Run CI gagal (❌) meskipun log menunjukkan semua test lulus (`OK, but there were issues!`)**
Terjadi jika PHPUnit mengeluarkan *warning* (mis. `No code coverage driver available`) yang membuat exit code proses menjadi `1`, walau baris `Tests: X, Assertions: Y` menunjukkan semuanya lulus tanpa kegagalan (*failure*). Aktifkan driver coverage (`coverage: xdebug` pada step Setup PHP di workflow) untuk menghilangkan warning tersebut sehingga exit code kembali `0` saat seluruh test benar-benar lulus.

---

<div align="center">

## 👤 Kontributor

Proyek ini dikembangkan secara mandiri sebagai sarana pembelajaran dan eksplorasi dalam membangun sistem web berbasis Fullstack (Angular & CodeIgniter).

<img src="https://readme-typing-svg.demolab.com?font=Baloo+2&size=14&pause=1500&color=64748B&center=true&vCenter=true&width=400&lines=Made+with+%E2%98%95+and+a+lot+of+debugging" alt="Footer" />

</div>
