import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';

type TabKey = 'semua' | 'pending' | 'approved' | 'rejected';

@Component({
  selector: 'app-approvals',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './approvals.html',
  styleUrl: './approvals.css',
})
export class Approvals implements OnInit {
  private api = inject(Api);
  auth = inject(Auth);

  allApprovals = signal<any[]>([]);
  loading = signal(true);
  errorMsg = signal('');
  actionMsg = signal('');

  activeTab = signal<TabKey>('semua');
  searchTerm = '';
  sortDesc = signal(true);
  page = signal(1);
  pageSize = 5;

  showRejectFor = signal<number | null>(null);
  rejectNotes = '';
  processingId = signal<number | null>(null);

  detailItem = signal<any | null>(null);

  totalPending = computed(() => this.allApprovals().filter(a => a.status === 'pending').length);
  totalApproved = computed(() => this.allApprovals().filter(a => a.status === 'approved').length);
  totalRejected = computed(() => this.allApprovals().filter(a => a.status === 'rejected').length);
  totalAll = computed(() => this.allApprovals().length);

  filteredApprovals = computed(() => {
    let list = this.allApprovals();

    if (this.activeTab() !== 'semua') {
      list = list.filter(a => a.status === this.activeTab());
    }

    const term = this.searchTerm.trim().toLowerCase();
    if (term) {
      list = list.filter(a =>
        (a.booking_code ?? '').toLowerCase().includes(term) ||
        (a.vehicle_name ?? '').toLowerCase().includes(term) ||
        (a.destination ?? '').toLowerCase().includes(term) ||
        (a.purpose ?? '').toLowerCase().includes(term)
      );
    }

    list = [...list].sort((a, b) => {
      const da = new Date(a.start_date).getTime();
      const db = new Date(b.start_date).getTime();
      return this.sortDesc() ? db - da : da - db;
    });

    return list;
  });

  totalPages = computed(() => Math.max(1, Math.ceil(this.filteredApprovals().length / this.pageSize)));

  pagedApprovals = computed(() => {
    const start = (this.page() - 1) * this.pageSize;
    return this.filteredApprovals().slice(start, start + this.pageSize);
  });

  ngOnInit() {
    this.loadApprovals();
  }

  loadApprovals() {
    const user = this.auth.currentUser();
    if (!user) return;

    this.loading.set(true);
    this.api.getApprovals(Number(user.id)).subscribe({
      next: (res) => {
        this.allApprovals.set(res.data ?? []);
        this.loading.set(false);
      },
      error: () => {
        this.errorMsg.set('Gagal memuat data approval');
        this.loading.set(false);
      },
    });
  }

  setTab(tab: TabKey) {
    this.activeTab.set(tab);
    this.page.set(1);
  }

  onSearchChange() {
    this.page.set(1);
  }

  toggleSort() {
    this.sortDesc.update(v => !v);
  }

  goToPage(p: number) {
    if (p < 1 || p > this.totalPages()) return;
    this.page.set(p);
  }

  approve(id: number) {
    this.processingId.set(id);
    this.actionMsg.set('');
    this.errorMsg.set('');

    this.api.approveBooking(id).subscribe({
      next: () => {
        this.actionMsg.set('Booking berhasil disetujui');
        this.processingId.set(null);
        this.loadApprovals();
      },
      error: (err) => {
        this.errorMsg.set(err?.error?.messages?.error ?? 'Gagal menyetujui booking');
        this.processingId.set(null);
      },
    });
  }

  openReject(id: number) {
    this.showRejectFor.set(id);
    this.rejectNotes = '';
  }

  cancelReject() {
    this.showRejectFor.set(null);
    this.rejectNotes = '';
  }

  confirmReject(id: number) {
    this.processingId.set(id);
    this.actionMsg.set('');

    this.api.rejectBooking(id, this.rejectNotes).subscribe({
      next: () => {
        this.actionMsg.set('Booking berhasil ditolak');
        this.processingId.set(null);
        this.showRejectFor.set(null);
        this.loadApprovals();
      },
      error: (err) => {
        this.errorMsg.set(err?.error?.messages?.error ?? 'Gagal menolak booking');
        this.processingId.set(null);
      },
    });
  }

  openDetail(item: any) {
    this.detailItem.set(item);
  }

  closeDetail() {
    this.detailItem.set(null);
  }

  statusLabel(status: string): string {
    const map: Record<string, string> = {
      pending: 'Menunggu',
      approved: 'Disetujui',
      rejected: 'Ditolak',
    };
    return map[status] ?? status;
  }

  statusClass(status: string): string {
    const map: Record<string, string> = {
      pending: 'badge-amber',
      approved: 'badge-green',
      rejected: 'badge-red',
    };
    return map[status] ?? '';
  }

  daysUntil(dateStr: string): number {
    const diff = new Date(dateStr).getTime() - new Date().setHours(0, 0, 0, 0);
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
  }
}