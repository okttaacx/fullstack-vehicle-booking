import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { SlicePipe } from '@angular/common';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';

@Component({
  selector: 'app-vehicles',
  standalone: true,
  imports: [FormsModule, SlicePipe],
  templateUrl: './vehicles.html',
  styleUrl: './vehicles.css',
})
export class Vehicles implements OnInit {
  private api = inject(Api);
  auth = inject(Auth);

  vehicles = signal<any[]>([]);
  bookings = signal<any[]>([]);
  loading = signal(true);
  errorMsg = signal('');

  searchTerm = '';
  filterType = '';
  filterOwnership = '';
  showFilterMenu = signal(false);

  showForm = signal(false);
  editingId: number | null = null;
  submitting = signal(false);
  formError = signal('');

  name = '';
  licensePlate = '';
  type = '';
  ownership = '';
  fuelConsumption: number | null = null;
  serviceSchedule = '';

  deletingId = signal<number | null>(null);
  openMenuId = signal<number | null>(null);
  copiedId = signal<number | null>(null);

  detailVehicle = signal<any | null>(null);
  statusVehicle = signal<any | null>(null);

  filteredVehicles = computed(() => {
    let list = this.vehicles();

    const term = this.searchTerm.trim().toLowerCase();
    if (term) {
      list = list.filter(v =>
        (v.name ?? '').toLowerCase().includes(term) ||
        (v.license_plate ?? '').toLowerCase().includes(term)
      );
    }

    if (this.filterType) {
      list = list.filter(v => v.type === this.filterType);
    }

    if (this.filterOwnership) {
      list = list.filter(v => v.ownership === this.filterOwnership);
    }

    return list;
  });

  ngOnInit() {
    this.loadVehicles();
    this.api.getBookings().subscribe({ next: (res) => this.bookings.set(res.data ?? []) });
  }

  loadVehicles() {
    this.loading.set(true);
    this.api.getVehicles().subscribe({
      next: (res) => {
        this.vehicles.set(res.data ?? []);
        this.loading.set(false);
      },
      error: () => {
        this.errorMsg.set('Gagal memuat data kendaraan');
        this.loading.set(false);
      },
    });
  }

  vehicleImage(type: string): string {
    return type === 'angkutan_barang'
      ? 'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?q=80&w=800&auto=format&fit=crop'
      : 'https://images.unsplash.com/photo-1605152322346-bd2391778772?q=80&w=800&auto=format&fit=crop';
  }

  toggleFilterMenu() {
    this.showFilterMenu.update(v => !v);
  }

  clearFilters() {
    this.filterType = '';
    this.filterOwnership = '';
  }

  toggleMenu(id: number) {
    this.openMenuId.set(this.openMenuId() === id ? null : id);
  }

  closeMenu() {
    this.openMenuId.set(null);
  }

  copyPlate(v: any) {
    navigator.clipboard.writeText(v.license_plate).then(() => {
      this.copiedId.set(v.id);
      setTimeout(() => this.copiedId.set(null), 1500);
    });
  }

  openDetail(v: any) {
    this.detailVehicle.set(v);
    this.closeMenu();
  }

  closeDetail() {
    this.detailVehicle.set(null);
  }

  openStatus(v: any) {
    this.statusVehicle.set(v);
    this.closeMenu();
  }

  closeStatus() {
    this.statusVehicle.set(null);
  }

  vehicleBookingHistory(vehicleId: number) {
    return this.bookings()
      .filter((b: any) => Number(b.vehicle_id) === Number(vehicleId))
      .sort((a: any, b: any) => new Date(b.start_date).getTime() - new Date(a.start_date).getTime());
  }

  openCreateForm() {
    this.editingId = null;
    this.resetForm();
    this.showForm.set(true);
    this.formError.set('');
  }

  openEditForm(v: any) {
    this.editingId = v.id;
    this.name = v.name;
    this.licensePlate = v.license_plate;
    this.type = v.type;
    this.ownership = v.ownership;
    this.fuelConsumption = v.fuel_consumption;
    this.serviceSchedule = v.service_schedule ?? '';
    this.showForm.set(true);
    this.formError.set('');
    this.closeMenu();
  }

  cancelForm() {
    this.showForm.set(false);
    this.resetForm();
  }

  private resetForm() {
    this.name = '';
    this.licensePlate = '';
    this.type = '';
    this.ownership = '';
    this.fuelConsumption = null;
    this.serviceSchedule = '';
  }

  submitVehicle() {
    this.formError.set('');

    if (!this.name || !this.licensePlate || !this.type || !this.ownership) {
      this.formError.set('Nama, plat nomor, tipe, dan status kepemilikan wajib diisi');
      return;
    }

    this.submitting.set(true);

    const payload = {
      name: this.name,
      license_plate: this.licensePlate,
      type: this.type,
      ownership: this.ownership,
      fuel_consumption: this.fuelConsumption,
      service_schedule: this.serviceSchedule || null,
    };

    const request = this.editingId
      ? this.api.updateVehicle(this.editingId, payload)
      : this.api.createVehicle(payload);

    request.subscribe({
      next: () => {
        this.submitting.set(false);
        this.showForm.set(false);
        this.resetForm();
        this.loadVehicles();
      },
      error: (err) => {
        this.formError.set(err?.error?.messages?.error ?? 'Gagal menyimpan data kendaraan');
        this.submitting.set(false);
      },
    });
  }

  confirmDelete(id: number) {
    this.deletingId.set(id);
    this.closeMenu();
  }

  cancelDelete() {
    this.deletingId.set(null);
  }

  doDelete(id: number) {
    this.api.deleteVehicle(id).subscribe({
      next: () => {
        this.deletingId.set(null);
        this.loadVehicles();
      },
      error: () => {
        this.errorMsg.set('Gagal menghapus kendaraan');
        this.deletingId.set(null);
      },
    });
  }

  typeLabel(type: string): string {
    return type === 'angkutan_orang' ? 'Angkutan Orang' : 'Angkutan Barang';
  }

  ownershipLabel(ownership: string): string {
    return ownership === 'milik_perusahaan' ? 'Milik Perusahaan' : 'Sewa';
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
}