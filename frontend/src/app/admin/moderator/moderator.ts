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
  selectedNodeIds = signal<Set<number>>(new Set());

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

  canMerge = computed(() => {
    const selected = this.selectedNodeIds();
    if (selected.size < 2) return false;

    // Check all selected nodes are siblings (same parent)
    const stmts = this.statements();
    const selectedStatements = stmts.filter(s => selected.has(s.id));
    const parentIds = new Set(selectedStatements.map(s => s.parentId));
    return parentIds.size === 1 && !parentIds.has(null); // All same parent, not root
  });

  canDelete = computed(() => {
    const selected = this.selectedNodeIds();
    if (selected.size < 1) return false;

    // Check none of the selected nodes is the root
    const stmts = this.statements();
    const selectedStatements = stmts.filter(s => selected.has(s.id));
    return selectedStatements.every(s => s.parentId !== null);
  });

  selectedStatements = computed(() => {
    const selected = this.selectedNodeIds();
    return this.statements().filter(s => selected.has(s.id));
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
    this.clearSelection();
    this.focusOnStatement(statementId);
  }

  onNodeSelect(statementId: number): void {
    const current = this.selectedNodeIds();
    const newSet = new Set(current);

    if (newSet.has(statementId)) {
      newSet.delete(statementId);
    } else {
      // Only allow selecting siblings
      const clickedStatement = this.statements().find(s => s.id === statementId);
      if (clickedStatement && clickedStatement.parentId !== null) {
        // If we have existing selections, check they share the same parent
        if (newSet.size > 0) {
          const firstSelectedId = newSet.values().next().value;
          const firstSelected = this.statements().find(s => s.id === firstSelectedId);
          if (firstSelected?.parentId !== clickedStatement.parentId) {
            // Different parent, start new selection
            newSet.clear();
          }
        }
        newSet.add(statementId);
      }
    }

    this.selectedNodeIds.set(newSet);
  }

  clearSelection(): void {
    this.selectedNodeIds.set(new Set());
  }

  mergeSelected(): void {
    const selected = Array.from(this.selectedNodeIds());
    if (selected.length < 2) return;

    this.mindmapService.mergeStatements(this.uuid, selected).subscribe({
      next: (response) => {
        this.statements.set(response.statements);
        this.clearSelection();
      },
      error: (err) => {
        console.error('Failed to merge statements', err);
      },
    });
  }

  deleteSelected(): void {
    const selected = Array.from(this.selectedNodeIds());
    if (selected.length < 1) return;

    this.mindmapService.deleteStatements(this.uuid, selected).subscribe({
      next: (response) => {
        this.statements.set(response.statements);
        this.clearSelection();
        // Reset focus if the focused statement was deleted
        const focusId = this.focusedStatementId();
        if (focusId && !response.statements.some(s => s.id === focusId)) {
          this.focusedStatementId.set(null);
        }
      },
      error: (err) => {
        console.error('Failed to delete statements', err);
      },
    });
  }
}
