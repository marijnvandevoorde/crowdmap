import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from './auth';
import { map, catchError, of } from 'rxjs';

export const authGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  // If already authenticated, allow
  if (authService.isAuthenticated()) {
    return true;
  }

  // Otherwise check with server
  return authService.checkAuth().pipe(
    map((response) => {
      if (response) {
        return true;
      }
      router.navigate(['/admin']);
      return false;
    }),
    catchError(() => {
      router.navigate(['/admin']);
      return of(false);
    })
  );
};
