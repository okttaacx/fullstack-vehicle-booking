import { Component, inject, signal } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { Auth } from '../../core/auth';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './main-layout.html',
  styleUrl: './main-layout.css',
})
export class MainLayout {
  auth = inject(Auth);
  userMenuOpen = signal(false);

  get initial(): string {
    const name = this.auth.currentUser()?.name ?? '?';
    return name.charAt(0).toUpperCase();
  }

  toggleUserMenu() {
    this.userMenuOpen.update(v => !v);
  }
}