@extends('layouts.admin')

@section('title', 'Manajemen Data Teknisi')

@section('content')
<header class="topbar">
  <div class="page-title">
    <h2>Manajemen Data Teknisi</h2>
    <p>Kelola data akun petugas teknisi lapangan, pantau beban kerja serta riwayat penanganan tugas fasilitas sekolah.</p>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-primary" onclick="openTeknisiModal()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -3px; margin-right: 4px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      Tambah Teknisi Baru
    </button>
  </div>
</header>

<main class="content-body">
  <!-- Summary Stat Cards -->
  <div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 20px; flex: 1; min-width: 200px; box-shadow: var(--shadow-sm);">
      <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Teknisi Terdaftar</div>
      <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-main); margin-top: 2px;" id="stat-total-teknisi">—</div>
    </div>
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 20px; flex: 1; min-width: 200px; box-shadow: var(--shadow-sm);">
      <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Sedang Ada Tugas Aktif</div>
      <div style="font-size: 1.6rem; font-weight: 700; color: var(--status-process-text); margin-top: 2px;" id="stat-active-teknisi">—</div>
    </div>
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 20px; flex: 1; min-width: 200px; box-shadow: var(--shadow-sm);">
      <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Tugas Terselesaikan</div>
      <div style="font-size: 1.6rem; font-weight: 700; color: var(--status-success-text); margin-top: 2px;" id="stat-finished-tasks">—</div>
    </div>
  </div>

  <!-- Search & Toolbar -->
  <div class="toolbar-container" style="margin-bottom: 16px;">
    <div class="search-input-wrap" style="max-width: 380px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/>
        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" id="searchInput" class="search-input" placeholder="Cari nama atau email/username teknisi..." oninput="handleSearch()">
    </div>
    <div style="font-size: 0.82rem; color: var(--text-muted);">
      Menampilkan <strong id="count-filtered">0</strong> akun teknisi
    </div>
  </div>

  <!-- Main Table -->
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Teknisi</th>
            <th>Email / Username Login</th>
            <th>Beban Penugasan</th>
            <th>Terdaftar Pada</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="teknisi-table-body">
          <!-- Dynamically Rendered -->
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal Add/Edit Teknisi -->
<div class="modal-overlay" id="teknisiModal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div>
        <h3 id="modal-title">Tambah Teknisi Baru</h3>
        <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">Akun teknisi dapat langsung digunakan untuk login di aplikasi SafeEdu Teknisi.</p>
      </div>
      <button class="close-btn" onclick="closeTeknisiModal()">&times;</button>
    </div>
    <div class="modal-body">
      <form id="teknisiForm" onsubmit="handleTeknisiSubmit(event)">
        <input type="hidden" id="teknisi-id">
        
        <div class="form-group">
          <label class="form-label" for="namaInput">Nama Lengkap Petugas / Teknisi *</label>
          <input type="text" id="namaInput" class="form-control" required placeholder="Contoh: Bambang Hermanto" maxlength="100">
          <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px;">Nama teknisi yang akan muncul saat admin mendisposisikan laporan.</small>
        </div>

        <div class="form-group">
          <label class="form-label" for="emailInput">Email / Username Login *</label>
          <input type="text" id="emailInput" class="form-control" required placeholder="Contoh: teknisi atau teknisi@safeedu.sch.id" maxlength="100">
          <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px;">Digunakan untuk masuk ke aplikasi SafeEdu Teknisi (bisa berupa username atau email).</small>
        </div>

        <div class="form-group">
          <label class="form-label" for="passwordInput" id="passwordLabel">Password *</label>
          <input type="password" id="passwordInput" class="form-control" placeholder="Minimal 6 karakter" minlength="6">
          <small id="passwordHelp" style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px;"></small>
        </div>

        <div class="modal-footer" style="margin-top: 24px; padding: 12px 0 0 0; background: transparent;">
          <button type="button" class="btn btn-outline" onclick="closeTeknisiModal()">Batal</button>
          <button type="submit" class="btn btn-primary" id="submitBtn">Simpan Data Teknisi</button>
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
  let teknisiData = [];
  let searchQuery = '';

  async function fetchTeknisi() {
    try {
      const response = await fetch('/api/teknisi');
      const json = await response.json();
      if (json.success) {
        teknisiData = json.data;
        updateStats();
        renderTable();
      }
    } catch (err) {
      console.error(err);
      showToast('Gagal memuat data teknisi.', true);
    }
  }

  function updateStats() {
    const total = teknisiData.length;
    const activeWorkers = teknisiData.filter(t => (t.tugas_aktif_count || 0) > 0).length;
    const totalFinished = teknisiData.reduce((acc, curr) => acc + (curr.tugas_selesai_count || 0), 0);

    document.getElementById('stat-total-teknisi').innerText = total;
    document.getElementById('stat-active-teknisi').innerText = activeWorkers;
    document.getElementById('stat-finished-tasks').innerText = totalFinished;
  }

  function handleSearch() {
    searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
    renderTable();
  }

  function renderTable() {
    const tbody = document.getElementById('teknisi-table-body');
    let filtered = teknisiData;

    if (searchQuery) {
      filtered = filtered.filter(t =>
        (t.nama && t.nama.toLowerCase().includes(searchQuery)) ||
        (t.email && t.email.toLowerCase().includes(searchQuery)) ||
        (t.id && t.id.toString().includes(searchQuery))
      );
    }

    document.getElementById('count-filtered').innerText = filtered.length;

    if (filtered.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 36px; color: var(--text-muted);">Tidak ada data teknisi yang cocok.</td></tr>`;
      return;
    }

    tbody.innerHTML = filtered.map(t => {
      const initials = t.nama ? t.nama.split(' ').map(w => w[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() : 'TK';
      const createdDate = t.created_at ? new Date(t.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }) : '-';
      const totalTasks = t.penugasans_count || 0;
      const activeTasks = t.tugas_aktif_count || 0;
      const finishedTasks = t.tugas_selesai_count || 0;

      let taskBadge = '';
      if (activeTasks > 0) {
        taskBadge = `<span class="badge" style="background: #fef3c7; color: #d97706; border: 1px solid #fde68a;">${activeTasks} Tugas Aktif</span> <span style="font-size:0.75rem; color:var(--text-muted); margin-left:4px;">(${finishedTasks} selesai)</span>`;
      } else if (totalTasks > 0) {
        taskBadge = `<span class="badge" style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;">${finishedTasks} Selesai</span>`;
      } else {
        taskBadge = `<span class="badge" style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;">Bebas Tugas</span>`;
      }

      return `
        <tr>
          <td><strong style="color: var(--primary);">TEK-${t.id}</strong></td>
          <td>
            <div style="display: flex; align-items: center; gap: 10px;">
              <div style="width: 32px; height: 32px; border-radius: 50%; background: #ccfbf1; color: #0f766e; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem;">
                ${initials}
              </div>
              <div>
                <div style="font-weight: 600; color: var(--text-main);">${escapeHtml(t.nama)}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Divisi Sarpras & Fasilitas</div>
              </div>
            </div>
          </td>
          <td>
            <span style="font-family: monospace; font-size: 0.85rem; color: #0f172a; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">
              ${escapeHtml(t.email)}
            </span>
          </td>
          <td>
            ${taskBadge}
          </td>
          <td style="font-size: 0.85rem; color: var(--text-muted);">${createdDate}</td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openTeknisiModal(${t.id})">Edit</button>
            <button class="btn btn-sm" style="color: #e11d48; border-color: #fecdd3; background: #fff1f2; margin-left: 4px;" onclick="deleteTeknisi(${t.id}, '${escapeJs(t.nama)}')">Hapus</button>
          </td>
        </tr>
      `;
    }).join('');
  }

  function openTeknisiModal(id = null) {
    const form = document.getElementById('teknisiForm');
    const title = document.getElementById('modal-title');
    const passwordInput = document.getElementById('passwordInput');
    const passwordHelp = document.getElementById('passwordHelp');
    const passwordLabel = document.getElementById('passwordLabel');

    if (id) {
      const teknisi = teknisiData.find(x => x.id === id);
      if (!teknisi) return;
      title.innerText = 'Edit Data Teknisi';
      document.getElementById('teknisi-id').value = teknisi.id;
      document.getElementById('namaInput').value = teknisi.nama;
      document.getElementById('emailInput').value = teknisi.email;
      passwordInput.value = '';
      passwordInput.required = false;
      passwordLabel.innerText = 'Password Baru (Opsional)';
      passwordHelp.innerText = 'Kosongkan jika Anda tidak ingin mengganti password akun ini.';
    } else {
      title.innerText = 'Tambah Teknisi Baru';
      form.reset();
      document.getElementById('teknisi-id').value = '';
      passwordInput.value = '';
      passwordInput.required = true;
      passwordLabel.innerText = 'Password *';
      passwordHelp.innerText = 'Minimal 6 karakter.';
    }

    document.getElementById('teknisiModal').classList.add('active');
  }

  function closeTeknisiModal() {
    document.getElementById('teknisiModal').classList.remove('active');
  }

  async function handleTeknisiSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('teknisi-id').value;
    const submitBtn = document.getElementById('submitBtn');

    const nama = document.getElementById('namaInput').value.trim();
    const email = document.getElementById('emailInput').value.trim();

    if (!nama) {
      alert('Nama teknisi wajib diisi.');
      return;
    }

    if (!email) {
      alert('Email / Username wajib diisi.');
      return;
    }

    submitBtn.disabled = true;
    submitBtn.innerText = 'Menyimpan...';

    const payload = {
      nama: nama,
      email: email,
    };

    const password = document.getElementById('passwordInput').value;
    if (password) {
      payload.password = password;
    }

    try {
      const method = id ? 'PUT' : 'POST';
      const url = id ? `/api/teknisi/${id}` : '/api/teknisi';

      const response = await fetch(url, {
        method: method,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
      });

      const json = await response.json();
      if (json.success) {
        showToast(json.message || 'Data teknisi berhasil disimpan!');
        closeTeknisiModal();
        fetchTeknisi();
      } else {
        let errMessage = json.message || 'Gagal menyimpan data teknisi.';
        if (json.errors) {
          errMessage = Object.values(json.errors).flat().join('\n');
        }
        alert(errMessage);
      }
    } catch (err) {
      console.error(err);
      alert('Terjadi kesalahan koneksi.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerText = 'Simpan Data Teknisi';
    }
  }

  async function deleteTeknisi(id, nama) {
    if (confirm(`Apakah Anda yakin ingin menghapus akun teknisi "${nama}"?`)) {
      try {
        const response = await fetch(`/api/teknisi/${id}`, {
          method: 'DELETE',
          headers: { 'Accept': 'application/json' },
        });
        const json = await response.json();
        if (json.success) {
          showToast('Akun teknisi berhasil dihapus.');
          fetchTeknisi();
        } else {
          alert(json.message || 'Gagal menghapus teknisi.');
        }
      } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan jaringan.');
      }
    }
  }

  function showToast(message, isError = false) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="${isError ? '#f43f5e' : '#4ade80'}" stroke-width="2.5">
        ${isError ? '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>' : '<polyline points="20 6 9 17 4 12"/>'}
      </svg>
      <span>${escapeHtml(message)}</span>
    `;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str.toString().replace(/[&<>"']/g, function(m) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
    });
  }

  function escapeJs(str) {
    if (!str) return '';
    return str.toString().replace(/'/g, "\\'").replace(/"/g, '&quot;');
  }

  document.addEventListener('DOMContentLoaded', fetchTeknisi);
</script>
@endsection
