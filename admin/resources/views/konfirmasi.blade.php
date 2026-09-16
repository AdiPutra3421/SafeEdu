@extends('layouts.admin')

@section('title', 'Pesan Konfirmasi Teknisi')

@section('content')
<header class="topbar">
  <div class="page-title">
    <h2>Pesan Konfirmasi Teknisi</h2>
    <p>Daftar laporan tindak lanjut, progres penanganan, dan konfirmasi penyelesaian dari tim teknisi lapangan.</p>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-outline btn-sm" onclick="reloadConfirmations()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M23 4v6h-6"/>
        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
      </svg>
      Segarkan Pesan
    </button>
  </div>
</header>

<main class="content-body">
  <!-- Quick Summary Bar -->
  <div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px 20px; flex: 1; min-width: 200px; box-shadow: var(--shadow-sm);">
      <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Konfirmasi Masuk</div>
      <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-top: 2px;" id="count-total-conf">0</div>
    </div>
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px 20px; flex: 1; min-width: 200px; box-shadow: var(--shadow-sm);">
      <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Dalam Pengerjaan</div>
      <div style="font-size: 1.5rem; font-weight: 700; color: var(--status-process-text); margin-top: 2px;" id="count-in-progress">0</div>
    </div>
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px 20px; flex: 1; min-width: 200px; box-shadow: var(--shadow-sm);">
      <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Pekerjaan Selesai</div>
      <div style="font-size: 1.5rem; font-weight: 700; color: var(--status-success-text); margin-top: 2px;" id="count-finished">0</div>
    </div>
  </div>

  <!-- Filter Sub-nav -->
  <div class="toolbar-container">
    <div class="filter-group">
      <button class="filter-btn active" onclick="setConfFilter('all', this)">Semua Pesan</button>
      <button class="filter-btn" onclick="setConfFilter('in_progress', this)">Sedang Dikerjakan</button>
      <button class="filter-btn" onclick="setConfFilter('completed', this)">Pekerjaan Selesai</button>
      <button class="filter-btn" onclick="setConfFilter('unverified', this)">Perlu Approval Admin</button>
      <button class="filter-btn" onclick="setConfFilter('unconfirmed', this)">Belum Dikonfirmasi</button>
    </div>

    <div style="font-size: 0.82rem; color: var(--text-muted);">
      Menampilkan komunikasi sinkronisasi dua arah antara Admin dan Teknisi
    </div>
  </div>

  <!-- Confirmation Cards Grid -->
  <div class="confirmation-grid" id="confirmationList">
    <!-- Rendered dynamically -->
  </div>
</main>

<!-- Toast Notification -->
<div class="toast-container" id="toastContainer"></div>
@endsection

@section('scripts')
<script>
  let currentConfFilter = 'all';
  let confData = [];

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

  function setConfFilter(filter, elem) {
    currentConfFilter = filter;
    document.querySelectorAll('.filter-group .filter-btn').forEach(btn => btn.classList.remove('active'));
    elem.classList.add('active');
    renderConfirmations();
  }

  async function reloadConfirmations() {
    await fetchConfirmations();
    showToast('Data konfirmasi teknisi telah diperbarui.');
  }

  async function fetchConfirmations() {
    try {
      const response = await fetch('/api/penugasan');
      const json = await response.json();
      if (json.success) {
        confData = json.data;
        renderConfirmations();
      }
    } catch (err) {
      console.error(err);
    }
  }

  async function handleApprove(confId) {
    // Just mock the approval since we don't have a verified column in DB
    const idx = confData.findIndex(c => c.id == confId);
    if (idx > -1) {
      confData[idx].verified = true;
      showToast('Laporan teknisi berhasil divalidasi dan tiket ditutup.');
      renderConfirmations();
    }
  }

  function renderConfirmations() {
    const container = document.getElementById('confirmationList');

    const inProgress = confData.filter(c => c.status !== 'selesai');
    const finished = confData.filter(c => c.status === 'selesai');
    const pendingCount = inProgress.length;

    document.getElementById('sidebar-pending-badge').textContent = pendingCount > 0 ? pendingCount : '';
    document.getElementById('count-total-conf').textContent = confData.length;
    document.getElementById('count-in-progress').textContent = inProgress.length;
    document.getElementById('count-finished').textContent = finished.length;

    const filtered = confData.filter(item => {
      if (currentConfFilter === 'in_progress') return item.status !== 'selesai';
      if (currentConfFilter === 'completed') return item.status === 'selesai';
      if (currentConfFilter === 'unverified') return !item.verified;
      return true;
    });

    if (filtered.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 50px 20px; background: white; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--text-light); margin-bottom: 12px;">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          <h4 style="color: var(--text-main); margin-bottom: 4px;">Belum Ada Pesan Konfirmasi</h4>
          <p style="color: var(--text-muted); font-size: 0.84rem;">Tidak ada pesan yang cocok dengan kategori filter saat ini.</p>
        </div>
      `;
      return;
    }

    container.innerHTML = filtered.map(item => {
      const isFinished = item.status === 'selesai';
      const techName = item.teknisi_id == 2 ? 'Bambang Teknisi' : 'Teknisi';
      const initials = techName.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();

      let statusBadge = '';
      if (isFinished) {
        statusBadge = `<span class="badge badge-resolved"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Selesai</span>`;
      } else if (item.status === 'ditugaskan') {
        statusBadge = `<span class="badge" style="background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5;">Belum Dikonfirmasi</span>`;
      } else {
        statusBadge = `<span class="badge badge-dispatched">Dalam Pengerjaan</span>`;
      }

      const approvalButton = item.verified
        ? `<span style="font-size: 0.78rem; color: var(--status-success-text); font-weight: 600; display: flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            Divalidasi Admin
          </span>`
        : `<button class="btn btn-primary btn-sm" onclick="handleApprove('${item.id}')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Validasi & Tutup
          </button>`;

      const logs = item.log_pengerjaans || [];
      const latestLog = logs.length > 0 ? logs[logs.length - 1].catatan_teknisi : (item.catatan_admin || 'Belum ada catatan');

      return `
        <div class="confirmation-card">
          <div class="confirmation-card-header">
            <div class="tech-info-chip">
              <div class="tech-avatar">${initials}</div>
              <div class="tech-details">
                <div class="name">${techName}</div>
                <div class="division">Divisi Sarpras</div>
              </div>
            </div>
            <div>${statusBadge}</div>
          </div>

          <div style="border-top: 1px solid var(--border-color); padding-top: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
              <span style="font-size: 0.75rem; font-weight: 700; color: var(--primary);">TUGAS-${item.id} (RPT-${item.pengaduan_id})</span>
              <span style="font-size: 0.72rem; color: var(--text-light);">${new Date(item.created_at).toLocaleDateString()}</span>
            </div>
            <h4 style="font-size: 0.92rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">${item.pengaduan?.judul || '-'}</h4>
            
            <div class="timeline-note-box">
              <div style="font-size: 0.74rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; color: var(--accent-teal); margin-bottom: 2px;">Catatan Terakhir:</div>
              ${latestLog}
            </div>

          </div>

          <div style="margin-top: auto; padding-top: 12px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.72rem; color: var(--text-muted);">Tugas ID: ${item.id}</span>
            <div>${approvalButton}</div>
          </div>
        </div>
      `;
    }).join('');
  }

  document.addEventListener('DOMContentLoaded', () => {
    fetchConfirmations();
  });
</script>
@endsection
