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
            <option value="Bambang Sudirjo (Divisi Perpipaan & Sanitasi)">Bambang Sudirjo - Divisi Perpipaan & Sanitasi</option>
            <option value="Eko Prasetyo (Divisi IT & Multimedia)">Eko Prasetyo - Divisi IT & Multimedia</option>
            <option value="Agus Santoso (Divisi Kelistrikan & AC)">Agus Santoso - Divisi Kelistrikan & AC</option>
            <option value="Suryono (Divisi Pengawas & Sarpras)">Suryono - Divisi Pengawas Keamanan & CCTV</option>
            <option value="Tim Disiplin & Konseling Kampus">Tim Satgas Disiplin & BK (Khusus Kode Etik)</option>
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
  let currentTypeFilter = 'all';
  let currentStatusFilter = 'all';

  function setFilterType(type, element) {
    currentTypeFilter = type;
    document.querySelectorAll('.toolbar-container .filter-btn').forEach(btn => btn.classList.remove('active'));
    element.classList.add('active');
    renderReportsTable();
  }

  function setStatusFilter(status, element) {
    currentStatusFilter = status;
    document.querySelectorAll('#filter-status-all, #filter-status-pending, #filter-status-dispatched, #filter-status-resolved').forEach(btn => {
      btn.classList.remove('btn-primary');
      btn.classList.add('btn-outline');
    });
    element.classList.remove('btn-outline');
    element.classList.add('btn-primary');
    renderReportsTable();
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
    setTimeout(() => {
      toast.remove();
    }, 3500);
  }

  function openDispatchModal(reportId) {
    const reports = SafeEduStore.getReports();
    const report = reports.find(r => r.id === reportId);
    if (!report) return;

    document.getElementById('dispatch-report-id').value = report.id;
    document.getElementById('modal-report-id').textContent = report.id;
    document.getElementById('modal-report-title').textContent = report.title;
    document.getElementById('modal-report-location').textContent = '📍 ' + report.location + ' | Kategori: ' + report.category;
    document.getElementById('modal-report-desc').textContent = '"' + report.description + '"';
    document.getElementById('adminNotesInput').value = report.adminNotes || '';

    const badgeElem = document.getElementById('modal-report-badge');
    if (report.type === 'fasilitas') {
      badgeElem.className = 'badge badge-facility';
      badgeElem.textContent = 'Fasilitas Kampus';
    } else {
      badgeElem.className = 'badge badge-ethics';
      badgeElem.textContent = 'Pelanggaran Kode Etik';
    }

    // Auto select suggested technician
    const select = document.getElementById('technicianSelect');
    if (report.type === 'kode_etik') {
      select.value = 'Tim Disiplin & Konseling Kampus';
    } else if (report.category.includes('Air') || report.category.includes('Sanitasi')) {
      select.value = 'Bambang Sudirjo (Divisi Perpipaan & Sanitasi)';
    } else if (report.category.includes('Multimedia') || report.category.includes('IT')) {
      select.value = 'Eko Prasetyo (Divisi IT & Multimedia)';
    } else {
      select.value = 'Agus Santoso (Divisi Kelistrikan & AC)';
    }

    document.getElementById('dispatchModal').classList.add('active');
  }

  function closeDispatchModal() {
    document.getElementById('dispatchModal').classList.remove('active');
  }

  function handleDispatchSubmit(event) {
    event.preventDefault();
    const reportId = document.getElementById('dispatch-report-id').value;
    const technician = document.getElementById('technicianSelect').value;
    const adminNotes = document.getElementById('adminNotesInput').value;

    if (!technician) return;

    SafeEduStore.dispatchToTechnician(reportId, technician, adminNotes);
    closeDispatchModal();
    showToast(`Tiket ${reportId} berhasil diverifikasi & dikirim ke ${technician}`);
    updateBadgeCounts();
    renderReportsTable();
  }

  function updateBadgeCounts() {
    const reports = SafeEduStore.getReports();
    const stats = SafeEduStore.getStats();

    document.getElementById('count-all').textContent = reports.length;
    document.getElementById('count-fasilitas').textContent = stats.fasilitasCount;
    document.getElementById('count-ethics').textContent = stats.ethicsCount;
    document.getElementById('sidebar-pending-badge').textContent = stats.pendingCount;
  }

  function renderReportsTable() {
    const reports = SafeEduStore.getReports();
    const search = (document.getElementById('searchInput').value || '').toLowerCase();
    const tbody = document.getElementById('reports-table-body');

    const filtered = reports.filter(item => {
      // Type filter
      if (currentTypeFilter !== 'all' && item.type !== currentTypeFilter) return false;

      // Status filter
      if (currentStatusFilter !== 'all' && item.status !== currentStatusFilter) return false;

      // Search filter
      if (search) {
        const matchTitle = item.title.toLowerCase().includes(search);
        const matchId = item.id.toLowerCase().includes(search);
        const matchLocation = item.location.toLowerCase().includes(search);
        const matchReporter = item.reporterName.toLowerCase().includes(search);
        const matchCat = item.category.toLowerCase().includes(search);
        if (!matchTitle && !matchId && !matchLocation && !matchReporter && !matchCat) return false;
      }

      return true;
    });

    if (filtered.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; opacity: 0.5;">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <div>Tidak ditemukan laporan yang sesuai dengan filter atau kata kunci pencarian.</div>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = filtered.map(item => {
      const isFacility = item.type === 'fasilitas';
      const typeBadge = isFacility
        ? `<span class="badge badge-facility"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="2" y="3" width="20" height="14" rx="2"/></svg> Fasilitas</span>`
        : `<span class="badge badge-ethics"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Kode Etik</span>`;

      let statusBadge = '';
      if (item.status === 'Menunggu Verifikasi') {
        statusBadge = `<span class="badge badge-pending">Menunggu Verifikasi</span>`;
      } else if (item.status === 'Diteruskan ke Teknisi') {
        statusBadge = `<span class="badge badge-dispatched">Diteruskan ke Teknisi</span>`;
      } else {
        statusBadge = `<span class="badge badge-resolved">Selesai</span>`;
      }

      const urgencyColor = item.urgency === 'Mendesak' || item.urgency === 'Tinggi' ? 'urgency-high' : 'urgency-medium';

      const actionButton = item.status === 'Menunggu Verifikasi'
        ? `<button class="btn btn-primary btn-sm" onclick="openDispatchModal('${item.id}')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            Verifikasi & Kirim
          </button>`
        : `<button class="btn btn-outline btn-sm" onclick="openDispatchModal('${item.id}')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Ubah Disposisi
          </button>`;

      const reporterDisplay = item.isAnonymous
        ? `<span style="display: flex; align-items: center; gap: 4px; color: var(--status-danger-text); font-weight: 600; font-size: 0.8rem;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            ${item.reporterName}
          </span>`
        : `<div>
            <div style="font-weight: 600; font-size: 0.84rem;">${item.reporterName}</div>
            <div style="font-size: 0.74rem; color: var(--text-muted);">${item.reporterClass}</div>
          </div>`;

      return `
        <tr>
          <td><strong>${item.id}</strong></td>
          <td>
            ${typeBadge}
            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">${item.category}</div>
          </td>
          <td>
            <div style="font-weight: 600; color: var(--text-main); margin-bottom: 2px;">${item.title}</div>
            <div style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.35; max-width: 280px;">${item.description}</div>
          </td>
          <td>
            <div style="font-size: 0.84rem;">${item.location}</div>
            <div style="font-size: 0.72rem; color: var(--text-muted);">${item.date}</div>
          </td>
          <td>${reporterDisplay}</td>
          <td><span class="urgency-indicator ${urgencyColor}">● ${item.urgency}</span></td>
          <td>
            ${statusBadge}
            ${item.assignedTechnician ? `<div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">Petugas: ${item.assignedTechnician.split('(')[0]}</div>` : ''}
          </td>
          <td>${actionButton}</td>
        </tr>
      `;
    }).join('');
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Check query param if navigated from Dashboard
    const urlParams = new URLSearchParams(window.location.search);
    const targetId = urlParams.get('id');

    updateBadgeCounts();
    renderReportsTable();

    if (targetId) {
      openDispatchModal(targetId);
    }
  });
</script>
@endsection
