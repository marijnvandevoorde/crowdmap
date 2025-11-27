import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap, catchError, of } from 'rxjs';

export interface User {
  id: number;
  email: string;
  name: string;
}

interface LoginResponse {
  user: User;
}

interface ErrorResponse {
  error: string;
}

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly apiUrl = '/api';

  currentUser = signal<User | null>(null);
  isAuthenticated = signal(false);

  constructor(private http: HttpClient) {}

  login(email: string, password: string): Observable<LoginResponse | ErrorResponse> {
    return this.http.post<LoginResponse>(
      `${this.apiUrl}/login`,
      { email, password },
      { withCredentials: true }
    ).pipe(
      tap((response) => {
        if ('user' in response) {
          this.currentUser.set(response.user);
          this.isAuthenticated.set(true);
        }
      })
    );
  }

  logout(): Observable<void> {
    return this.http.post<void>(
      `${this.apiUrl}/logout`,
      {},
      { withCredentials: true }
    ).pipe(
      tap(() => {
        this.currentUser.set(null);
        this.isAuthenticated.set(false);
      })
    );
  }

  checkAuth(): Observable<LoginResponse | null> {
    return this.http.get<LoginResponse>(
      `${this.apiUrl}/me`,
      { withCredentials: true }
    ).pipe(
      tap((response) => {
        this.currentUser.set(response.user);
        this.isAuthenticated.set(true);
      }),
      catchError(() => {
        this.currentUser.set(null);
        this.isAuthenticated.set(false);
        return of(null);
      })
    );
  }
}
