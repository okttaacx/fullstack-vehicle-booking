import { Component, OnInit, OnDestroy, inject, signal, computed, ViewChild, ElementRef } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { Auth } from '../../core/auth';
import { Api } from '../../core/api';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, FormsModule],
  templateUrl: './main-layout.html',
  styleUrl: './main-layout.css',
})
export class MainLayout implements OnInit, OnDestroy {
  auth = inject(Auth);
  private api = inject(Api);

  userMenuOpen = signal(false);
  showPasswordModal = signal(false);

  // ---- Notifikasi ----
  notifOpen = signal(false);
  notifItems = signal<any[]>([]);
  notifCount = computed(() => this.notifItems().length);
  notifPosition = signal({ top: 0, left: 0, width: 0 });
  private notifTimer: any;

  @ViewChild('notifBellBtn') notifBellBtn!: ElementRef<HTMLButtonElement>;

  oldPassword = '';
  newPassword = '';
  confirmPassword = '';
  changingPassword = signal(false);
  passwordError = signal('');
  passwordSuccess = signal('');

  get initial(): string {
    const name = this.auth.currentUser()?.name ?? '?';
    return name.charAt(0).toUpperCase();
  }

  ngOnInit() {
    this.loadNotifications();
    this.notifTimer = setInterval(() => this.loadNotifications(), 30000);
  }

  ngOnDestroy() {
    if (this.notifTimer) {
      clearInterval(this.notifTimer);
    }
  }

  toggleNotif() {
    if (!this.notifOpen()) {
      const rect = this.notifBellBtn.nativeElement.getBoundingClientRect();
      this.notifPosition.set({
        top: rect.bottom + 8,
        left: rect.left,
        width: rect.width,
      });
    }
    this.notifOpen.update(v => !v);
    this.userMenuOpen.set(false);
  }

  closeNotif() {
    this.notifOpen.set(false);
  }

  loadNotifications() {
    const user = this.auth.currentUser();
    if (!user) return;

    if (this.auth.isApprover()) {
      this.api.getApprovals(Number(user.id)).subscribe({
        next: (res) => {
          // Filter dengan tambahan && a.actionable === true
          const pending = (res.data ?? []).filter((a: any) => a.status === 'pending' && a.actionable === true);
          this.notifItems.set(
            pending.map((a: any) => ({
              id: 'approval-' + a.id,
              icon: 'approval',
              title: a.booking_code ?? `Booking #${a.booking_id}`,
              subtitle: `${a.vehicle_name ?? 'Kendaraan'} — perlu persetujuan Anda`,
              date: a.created_at,
              link: '/approvals',
            }))
          );
        },
        error: () => {},
      });
    } else if (this.auth.isAdmin()) {
      this.api.getBookings().subscribe({
        next: (res) => {
          const bookings = res.data ?? [];
          const now = new Date().getTime();
          const threeDaysMs = 3 * 24 * 60 * 60 * 1000;

          const ready = bookings
            .filter((b: any) => b.status === 'approved_l2')
            .map((b: any) => ({
              id: 'ready-' + b.id,
              icon: 'ready',
              title: b.booking_code,
              subtitle: `${b.vehicle_name ?? ''} — siap ditandai selesai`,
              date: b.updated_at,
              link: '/bookings',
            }));

          const rejected = bookings
            .filter((b: any) => {
              if (b.status !== 'rejected') return false;
              const updated = new Date(b.updated_at).getTime();
              return now - updated <= threeDaysMs;
            })
            .map((b: any) => ({
              id: 'rejected-' + b.id,
              icon: 'rejected',
              title: b.booking_code,
              subtitle: `${b.vehicle_name ?? ''} — pemesanan ditolak`,
              date: b.updated_at,
              link: '/bookings',
            }));

          this.notifItems.set(
            [...ready, ...rejected].sort(
              (a, b) => new Date(b.date).getTime() - new Date(a.date).getTime()
            )
          );
        },
        error: () => {},
      });
    }
  }

  toggleUserMenu() {
    this.userMenuOpen.update(v => !v);
    this.notifOpen.set(false);
  }

  openPasswordModal() {
    this.showPasswordModal.set(true);
    this.userMenuOpen.set(false);
    this.oldPassword = '';
    this.newPassword = '';
    this.confirmPassword = '';
    this.passwordError.set('');
    this.passwordSuccess.set('');
  }

  closePasswordModal() {
    this.showPasswordModal.set(false);
  }

  submitPasswordChange() {
    this.passwordError.set('');
    this.passwordSuccess.set('');

    if (!this.oldPassword || !this.newPassword || !this.confirmPassword) {
      this.passwordError.set('Semua field wajib diisi');
      return;
    }

    if (this.newPassword.length < 6) {
      this.passwordError.set('Password baru minimal 6 karakter');
      return;
    }

    if (this.newPassword !== this.confirmPassword) {
      this.passwordError.set('Konfirmasi password tidak cocok');
      return;
    }

    const userId = this.auth.currentUser()?.id;
    if (!userId) {
      this.passwordError.set('Sesi login tidak ditemukan');
      return;
    }

    this.changingPassword.set(true);

    this.api.changePassword(userId, this.oldPassword, this.newPassword).subscribe({
      next: () => {
        this.passwordSuccess.set('Password berhasil diubah');
        this.changingPassword.set(false);
        this.oldPassword = '';
        this.newPassword = '';
        this.confirmPassword = '';
        setTimeout(() => this.showPasswordModal.set(false), 1500);
      },
      error: (err) => {
        this.passwordError.set(err?.error?.messages?.error ?? 'Gagal mengubah password');
        this.changingPassword.set(false);
      },
    });
  }
}