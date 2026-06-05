import { Component, signal, ChangeDetectionStrategy } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth';

@Component({
  selector: 'app-register',
  imports: [FormsModule, RouterLink],
  templateUrl: './register.html',
  changeDetection: ChangeDetectionStrategy.Eager,
  styleUrl: './register.scss',
})
export class RegisterComponent {
  name = '';
  email = '';
  password = '';
  error = signal<string | null>(null);
  loading = signal(false);

  constructor(
    private authService: AuthService,
    private router: Router,
  ) {}

  onSubmit(): void {
    this.error.set(null);
    this.loading.set(true);

    this.authService.register(this.email, this.name, this.password).subscribe({
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
        this.error.set(err.error?.error || 'Registration failed');
      },
    });
  }
}
