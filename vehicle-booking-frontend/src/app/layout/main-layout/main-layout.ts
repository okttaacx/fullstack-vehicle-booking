import { Component, inject, signal } from '@angular/core';
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
export class MainLayout {
  auth = inject(Auth);
  private api = inject(Api);

  userMenuOpen = signal(false);
  showPasswordModal = signal(false);

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

  toggleUserMenu() {
    this.userMenuOpen.update(v => !v);
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