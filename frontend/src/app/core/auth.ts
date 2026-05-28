import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap, catchError, of } from 'rxjs';

export interface User {
  id: number;
  email: string;
  name: string;
  mfaEnabled: boolean;
}

interface UserResponse {
  user: User;
}

interface MfaRequiredResponse {
  mfaRequired: true;
}

interface ErrorResponse {
  error: string;
}

export interface MfaSetupResponse {
  secret: string;
  otpauthUri: string;
}

export interface MfaConfirmResponse {
  recoveryCodes: string[];
}

export type LoginResult = UserResponse | MfaRequiredResponse | ErrorResponse;

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly apiUrl = '/api';

  currentUser = signal<User | null>(null);
  isAuthenticated = signal(false);

  constructor(private http: HttpClient) {}

  login(email: string, password: string): Observable<LoginResult> {
    return this.http.post<LoginResult>(
      `${this.apiUrl}/login`,
      { email, password },
      { withCredentials: true }
    ).pipe(
      tap((response) => this.applyUser(response))
    );
  }

  verifyMfa(code: string): Observable<UserResponse | ErrorResponse> {
    return this.http.post<UserResponse>(
      `${this.apiUrl}/login/mfa`,
      { code },
      { withCredentials: true }
    ).pipe(
      tap((response) => this.applyUser(response))
    );
  }

  register(email: string, name: string, password: string): Observable<UserResponse | ErrorResponse> {
    return this.http.post<UserResponse>(
      `${this.apiUrl}/register`,
      { email, name, password },
      { withCredentials: true }
    ).pipe(
      tap((response) => this.applyUser(response))
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

  checkAuth(): Observable<UserResponse | null> {
    return this.http.get<UserResponse>(
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

  setupMfa(): Observable<MfaSetupResponse> {
    return this.http.post<MfaSetupResponse>(
      `${this.apiUrl}/mfa/setup`,
      {},
      { withCredentials: true }
    );
  }

  confirmMfa(code: string): Observable<MfaConfirmResponse> {
    return this.http.post<MfaConfirmResponse>(
      `${this.apiUrl}/mfa/confirm`,
      { code },
      { withCredentials: true }
    ).pipe(
      tap(() => this.setMfaEnabled(true))
    );
  }

  disableMfa(password: string): Observable<{ success: boolean }> {
    return this.http.post<{ success: boolean }>(
      `${this.apiUrl}/mfa/disable`,
      { password },
      { withCredentials: true }
    ).pipe(
      tap(() => this.setMfaEnabled(false))
    );
  }

  private applyUser(response: LoginResult): void {
    if ('user' in response) {
      this.currentUser.set(response.user);
      this.isAuthenticated.set(true);
    }
  }

  private setMfaEnabled(enabled: boolean): void {
    const user = this.currentUser();
    if (user) {
      this.currentUser.set({ ...user, mfaEnabled: enabled });
    }
  }
}
