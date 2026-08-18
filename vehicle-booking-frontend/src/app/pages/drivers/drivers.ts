import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';

@Component({
  selector: 'app-drivers',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './drivers.html',
  styleUrl: './drivers.css',
})
export class Drivers implements OnInit {
  private api = inject(Api);
  auth = inject(Auth);

  drivers = signal<any[]>([]);
  loading = signal(true);
  errorMsg = signal('');

  searchTerm = '';

  showForm = signal(false);
  editingId: number | null = null;
  submitting = signal(false);
  formError = signal('');

  name = '';
  phone = '';
  licenseNumber = '';
  licenseExpiry = '';
  status = 'active';

  deletingId = signal<number | null>(null);

  filteredDrivers = computed(() => {
    const term = this.searchTerm.trim().toLowerCase();
    if (!term) return this.drivers();
    return this.drivers().filter(d =>
      (d.name ?? '').toLowerCase().includes(term) ||
      (d.license_number ?? '').toLowerCase().includes(term)
    );
  });

  ngOnInit() {
    this.loadDrivers();
  }

  loadDrivers() {
    this.loading.set(true);
    this.api.getDrivers().subscribe({
      next: (res) => {
        this.drivers.set(res.data ?? []);
        this.loading.set(false);
      },
      error: () => {
        this.errorMsg.set('Gagal memuat data driver');
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

  openEditForm(d: any) {
    this.editingId = d.id;
    this.name = d.name;
    this.phone = d.phone ?? '';
    this.licenseNumber = d.license_number ?? '';
    this.licenseExpiry = d.license_expiry ?? '';
    this.status = d.status ?? 'active';
    this.showForm.set(true);
    this.formError.set('');
  }

  cancelForm() {
    this.showForm.set(false);
    this.resetForm();
  }

  private resetForm() {
    this.name = '';
    this.phone = '';
    this.licenseNumber = '';
    this.licenseExpiry = '';
    this.status = 'active';
  }

  submitDriver() {
    this.formError.set('');

    if (!this.name.trim()) {
      this.formError.set('Nama driver wajib diisi');
      return;
    }

    this.submitting.set(true);

    const payload = {
      name: this.name,
      phone: this.phone || null,
      license_number: this.licenseNumber || null,
      license_expiry: this.licenseExpiry || null,
      status: this.status,
    };

    const request = this.editingId
      ? this.api.updateDriver(this.editingId, payload)
      : this.api.createDriver(payload);

    request.subscribe({
      next: () => {
        this.submitting.set(false);
        this.showForm.set(false);
        this.resetForm();
        this.loadDrivers();
      },
      error: (err) => {
        this.formError.set(err?.error?.messages?.error ?? 'Gagal menyimpan data driver');
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
    this.api.deleteDriver(id).subscribe({
      next: () => {
        this.deletingId.set(null);
        this.loadDrivers();
      },
      error: () => {
        this.errorMsg.set('Gagal menghapus driver');
        this.deletingId.set(null);
      },
    });
  }

  statusLabel(status: string): string {
    return status === 'active' ? 'Aktif' : 'Nonaktif';
  }

  licenseWarning(expiry: string | null): 'expired' | 'soon' | 'ok' | null {
    if (!expiry) return null;
    const days = Math.ceil((new Date(expiry).getTime() - new Date().setHours(0, 0, 0, 0)) / (1000 * 60 * 60 * 24));
    if (days < 0) return 'expired';
    if (days <= 30) return 'soon';
    return 'ok';
  }

  licenseDaysLeft(expiry: string): number {
    return Math.ceil((new Date(expiry).getTime() - new Date().setHours(0, 0, 0, 0)) / (1000 * 60 * 60 * 24));
  }

  initials(name: string): string {
    return (name ?? '?').charAt(0).toUpperCase();
  }
}