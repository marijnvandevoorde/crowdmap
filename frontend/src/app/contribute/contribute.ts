import { Component, OnInit, OnDestroy, AfterViewInit, signal, computed, ViewChild, ElementRef } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { MindmapService, Statement, Mindmap } from '../core/mindmap';

interface SuggestionMatch {
  statement: Statement;
  score: number;
}

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
  inputValue = signal('');
  submitting = signal(false);
  sessionId = '';
  showSuggestions = signal(false);
  selectedSuggestionIndex = signal(-1);

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

  suggestions = computed(() => {
    const input = this.inputValue().trim().toLowerCase();
    if (input.length < 2) return [];

    const reactions = this.reactions();
    const matches: SuggestionMatch[] = [];

    for (const reaction of reactions) {
      const score = this.fuzzyMatch(input, reaction.content.toLowerCase());
      if (score > 0.3) {
        matches.push({ statement: reaction, score });
      }
    }

    return matches
      .sort((a, b) => b.score - a.score)
      .slice(0, 5)
      .map(m => m.statement);
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

  onInputChange(): void {
    this.inputValue.set(this.newReaction);
    this.showSuggestions.set(this.newReaction.trim().length >= 2);
    this.selectedSuggestionIndex.set(-1);
  }

  onInputFocus(): void {
    if (this.newReaction.trim().length >= 2) {
      this.showSuggestions.set(true);
    }
  }

  onInputBlur(): void {
    // Delay to allow click on suggestion to register
    setTimeout(() => this.showSuggestions.set(false), 150);
  }

  onInputKeydown(event: KeyboardEvent): void {
    const suggestions = this.suggestions();
    if (!suggestions.length || !this.showSuggestions()) return;

    switch (event.key) {
      case 'ArrowUp':
        event.preventDefault();
        this.selectedSuggestionIndex.update(i =>
          i <= 0 ? suggestions.length - 1 : i - 1
        );
        break;
      case 'ArrowDown':
        event.preventDefault();
        this.selectedSuggestionIndex.update(i =>
          i >= suggestions.length - 1 ? 0 : i + 1
        );
        break;
      case 'Enter':
        if (this.selectedSuggestionIndex() >= 0) {
          event.preventDefault();
          this.selectSuggestion(suggestions[this.selectedSuggestionIndex()]);
        }
        break;
      case 'Escape':
        this.showSuggestions.set(false);
        this.selectedSuggestionIndex.set(-1);
        break;
    }
  }

  selectSuggestion(statement: Statement): void {
    this.showSuggestions.set(false);
    this.selectedSuggestionIndex.set(-1);
    this.toggleVote(statement);
    this.newReaction = '';
    this.inputValue.set('');
    this.focusInput();
  }

  private fuzzyMatch(input: string, target: string): number {
    // Tokenize both strings into words
    const inputWords = input.split(/\s+/).filter(w => w.length > 0);
    const targetWords = target.split(/\s+/).filter(w => w.length > 0);

    if (inputWords.length === 0 || targetWords.length === 0) return 0;

    let totalScore = 0;
    let matchedWords = 0;

    for (const inputWord of inputWords) {
      let bestWordScore = 0;

      for (const targetWord of targetWords) {
        // Exact match
        if (targetWord === inputWord) {
          bestWordScore = Math.max(bestWordScore, 1);
          continue;
        }

        // Starts with (prefix match)
        if (targetWord.startsWith(inputWord)) {
          bestWordScore = Math.max(bestWordScore, 0.9);
          continue;
        }

        // Contains
        if (targetWord.includes(inputWord)) {
          bestWordScore = Math.max(bestWordScore, 0.7);
          continue;
        }

        // Levenshtein-based similarity for typo tolerance
        const distance = this.levenshteinDistance(inputWord, targetWord);
        const maxLen = Math.max(inputWord.length, targetWord.length);
        const similarity = 1 - distance / maxLen;
        if (similarity > 0.6) {
          bestWordScore = Math.max(bestWordScore, similarity * 0.8);
        }
      }

      if (bestWordScore > 0) {
        matchedWords++;
        totalScore += bestWordScore;
      }
    }

    // Score based on how many input words matched
    const coverage = matchedWords / inputWords.length;
    return coverage * (totalScore / inputWords.length);
  }

  private levenshteinDistance(a: string, b: string): number {
    const matrix: number[][] = [];

    for (let i = 0; i <= b.length; i++) {
      matrix[i] = [i];
    }
    for (let j = 0; j <= a.length; j++) {
      matrix[0][j] = j;
    }

    for (let i = 1; i <= b.length; i++) {
      for (let j = 1; j <= a.length; j++) {
        if (b.charAt(i - 1) === a.charAt(j - 1)) {
          matrix[i][j] = matrix[i - 1][j - 1];
        } else {
          matrix[i][j] = Math.min(
            matrix[i - 1][j - 1] + 1, // substitution
            matrix[i][j - 1] + 1,     // insertion
            matrix[i - 1][j] + 1      // deletion
          );
        }
      }
    }

    return matrix[b.length][a.length];
  }
}
