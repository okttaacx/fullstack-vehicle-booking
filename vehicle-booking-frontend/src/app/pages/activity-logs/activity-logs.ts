import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DatePipe } from '@angular/common';
import { Api } from '../../core/api';

@Component({
  selector: 'app-activity-logs',
  standalone: true,
  imports: [FormsModule, DatePipe],
  templateUrl: './activity-logs.html',
  styleUrl: './activity-logs.css',
})
export class ActivityLogs implements OnInit {
  private api = inject(Api);

  logs = signal<any[]>([]);
  loading = signal(true);
  errorMsg = signal('');

  searchTerm = '';
  filterAction = '';
  filterStart = '';
  filterEnd = '';

  filteredLogs = computed(() => {
    let list = this.logs();

    const term = this.searchTerm.trim().toLowerCase();
    if (term) {
      list = list.filter(l =>
        (l.user_name ?? '').toLowerCase().includes(term) ||
        (l.description ?? '').toLowerCase().includes(term) ||
        (l.action ?? '').toLowerCase().includes(term)
      );
    }

    return list;
  });

  uniqueActions = computed(() => {
    const actions = new Set(this.logs().map(l => l.action));
    return Array.from(actions).sort();
  });

  ngOnInit() {
    this.loadLogs();
  }

  loadLogs() {
    this.loading.set(true);
    this.errorMsg.set('');

    this.api.getActivityLogs({
      action: this.filterAction || undefined,
      start: this.filterStart || undefined,
      end: this.filterEnd || undefined,
    }).subscribe({
      next: (res) => {
        this.logs.set(res.data ?? []);
        this.loading.set(false);
      },
      error: () => {
        this.errorMsg.set('Gagal memuat riwayat aktivitas');
        this.loading.set(false);
      },
    });
  }

  applyFilter() {
    this.loadLogs();
  }

  clearFilter() {
    this.filterAction = '';
    this.filterStart = '';
    this.filterEnd = '';
    this.loadLogs();
  }

  actionLabel(action: string): string {
    const map: Record<string, string> = {
      login_success: 'Login Berhasil',
      login_failed: 'Login Gagal',
      logout: 'Logout',
      change_password: 'Ganti Password',
      create_booking: 'Buat Pemesanan',
      update_booking: 'Ubah Pemesanan',
      delete_booking: 'Hapus Pemesanan',
      complete_booking: 'Selesaikan Pemesanan',
      approve_booking: 'Setujui Pemesanan',
      reject_booking: 'Tolak Pemesanan',
      create_vehicle: 'Tambah Kendaraan',
      update_vehicle: 'Ubah Kendaraan',
      delete_vehicle: 'Hapus Kendaraan',
      create_driver: 'Tambah Driver',
      update_driver: 'Ubah Driver',
      delete_driver: 'Hapus Driver',
      create_user: 'Tambah User',
      update_user: 'Ubah User',
      delete_user: 'Hapus User',
    };
    return map[action] ?? action;
  }

  actionClass(action: string): string {
    if (action.startsWith('delete_') || action === 'reject_booking' || action === 'login_failed') return 'badge-red';
    if (action.startsWith('create_') || action === 'approve_booking' || action === 'login_success') return 'badge-green';
    if (action.startsWith('update_') || action === 'change_password') return 'badge-blue';
    return 'badge-gray';
  }

  initials(name: string): string {
    return (name ?? '?').charAt(0).toUpperCase();
  }
}