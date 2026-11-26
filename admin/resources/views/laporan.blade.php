@extends('layouts.admin')

@section('title', 'Laporan User')

@section('content')
<header class="topbar">
  <div class="page-title">
    <h2>Daftar Laporan User</h2>
    <p>Filter antara laporan fasilitas dan pelanggaran kode etik, lalu verifikasi, teruskan ke penanggung jawab, atau tolak laporan.</p>
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
  <div class="toolbar-container" style="flex-wrap: wrap; gap: 12px;">
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

    <!-- Search Input & Date Picker -->
    <div style="display: flex; gap: 8px; flex: 1; min-width: 300px; justify-content: flex-end; align-items: center;">
      <div class="search-input-wrap" style="flex: 1; max-width: 380px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" id="searchInput" class="search-input" placeholder="Cari ID, judul, lokasi, pelapor, jam (09:00), tanggal..." oninput="renderReportsTable()">
      </div>

      <div class="search-input-wrap" style="width: 160px;" title="Cari tanggal tertentu">
        <input type="date" id="dateFilterInput" class="search-input" style="padding-left: 10px;" onchange="renderReportsTable()">
      </div>

      <button type="button" class="btn btn-outline btn-sm" onclick="clearFilters()" title="Reset pencarian dan tanggal">Reset</button>
    </div>
  </div>

  <!-- Status & Urgensi Sub-Filters -->
  <div style="display: flex; gap: 16px; margin-bottom: 16px; align-items: center; flex-wrap: wrap;">
    <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
      <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">Status Respon:</span>
      <button class="btn btn-outline btn-sm active" id="filter-status-all" onclick="setStatusFilter('all', this)">Semua Status</button>
      <button class="btn btn-outline btn-sm" id="filter-status-pending" onclick="setStatusFilter('Menunggu Verifikasi', this)">Menunggu</button>
      <button class="btn btn-outline btn-sm" id="filter-status-dispatched" onclick="setStatusFilter('Diteruskan ke Teknisi', this)">Diproses</button>
      <button class="btn btn-outline btn-sm" id="filter-status-resolved" onclick="setStatusFilter('Selesai', this)">Selesai</button>
      <button class="btn btn-outline btn-sm" id="filter-status-rejected" onclick="setStatusFilter('Ditolak', this)" style="color: #dc2626;">Ditolak</button>
    </div>

    <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-left: auto;">
      <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">Urgensi:</span>
      <button class="btn btn-outline btn-sm active" id="filter-urgency-all" onclick="setUrgencyFilter('all', this)">Semua</button>
      <button class="btn btn-outline btn-sm" id="filter-urgency-urgent" onclick="setUrgencyFilter('urgent', this)" style="color: #b91c1c;">Mendesak</button>
      <button class="btn btn-outline btn-sm" id="filter-urgency-medium" onclick="setUrgencyFilter('medium', this)" style="color: #b45309;">Sedang</button>
      <button class="btn btn-outline btn-sm" id="filter-urgency-low" onclick="setUrgencyFilter('low', this)" style="color: #047857;">Rendah</button>
    </div>
  </div>

  <!-- Main Data Table -->
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID Tiket & Waktu</th>
            <th>Kategori & Jenis</th>
            <th>Judul Laporan & Kronologi</th>
            <th>Lokasi Insiden</th>
            <th>Pelapor (User)</th>
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
  <div class="modal-dialog" style="max-width: 620px;">
    <div class="modal-header">
      <div>
        <h3 id="modal-heading-title">Verifikasi & Teruskan Laporan</h3>
        <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">Teruskan tiket pekerjaan ini ke petugas penanggung jawab atau tolak jika tidak sesuai</p>
      </div>
      <button class="close-btn" onclick="closeDispatchModal()">&times;</button>
    </div>
    <div class="modal-body">
      <!-- Status Box if rejected -->
      <div id="modal-rejected-box" style="display: none; background: #fee2e2; border-left: 4px solid #dc2626; padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 16px;">
        <div style="font-size: 0.82rem; font-weight: 700; color: #991b1b; margin-bottom: 4px;">⚠️ Laporan Ini Telah Ditolak</div>
        <div id="modal-rejected-reason" style="font-size: 0.8rem; color: #7f1d1d;"></div>
      </div>

      <div style="background: var(--bg-subtle); padding: 14px 16px; border-radius: var(--radius-md); margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span id="modal-report-id" style="font-size: 0.78rem; font-weight: 700; color: var(--primary);"></span>
          <div style="display: flex; align-items: center; gap: 6px;">
            <span id="modal-report-urgency"></span>
            <span id="modal-report-badge"></span>
          </div>
        </div>
        <h4 id="modal-report-title" style="font-size: 0.98rem; font-weight: 600; margin-bottom: 4px; color: var(--text-main);"></h4>
        <div id="modal-report-location" style="font-size: 0.8rem; color: var(--text-muted);"></div>
        <div id="modal-report-desc" style="font-size: 0.82rem; color: var(--text-main); margin-top: 8px; line-height: 1.45; white-space: pre-wrap;"></div>
        <div id="modal-report-attachment" style="font-size: 0.82rem; margin-top: 10px;"></div>
      </div>

      <!-- Reject Confirmation Card (Hidden by Default) -->
      <div id="rejectConfirmationCard" style="display: none; background: #fff5f5; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 14px 16px; margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
          <strong style="color: #b91c1c; font-size: 0.88rem;">Tolak Laporan Ini</strong>
        </div>
        <p style="font-size: 0.78rem; color: #7f1d1d; margin-bottom: 10px;">
          Laporan akan berstatus <strong>Ditolak</strong> dan notifikasi alasan penolakan akan langsung dikirimkan ke perangkat pelapor.
        </p>
        <div class="form-group" style="margin-bottom: 10px;">
          <label class="form-label" for="rejectionReasonInput" style="color: #991b1b; font-size: 0.78rem;">Alasan Penolakan Laporan (Opsional)</label>
          <textarea id="rejectionReasonInput" class="form-control" rows="2" placeholder="Contoh: Deskripsi laporan kurang jelas, bukan pelanggaran tata tertib, laporan tidak valid..."></textarea>
        </div>
        <div style="display: flex; gap: 8px; justify-content: flex-end;">
          <button type="button" class="btn btn-outline btn-sm" onclick="toggleRejectSection(false)">Batal</button>
          <button type="button" class="btn btn-sm" style="background: #dc2626; color: white;" onclick="handleRejectSubmit()">
            Konfirmasi Tolak Laporan
          </button>
        </div>
      </div>

      <form id="dispatchForm" onsubmit="handleDispatchSubmit(event)">
        <input type="hidden" id="dispatch-report-id">
        
        <div class="form-group">
          <label class="form-label" for="technicianSelect" id="technicianSelectLabel">Pilih Penanggung Jawab *</label>
          <select id="technicianSelect" class="form-control" required>
            <option value="">-- Pilih Penanggung Jawab --</option>
          </select>
          <small id="technicianNotice" style="display: block; font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;"></small>
        </div>

        <div class="form-group">
          <label class="form-label" for="modalUrgencySelect">Tingkat Urgensi Penanganan *</label>
          <select id="modalUrgencySelect" class="form-control">
            <option value="low">Rendah (Penyelesaian santai / reguler)</option>
            <option value="medium" selected>Sedang (Normal / perlu ditindaklanjuti)</option>
            <option value="urgent">Mendesak (Prioritas tinggi / butuh respon cepat)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="adminNotesInput">Catatan / Instruksi Kerja Admin</label>
          <textarea id="adminNotesInput" class="form-control" rows="2" placeholder="Contoh: Segera tindaklanjuti, koordinasikan dengan wali kelas/sarpras..."></textarea>
        </div>

        <div style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; color: var(--text-muted);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="16" x2="12" y2="12"/>
            <line x1="12" y1="8" x2="12.01" y2="8"/>
          </svg>
          Status laporan akan berubah ke 'Diproses' dan notifikasi dikirim ke perangkat penanggung jawab terkait.
        </div>

        <div class="modal-footer" id="modalActionFooter" style="margin-top: 20px; padding: 12px 0 0 0; background: transparent; display: flex; justify-content: space-between; align-items: center;">
          <div>
            <button type="button" class="btn btn-outline" id="btnRejectToggle" onclick="toggleRejectSection(true)" style="color: #dc2626; border-color: #fca5a5;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px; margin-right: 2px;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
              Tolak Laporan
            </button>
          </div>
          <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-outline" onclick="closeDispatchModal()">Tutup</button>
            <button type="submit" class="btn btn-primary" id="btnSubmitDispatch">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
              </svg>
              Verifikasi & Teruskan
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toast Notification -->
<div class="toast-container" id="toastContainer"></div>
@endsection

