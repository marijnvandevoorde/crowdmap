import { Component, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth';

@Component({
  selector: 'app-login',
  imports: [FormsModule, RouterLink],
  templateUrl: './login.html',
  styleUrl: './login.scss',
})
export class LoginComponent {
  email = '';
  password = '';
  code = '';
  error = signal<string | null>(null);
  loading = signal(false);
  mfaStep = signal(false);
  useRecovery = signal(false);

  constructor(
    private authService: AuthService,
    private router: Router,
  ) {}

  onSubmit(): void {
    this.error.set(null);
    this.loading.set(true);

    this.authService.login(this.email, this.password).subscribe({
      next: (response) => {
        this.loading.set(false);
        if ('error' in response) {
          this.error.set(response.error);
        } else if ('mfaRequired' in response) {
          this.mfaStep.set(true);
        } else {
          this.router.navigate(['/admin/dashboard']);
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.error.set(err.error?.error || 'Login failed');
      },
    });
  }

  onVerifyMfa(): void {
    this.error.set(null);
    this.loading.set(true);

    this.authService.verifyMfa(this.code).subscribe({
      next: (response) => {
        this.loading.set(false);
        if ('error' in response) {
          this.error.set(response.error);
        } else {
          this.router.navigate(['/admin/dashboard']);
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.error.set(err.error?.error || 'Verification failed');
      },
    });
  }

  toggleRecovery(): void {
    this.useRecovery.set(!this.useRecovery());
    this.code = '';
    this.error.set(null);
  }
}
