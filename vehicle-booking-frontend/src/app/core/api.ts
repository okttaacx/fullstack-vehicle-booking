import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root',
})
export class Api {
  private http = inject(HttpClient);
  private base = 'http://localhost:8080/api';

  getVehicles() {
    return this.http.get<any>(`${this.base}/vehicles`);
  }

  getDrivers() {
    return this.http.get<any>(`${this.base}/drivers`);
  }

  getApprovers() {
    return this.http.get<any>(`${this.base}/users?role=approver`);
  }

  getBookings() {
    return this.http.get<any>(`${this.base}/bookings`);
  }

  getBookingDetail(id: number) {
    return this.http.get<any>(`${this.base}/bookings/${id}`);
  }

  createBooking(payload: any) {
    return this.http.post<any>(`${this.base}/bookings`, payload);
  }

  getApprovals(approverId: number) {
    return this.http.get<any>(`${this.base}/approvals?approver_id=${approverId}`);
  }

  approveBooking(id: number) {
    return this.http.post<any>(`${this.base}/approvals/${id}/approve`, {});
  }

  rejectBooking(id: number, notes: string) {
    return this.http.post<any>(`${this.base}/approvals/${id}/reject`, { notes });
  }
}