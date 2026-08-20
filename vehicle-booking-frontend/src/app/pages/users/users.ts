import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';

@Component({
  selector: 'app-users',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './users.html',
  styleUrl: './users.css',
})
export class Users implements OnInit {
  private api = inject(Api);
  auth = inject(Auth);

  users = signal<any[]>([]);
  loading = signal(true);
  errorMsg = signal('');

  searchTerm = '';

  showForm = signal(false);
  editingId: number | null = null;
  submitting = signal(false);
  formError = signal('');

  name = '';
  username = '';
  password = '';
  role = 'approver';
  level = '1';

  deletingId = signal<number | null>(null);
  deleteError = signal('');

  filteredUsers = computed(() => {
    const term = this.searchTerm.trim().toLowerCase();
    if (!term) return this.users();
    return this.users().filter(u =>
      (u.name ?? '').toLowerCase().includes(term) ||
      (u.username ?? '').toLowerCase().includes(term)
    );
  });

  ngOnInit() {
    this.loadUsers();
  }

  loadUsers() {
    this.loading.set(true);
    this.api.getAllUsers().subscribe({
      next: (res) => {
        this.users.set(res.data ?? []);
        this.loading.set(false);
      },
      error: () => {
        this.errorMsg.set('Gagal memuat data user');
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

  openEditForm(u: any) {
    this.editingId = u.id;
    this.name = u.name;
    this.username = u.username;
    this.password = '';
    this.role = u.role;
    this.level = u.level ? String(u.level) : '1';
    this.showForm.set(true);
    this.formError.set('');
  }

  cancelForm() {
    this.showForm.set(false);
    this.resetForm();
  }

  private resetForm() {
    this.name = '';
    this.username = '';
    this.password = '';
    this.role = 'approver';
    this.level = '1';
  }

  submitUser() {
    this.formError.set('');

    if (!this.name.trim() || !this.username.trim()) {
      this.formError.set('Nama dan username wajib diisi');
      return;
    }

    if (!this.editingId && !this.password.trim()) {
      this.formError.set('Password wajib diisi untuk user baru');
      return;
    }

    this.submitting.set(true);

    const payload: any = {
      name: this.name,
      username: this.username,
      role: this.role,
      level: this.role === 'approver' ? this.level : null,
    };

    if (this.password.trim()) {
      payload.password = this.password;
    }

    const request = this.editingId
      ? this.api.updateUser(this.editingId, payload)
      : this.api.createUser(payload);

    request.subscribe({
      next: () => {
        this.submitting.set(false);
        this.showForm.set(false);
        this.resetForm();
        this.loadUsers();
      },
      error: (err) => {
        this.formError.set(err?.error?.messages?.error ?? 'Gagal menyimpan data user');
        this.submitting.set(false);
      },
    });
  }

  confirmDelete(id: number) {
    this.deletingId.set(id);
    this.deleteError.set('');
  }

  cancelDelete() {
    this.deletingId.set(null);
  }

  doDelete(id: number) {
    this.api.deleteUser(id).subscribe({
      next: () => {
        this.deletingId.set(null);
        this.loadUsers();
      },
      error: (err) => {
        this.deleteError.set(err?.error?.messages?.error ?? 'Gagal menghapus user');
      },
    });
  }

  roleLabel(role: string): string {
    return role === 'admin' ? 'Admin' : 'Approver';
  }

  initials(name: string): string {
    return (name ?? '?').charAt(0).toUpperCase();
  }
}