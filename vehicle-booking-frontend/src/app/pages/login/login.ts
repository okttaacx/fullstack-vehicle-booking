import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { Auth } from '../../core/auth';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './login.html',
})
export class Login {
  private auth = inject(Auth);
  private router = inject(Router);

  username = '';
  password = '';
  loginError = '';
  loading = false;

  doLogin() {
    this.loading = true;
    this.loginError = '';

    this.auth.login(this.username, this.password).subscribe({
      next: (res) => {
        this.auth.setSession(res.data.user ?? res.data);
        this.router.navigate(['/dashboard']);
      },
      error: () => {
        this.loginError = 'Username atau password salah';
        this.loading = false;
      },
    });
  }
}