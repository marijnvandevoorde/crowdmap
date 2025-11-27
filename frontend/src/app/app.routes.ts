import { Routes } from '@angular/router';
import { authGuard } from './core/auth-guard';

export const routes: Routes = [
  {
    path: 'admin',
    loadComponent: () => import('./admin/login/login').then(m => m.LoginComponent),
  },
  {
    path: 'admin/dashboard',
    loadComponent: () => import('./admin/dashboard/dashboard').then(m => m.DashboardComponent),
    canActivate: [authGuard],
  },
  {
    path: 'admin/mindmap/:uuid',
    loadComponent: () => import('./admin/moderator/moderator').then(m => m.ModeratorComponent),
    canActivate: [authGuard],
  },
  {
    path: 'contribute/:uuid',
    loadComponent: () => import('./contribute/contribute').then(m => m.ContributeComponent),
  },
  {
    path: '',
    redirectTo: 'admin',
    pathMatch: 'full',
  },
];
