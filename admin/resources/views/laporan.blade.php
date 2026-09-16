@extends('layouts.admin')

@section('title', 'Laporan User')

@section('content')
<header class="topbar">
  <div class="page-title">
    <h2>Daftar Laporan Siswa</h2>
    <p>Filter antara laporan fasilitas dan pelanggaran kode etik, lalu verifikasi dan teruskan ke teknisi.</p>
  </div>
  <div class="topbar-actions">
    <div class="system-status-indicator">
      <span class="status-dot-pulse"></span>
      Real-time Sync
    </div>
  </div>
</header>

<main class="content-body">
  <!-- Filter Toolbar -->
  <div class="toolbar-container">
    <!-- Filter Tabs for Report Types -->
    <div class="filter-group">
      <button class="filter-btn active" data-type="all" onclick="setFilterType('all', this)">Semua Laporan (<span id="count-all">0</span>)</button>
      <button class="filter-btn" data-type="fasilitas" onclick="setFilterType('fasilitas', this)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="vertical-align: -2px; margin-right: 4px;">
          <rect x="2" y="3" width="20" height="14" rx="2"/>
        </svg>
        Pelaporan Fasilitas (<span id="count-fasilitas">0</span>)
      </button>
      <button class="filter-btn" data-type="kode_etik" onclick="setFilterType('kode_etik', this)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="vertical-align: -2px; margin-right: 4px;">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        Pelanggaran Kode Etik (<span id="count-ethics">0</span>)
      </button>
    </div>

    <!-- Search Input -->
    <div class="search-input-wrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/>
        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" id="searchInput" class="search-input" placeholder="Cari ID, judul, lokasi, pelapor..." oninput="renderReportsTable()">
    </div>
  </div>

  <!-- Status Sub-Filter -->
  <div style="display: flex; gap: 8px; margin-bottom: 16px; align-items: center;">
    <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">Status Respon:</span>
    <button class="btn btn-outline btn-sm active" id="filter-status-all" onclick="setStatusFilter('all', this)">Semua Status</button>
    <button class="btn btn-outline btn-sm" id="filter-status-pending" onclick="setStatusFilter('Menunggu Verifikasi', this)">Menunggu Verifikasi</button>
    <button class="btn btn-outline btn-sm" id="filter-status-dispatched" onclick="setStatusFilter('Diteruskan ke Teknisi', this)">Diteruskan ke Teknisi</button>
    <button class="btn btn-outline btn-sm" id="filter-status-resolved" onclick="setStatusFilter('Selesai', this)">Selesai</button>
  </div>

  <!-- Main Data Table -->
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID Tiket</th>
            <th>Kategori & Jenis</th>
            <th>Judul Laporan & Kronologi</th>
            <th>Lokasi Insiden</th>
            <th>Pelapor (Siswa)</th>
            <th>Tingkat Urgensi</th>
            <th>Status Respon</th>
            <th>Aksi Verifikasi</th>
          </tr>
        </thead>
        <tbody id="reports-table-body">
          <!-- Dynamically Rendered -->
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal Disposisi & Verifikasi ke Teknisi -->
<div class="modal-overlay" id="dispatchModal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div>
        <h3>Verifikasi & Teruskan ke Teknisi</h3>
        <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">Teruskan tiket pekerjaan ini ke aplikasi petugas teknisi lapangan</p>
      </div>
      <button class="close-btn" onclick="closeDispatchModal()">&times;</button>
    </div>
    <div class="modal-body">
      <div style="background: var(--bg-subtle); padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <span id="modal-report-id" style="font-size: 0.78rem; font-weight: 700; color: var(--primary);"></span>
          <span id="modal-report-badge"></span>
        </div>
        <h4 id="modal-report-title" style="font-size: 0.95rem; font-weight: 600; margin-bottom: 4px;"></h4>
        <div id="modal-report-location" style="font-size: 0.8rem; color: var(--text-muted);"></div>
        <div id="modal-report-desc" style="font-size: 0.82rem; color: var(--text-main); margin-top: 8px; line-height: 1.4;"></div>
      </div>

      <form id="dispatchForm" onsubmit="handleDispatchSubmit(event)">
        <input type="hidden" id="dispatch-report-id">
        
        <div class="form-group">
          <label class="form-label" for="technicianSelect">Pilih Teknisi / Divisi Penanggung Jawab *</label>
          <select id="technicianSelect" class="form-control" required>
            <option value="">-- Pilih Petugas / Teknisi --</option>
            @foreach($teknisi as $t)
              <option value="{{ $t->id }}">{{ $t->nama }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="adminNotesInput">Catatan / Instruksi Kerja untuk Teknisi</label>
          <textarea id="adminNotesInput" class="form-control" rows="3" placeholder="Contoh: Bawa peralatan cadangan, koordinasikan dengan penanggung jawab lab, prioritaskan sebelum jam kuliah..."></textarea>
        </div>

        <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--text-muted);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="16" x2="12" y2="12"/>
            <line x1="12" y1="8" x2="12.01" y2="8"/>
          </svg>
          Status laporan akan otomatis berganti ke 'Diteruskan ke Teknisi' dan notifikasi dikirim ke perangkat teknisi.
        </div>

        <div class="modal-footer" style="margin-top: 20px; padding: 12px 0 0 0; background: transparent;">
          <button type="button" class="btn btn-outline" onclick="closeDispatchModal()">Batal</button>
          <button type="submit" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="22" y1="2" x2="11" y2="13"/>
              <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
            Verifikasi & Kirim ke Teknisi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toast Notification -->
<div class="toast-container" id="toastContainer"></div>
@endsection

