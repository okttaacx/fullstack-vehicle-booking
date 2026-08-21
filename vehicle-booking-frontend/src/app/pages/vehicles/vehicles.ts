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
  imageUrl = '';

  deletingId = signal<number | null>(null);
  openMenuId = signal<number | null>(null);
  copiedId = signal<number | null>(null);

  detailVehicle = signal<any | null>(null);
  detailNextService = signal<any | null>(null);
  statusVehicle = signal<any | null>(null);
  nextServiceMap = signal<Record<number, any>>({});

  // ---- Riwayat Service ----
  serviceVehicle = signal<any | null>(null);
  serviceLogs = signal<any[]>([]);
  serviceLoading = signal(false);

  showServiceForm = signal(false);
  editingServiceId: number | null = null;
  serviceSubmitting = signal(false);
  serviceFormError = signal('');

  serviceDate = '';
  serviceDescription = '';
  serviceStatus = 'scheduled';

  deletingServiceId = signal<number | null>(null);

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

  ownedCount = computed(() => this.vehicles().filter(v => v.ownership === 'milik_perusahaan').length);
  rentedCount = computed(() => this.vehicles().filter(v => v.ownership === 'sewa').length);

  ngOnInit() {
    this.loadVehicles();
    this.api.getBookings().subscribe({
      next: (res) => this.bookings.set(res.data ?? []),
    });
    this.loadNextServiceMap();
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

  loadNextServiceMap() {
    this.api.getUpcomingVehicleServices().subscribe({
      next: (res) => {
        const records = res.data ?? [];
        const map: Record<number, any> = {};
        
        // Data sudah terurut ASC by service_date dari backend,
        // jadi entri pertama yang ketemu per vehicle_id itu yang terdekat.
        for (const r of records) {
          const vId = Number(r.vehicle_id);
          if (!map[vId]) {
            map[vId] = r;
          }
        }

        this.nextServiceMap.set(map);
      },
      error: () => {},
    });
  }

  nextServiceFor(vehicleId: number): any {
    return this.nextServiceMap()[vehicleId] ?? null;
  }

  vehicleImage(v: any): string {
    if (v.image_url) {
      return v.image_url;
    }

    return v.type === 'angkutan_barang'
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
    this.detailNextService.set(null);
    this.closeMenu();

    this.api.getVehicleServices(v.id).subscribe({
      next: (res) => {
        const records = res.data ?? [];
        const upcoming = records
          .filter((r: any) => r.status === 'scheduled')
          .sort(
            (a: any, b: any) =>
              new Date(a.service_date).getTime() - new Date(b.service_date).getTime()
          );
        this.detailNextService.set(upcoming[0] ?? null);
      },
      error: () => {},
    });
  }

  closeDetail() {
    this.detailVehicle.set(null);
    this.detailNextService.set(null);
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
      .sort(
        (a: any, b: any) =>
          new Date(b.start_date).getTime() - new Date(a.start_date).getTime()
      );
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
    this.imageUrl = v.image_url ?? '';
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
    this.imageUrl = '';
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
      image_url: this.imageUrl || null,
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
        this.formError.set(
          err?.error?.messages?.error ?? 'Gagal menyimpan data kendaraan'
        );
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
      completed: 'Selesai',
      rejected: 'Ditolak',
    };

    return map[status] ?? status;
  }

  statusClass(status: string): string {
    const map: Record<string, string> = {
      pending: 'badge-amber',
      approved_l1: 'badge-blue',
      approved_l2: 'badge-green',
      completed: 'badge-gray',
      rejected: 'badge-red',
    };

    return map[status] ?? '';
  }

  // ---- Riwayat Service ----

  openServiceHistory(v: any) {
    this.serviceVehicle.set(v);
    this.closeMenu();
    this.loadServiceLogs(v.id);
  }

  closeServiceHistory() {
    this.serviceVehicle.set(null);
    this.serviceLogs.set([]);
    this.cancelServiceForm();
  }

  loadServiceLogs(vehicleId: number) {
    this.serviceLoading.set(true);
    this.api.getVehicleServices(vehicleId).subscribe({
      next: (res) => {
        this.serviceLogs.set(res.data ?? []);
        this.serviceLoading.set(false);
      },
      error: () => {
        this.serviceLoading.set(false);
      },
    });
  }

  openServiceCreateForm() {
    this.editingServiceId = null;
    this.serviceDate = '';
    this.serviceDescription = '';
    this.serviceStatus = 'scheduled';
    this.serviceFormError.set('');
    this.showServiceForm.set(true);
  }

  openServiceEditForm(s: any) {
    this.editingServiceId = s.id;
    this.serviceDate = s.service_date;
    this.serviceDescription = s.description ?? '';
    this.serviceStatus = s.status;
    this.serviceFormError.set('');
    this.showServiceForm.set(true);
  }

  cancelServiceForm() {
    this.showServiceForm.set(false);
    this.editingServiceId = null;
  }

  submitServiceLog() {
    this.serviceFormError.set('');

    if (!this.serviceDate) {
      this.serviceFormError.set('Tanggal service wajib diisi');
      return;
    }

    this.serviceSubmitting.set(true);
    const vehicleId = this.serviceVehicle().id;

    const payload = {
      vehicle_id: vehicleId,
      service_date: this.serviceDate,
      description: this.serviceDescription || null,
      status: this.serviceStatus,
    };

    const request = this.editingServiceId
      ? this.api.updateVehicleService(this.editingServiceId, payload)
      : this.api.createVehicleService(payload);

    request.subscribe({
      next: () => {
        this.serviceSubmitting.set(false);
        this.cancelServiceForm();
        this.loadServiceLogs(vehicleId);
        this.loadNextServiceMap(); // Refresh jadwal utama
      },
      error: (err) => {
        this.serviceFormError.set(
          err?.error?.messages?.error ?? 'Gagal menyimpan catatan service'
        );
        this.serviceSubmitting.set(false);
      },
    });
  }

  markServiceDone(s: any) {
    this.api.updateVehicleService(s.id, { status: 'done' }).subscribe({
      next: () => {
        this.loadServiceLogs(this.serviceVehicle().id);
        this.loadNextServiceMap(); // Refresh jadwal utama
      }
    });
  }

  confirmDeleteService(id: number) {
    this.deletingServiceId.set(id);
  }

  cancelDeleteService() {
    this.deletingServiceId.set(null);
  }

  doDeleteService(id: number) {
    const vehicleId = this.serviceVehicle().id;
    this.api.deleteVehicleService(id).subscribe({
      next: () => {
        this.deletingServiceId.set(null);
        this.loadServiceLogs(vehicleId);
        this.loadNextServiceMap(); // Refresh jadwal utama
      },
    });
  }

  serviceStatusLabel(status: string): string {
    const map: Record<string, string> = {
      scheduled: 'Terjadwal',
      done: 'Selesai',
      cancelled: 'Dibatalkan',
    };
    return map[status] ?? status;
  }

  serviceStatusClass(status: string): string {
    const map: Record<string, string> = {
      scheduled: 'badge-amber',
      done: 'badge-green',
      cancelled: 'badge-red',
    };
    return map[status] ?? '';
  }
}