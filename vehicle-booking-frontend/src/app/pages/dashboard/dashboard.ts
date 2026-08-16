import { Component, OnInit, AfterViewInit, ElementRef, ViewChild, inject, signal, computed } from '@angular/core';
import { Chart, registerables } from 'chart.js';
import { Api } from '../../core/api';
import { Auth } from '../../core/auth';

Chart.register(...registerables);

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css',
})
export class Dashboard implements OnInit, AfterViewInit {
  private api = inject(Api);
  auth = inject(Auth);

  @ViewChild('usageCanvas') usageCanvas!: ElementRef<HTMLCanvasElement>;
  @ViewChild('statusCanvas') statusCanvas!: ElementRef<HTMLCanvasElement>;

  loading = signal(true);
  errorMsg = signal('');

  totalBookings = signal(0);
  totalPending = signal(0);
  totalApproved = signal(0);
  totalVehicles = signal(0);
  avgFuel = signal(0);

  recentBookings = signal<any[]>([]);

  private bookingsData: any[] = [];
  private viewReady = false;

  today = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

  ngOnInit() {
    this.api.getVehicles().subscribe({
      next: (res) => {
        const vehicles = res.data ?? [];
        this.totalVehicles.set(vehicles.length);
        const fuels = vehicles.map((v: any) => Number(v.fuel_consumption)).filter((n: number) => !isNaN(n));
        this.avgFuel.set(fuels.length ? Math.round((fuels.reduce((a: number, b: number) => a + b, 0) / fuels.length) * 10) / 10 : 0);
      },
    });

    this.api.getBookings().subscribe({
      next: (res) => {
        this.bookingsData = res.data ?? [];
        this.totalBookings.set(this.bookingsData.length);
        this.totalPending.set(this.bookingsData.filter(b => b.status === 'pending' || b.status === 'approved_l1').length);
        this.totalApproved.set(this.bookingsData.filter(b => b.status === 'approved_l2' || b.status === 'completed').length);
        this.recentBookings.set(this.bookingsData.slice(0, 5));
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

    const barCtx = this.usageCanvas.nativeElement.getContext('2d');
    let gradient;
    if (barCtx) {
      gradient = barCtx.createLinearGradient(0, 0, 0, 280);
      gradient.addColorStop(0, '#16a34a');
      gradient.addColorStop(1, '#bbf7d0');
    }

    new Chart(this.usageCanvas.nativeElement, {
      type: 'bar',
      data: {
        labels: Object.keys(usageCounts),
        datasets: [{
          label: 'Jumlah Pemakaian',
          data: Object.values(usageCounts),
          backgroundColor: gradient ?? '#16a34a',
          borderRadius: 8,
          maxBarThickness: 48,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#eef2f0' } },
          x: { grid: { display: false } },
        },
      },
    });

    const statusMap: Record<string, string> = {
      pending: 'Menunggu L1',
      approved_l1: 'Menunggu L2',
      approved_l2: 'Disetujui',
      rejected: 'Ditolak',
      completed: 'Selesai',
    };

    const statusCounts: Record<string, number> = {};
    for (const b of this.bookingsData) {
      const label = statusMap[b.status] ?? b.status ?? 'Lainnya';
      statusCounts[label] = (statusCounts[label] ?? 0) + 1;
    }

    new Chart(this.statusCanvas.nativeElement, {
      type: 'doughnut',
      data: {
        labels: Object.keys(statusCounts),
        datasets: [{
          data: Object.values(statusCounts),
          backgroundColor: ['#f59e0b', '#0ea5e9', '#16a34a', '#ef4444', '#a855f7'],
          borderWidth: 0,
          spacing: 3,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 10, font: { family: 'Baloo 2' } },
          },
        },
      },
    });
  }

  statusLabel(status: string): string {
    const map: Record<string, string> = {
      pending: 'Menunggu L1',
      approved_l1: 'Menunggu L2',
      approved_l2: 'Disetujui',
      rejected: 'Ditolak',
      completed: 'Selesai',
    };
    return map[status] ?? status;
  }

  statusClass(status: string): string {
    const map: Record<string, string> = {
      pending: 'badge-amber',
      approved_l1: 'badge-blue',
      approved_l2: 'badge-green',
      rejected: 'badge-red',
      completed: 'badge-green',
    };
    return map[status] ?? '';
  }
}