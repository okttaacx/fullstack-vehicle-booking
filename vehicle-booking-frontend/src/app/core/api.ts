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

  createVehicle(payload: any) {
    return this.http.post<any>(`${this.base}/vehicles`, payload);
  }

  updateVehicle(id: number, payload: any) {
    return this.http.put<any>(`${this.base}/vehicles/${id}`, payload);
  }

  deleteVehicle(id: number) {
    return this.http.delete<any>(`${this.base}/vehicles/${id}`);
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

  exportBookings(start?: string, end?: string) {
    let url = `${this.base}/reports/bookings/export`;
    const params: string[] = [];
    if (start) params.push(`start=${start}`);
    if (end) params.push(`end=${end}`);
    if (params.length) url += `?${params.join('&')}`;

    return this.http.get(url, { responseType: 'blob' });
  }
}