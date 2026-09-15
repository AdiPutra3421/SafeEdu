@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<!-- Topbar Header -->
<header class="topbar">
  <div class="page-title">
    <h2>Statistik & Monitoring Sekolah</h2>
    <p>Ikhtisar penerimaan laporan fasilitas, pelanggaran kode etik, dan status respon penanganan.</p>
  </div>
  <div class="topbar-actions">
    <div class="system-status-indicator">
      <span class="status-dot-pulse"></span>
      Sistem Aktif & Terhubung
    </div>
    <a href="{{ route('laporan') }}" class="btn btn-primary btn-sm">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Tinjau Laporan Baru
    </a>
  </div>
</header>

<!-- Content Body -->
<main class="content-body">
  <!-- KPI Cards Grid -->
  <div class="stats-grid">
    <!-- Total Laporan -->
    <div class="stat-card">
      <div>
        <div class="stat-title">Total Laporan Masuk</div>
        <div class="stat-number" id="stat-total">5</div>
        <div class="stat-meta up">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
            <polyline points="17 6 23 6 23 12"/>
          </svg>
          Semua kategori terdata
        </div>
      </div>
      <div class="stat-icon-wrapper icon-blue">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
        </svg>
      </div>
    </div>

    <!-- Fasilitas -->
    <div class="stat-card">
      <div>
        <div class="stat-title">Laporan Fasilitas</div>
        <div class="stat-number" id="stat-fasilitas">3</div>
        <div class="stat-meta">
          Sarana & prasarana sekolah
        </div>
      </div>
      <div class="stat-icon-wrapper icon-teal">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
          <line x1="8" y1="21" x2="16" y2="21"/>
          <line x1="12" y1="17" x2="12" y2="21"/>
        </svg>
      </div>
    </div>

    <!-- Pelanggaran Kode Etik -->
    <div class="stat-card">
      <div>
        <div class="stat-title">Pelanggaran Kode Etik</div>
        <div class="stat-number" id="stat-ethics">2</div>
        <div class="stat-meta" style="color: var(--status-danger-text);">
          Perundungan & Ketertiban
        </div>
      </div>
      <div class="stat-icon-wrapper icon-rose">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
          <line x1="12" y1="9" x2="12" y2="13"/>
          <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
      </div>
    </div>

    <!-- Tingkat Respon -->
    <div class="stat-card">
      <div>
        <div class="stat-title">Respon Diberikan</div>
        <div class="stat-number" id="stat-response-rate">60%</div>
        <div class="stat-meta">
          <span id="stat-responded-count">3</span> dari <span id="stat-responded-total">5</span> tertangani
        </div>
      </div>
      <div class="stat-icon-wrapper icon-amber">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
    </div>
  </div>

  <!-- Charts Section -->
  <div class="charts-grid">
    <!-- Chart 1: Respon Berdasarkan Jenis Laporan -->
    <div class="card">
      <div class="card-header">
        <div>
          <h3>Status Respon Penanganan Laporan</h3>
          <p>Distribusi penanganan berdasarkan jenis pelaporan insiden</p>
        </div>
      </div>
      <div class="card-body">
        <div class="chart-container">
          <!-- Fasilitas Progress -->
          <div class="bar-chart-row">
            <div class="bar-label-wrap">
              <span>Laporan Fasilitas Sekolah (Disposisi ke Teknisi)</span>
              <span id="label-bar-facility">3 Tiket (60%)</span>
            </div>
            <div class="bar-track">
              <div class="bar-fill facility" id="fill-bar-facility" style="width: 60%;"></div>
            </div>
          </div>

          <!-- Kode Etik Progress -->
          <div class="bar-chart-row">
            <div class="bar-label-wrap">
              <span>Pelanggaran Kode Etik & Ketertiban (Investigasi)</span>
              <span id="label-bar-ethics">2 Kasus (40%)</span>
            </div>
            <div class="bar-track">
              <div class="bar-fill ethics" id="fill-bar-ethics" style="width: 40%;"></div>
            </div>
          </div>

          <!-- Respon Selesai -->
          <div class="bar-chart-row">
            <div class="bar-label-wrap">
              <span>Tiket Telah Selesai Ditangani & Dikonfirmasi</span>
              <span id="label-bar-resolved">1 Tiket (20%)</span>
            </div>
            <div class="bar-track">
              <div class="bar-fill resolved" id="fill-bar-resolved" style="width: 20%;"></div>
            </div>
          </div>
        </div>

        <!-- Matrix Summary -->
        <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; text-align: center;">
          <div style="padding: 12px; background: var(--bg-subtle); border-radius: 8px;">
            <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600;">Menunggu Verifikasi</div>
            <div style="font-size: 1.4rem; font-weight: 700; color: var(--status-pending-text);" id="stat-pending">2</div>
          </div>
          <div style="padding: 12px; background: var(--bg-subtle); border-radius: 8px;">
            <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600;">Diteruskan ke Teknisi</div>
            <div style="font-size: 1.4rem; font-weight: 700; color: var(--status-process-text);" id="stat-dispatched">2</div>
          </div>
          <div style="padding: 12px; background: var(--bg-subtle); border-radius: 8px;">
            <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600;">Tuntas / Terverifikasi</div>
            <div style="font-size: 1.4rem; font-weight: 700; color: var(--status-success-text);" id="stat-resolved">1</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart 2: Komposisi Jenis Laporan -->
    <div class="card">
      <div class="card-header">
        <div>
          <h3>Rasio Jenis Laporan</h3>
          <p>Proporsi Fasilitas vs Kode Etik</p>
        </div>
      </div>
      <div class="card-body">
        <div class="donut-chart-wrap">
          <svg class="donut-svg" viewBox="0 0 160 160">
            <circle class="donut-circle-bg" cx="80" cy="80" r="60"/>
            <circle class="donut-circle-1" cx="80" cy="80" r="60" id="donut-slice-facility"/>
            <circle class="donut-circle-2" cx="80" cy="80" r="60" id="donut-slice-ethics"/>
          </svg>
        </div>

        <div class="donut-legend">
          <div class="legend-item">
            <div>
              <span class="legend-color-dot" style="background-color: var(--primary);"></span>
              Fasilitas Sekolah
            </div>
            <strong id="legend-facility-count">3 Laporan</strong>
          </div>
          <div class="legend-item">
            <div>
              <span class="legend-color-dot" style="background-color: #f43f5e;"></span>
              Pelanggaran Kode Etik
            </div>
            <strong id="legend-ethics-count">2 Laporan</strong>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Dispatches & Activity -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3>Aktivitas Laporan Terkini</h3>
        <p>Laporan yang baru saja masuk dari siswa</p>
      </div>
      <a href="{{ route('laporan') }}" class="btn btn-outline btn-sm">Lihat Semua Data</a>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID Tiket</th>
            <th>Jenis Laporan</th>
            <th>Judul & Deskripsi</th>
            <th>Pelapor</th>
            <th>Urgensi</th>
            <th>Status Respon</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="recent-reports-table">
          <!-- Injected via JavaScript -->
        </tbody>
      </table>
    </div>
  </div>
