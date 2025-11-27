import { Component, OnInit, OnDestroy, signal, computed } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { NgTemplateOutlet } from '@angular/common';
import { MindmapService, Statement, Mindmap } from '../../core/mindmap';
import { AuthService } from '../../core/auth';

interface TreeNode {
  statement: Statement;
  children: TreeNode[];
}

@Component({
  selector: 'app-moderator',
  imports: [NgTemplateOutlet],
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

  private pollInterval: ReturnType<typeof setInterval> | null = null;

  // Always build tree from root to show full mindmap
  tree = computed(() => {
    const stmts = this.statements();
    if (stmts.length === 0) return null;

    const root = stmts.find(s => s.parentId === null);
    if (!root) return null;

    return this.buildTree(stmts, root.id);
  });

  // Get the path from root to focused statement
  focusPath = computed(() => {
    const focusId = this.focusedStatementId();
    if (!focusId) return new Set<number>();

    const stmts = this.statements();
    const path = new Set<number>();
    let current: Statement | undefined = stmts.find(s => s.id === focusId);

    while (current) {
      path.add(current.id);
      current = current.parentId ? stmts.find(s => s.id === current!.parentId) : undefined;
    }

    return path;
  });

  rootStatement = computed(() => {
    const stmts = this.statements();
    return stmts.find(s => s.parentId === null) ?? null;
  });

  focusedStatement = computed(() => {
    const focusId = this.focusedStatementId();
    if (!focusId) return this.rootStatement();
    return this.statements().find(s => s.id === focusId) ?? null;
  });

  contributorUrl = signal('');

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

  isInFocusPath(statementId: number): boolean {
    return this.focusPath().has(statementId);
  }

  private buildTree(statements: Statement[], rootId: number): TreeNode | null {
    const root = statements.find(s => s.id === rootId);
    if (!root) return null;

    const children = statements.filter(s => s.parentId === rootId);

    return {
      statement: root,
      children: children.map(child => this.buildTree(statements, child.id)!).filter(Boolean),
    };
  }
}