@section('scripts')

<script>
  let laporanData = [];
  let currentTypeFilter = 'all';
  let currentStatusFilter = 'all';
  
  async function fetchLaporan() {
    try {
      const response = await fetch('/api/pengaduan');
      const json = await response.json();
      if (json.success) {
        laporanData = json.data;
        updateCounts();
        renderReportsTable();
      }
    } catch (err) {
      console.error(err);
    }
  }

  function updateCounts() {
    document.getElementById('count-all').innerText = laporanData.length;
    document.getElementById('count-fasilitas').innerText = laporanData.filter(x => x.kategori?.jenis === 'fasilitas').length;
    document.getElementById('count-ethics').innerText = laporanData.filter(x => x.kategori?.jenis !== 'fasilitas').length;
  }

  function setFilterType(type, btn) {
    document.querySelectorAll('.filter-group .filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentTypeFilter = type;
    renderReportsTable();
  }

  function setStatusFilter(status, btn) {
    document.querySelectorAll('[id^="filter-status-"]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentStatusFilter = status;
    renderReportsTable();
  }

  function renderReportsTable() {
    const tbody = document.getElementById('reports-table-body');
    const searchQuery = document.getElementById('searchInput').value.toLowerCase();
    
    let filtered = laporanData;
    
    if (currentTypeFilter === 'fasilitas') {
      filtered = filtered.filter(x => x.kategori?.jenis === 'fasilitas');
    } else if (currentTypeFilter === 'kode_etik') {
      filtered = filtered.filter(x => x.kategori?.jenis !== 'fasilitas');
    }
    
    if (currentStatusFilter !== 'all') {
      filtered = filtered.filter(x => {
        if (currentStatusFilter === 'Menunggu Verifikasi') return x.status === 'menunggu';
        if (currentStatusFilter === 'Diteruskan ke Teknisi') return x.status === 'diproses';
        if (currentStatusFilter === 'Selesai') return x.status === 'selesai';
        return true;
      });
    }

    if (searchQuery) {
      filtered = filtered.filter(x => 
        (x.judul && x.judul.toLowerCase().includes(searchQuery)) ||
        (x.lokasi_kejadian && x.lokasi_kejadian.toLowerCase().includes(searchQuery)) ||
        (x.id && x.id.toString().includes(searchQuery))
      );
    }

    if (filtered.length === 0) {
      tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding:30px; color: #94a3b8;">Tidak ada data laporan yang sesuai filter</td></tr>`;
      return;
    }

    tbody.innerHTML = filtered.map(item => {
      let badgeClass = 'badge-resolved';
      let statusLabel = 'Selesai';
      if(item.status === 'menunggu') {
        badgeClass = 'badge-ethics';
        statusLabel = 'Menunggu';
      } else if(item.status === 'diproses') {
        badgeClass = 'badge-pending';
        statusLabel = 'Diproses Teknisi';
      }
      
      const judul = item.judul || '-';
      const type = item.kategori?.jenis === 'fasilitas' ? 'Fasilitas Sekolah' : 'Kode Etik';

      return `
        <tr>
          <td><strong>RPT-${item.id}</strong><br><span style="font-size:0.75rem; color:#94a3b8;">${new Date(item.created_at).toLocaleDateString()} - ${new Date(item.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span></td>
          <td><span class="badge ${item.kategori?.jenis === 'fasilitas' ? 'badge-facility' : 'badge-ethics'}">${type}</span></td>
          <td style="font-weight:600; color:var(--text-main);">${judul}</td>
          <td style="font-size:0.85rem;">${item.lokasi_kejadian || '-'}</td>
          <td>${item.is_anonim ? '<i>Anonim</i>' : 'Siswa'}</td>
          <td><span class="badge" style="background:#fef3c7; color:#d97706;">Sedang</span></td>
          <td><span class="badge ${badgeClass}">${statusLabel}</span></td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openDisposisiModal(${item.id})">Lihat / Disposisi</button>
          </td>
        </tr>
      `;
    }).join('');
  }

  function showToast(message) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
      <span>${message}</span>
    `;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  let selectedId = null;
  function openDisposisiModal(id) {
    selectedId = id;
    const item = laporanData.find(x => x.id === id);
    if(!item) return;

    document.getElementById('modal-report-id').innerText = `RPT-${item.id} - ${item.is_anonim ? 'Anonim' : 'Siswa'} (Dikirim: ${new Date(item.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})})`;
    document.getElementById('modal-report-title').innerText = item.judul;
    document.getElementById('modal-report-location').innerText = `Lokasi: ${item.lokasi_kejadian}`;
    document.getElementById('modal-report-desc').innerText = item.deskripsi;
    
    document.getElementById('dispatchModal').classList.add('active');
  }

  function closeDispatchModal() {
    document.getElementById('dispatchModal').classList.remove('active');
    selectedId = null;
  }

  async function handleDispatchSubmit(e) {
    e.preventDefault();
    if (!selectedId) return;

    try {
      const response = await fetch('/api/penugasan', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          pengaduan_id: selectedId,
          teknisi_id: document.getElementById('technicianSelect').value,
          catatan_admin: document.getElementById('adminNotesInput')?.value || ''
        })
      });

      const json = await response.json();
      if (json.success) {
        showToast('Laporan berhasil didisposisikan ke tim terkait!');
        closeDispatchModal();
        fetchLaporan(); // refresh table
      } else {
        alert('Gagal disposisi');
      }
    } catch (err) {
      console.error(err);
      alert('Terjadi kesalahan');
    }
  }

  document.addEventListener('DOMContentLoaded', fetchLaporan);
</script>

@endsection
