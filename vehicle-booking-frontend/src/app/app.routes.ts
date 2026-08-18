import { Routes } from '@angular/router';
import { authGuard } from './core/auth-guard';
import { Login } from './pages/login/login';
import { MainLayout } from './layout/main-layout/main-layout';

export const routes: Routes = [
  { path: 'login', component: Login },
  {
    path: '',
    component: MainLayout,
    canActivate: [authGuard],
    children: [
      { path: 'dashboard', loadComponent: () => import('./pages/dashboard/dashboard').then(m => m.Dashboard) },
      { path: 'vehicles', loadComponent: () => import('./pages/vehicles/vehicles').then(m => m.Vehicles) },
      { path: 'bookings', loadComponent: () => import('./pages/bookings/bookings').then(m => m.Bookings) },
      { path: 'approvals', loadComponent: () => import('./pages/approvals/approvals').then(m => m.Approvals) },
      { path: 'drivers', loadComponent: () => import('./pages/drivers/drivers').then(m => m.Drivers) },
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
    ],
  },
  { path: '**', redirectTo: 'login' },
];