@section('scripts')
<style>
  .badge-rejected {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
  }
</style>

<script>
  let laporanData = [];
  let currentTypeFilter = 'all';
  let currentStatusFilter = 'all';
  let currentUrgencyFilter = 'all';
  const allOfficers = @json($teknisi);
  let selectedId = null;

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    })[character]);
  }

  function reportAttachment(value) {
    if (typeof value !== 'string') return null;

    try {
      const fileUrl = new URL(value, window.location.origin);
      if (fileUrl.origin !== window.location.origin || !fileUrl.pathname.startsWith('/storage/bukti/')) {
        return null;
      }

      const storedPath = fileUrl.pathname.slice('/storage/'.length);
      const url = `/api/gambar/${storedPath.split('/').map(encodeURIComponent).join('/')}`;
      const extension = storedPath.split('.').pop().toLowerCase();

      return {
        url,
        isImage: ['jpg', 'jpeg', 'png'].includes(extension),
      };
    } catch {
      return null;
    }
  }

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
      showToast('Gagal memuat data laporan.', true);
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

  function setUrgencyFilter(urgency, btn) {
    document.querySelectorAll('[id^="filter-urgency-"]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentUrgencyFilter = urgency;
    renderReportsTable();
  }

  function clearFilters() {
    document.getElementById('searchInput').value = '';
    const dateInput = document.getElementById('dateFilterInput');
    if (dateInput) dateInput.value = '';
    currentTypeFilter = 'all';
    currentStatusFilter = 'all';
    currentUrgencyFilter = 'all';
    document.querySelectorAll('.filter-group .filter-btn').forEach(b => b.classList.remove('active'));
    document.querySelector('.filter-group .filter-btn[data-type="all"]')?.classList.add('active');
    document.querySelectorAll('[id^="filter-status-"]').forEach(b => b.classList.remove('active'));
    document.getElementById('filter-status-all')?.classList.add('active');
    document.querySelectorAll('[id^="filter-urgency-"]').forEach(b => b.classList.remove('active'));
    document.getElementById('filter-urgency-all')?.classList.add('active');
    renderReportsTable();
  }

  function urgencyBadge(urgency) {
    const badges = {
      urgent: ['Mendesak', '#fee2e2', '#b91c1c', '#fca5a5'],
      medium: ['Sedang', '#fef3c7', '#b45309', '#fcd34d'],
      low: ['Rendah', '#dcfce7', '#047857', '#86efac']
    };
    const key = (urgency === 'urgent' || urgency === 'low' || urgency === 'medium') ? urgency : 'medium';
    const [label, background, color, border] = badges[key];
    return `<span style="display:inline-block;padding:3px 9px;border-radius:999px;background:${background};color:${color};border:1px solid ${border};font-size:.72rem;font-weight:700;">${label}</span>`;
  }

  function renderReportsTable() {
    const tbody = document.getElementById('reports-table-body');
    const searchQuery = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();
    const dateQuery = document.getElementById('dateFilterInput')?.value;
    
    let filtered = laporanData;
    
    // Type Filter
    if (currentTypeFilter === 'fasilitas') {
      filtered = filtered.filter(x => x.kategori?.jenis === 'fasilitas');
    } else if (currentTypeFilter === 'kode_etik') {
      filtered = filtered.filter(x => x.kategori?.jenis !== 'fasilitas');
    }
    
    // Status Filter
    if (currentStatusFilter !== 'all') {
      filtered = filtered.filter(x => {
        if (currentStatusFilter === 'Menunggu Verifikasi') return x.status === 'menunggu';
        if (currentStatusFilter === 'Diteruskan ke Teknisi') return x.status === 'diproses';
        if (currentStatusFilter === 'Selesai') return x.status === 'selesai';
        if (currentStatusFilter === 'Ditolak') return x.status === 'ditolak';
        return true;
      });
    }

    // Urgency Filter
    if (currentUrgencyFilter !== 'all') {
      filtered = filtered.filter(x => {
        const itemUrgency = x.urgency || 'medium';
        return itemUrgency === currentUrgencyFilter;
      });
    }

    // Specific Date Input Filter
    if (dateQuery) {
      filtered = filtered.filter(x => {
        if (!x.created_at) return false;
        return x.created_at.startsWith(dateQuery);
      });
    }

    // Text & Date/Time Search
    if (searchQuery) {
      filtered = filtered.filter(x => {
        const idStr = String(x.id || '');
        const rptIdStr = `rpt-${idStr}`;
        const judul = (x.judul || '').toLowerCase();
        const lokasi = (x.lokasi_kejadian || '').toLowerCase();
        const pelapor = x.is_anonim ? 'anonim' : (x.pelapor?.nama || 'user').toLowerCase();
        const urgency = (x.urgency || 'medium').toLowerCase();
        const urgencyLabel = urgency === 'urgent' ? 'mendesak' : (urgency === 'low' ? 'rendah' : 'sedang');
        
        let dateIdStr = '';
        let dateFullStr = '';
        let timeStr = '';
        let rawDate = x.created_at ? String(x.created_at).toLowerCase() : '';

        if (x.created_at) {
          const d = new Date(x.created_at);
          if (!isNaN(d.getTime())) {
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            dateIdStr = `${day}/${month}/${year}`;
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            timeStr = `${hours}:${minutes}`;
            try {
              dateFullStr = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).toLowerCase();
            } catch (e) {
              dateFullStr = '';
            }
          }
        }

        return idStr.includes(searchQuery) ||
          rptIdStr.includes(searchQuery) ||
          judul.includes(searchQuery) ||
          lokasi.includes(searchQuery) ||
          pelapor.includes(searchQuery) ||
          urgency.includes(searchQuery) ||
          urgencyLabel.includes(searchQuery) ||
          rawDate.includes(searchQuery) ||
          dateIdStr.includes(searchQuery) ||
          timeStr.includes(searchQuery) ||
          timeStr.replace(':', '.').includes(searchQuery) ||
          dateFullStr.includes(searchQuery);
      });
    }

    if (filtered.length === 0) {
      tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding:30px; color: #94a3b8;">Tidak ada data laporan yang sesuai filter</td></tr>`;
      return;
    }

    tbody.innerHTML = filtered.map(item => {
      let badgeClass = 'badge-resolved';
      let statusLabel = 'Selesai';
      if (item.status === 'menunggu') {
        badgeClass = 'badge-ethics';
        statusLabel = 'Menunggu';
      } else if (item.status === 'diproses') {
        badgeClass = 'badge-pending';
        statusLabel = 'Diproses';
      } else if (item.status === 'ditolak') {
        badgeClass = 'badge-rejected';
        statusLabel = 'Ditolak';
      }
      
      const judul = item.judul || '-';
      const type = item.kategori?.jenis === 'fasilitas' ? 'Fasilitas Sekolah' : 'Kode Etik';
      const createdDate = new Date(item.created_at);
      const formattedDate = !isNaN(createdDate.getTime())
        ? `${createdDate.toLocaleDateString('id-ID')} ${createdDate.toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'})}`
        : (item.created_at || '-');
      const attachment = reportAttachment(item.file_bukti_url);
      const attachmentMarkup = attachment
        ? attachment.isImage
          ? `<a href="${escapeHtml(attachment.url)}" target="_blank" rel="noopener" title="Lihat bukti foto">
              <img src="${escapeHtml(attachment.url)}" alt="Bukti foto ${escapeHtml(judul)}" style="display:block;width:52px;height:52px;object-fit:cover;border-radius:6px;margin-top:6px;">
            </a>`
          : '<span style="display:block;margin-top:6px;font-size:.72rem;color:var(--text-muted);">Lampiran tersedia</span>'
        : '';

      return `
        <tr>
          <td><strong>RPT-${escapeHtml(item.id)}</strong><br><span style="font-size:0.75rem; color:#94a3b8;">${formattedDate}</span></td>
          <td><span class="badge ${item.kategori?.jenis === 'fasilitas' ? 'badge-facility' : 'badge-ethics'}">${escapeHtml(type)}</span></td>
          <td style="font-weight:600; color:var(--text-main);">${escapeHtml(judul)}${attachmentMarkup}</td>
          <td style="font-size:0.85rem;">${escapeHtml(item.lokasi_kejadian || '-')}</td>
          <td>${item.is_anonim ? '<i>Anonim</i>' : escapeHtml(item.pelapor?.nama || 'User')}</td>
          <td>${urgencyBadge(item.urgency)}</td>
          <td><span class="badge ${badgeClass}">${statusLabel}</span></td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openDisposisiModal(${Number(item.id)})">
              ${item.status === 'menunggu' ? 'Lihat / Disposisi' : 'Lihat Detail'}
            </button>
          </td>
        </tr>
      `;
    }).join('');
  }

  function showToast(message, isError = false) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.style.borderLeft = `4px solid ${isError ? '#ef4444' : '#4ade80'}`;
    const text = document.createElement('span');
    text.textContent = message;
    toast.appendChild(text);
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  function openDisposisiModal(id) {
    selectedId = id;
    const item = laporanData.find(x => x.id === id);
    if (!item) return;

    toggleRejectSection(false);

    const createdDate = new Date(item.created_at);
    const dateFormatted = !isNaN(createdDate.getTime())
      ? `${createdDate.toLocaleDateString('id-ID')} pukul ${createdDate.toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'})}`
      : (item.created_at || '-');

    document.getElementById('modal-report-id').innerText = `RPT-${item.id} - ${item.is_anonim ? 'Pelapor Anonim' : (item.pelapor?.nama || 'User')} (Dibuat: ${dateFormatted})`;
    document.getElementById('modal-report-title').innerText = item.judul;
    document.getElementById('modal-report-urgency').innerHTML = urgencyBadge(item.urgency);
    document.getElementById('modal-report-location').innerText = `Lokasi: ${item.lokasi_kejadian || '-'}`;
    document.getElementById('modal-report-desc').innerText = item.deskripsi || '-';
    
    // Status Badge in modal
    let statusBadgeText = item.status.toUpperCase();
    if (item.status === 'menunggu') statusBadgeText = 'MENUNGGU VERIFIKASI';
    else if (item.status === 'diproses') statusBadgeText = 'SEDANG DIPROSES';
    else if (item.status === 'selesai') statusBadgeText = 'SELESAI';
    else if (item.status === 'ditolak') statusBadgeText = 'DITOLAK';
    document.getElementById('modal-report-badge').innerHTML = `<span class="badge" style="font-size: 0.7rem; font-weight: 700;">${statusBadgeText}</span>`;

    // Rejection Box
    const rejectedBox = document.getElementById('modal-rejected-box');
    if (item.status === 'ditolak') {
      rejectedBox.style.display = 'block';
      document.getElementById('modal-rejected-reason').innerText = item.alasan_penolakan
        ? `Alasan: ${item.alasan_penolakan}`
        : 'Laporan ini ditolak oleh admin dan tidak diproses ke penanggung jawab.';
    } else {
      rejectedBox.style.display = 'none';
    }

    // Attachment
    const attachment = document.getElementById('modal-report-attachment');
    const reportFile = reportAttachment(item.file_bukti_url);
    if (reportFile) {
      const link = document.createElement('a');
      link.href = reportFile.url;
      link.target = '_blank';
      link.rel = 'noopener';
      link.textContent = '📎 Buka Lampiran / Bukti Foto';
      link.style.color = 'var(--primary)';
      link.style.fontWeight = '600';
      if (reportFile.isImage) {
        const preview = document.createElement('img');
        preview.src = reportFile.url;
        preview.alt = 'Foto lampiran laporan';
        preview.style.display = 'block';
        preview.style.maxWidth = '100%';
        preview.style.maxHeight = '280px';
        preview.style.objectFit = 'contain';
        preview.style.marginBottom = '8px';
        preview.onerror = () => preview.remove();
        attachment.replaceChildren(preview, link);
      } else {
        attachment.replaceChildren(link);
      }
    } else {
      attachment.textContent = item.file_bukti_url
        ? 'Lampiran tidak tersedia.'
        : 'Tidak ada lampiran bukti.';
    }

    // Dropdown penanggung jawab hanya menampilkan teknisi dengan sub-role yang sesuai.
    const isFacility = item.kategori?.jenis === 'fasilitas';
    const selectLabel = document.getElementById('technicianSelectLabel');
    const selectElem = document.getElementById('technicianSelect');
    const noticeElem = document.getElementById('technicianNotice');

    selectLabel.innerText = isFacility
      ? 'Pilih Teknisi Lapangan (Fasilitas & Sarpras) *'
      : 'Pilih Teknisi Konseling (Kode Etik) *';

    // Filter officers
    const eligibleOfficers = allOfficers.filter(o => {
      const isCounseling = ['konseling', 'etik'].includes(o.bidang) || o.role === 'guru_bk';
      return isFacility ? !isCounseling : isCounseling;
    });

    selectElem.innerHTML = `<option value="">-- Pilih ${isFacility ? 'Teknisi Lapangan' : 'Teknisi Konseling'} --</option>`;
    
    let selectableCount = 0;
    eligibleOfficers.forEach(o => {
      const activeCount = Number(o.tugas_aktif_count || 0);
      const isFull = activeCount >= 5;
      const opt = document.createElement('option');
      opt.value = o.id;
      if (isFull) {
        opt.disabled = true;
        opt.textContent = `${o.nama} (Penuh - 5 tugas aktif)`;
        opt.style.color = '#94a3b8';
      } else {
        selectableCount++;
        opt.textContent = `${o.nama} (${activeCount}/5 tugas aktif)`;
      }
      selectElem.appendChild(opt);
    });

    if (eligibleOfficers.length === 0) {
      noticeElem.innerHTML = `<span style="color: #dc2626;">Belum ada akun ${isFacility ? 'Teknisi Lapangan' : 'Teknisi Konseling'} yang terdaftar di sistem.</span>`;
    } else if (selectableCount === 0) {
      noticeElem.innerHTML = `<span style="color: #dc2626;">Seluruh ${isFacility ? 'teknisi lapangan' : 'teknisi konseling'} saat ini sedang menangani kuota maksimal 5 tugas aktif.</span>`;
    } else {
      noticeElem.innerText = `Menampilkan ${selectableCount} petugas tersedia (${isFacility ? 'Teknisi Lapangan' : 'Teknisi Konseling'}) dengan kuota tugas < 5.`;
    }

    // Urgency Select in Modal
    const urgencySelect = document.getElementById('modalUrgencySelect');
    if (urgencySelect) {
      urgencySelect.value = item.urgency || 'medium';
    }

    // Admin Notes
    document.getElementById('adminNotesInput').value = '';

    // Disposisi controls availability
    const canDispatch = item.status === 'menunggu';
    selectElem.disabled = !canDispatch;
    document.getElementById('adminNotesInput').disabled = !canDispatch;
    if (urgencySelect) urgencySelect.disabled = !canDispatch;
    
    document.getElementById('btnSubmitDispatch').disabled = !canDispatch;
    document.getElementById('btnRejectToggle').style.display = canDispatch ? 'inline-flex' : 'none';
    document.getElementById('btnSubmitDispatch').style.display = canDispatch ? 'inline-flex' : 'none';

    document.getElementById('dispatchModal').classList.add('active');
  }

  function toggleRejectSection(show = true) {
    const card = document.getElementById('rejectConfirmationCard');
    const dispatchForm = document.getElementById('dispatchForm');
    if (card) {
      card.style.display = show ? 'block' : 'none';
    }
    if (dispatchForm) {
      dispatchForm.style.opacity = show ? '0.45' : '1';
      document.getElementById('btnSubmitDispatch').disabled = show;
    }
    if (!show) {
      document.getElementById('rejectionReasonInput').value = '';
    }
  }

  async function handleRejectSubmit() {
    if (!selectedId) return;

    const reason = document.getElementById('rejectionReasonInput')?.value || '';

    try {
      const response = await fetch(`/api/pengaduan/${selectedId}/reject`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          alasan_penolakan: reason
        })
      });

      const responseText = await response.text();
      let json;
      try {
        json = JSON.parse(responseText);
      } catch {
        showToast(`Server gagal memproses penolakan (HTTP ${response.status}).`, true);
        return;
      }

      if (response.ok && json.success) {
        showToast('Laporan berhasil ditolak.');
        closeDispatchModal();
        fetchLaporan();
      } else {
        const validationErrors = json.errors
          ? Object.values(json.errors).flat().join(' ')
          : '';
        showToast(json.message || validationErrors || `Gagal menolak laporan (HTTP ${response.status}).`, true);
      }
    } catch (err) {
      console.error(err);
      showToast('Terjadi kesalahan saat memproses penolakan.', true);
    }
  }

  function closeDispatchModal() {
    document.getElementById('dispatchModal').classList.remove('active');
    toggleRejectSection(false);
    selectedId = null;
  }

  async function handleDispatchSubmit(e) {
    e.preventDefault();
    if (!selectedId) return;

    const techId = document.getElementById('technicianSelect').value;
    if (!techId) {
      showToast('Harap pilih petugas penanggung jawab.', true);
      return;
    }

    const urgencyVal = document.getElementById('modalUrgencySelect')?.value || 'medium';

    try {
      const response = await fetch('/api/penugasan', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          pengaduan_id: selectedId,
          teknisi_id: techId,
          urgency: urgencyVal,
          catatan_admin: document.getElementById('adminNotesInput')?.value || ''
        })
      });

      const json = await response.json();
      if (json.success) {
        showToast('Laporan berhasil didisposisikan ke penanggung jawab terkait!');
        closeDispatchModal();
        fetchLaporan(); // refresh table
      } else {
        const validationErrors = json.errors
          ? Object.values(json.errors).flat().join(' ')
          : '';
        showToast(json.message || validationErrors || 'Gagal mendisposisikan laporan', true);
      }
    } catch (err) {
      console.error(err);
      showToast('Terjadi kesalahan saat mengirim disposisi.', true);
    }
  }

  document.addEventListener('DOMContentLoaded', fetchLaporan);
</script>
@endsection
