import { Component, OnInit, inject, signal } from '@angular/core';
import { Api } from '../../core/api';

@Component({
  selector: 'app-vehicles',
  standalone: true,
  imports: [],
  templateUrl: './vehicles.html',
})
export class Vehicles implements OnInit {
  private api = inject(Api);

  vehicles = signal<any[]>([]);
  loading = signal(true);
  errorMsg = signal('');

  ngOnInit() {
    this.api.getVehicles().subscribe({
      next: (res) => {
        this.vehicles.set(res.data ?? []);
        this.loading.set(false);
      },
      error: () => {
        this.errorMsg.set('Gagal memuat data kendaraan');
        this.loading.set(false);
      },
    });
  }
}