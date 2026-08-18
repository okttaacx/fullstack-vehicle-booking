import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { SlicePipe } from '@angular/common';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';

@Component({
  selector: 'app-bookings',
  standalone: true,
  imports: [FormsModule, SlicePipe],
  templateUrl: './bookings.html',
  styleUrl: './bookings.css',
})
export class Bookings implements OnInit {
  private api = inject(Api);
  auth = inject(Auth);

  vehicles = signal<any[]>([]);
  drivers = signal<any[]>([]);
  approvers = signal<any[]>([]);

  allBookings = signal<any[]>([]);
  loadingList = signal(true);

  searchTerm = '';
  filterStatus = '';
  showFilterMenu = signal(false);
  sortDesc = signal(true);
  page = signal(1);
  pageSize = 5;

  showForm = signal(false);
  submitting = signal(false);
  formError = signal('');
  formSuccess = signal('');

  vehicleId = '';
  driverName = '';
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

  openMenuId = signal<number | null>(null);
  copiedId = signal<number | null>(null);
  detailBooking = signal<any | null>(null);

  get approversLevel1() {
    return this.approvers().filter(a => String(a.level) === '1');
  }

  get approversLevel2() {
    return this.approvers().filter(a => String(a.level) === '2');
  }

  totalAll = computed(() => this.allBookings().length);
  totalPending = computed(() => this.allBookings().filter(b => b.status === 'pending' || b.status === 'approved_l1').length);
  totalApproved = computed(() => this.allBookings().filter(b => b.status === 'approved_l2' || b.status === 'completed').length);
  totalRejected = computed(() => this.allBookings().filter(b => b.status === 'rejected').length);

  filteredBookings = computed(() => {
    let list = this.allBookings();

    const term = this.searchTerm.trim().toLowerCase();
    if (term) {
      list = list.filter(b =>
        (b.booking_code ?? '').toLowerCase().includes(term) ||
        (b.vehicle_name ?? '').toLowerCase().includes(term)
      );
    }

    if (this.filterStatus) {
      list = list.filter(b => b.status === this.filterStatus);
    }

    list = [...list].sort((a, b) => {
      const da = new Date(a.start_date).getTime();
      const db = new Date(b.start_date).getTime();
      return this.sortDesc() ? db - da : da - db;
    });

    return list;
  });

  totalPages = computed(() => Math.max(1, Math.ceil(this.filteredBookings().length / this.pageSize)));

  pagedBookings = computed(() => {
    const start = (this.page() - 1) * this.pageSize;
    return this.filteredBookings().slice(start, start + this.pageSize);
  });

  Math = Math;

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
        this.allBookings.set(res.data ?? []);
        this.loadingList.set(false);
      },
      error: () => this.loadingList.set(false),
    });
  }

  vehicleImage(type: string): string {
    return type === 'angkutan_barang'
      ? 'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?q=80&w=400&auto=format&fit=crop'
      : 'https://images.unsplash.com/photo-1605152322346-bd2391778772?q=80&w=400&auto=format&fit=crop';
  }

  toggleFilterMenu() {
    this.showFilterMenu.update(v => !v);
  }

  clearFilters() {
    this.filterStatus = '';
  }

  toggleSort() {
    this.sortDesc.update(v => !v);
  }

  onSearchChange() {
    this.page.set(1);
  }

  goToPage(p: number) {
    if (p < 1 || p > this.totalPages()) return;
    this.page.set(p);
  }

  toggleMenu(id: number) {
    this.openMenuId.set(this.openMenuId() === id ? null : id);
  }

  closeMenu() {
    this.openMenuId.set(null);
  }

  copyCode(b: any) {
    navigator.clipboard.writeText(b.booking_code).then(() => {
      this.copiedId.set(b.id);
      setTimeout(() => this.copiedId.set(null), 1500);
    });
  }

  openDetail(b: any) {
    this.detailBooking.set(b);
    this.closeMenu();
  }

  closeDetail() {
    this.detailBooking.set(null);
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
    this.driverName = '';
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
    this.resolveDriverId((driverId) => this.createBookingWithDriver(currentUser.id, driverId));
  }

  private resolveDriverId(callback: (driverId: string | null) => void) {
    const typedName = this.driverName.trim();

    if (!typedName) {
      callback(null);
      return;
    }

    const existing = this.drivers().find(
      (d: any) => d.name.trim().toLowerCase() === typedName.toLowerCase()
    );

    if (existing) {
      callback(existing.id);
      return;
    }

    this.api.createDriver({ name: typedName }).subscribe({
      next: (res) => {
        const newId = res.data?.id;
        this.api.getDrivers().subscribe({ next: (r) => this.drivers.set(r.data ?? []) });
        callback(newId ?? null);
      },
      error: () => {
        this.formError.set('Gagal menambahkan driver baru');
        this.submitting.set(false);
      },
    });
  }

  private createBookingWithDriver(requestedBy: string, driverId: string | null) {
    const payload = {
      requested_by: requestedBy,
      vehicle_id: this.vehicleId,
      driver_id: driverId,
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
      pending: 'Menunggu L1',
      approved_l1: 'Menunggu L2',
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

  initials(name: string): string {
    return (name ?? '?').charAt(0).toUpperCase();
  }
}