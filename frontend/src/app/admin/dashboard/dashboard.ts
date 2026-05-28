import { Component, OnInit, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { DatePipe } from '@angular/common';
import * as QRCode from 'qrcode';
import { MindmapService, MindmapSummary } from '../../core/mindmap';
import { AuthService } from '../../core/auth';

@Component({
  selector: 'app-dashboard',
  imports: [FormsModule, DatePipe],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.scss',
})
export class DashboardComponent implements OnInit {
  mindmaps = signal<MindmapSummary[]>([]);
  loading = signal(true);
  showCreateModal = signal(false);
  newQuestion = '';
  creating = signal(false);

  // MFA enrollment / management state
  mfaSecret = signal<string | null>(null);
  qrDataUrl = signal<string | null>(null);
  mfaCode = '';
  recoveryCodes = signal<string[] | null>(null);
  mfaError = signal<string | null>(null);
  mfaLoading = signal(false);
  showDisable = signal(false);
  disablePassword = '';

  constructor(
    private mindmapService: MindmapService,
    private authService: AuthService,
    private router: Router,
  ) {}

  ngOnInit(): void {
    this.loadMindmaps();
  }

  loadMindmaps(): void {
    this.loading.set(true);
    this.mindmapService.listMindmaps().subscribe({
      next: (response) => {
        this.mindmaps.set(response.mindmaps);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
      },
    });
  }

  openCreateModal(): void {
    this.newQuestion = '';
    this.showCreateModal.set(true);
  }

  closeCreateModal(): void {
    this.showCreateModal.set(false);
  }

  createMindmap(): void {
    if (!this.newQuestion.trim()) return;

    this.creating.set(true);
    this.mindmapService.createMindmap(this.newQuestion).subscribe({
      next: (response) => {
        this.creating.set(false);
        this.showCreateModal.set(false);
        this.router.navigate(['/admin/mindmap', response.mindmap.uuid]);
      },
      error: () => {
        this.creating.set(false);
      },
    });
  }

  openMindmap(uuid: string): void {
    this.router.navigate(['/admin/mindmap', uuid]);
  }

  logout(): void {
    this.authService.logout().subscribe(() => {
      this.router.navigate(['/admin']);
    });
  }

  get userName(): string {
    return this.authService.currentUser()?.name ?? '';
  }

  get mfaEnabled(): boolean {
    return this.authService.currentUser()?.mfaEnabled ?? false;
  }

  get enrolling(): boolean {
    return this.mfaSecret() !== null;
  }

  startMfaSetup(): void {
    this.mfaError.set(null);
    this.mfaLoading.set(true);
    this.authService.setupMfa().subscribe({
      next: async (response) => {
        this.mfaSecret.set(response.secret);
        this.mfaCode = '';
        try {
          this.qrDataUrl.set(await QRCode.toDataURL(response.otpauthUri));
        } catch {
          this.qrDataUrl.set(null);
        }
        this.mfaLoading.set(false);
      },
      error: (err) => {
        this.mfaLoading.set(false);
        this.mfaError.set(err.error?.error || 'Could not start MFA setup');
      },
    });
  }

  confirmMfaSetup(): void {
    this.mfaError.set(null);
    this.mfaLoading.set(true);
    this.authService.confirmMfa(this.mfaCode).subscribe({
      next: (response) => {
        this.mfaLoading.set(false);
        this.recoveryCodes.set(response.recoveryCodes);
        this.mfaSecret.set(null);
        this.qrDataUrl.set(null);
        this.mfaCode = '';
      },
      error: (err) => {
        this.mfaLoading.set(false);
        this.mfaError.set(err.error?.error || 'Invalid code');
      },
    });
  }

  cancelMfaSetup(): void {
    this.mfaSecret.set(null);
    this.qrDataUrl.set(null);
    this.mfaCode = '';
    this.mfaError.set(null);
  }

  dismissRecoveryCodes(): void {
    this.recoveryCodes.set(null);
  }

  startDisable(): void {
    this.showDisable.set(true);
    this.disablePassword = '';
    this.mfaError.set(null);
  }

  confirmDisable(): void {
    this.mfaError.set(null);
    this.mfaLoading.set(true);
    this.authService.disableMfa(this.disablePassword).subscribe({
      next: () => {
        this.mfaLoading.set(false);
        this.showDisable.set(false);
        this.disablePassword = '';
      },
      error: (err) => {
        this.mfaLoading.set(false);
        this.mfaError.set(err.error?.error || 'Could not disable MFA');
      },
    });
  }

  cancelDisable(): void {
    this.showDisable.set(false);
    this.disablePassword = '';
    this.mfaError.set(null);
  }
}
