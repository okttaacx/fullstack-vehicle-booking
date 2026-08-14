import { Injectable, inject, signal, PLATFORM_ID } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';

export interface CurrentUser {
  id: string;
  name: string;
  username: string;
  role: 'admin' | 'approver';
  level: number | null;
}

@Injectable({
  providedIn: 'root',
})
export class Auth {
  private http = inject(HttpClient);
  private router = inject(Router);
  private platformId = inject(PLATFORM_ID);
  private apiUrl = 'http://localhost:8080/api';

  private isBrowser = isPlatformBrowser(this.platformId);

  currentUser = signal<CurrentUser | null>(this.loadUser());

  private loadUser(): CurrentUser | null {
    if (!this.isBrowser) {
      return null;
    }
    const raw = localStorage.getItem('vb_user');
    return raw ? JSON.parse(raw) : null;
  }

  isLoggedIn(): boolean {
    return this.currentUser() !== null;
  }

  isAdmin(): boolean {
    return this.currentUser()?.role === 'admin';
  }

  isApprover(): boolean {
    return this.currentUser()?.role === 'approver';
  }

  login(username: string, password: string) {
    return this.http.post<any>(`${this.apiUrl}/login`, { username, password });
  }

  setSession(user: CurrentUser) {
    if (this.isBrowser) {
      localStorage.setItem('vb_user', JSON.stringify(user));
    }
    this.currentUser.set(user);
  }

  logout() {
    if (this.isBrowser) {
      localStorage.removeItem('vb_user');
    }
    this.currentUser.set(null);
    this.router.navigate(['/login']);
  }
}