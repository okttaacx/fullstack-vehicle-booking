import {
  Component,
  OnInit,
  AfterViewInit,
  ElementRef,
  ViewChild,
  inject,
  signal,
} from '@angular/core';
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

  @ViewChild('trendCanvas') trendCanvas!: ElementRef<HTMLCanvasElement>;
  @ViewChild('ownershipCanvas') ownershipCanvas!: ElementRef<HTMLCanvasElement>;
  @ViewChild('gaugeCanvas') gaugeCanvas!: ElementRef<HTMLCanvasElement>;

  loading = signal(true);
  errorMsg = signal('');

  totalVehicles = signal(0);
  totalBookings = signal(0);
  totalPending = signal(0);
  avgFuel = signal(0);

  recentBookings = signal<any[]>([]);
  upcomingService = signal<any[]>([]);

  availabilityRate = signal(0);
  approvalRate = signal(0);

  today = new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  nowTime = new Date().toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  });

  private vehiclesData: any[] = [];
  private bookingsData: any[] = [];
  private vehiclesLoaded = false;
  private bookingsLoaded = false;
  private rendered = false;

  ngOnInit() {
    this.api.getVehicles().subscribe({
      next: (res) => {
        this.vehiclesData = res.data ?? [];
        this.vehiclesLoaded = true;
        this.totalVehicles.set(this.vehiclesData.length);

        const fuels = this.vehiclesData
          .map((v: any) => Number(v.fuel_consumption))
          .filter((n: number) => !isNaN(n));

        this.avgFuel.set(
          fuels.length
            ? Math.round(
                (fuels.reduce((a: number, b: number) => a + b, 0) /
                  fuels.length) *
                  10
              ) / 10
            : 0
        );

        this.computeUpcomingService();
        this.tryRender();
      },
      error: () => this.errorMsg.set('Gagal memuat data kendaraan'),
    });

    this.api.getBookings().subscribe({
      next: (res) => {
        this.bookingsData = res.data ?? [];
        this.bookingsLoaded = true;

        this.totalBookings.set(this.bookingsData.length);

        this.totalPending.set(
          this.bookingsData.filter(
            (b: any) =>
              b.status === 'pending' || b.status === 'approved_l1'
          ).length
        );

        this.recentBookings.set(this.bookingsData.slice(0, 5));

        const finished = this.bookingsData.filter((b: any) =>
          ['approved_l2', 'completed', 'rejected'].includes(b.status)
        );

        const approved = this.bookingsData.filter((b: any) =>
          ['approved_l2', 'completed'].includes(b.status)
        );

        this.approvalRate.set(
          finished.length
            ? Math.round((approved.length / finished.length) * 100)
            : 0
        );

        this.loading.set(false);
        this.tryRender();
      },
      error: () => {
        this.errorMsg.set('Gagal memuat data pemesanan');
        this.loading.set(false);
      },
    });
  }

  ngAfterViewInit() {
    this.tryRender();
  }

  private tryRender() {
    if (this.rendered) return;
    if (!this.vehiclesLoaded || !this.bookingsLoaded) return;

    setTimeout(() => {
      if (
        !this.trendCanvas ||
        !this.ownershipCanvas ||
        !this.gaugeCanvas
      ) {
        setTimeout(() => this.tryRender(), 50);
        return;
      }

      this.rendered = true;
      this.computeAvailability();
      this.renderCharts();
    }, 0);
  }

  private computeUpcomingService() {
    const now = new Date();
    const in14days = new Date(now.getTime() + 14 * 24 * 60 * 60 * 1000);

    const list = this.vehiclesData
      .filter((v: any) => v.service_schedule)
      .map((v: any) => ({
        ...v,
        serviceDate: new Date(v.service_schedule),
      }))
      .filter((v: any) => v.serviceDate <= in14days)
      .sort(
        (a: any, b: any) =>
          a.serviceDate.getTime() - b.serviceDate.getTime()
      )
      .slice(0, 5);

    this.upcomingService.set(list);
  }

  private computeAvailability() {
    const now = new Date();

    const busyVehicleIds = new Set(
      this.bookingsData
        .filter((b: any) =>
          ['pending', 'approved_l1', 'approved_l2'].includes(b.status)
        )
        .filter(
          (b: any) =>
            new Date(b.start_date) <= now && now <= new Date(b.end_date)
        )
        .map((b: any) => b.vehicle_id)
    );

    const total = this.vehiclesData.length;
    const available = total - busyVehicleIds.size;

    this.availabilityRate.set(
      total ? Math.round((available / total) * 100) : 0
    );
  }

  private renderCharts() {
    this.renderTrendChart();
    this.renderOwnershipChart();
    this.renderGaugeChart();
  }

  /*
   * Mengubah Date/string menjadi format YYYY-MM-DD berdasarkan
   * zona waktu lokal, sehingga tidak bergeser akibat toISOString().
   */
  private dateKey(value: Date | string | null | undefined): string {
    const date = value instanceof Date ? value : new Date(value ?? '');

    if (Number.isNaN(date.getTime())) {
      return '';
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }

  /*
   * Prioritas tanggal:
   * 1. created_at dari backend
   * 2. createdAt / booking_date bila namanya berbeda
   * 3. tanggal dari kode BK-YYYYMMDD-xxxxx
   * 4. start_date sebagai cadangan terakhir
   */
  private bookingCreatedDateKey(booking: any): string {
    const createdAt =
      booking.created_at ?? booking.createdAt ?? booking.booking_date;

    if (createdAt) {
      return this.dateKey(createdAt);
    }

    const code = String(booking.booking_code ?? '');
    const match = code.match(/^BK-(\d{4})(\d{2})(\d{2})-/);

    if (match) {
      return `${match[1]}-${match[2]}-${match[3]}`;
    }

    return this.dateKey(booking.start_date);
  }

  private renderTrendChart() {
    const days: string[] = [];
    const counts: number[] = [];

    for (let i = 6; i >= 0; i--) {
      const date = new Date();
      date.setDate(date.getDate() - i);
      date.setHours(0, 0, 0, 0);

      const label = date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
      });

      const currentDateKey = this.dateKey(date);

      const count = this.bookingsData.filter(
        (booking: any) =>
          this.bookingCreatedDateKey(booking) === currentDateKey
      ).length;

      days.push(label);
      counts.push(count);
    }

    const ctx = this.trendCanvas.nativeElement.getContext('2d');

    let fillGradient: CanvasGradient | undefined;

    if (ctx) {
      fillGradient = ctx.createLinearGradient(0, 0, 0, 260);
      fillGradient.addColorStop(0, 'rgba(22,163,74,0.25)');
      fillGradient.addColorStop(1, 'rgba(22,163,74,0)');
    }

    new Chart(this.trendCanvas.nativeElement, {
      type: 'line',
      data: {
        labels: days,
        datasets: [
          {
            label: 'Pemesanan',
            data: counts,
            borderColor: '#16a34a',
            backgroundColor:
              fillGradient ?? 'rgba(22,163,74,0.15)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointBackgroundColor: '#16a34a',
            pointBorderColor: 'white',
            pointBorderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
              font: {
                family: 'Baloo 2',
              },
            },
            grid: {
              color: '#eef2f0',
            },
          },
          x: {
            grid: {
              display: false,
            },
            ticks: {
              font: {
                family: 'Baloo 2',
              },
            },
          },
        },
      },
    });
  }

  private renderOwnershipChart() {
    const milik = this.vehiclesData.filter(
      (v: any) => v.ownership === 'milik_perusahaan'
    ).length;

    const sewa = this.vehiclesData.filter(
      (v: any) => v.ownership === 'sewa'
    ).length;

    new Chart(this.ownershipCanvas.nativeElement, {
      type: 'doughnut',
      data: {
        labels: ['Milik Perusahaan', 'Sewa'],
        datasets: [
          {
            data: [milik, sewa],
            backgroundColor: ['#16a34a', '#f59e0b'],
            borderWidth: 0,
            spacing: 3,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              boxWidth: 10,
              font: {
                family: 'Baloo 2',
              },
            },
          },
        },
      },
    });
  }

  private renderGaugeChart() {
    const value = this.availabilityRate();

    new Chart(this.gaugeCanvas.nativeElement, {
      type: 'doughnut',
      data: {
        datasets: [
          {
            data: [value, 100 - value],
            backgroundColor: [
              '#4ade80',
              'rgba(255,255,255,0.15)',
            ],
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        circumference: 180,
        rotation: 270,
        cutout: '78%',
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            enabled: false,
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

  daysUntil(dateStr: string): number {
    const diff =
      new Date(dateStr).getTime() -
      new Date().setHours(0, 0, 0, 0);

    return Math.ceil(diff / (1000 * 60 * 60 * 24));
  }
}