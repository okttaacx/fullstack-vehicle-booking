import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';

@Component({
  selector: 'app-vehicles',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './vehicles.html',
  styleUrl: './vehicles.css',
})
export class Vehicles implements OnInit {
  private api = inject(Api);
  auth = inject(Auth);

  vehicles = signal<any[]>([]);
  loading = signal(true);
  errorMsg = signal('');

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

  ngOnInit() {
    this.loadVehicles();
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
}