</main>
@endsection

@section('scripts')

<script>
  async function fetchDashboard() {
    try {
      const response = await fetch('/api/pengaduan');
      const json = await response.json();
      if (json.success) {
        const data = json.data;
        
        // Update stats
        document.getElementById('stat-total').innerText = data.length;
        document.getElementById('stat-proses').innerText = data.filter(x => x.status === 'diproses' || x.status === 'menunggu').length;
        document.getElementById('stat-selesai').innerText = data.filter(x => x.status === 'selesai').length;

        // Render Aktivitas Terakhir
        const tbody = document.getElementById('recent-activity-body');
        const recent = data.slice(0, 5);
        
        if (recent.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: #94a3b8;">Belum ada aktivitas</td></tr>`;
            return;
        }

        tbody.innerHTML = recent.map(item => {
          let badgeClass = 'badge-resolved';
          if(item.status === 'menunggu') badgeClass = 'badge-ethics';
          if(item.status === 'diproses') badgeClass = 'badge-pending';
          
          return `
            <tr>
              <td><strong>RPT-${item.id}</strong><br><span style="font-size:0.75rem; color:#94a3b8;">${new Date(item.created_at).toLocaleDateString()}</span></td>
              <td style="font-weight:600; color:var(--text-main);">${item.judul || '-'}</td>
              <td>${item.is_anonim ? 'Anonim' : 'Siswa'}</td>
              <td><span class="badge ${badgeClass}">${item.status}</span></td>
            </tr>
          `;
        }).join('');
      }
    } catch (err) {
      console.error(err);
    }
  }

  document.addEventListener('DOMContentLoaded', fetchDashboard);
</script>

@endsection
