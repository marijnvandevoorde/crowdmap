import { Component, OnInit, OnDestroy, signal, computed } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { MindmapService, Statement, Mindmap } from '../../core/mindmap';
import { AuthService } from '../../core/auth';
import { MindmapGraphComponent } from './mindmap-graph/mindmap-graph';

@Component({
  selector: 'app-moderator',
  imports: [MindmapGraphComponent],
  templateUrl: './moderator.html',
  styleUrl: './moderator.scss',
})
export class ModeratorComponent implements OnInit, OnDestroy {
  uuid = '';
  mindmap = signal<Mindmap | null>(null);
  statements = signal<Statement[]>([]);
  loading = signal(true);
  error = signal<string | null>(null);
  focusedStatementId = signal<number | null>(null);
  contributorUrl = signal('');

  private pollInterval: ReturnType<typeof setInterval> | null = null;

  rootStatement = computed(() => {
    const stmts = this.statements();
    return stmts.find(s => s.parentId === null) ?? null;
  });

  focusedStatement = computed(() => {
    const focusId = this.focusedStatementId();
    if (!focusId) return this.rootStatement();
    return this.statements().find(s => s.id === focusId) ?? null;
  });

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private mindmapService: MindmapService,
    private authService: AuthService,
  ) {}

  ngOnInit(): void {
    this.uuid = this.route.snapshot.paramMap.get('uuid') ?? '';
    this.loadMindmap();
    this.startPolling();
  }

  ngOnDestroy(): void {
    this.stopPolling();
  }

  loadMindmap(): void {
    this.mindmapService.getMindmapForModerator(this.uuid).subscribe({
      next: (response) => {
        this.mindmap.set(response.mindmap);
        this.statements.set(response.statements);
        // Sync focused statement from backend
        this.focusedStatementId.set(response.mindmap.focusedStatementId);
        // Set contributor URL from backend (only on initial load)
        if (response.contributorUrl && !this.contributorUrl()) {
          this.contributorUrl.set(response.contributorUrl);
        }
        this.loading.set(false);
      },
      error: (err) => {
        if (err.status === 401 || err.status === 403) {
          this.router.navigate(['/admin']);
        } else {
          this.error.set('Failed to load mindmap');
        }
        this.loading.set(false);
      },
    });
  }

  startPolling(): void {
    this.pollInterval = setInterval(() => {
      if (!this.loading()) {
        this.mindmapService.getMindmapForModerator(this.uuid).subscribe({
          next: (response) => {
            this.statements.set(response.statements);
          },
        });
      }
    }, 2000);
  }

  stopPolling(): void {
    if (this.pollInterval) {
      clearInterval(this.pollInterval);
      this.pollInterval = null;
    }
  }

  focusOnStatement(statementId: number): void {
    this.mindmapService.setFocusedStatement(this.uuid, statementId).subscribe({
      next: () => {
        this.focusedStatementId.set(statementId);
      },
    });
  }

  resetFocus(): void {
    this.mindmapService.setFocusedStatement(this.uuid, null).subscribe({
      next: () => {
        this.focusedStatementId.set(null);
      },
    });
  }

  goBack(): void {
    const focused = this.focusedStatement();
    if (focused && focused.parentId) {
      this.mindmapService.setFocusedStatement(this.uuid, focused.parentId).subscribe({
        next: () => {
          this.focusedStatementId.set(focused.parentId);
        },
      });
    } else {
      this.router.navigate(['/admin/dashboard']);
    }
  }

  onNodeClick(statementId: number): void {
    this.focusOnStatement(statementId);
  }
}
