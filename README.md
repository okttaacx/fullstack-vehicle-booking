# 🚗 Vehicle Booking System

Aplikasi pemesanan kendaraan perusahaan (tambang nikel) untuk memonitor kendaraan, konsumsi BBM, jadwal service, dan riwayat pemakaian — lengkap dengan alur persetujuan berjenjang (2 level).

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
- **Manajemen Kendaraan** — CRUD lengkap (tambah, lihat, edit, hapus), pencarian, filter tipe/kepemilikan, riwayat pemakaian per kendaraan.
- **Pemesanan Kendaraan** — admin membuat pemesanan (pilih kendaraan, driver, dan 2 approver), dilengkapi pencarian, filter status, sorting, dan pagination.
- **Persetujuan Berjenjang (2 Level)** — approver Level 1 menyetujui/menolak terlebih dahulu, baru approver Level 2 bisa bertindak. Alasan penolakan tercatat dan bisa dilihat admin.
- **Dashboard** — ringkasan total kendaraan, total pemesanan, tren 7 hari terakhir, distribusi kepemilikan armada, ketersediaan armada, dan pengingat jadwal service.
- **Export Laporan Excel** — laporan pemesanan periodik (bisa difilter rentang tanggal) diunduh dalam format `.xlsx`.
- **Log Aktivitas** — setiap aksi penting (login, buat/ubah/hapus kendaraan, buat/approve/reject pemesanan) tercatat di tabel `activity_logs`.

---

## 📁 Struktur Folder

```
vehicle-booking-system/
├── vehicle-booking/            # Backend — CodeIgniter 4
│   ├── app/
│   │   ├── Controllers/        # Auth, Users, Vehicles, Drivers, Bookings, Approvals, Reports
│   │   ├── Models/              # UsersModel, VehiclesModel, dll.
│   │   ├── Database/Migrations/
│   │   ├── Filters/             # CorsFilter
│   │   └── Config/Routes.php
│   └── public/
│
└── vehicle-booking-frontend/   # Frontend — Angular
    └── src/app/
        ├── core/                # Auth, Api service, auth guard
        ├── layout/main-layout/  # Sidebar & shell utama
        └── pages/
            ├── login/
            ├── dashboard/
            ├── vehicles/
            ├── bookings/
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
#  dengan password yang di-hash pakai password_hash() PHP)

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

> ⚠️ **Backend dan frontend harus berjalan bersamaan** di dua terminal terpisah — frontend memanggil API ke `http://localhost:8080/api/...`.

---

## 🔑 Kredensial Default

| Username | Password | Role | Level Approval |
|---|---|---|---|
| `admin` | `admin123` | Admin | - |
| `spv_tambang1` | *(sesuaikan dengan yang di-set di database)* | Approver | Level 1 |
| `manager_hq` | *(sesuaikan dengan yang di-set di database)* | Approver | Level 2 |

> Password approver di atas dibuat manual saat seeding awal database dan di-hash dengan `password_hash()`. Jika lupa, reset via query:
> ```sql
> UPDATE users SET password = '<hasil password_hash()>' WHERE username = 'spv_tambang1';
> ```
> atau generate hash baru lewat terminal:
> ```powershell
> php -r "echo password_hash('password_baru', PASSWORD_DEFAULT);"
> ```

---

## 📖 Alur Bisnis / Panduan Penggunaan

Sistem punya 2 peran: **Admin** (membuat pemesanan) dan **Approver** (menyetujui, terbagi 2 level).

### Alur satu siklus pemesanan kendaraan

```
1. Admin login → menu "Pemesanan" → klik "Buat Pemesanan"
   Pilih kendaraan, driver (opsional), approver Level 1, approver Level 2, dan tanggal
   → status booking: PENDING (menunggu approver Level 1)

2. Approver Level 1 login → menu "Approval"
   Melihat daftar pemesanan yang menunggu persetujuannya → klik "Setujui" atau "Tolak"
   → jika disetujui, status berubah: APPROVED_L1 (menunggu approver Level 2)
   → jika ditolak, status: REJECTED (alur berhenti, alasan tercatat)

3. Approver Level 2 login → menu "Approval"
   Pemesanan baru muncul di daftarnya SETELAH Level 1 menyetujui → klik "Setujui" atau "Tolak"
   → jika disetujui, status akhir: APPROVED_L2 (pemesanan siap digunakan)
   → jika ditolak, status: REJECTED
```

