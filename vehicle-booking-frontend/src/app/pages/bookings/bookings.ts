import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';

@Component({
  selector: 'app-bookings',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './bookings.html',
  styleUrl: './bookings.css',
})
export class Bookings implements OnInit {
  private api = inject(Api);
  auth = inject(Auth);

  vehicles = signal<any[]>([]);
  drivers = signal<any[]>([]);
  approvers = signal<any[]>([]);

  bookings = signal<any[]>([]);
  loadingList = signal(true);

  showForm = signal(false);
  submitting = signal(false);
  formError = signal('');
  formSuccess = signal('');

  vehicleId = '';
  driverId = '';
  approverL1Id = '';
  approverL2Id = '';
  purpose = '';
  destination = '';
  startDate = '';
  endDate = '';

  exportStart = '';
  exportEnd = '';
  exporting = signal(false);
  exportError = signal('');

  get approversLevel1() {
    return this.approvers().filter(a => String(a.level) === '1');
  }

  get approversLevel2() {
    return this.approvers().filter(a => String(a.level) === '2');
  }

  ngOnInit() {
    this.loadBookings();
    this.api.getVehicles().subscribe({ next: (res) => this.vehicles.set(res.data ?? []) });
    this.api.getDrivers().subscribe({ next: (res) => this.drivers.set(res.data ?? []) });
    this.api.getApprovers().subscribe({ next: (res) => this.approvers.set(res.data ?? []) });
  }

  loadBookings() {
    this.loadingList.set(true);
    this.api.getBookings().subscribe({
      next: (res) => {
        this.bookings.set(res.data ?? []);
        this.loadingList.set(false);
      },
      error: () => this.loadingList.set(false),
    });
  }

  openForm() {
    this.showForm.set(true);
    this.formError.set('');
    this.formSuccess.set('');
  }

  cancelForm() {
    this.showForm.set(false);
    this.resetForm();
  }

  private resetForm() {
    this.vehicleId = '';
    this.driverId = '';
    this.approverL1Id = '';
    this.approverL2Id = '';
    this.purpose = '';
    this.destination = '';
    this.startDate = '';
    this.endDate = '';
  }

  submitBooking() {
    this.formError.set('');

    if (!this.vehicleId || !this.approverL1Id || !this.approverL2Id || !this.startDate || !this.endDate) {
      this.formError.set('Kendaraan, approver level 1 & 2, serta tanggal wajib diisi');
      return;
    }

    const currentUser = this.auth.currentUser();
    if (!currentUser) {
      this.formError.set('Sesi login tidak ditemukan, silakan login ulang');
      return;
    }

    this.submitting.set(true);

    const payload = {
      requested_by: currentUser.id,
      vehicle_id: this.vehicleId,
      driver_id: this.driverId || null,
      purpose: this.purpose || null,
      destination: this.destination || null,
      start_date: this.startDate,
      end_date: this.endDate,
      approver_level1_id: this.approverL1Id,
      approver_level2_id: this.approverL2Id,
    };

    this.api.createBooking(payload).subscribe({
      next: (res) => {
        this.formSuccess.set(`Pemesanan berhasil dibuat: ${res.data?.booking_code ?? ''}`);
        this.submitting.set(false);
        this.resetForm();
        this.loadBookings();
        setTimeout(() => this.showForm.set(false), 1500);
      },
      error: (err) => {
        this.formError.set(err?.error?.messages?.error ?? 'Gagal membuat pemesanan');
        this.submitting.set(false);
      },
    });
  }

  exportToExcel() {
    this.exportError.set('');
    this.exporting.set(true);

    this.api.exportBookings(this.exportStart, this.exportEnd).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Laporan_Pemesanan_Kendaraan_${new Date().getTime()}.xlsx`;
        a.click();
        window.URL.revokeObjectURL(url);
        this.exporting.set(false);
      },
      error: () => {
        this.exportError.set('Gagal mengekspor laporan');
        this.exporting.set(false);
      },
    });
  }

  statusLabel(status: string): string {
    const map: Record<string, string> = {
      pending: 'Menunggu Persetujuan L1',
      approved_l1: 'Menunggu Persetujuan L2',
      approved_l2: 'Disetujui',
      rejected: 'Ditolak',
      completed: 'Selesai',
    };
    return map[status] ?? status;
  }

  statusClass(status: string): string {
    const map: Record<string, string> = {
      pending: 'badge-amber',
      approved_l1: 'badge-blue',
      approved_l2: 'badge-green',
      rejected: 'badge-red',
      completed: 'badge-green',
    };
    return map[status] ?? '';
  }
}