import { Component, OnInit, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { DatePipe } from '@angular/common';
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
}
