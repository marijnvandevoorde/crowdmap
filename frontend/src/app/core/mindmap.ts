import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

export interface MindmapSummary {
  id: number;
  uuid: string;
  question: string;
  createdAt: string;
}

export interface Statement {
  id: number;
  parentId: number | null;
  content: string;
  sessionId: string | null;
  voteCount: number;
  createdAt: string;
}

export interface Mindmap {
  id: number;
  uuid: string;
  userId: number;
  focusedStatementId: number | null;
  createdAt: string;
}

export interface MindmapWithStatements {
  mindmap: Mindmap;
  statements: Statement[];
  contributorUrl?: string;
}

@Injectable({
  providedIn: 'root',
})
export class MindmapService {
  private readonly apiUrl = '/api';

  constructor(private http: HttpClient) {}

  listMindmaps(): Observable<{ mindmaps: MindmapSummary[] }> {
    return this.http.get<{ mindmaps: MindmapSummary[] }>(
      `${this.apiUrl}/mindmaps`,
      { withCredentials: true }
    );
  }

  createMindmap(question: string): Observable<{ mindmap: { id: number; uuid: string } }> {
    return this.http.post<{ mindmap: { id: number; uuid: string } }>(
      `${this.apiUrl}/mindmaps`,
      { question },
      { withCredentials: true }
    );
  }

  getMindmap(uuid: string): Observable<MindmapWithStatements> {
    return this.http.get<MindmapWithStatements>(
      `${this.apiUrl}/mindmaps/${uuid}`,
      { withCredentials: true }
    );
  }

  getMindmapForModerator(uuid: string): Observable<MindmapWithStatements> {
    return this.http.get<MindmapWithStatements>(
      `${this.apiUrl}/mindmaps/${uuid}/moderate`,
      { withCredentials: true }
    );
  }

  addStatement(uuid: string, parentId: number, content: string, sessionId: string): Observable<{ statement: Statement }> {
    return this.http.post<{ statement: Statement }>(
      `${this.apiUrl}/mindmaps/${uuid}/statements`,
      { parentId, content, sessionId },
      { withCredentials: true }
    );
  }

  toggleVote(statementId: number, sessionId: string): Observable<{ voted: boolean; voteCount: number }> {
    return this.http.post<{ voted: boolean; voteCount: number }>(
      `${this.apiUrl}/statements/${statementId}/vote`,
      { sessionId },
      { withCredentials: true }
    );
  }

  setFocusedStatement(uuid: string, statementId: number | null): Observable<{ success: boolean; focusedStatementId: number | null }> {
    return this.http.post<{ success: boolean; focusedStatementId: number | null }>(
      `${this.apiUrl}/mindmaps/${uuid}/focus`,
      { statementId },
      { withCredentials: true }
    );
  }

  mergeStatements(uuid: string, statementIds: number[]): Observable<{ statements: Statement[] }> {
    return this.http.post<{ statements: Statement[] }>(
      `${this.apiUrl}/mindmaps/${uuid}/merge`,
      { statementIds },
      { withCredentials: true }
    );
  }

  deleteStatements(uuid: string, statementIds: number[]): Observable<{ statements: Statement[] }> {
    return this.http.post<{ statements: Statement[] }>(
      `${this.apiUrl}/mindmaps/${uuid}/delete`,
      { statementIds },
      { withCredentials: true }
    );
  }

  updateStatement(uuid: string, statementId: number, content: string): Observable<{ statements: Statement[] }> {
    return this.http.put<{ statements: Statement[] }>(
      `${this.apiUrl}/mindmaps/${uuid}/statements/${statementId}`,
      { content },
      { withCredentials: true }
    );
  }
}
