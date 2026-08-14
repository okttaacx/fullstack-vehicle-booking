import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';

@Component({
  selector: 'app-approvals',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './approvals.html',
})
export class Approvals implements OnInit {
  private api = inject(Api);
  auth = inject(Auth);

  approvals = signal<any[]>([]);
  loading = signal(true);
  errorMsg = signal('');
  actionMsg = signal('');

  // Untuk modal reject
  showRejectFor = signal<number | null>(null);
  rejectNotes = '';
  processingId = signal<number | null>(null);

  ngOnInit() {
    this.loadApprovals();
  }

  loadApprovals() {
    const user = this.auth.currentUser();
    if (!user) return;

    this.loading.set(true);
    this.api.getApprovals(Number(user.id)).subscribe({
      next: (res) => {
        this.approvals.set(res.data ?? []);
        this.loading.set(false);
      },
      error: () => {
        this.errorMsg.set('Gagal memuat data approval');
        this.loading.set(false);
      },
    });
  }

  approve(id: number) {
    this.processingId.set(id);
    this.actionMsg.set('');

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
}