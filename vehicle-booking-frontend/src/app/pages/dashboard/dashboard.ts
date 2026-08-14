import { Component, OnInit, AfterViewInit, ElementRef, ViewChild, inject, signal } from '@angular/core';
import { Chart, registerables } from 'chart.js';
import { Api } from '../../core/api';

Chart.register(...registerables);

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [],
  templateUrl: './dashboard.html',
})
export class Dashboard implements OnInit, AfterViewInit {
  private api = inject(Api);

  @ViewChild('usageCanvas') usageCanvas!: ElementRef<HTMLCanvasElement>;
  @ViewChild('statusCanvas') statusCanvas!: ElementRef<HTMLCanvasElement>;

  loading = signal(true);
  errorMsg = signal('');
  totalBookings = signal(0);
  totalPending = signal(0);
  totalCompleted = signal(0);

  private bookingsData: any[] = [];
  private viewReady = false;

  ngOnInit() {
    this.api.getBookings().subscribe({
      next: (res) => {
        this.bookingsData = res.data ?? [];
        this.totalBookings.set(this.bookingsData.length);
        this.totalPending.set(this.bookingsData.filter(b => b.status === 'pending').length);
        this.totalCompleted.set(this.bookingsData.filter(b => b.status === 'completed').length);
        this.loading.set(false);

        if (this.viewReady) {
          this.renderCharts();
        }
      },
      error: () => {
        this.errorMsg.set('Gagal memuat data pemesanan');
        this.loading.set(false);
      },
    });
  }

  ngAfterViewInit() {
    this.viewReady = true;
    if (this.bookingsData.length > 0 || !this.loading()) {
      this.renderCharts();
    }
  }

  private renderCharts() {
    if (!this.usageCanvas || !this.statusCanvas) return;

    const usageCounts: Record<string, number> = {};
    for (const b of this.bookingsData) {
      const name = b.vehicle_name ?? 'Tidak diketahui';
      usageCounts[name] = (usageCounts[name] ?? 0) + 1;
    }

    new Chart(this.usageCanvas.nativeElement, {
      type: 'bar',
      data: {
        labels: Object.keys(usageCounts),
        datasets: [{
          label: 'Jumlah Pemakaian',
          data: Object.values(usageCounts),
          backgroundColor: '#2563eb',
        }],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
      },
    });

    const statusCounts: Record<string, number> = {};
    for (const b of this.bookingsData) {
      const status = b.status ?? 'unknown';
      statusCounts[status] = (statusCounts[status] ?? 0) + 1;
    }

    new Chart(this.statusCanvas.nativeElement, {
      type: 'doughnut',
      data: {
        labels: Object.keys(statusCounts),
        datasets: [{
          data: Object.values(statusCounts),
          backgroundColor: ['#f59e0b', '#3b82f6', '#22c55e', '#ef4444', '#a855f7'],
        }],
      },
      options: { responsive: true },
    });
  }
}