### Peta Halaman

| Halaman | Fungsi | Akses |
|---|---|---|
| **Dashboard** | Ringkasan statistik & grafik operasional | Semua role |
| **Kendaraan** | Daftar kendaraan, detail, riwayat pemakaian | Lihat: semua role. Tambah/Edit/Hapus: **Admin** |
| **Pemesanan** | Riwayat semua pemesanan + export Excel | Lihat: semua role. Buat pemesanan: **Admin** |
| **Approval** | Daftar pemesanan yang perlu disetujui user yang login | Hanya tampil untuk role **Approver** |

### Contoh Uji Coba Alur Lengkap

1. Login `admin` / `admin123` → buka **Pemesanan** → buat 1 pemesanan baru, pilih approver Level 1 = `spv_tambang1`, Level 2 = `manager_hq`.
2. Logout → login `spv_tambang1` → buka **Approval** → klik **Setujui** pada pemesanan tadi.
3. Logout → login `manager_hq` → buka **Approval** → pemesanan kini muncul di daftarnya → klik **Setujui**.
4. Login kembali sebagai `admin` → cek **Pemesanan**, status sudah berubah menjadi **Disetujui**.

### Export Laporan

Di halaman **Pemesanan**, isi rentang tanggal (opsional) lalu klik **Export Excel** untuk mengunduh laporan periodik dalam format `.xlsx`.

---

## 🗄 Skema Database

Tabel utama:

| Tabel | Keterangan |
|---|---|
| `users` | Data admin & approver (kolom `role`, `level` untuk approver) |
| `vehicles` | Data kendaraan (`type`: angkutan_orang/angkutan_barang, `ownership`: milik_perusahaan/sewa) |
| `drivers` | Data driver/pengemudi |
| `vehicle_bookings` | Data pemesanan kendaraan |
| `booking_approvals` | Baris persetujuan per level per pemesanan (status, notes, approved_at) |
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
| GET | `/users?role=approver` | Daftar user approver |
| GET | `/vehicles` | Daftar kendaraan |
| POST | `/vehicles` | Tambah kendaraan |
| PUT | `/vehicles/{id}` | Update kendaraan |
| DELETE | `/vehicles/{id}` | Hapus kendaraan |
| GET | `/drivers` | Daftar driver |
| POST | `/drivers` | Tambah driver |
| GET | `/bookings` | Daftar pemesanan |
| GET | `/bookings/{id}` | Detail pemesanan |
| POST | `/bookings` | Buat pemesanan baru |
| GET | `/approvals?approver_id={id}` | Daftar approval milik seorang approver |
| POST | `/approvals/{id}/approve` | Menyetujui pemesanan |
| POST | `/approvals/{id}/reject` | Menolak pemesanan |
| GET | `/reports/bookings/export` | Export laporan pemesanan ke Excel |

---

## 🩹 Troubleshooting Umum

**CORS error di browser**
Pastikan `php spark serve` aktif dan filter `cors` di `app/Config/Filters.php` sudah terpasang di grup route `api`.

**Frontend menampilkan "Cannot GET"**
Biasanya karena SSR mencoba mengakses `localStorage` di sisi server. Pastikan pengecekan `isPlatformBrowser` digunakan sebelum memanggil Web API browser di service Angular.

**Grafik dashboard kosong**
Pastikan elemen `<canvas>` sudah ter-render di DOM sebelum Chart.js dipanggil (gunakan `setTimeout` singkat setelah data selesai dimuat jika diperlukan).

**Server backend mati sendiri**
`php spark serve` berjalan di foreground — jangan tutup terminal tempat command ini dijalankan, dan jangan pakai terminal yang sama untuk command lain.

---

## 👤 Kontributor

Dibangun sebagai bagian dari technical test **Fullstack Developer (Intern)**.
