import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { DatePipe } from '@angular/common';
import { Api } from '../../core/api';

@Component({
  selector: 'app-calendar',
  standalone: true,
  imports: [DatePipe],
  templateUrl: './calendar.html',
  styleUrl: './calendar.css',
})
export class Calendar implements OnInit {
  private api = inject(Api);

  vehicles = signal<any[]>([]);
  bookings = signal<any[]>([]);
  loading = signal(true);

  // Tanggal awal minggu yang sedang ditampilkan
  weekStart = signal(this.getMonday(new Date()));

  weekDays = computed(() => {
    const start = this.weekStart();
    const days: Date[] = [];

    for (let i = 0; i < 7; i++) {
      const d = new Date(start);
      d.setDate(start.getDate() + i);
      days.push(d);
    }

    return days;
  });

  weekLabel = computed(() => {
    const days = this.weekDays();
    const first = days[0];
    const last = days[6];
    const fmt = (d: Date) =>
      d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });

    return `${fmt(first)} - ${fmt(last)} ${last.getFullYear()}`;
  });

  ngOnInit() {
    this.loading.set(true);

    this.api.getVehicles().subscribe({
      next: (res) => this.vehicles.set(res.data ?? []),
    });

    this.api.getBookings().subscribe({
      next: (res) => {
        this.bookings.set(res.data ?? []);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  private getMonday(date: Date): Date {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);

    d.setHours(0, 0, 0, 0);

    return new Date(d.setDate(diff));
  }

  prevWeek() {
    const d = new Date(this.weekStart());
    d.setDate(d.getDate() - 7);
    this.weekStart.set(d);
  }

  nextWeek() {
    const d = new Date(this.weekStart());
    d.setDate(d.getDate() + 7);
    this.weekStart.set(d);
  }

  goToday() {
    this.weekStart.set(this.getMonday(new Date()));
  }

  // Booking milik kendaraan tertentu yang overlap dengan minggu yang ditampilkan
  bookingsForVehicle(vehicleId: number) {
    const weekStart = this.weekDays()[0];
    const weekEnd = new Date(this.weekDays()[6]);

    weekEnd.setHours(23, 59, 59, 999);

    return this.bookings().filter((b: any) => {
      if (Number(b.vehicle_id) !== Number(vehicleId)) return false;
      if (b.status === 'rejected') return false;

      const start = new Date(b.start_date);
      const end = new Date(b.end_date);

      return start <= weekEnd && end >= weekStart;
    });
  }

  // Hitung posisi bar (persen kiri & lebar) relatif terhadap grid 7 hari
  barStyle(booking: any): { left: string; width: string } {
    const weekStart = this.weekDays()[0].getTime();
    const dayMs = 24 * 60 * 60 * 1000;
    const weekEndExclusive = weekStart + 7 * dayMs;

    let start = new Date(booking.start_date).getTime();
    let end = new Date(booking.end_date).getTime();

    if (start < weekStart) start = weekStart;
    if (end > weekEndExclusive) end = weekEndExclusive;

    const leftPercent = ((start - weekStart) / (7 * dayMs)) * 100;
    const widthPercent = Math.max(
      ((end - start) / (7 * dayMs)) * 100,
      4,
    );

    return {
      left: `${leftPercent}%`,
      width: `${widthPercent}%`,
    };
  }

  statusBarClass(status: string): string {
    const map: Record<string, string> = {
      pending: 'bar-pending',
      approved_l1: 'bar-approved-l1',
      approved_l2: 'bar-approved-l2',
      completed: 'bar-completed',
    };

    return map[status] ?? 'bar-pending';
  }

  isToday(date: Date): boolean {
    const today = new Date();

    return date.toDateString() === today.toDateString();
  }
}