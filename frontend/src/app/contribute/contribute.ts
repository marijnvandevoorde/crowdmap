import { Component, OnInit, OnDestroy, AfterViewInit, signal, computed, ViewChild, ElementRef } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { MindmapService, Statement, Mindmap } from '../core/mindmap';

@Component({
  selector: 'app-contribute',
  imports: [FormsModule],
  templateUrl: './contribute.html',
  styleUrl: './contribute.scss',
})
export class ContributeComponent implements OnInit, OnDestroy, AfterViewInit {
  @ViewChild('reactionInput') reactionInput!: ElementRef<HTMLInputElement>;
  uuid = '';
  mindmap = signal<Mindmap | null>(null);
  statements = signal<Statement[]>([]);
  loading = signal(true);
  error = signal<string | null>(null);
  newReaction = '';
  submitting = signal(false);
  sessionId = '';

  private pollInterval: ReturnType<typeof setInterval> | null = null;

  rootStatement = computed(() => {
    return this.statements().find(s => s.parentId === null) ?? null;
  });

  // The focused statement is controlled by the moderator
  focusedStatement = computed(() => {
    const mindmap = this.mindmap();
    const stmts = this.statements();
    if (!mindmap) return null;

    // If moderator has set a focus, use that; otherwise use root
    if (mindmap.focusedStatementId) {
      return stmts.find(s => s.id === mindmap.focusedStatementId) ?? null;
    }
    return this.rootStatement();
  });

  reactions = computed(() => {
    const focused = this.focusedStatement();
    if (!focused) return [];
    return this.statements().filter(s => s.parentId === focused.id);
  });

  constructor(
    private route: ActivatedRoute,
    private mindmapService: MindmapService,
  ) {
    this.sessionId = this.getOrCreateSessionId();
  }

  ngOnInit(): void {
    this.uuid = this.route.snapshot.paramMap.get('uuid') ?? '';
    this.loadMindmap();
    this.startPolling();
  }

  ngAfterViewInit(): void {
    this.focusInput();
  }

  ngOnDestroy(): void {
    this.stopPolling();
  }

  focusInput(): void {
    setTimeout(() => this.reactionInput?.nativeElement?.focus(), 0);
  }

  loadMindmap(): void {
    this.mindmapService.getMindmap(this.uuid).subscribe({
      next: (response) => {
        this.mindmap.set(response.mindmap);
        this.statements.set(response.statements);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Mindmap not found');
        this.loading.set(false);
      },
    });
  }

  startPolling(): void {
    this.pollInterval = setInterval(() => {
      if (!this.loading()) {
        this.mindmapService.getMindmap(this.uuid).subscribe({
          next: (response) => {
            this.mindmap.set(response.mindmap);
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

  submitReaction(): void {
    const focused = this.focusedStatement();
    if (!focused || !this.newReaction.trim()) return;

    this.submitting.set(true);
    this.mindmapService.addStatement(this.uuid, focused.id, this.newReaction.trim(), this.sessionId).subscribe({
      next: (response) => {
        this.statements.update(stmts => [...stmts, response.statement]);
        this.newReaction = '';
        this.submitting.set(false);
        this.focusInput();
      },
      error: () => {
        this.submitting.set(false);
      },
    });
  }

  toggleVote(statement: Statement): void {
    // Cannot vote on own statements
    if (this.isOwnStatement(statement)) return;

    this.mindmapService.toggleVote(statement.id, this.sessionId).subscribe({
      next: (response) => {
        this.statements.update(stmts =>
          stmts.map(s =>
            s.id === statement.id ? { ...s, voteCount: response.voteCount } : s
          )
        );
        this.focusInput();
      },
    });
  }

  isOwnStatement(statement: Statement): boolean {
    return statement.sessionId === this.sessionId;
  }

  hasVoted(statement: Statement): boolean {
    // This would need to be tracked - for now just visual feedback on click
    return false;
  }

  private getOrCreateSessionId(): string {
    const key = 'crowdmap_session_id';
    let sessionId = localStorage.getItem(key);
    if (!sessionId) {
      sessionId = this.generateSessionId();
      localStorage.setItem(key, sessionId);
    }
    return sessionId;
  }

  private generateSessionId(): string {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
      const r = (Math.random() * 16) | 0;
      const v = c === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }
}
