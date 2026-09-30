@extends('layouts.admin')

@section('title', 'Manajemen Data Siswa')

@section('content')
<header class="topbar">
  <div class="page-title">
    <h2>Manajemen Data Siswa</h2>
    <p>Kelola data akun siswa, NISN/Email login, serta pantau histori partisipasi pelaporan insiden & fasilitas.</p>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-primary" onclick="openSiswaModal()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -3px; margin-right: 4px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      Tambah Siswa Baru
    </button>
  </div>
</header>

<main class="content-body">
  <!-- Summary Stat Cards -->
  <div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 20px; flex: 1; min-width: 200px; box-shadow: var(--shadow-sm);">
      <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Siswa Terdaftar</div>
      <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-main); margin-top: 2px;" id="stat-total-siswa">0</div>
    </div>
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 20px; flex: 1; min-width: 200px; box-shadow: var(--shadow-sm);">
      <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Siswa Pernah Melapor</div>
      <div style="font-size: 1.6rem; font-weight: 700; color: var(--primary); margin-top: 2px;" id="stat-active-reporters">0</div>
    </div>
    <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 20px; flex: 1; min-width: 200px; box-shadow: var(--shadow-sm);">
      <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Laporan dari Siswa</div>
      <div style="font-size: 1.6rem; font-weight: 700; color: var(--status-success-text); margin-top: 2px;" id="stat-total-reports">0</div>
    </div>
  </div>

  <!-- Search & Toolbar -->
  <div class="toolbar-container" style="margin-bottom: 16px;">
    <div class="search-input-wrap" style="max-width: 380px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/>
        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <input type="text" id="searchInput" class="search-input" placeholder="Cari nama atau NISN / Email siswa..." oninput="handleSearch()">
    </div>
    <div style="font-size: 0.82rem; color: var(--text-muted);">
      Menampilkan <strong id="count-filtered">0</strong> akun siswa
    </div>
  </div>

  <!-- Main Table -->
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Siswa</th>
            <th>NISN / Email Login</th>
            <th>Partisipasi Laporan</th>
            <th>Terdaftar Pada</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="siswa-table-body">
          <!-- Dynamically Rendered -->
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal Add/Edit Siswa -->
<div class="modal-overlay" id="siswaModal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div>
        <h3 id="modal-title">Tambah Siswa Baru</h3>
        <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">Akun siswa dapat langsung digunakan untuk login di aplikasi mobile SafeEdu.</p>
      </div>
      <button class="close-btn" onclick="closeSiswaModal()">&times;</button>
    </div>
    <div class="modal-body">
      <form id="siswaForm" onsubmit="handleSiswaSubmit(event)">
        <input type="hidden" id="siswa-id">
        
        <div class="form-group">
          <label class="form-label" for="namaInput">Nama Lengkap Siswa (Hanya Huruf) *</label>
          <input type="text" id="namaInput" class="form-control" required placeholder="Contoh: Budi Santoso" maxlength="100" pattern="[a-zA-Z\s]+" title="Nama hanya boleh berisi huruf dan spasi" oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
          <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px;">Nama hanya boleh huruf dan spasi (tanpa angka/simbol).</small>
        </div>

        <div class="form-group">
          <label class="form-label" for="emailInput">NISN Siswa (Hanya 10 Angka) *</label>
          <input type="text" id="emailInput" class="form-control" required placeholder="Contoh: 2024091101" maxlength="10" minlength="10" inputmode="numeric" pattern="[0-9]{10}" title="NISN harus tepat 10 digit angka" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
          <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px;">NISN wajib tepat 10 angka digit (hanya angka). Digunakan untuk login aplikasi mobile.</small>
        </div>

        <div class="form-group">
          <label class="form-label" for="passwordInput" id="passwordLabel">Password *</label>
          <input type="password" id="passwordInput" class="form-control" placeholder="Minimal 6 karakter" minlength="6">
          <small id="passwordHelp" style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px;"></small>
        </div>

        <div class="modal-footer" style="margin-top: 24px; padding: 12px 0 0 0; background: transparent;">
          <button type="button" class="btn btn-outline" onclick="closeSiswaModal()">Batal</button>
          <button type="submit" class="btn btn-primary" id="submitBtn">Simpan Data Siswa</button>
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
  let siswaData = [];
  let searchQuery = '';

  async function fetchSiswa() {
    try {
      const response = await fetch('/api/siswa');
      const json = await response.json();
      if (json.success) {
        siswaData = json.data;
        updateStats();
        renderTable();
      }
    } catch (err) {
      console.error(err);
      showToast('Gagal memuat data siswa.');
    }
  }

  function updateStats() {
    const total = siswaData.length;
    const activeReporters = siswaData.filter(s => (s.pengaduans_count || 0) > 0).length;
    const totalReports = siswaData.reduce((acc, curr) => acc + (curr.pengaduans_count || 0), 0);

    document.getElementById('stat-total-siswa').innerText = total;
    document.getElementById('stat-active-reporters').innerText = activeReporters;
    document.getElementById('stat-total-reports').innerText = totalReports;
  }

  function handleSearch() {
    searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
    renderTable();
  }

  function renderTable() {
    const tbody = document.getElementById('siswa-table-body');
    let filtered = siswaData;

    if (searchQuery) {
      filtered = filtered.filter(s =>
        (s.nama && s.nama.toLowerCase().includes(searchQuery)) ||
        (s.email && s.email.toLowerCase().includes(searchQuery)) ||
        (s.id && s.id.toString().includes(searchQuery))
      );
    }

    document.getElementById('count-filtered').innerText = filtered.length;

    if (filtered.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 36px; color: var(--text-muted);">Tidak ada data siswa yang cocok.</td></tr>`;
      return;
    }

    tbody.innerHTML = filtered.map(s => {
      const initials = s.nama ? s.nama.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase() : 'SW';
      const createdDate = s.created_at ? new Date(s.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }) : '-';
      const reportCount = s.pengaduans_count || 0;
      
      const badgeStyle = reportCount > 0 
        ? 'background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;' 
        : 'background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;';

      return `
        <tr>
          <td><strong style="color: var(--primary);">SIS-${s.id}</strong></td>
          <td>
            <div style="display: flex; align-items: center; gap: 10px;">
              <div style="width: 32px; height: 32px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem;">
                ${initials}
              </div>
              <div style="font-weight: 600; color: var(--text-main);">${escapeHtml(s.nama)}</div>
            </div>
          </td>
          <td>
            <span style="font-family: monospace; font-size: 0.85rem; color: #0f172a; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">
              ${escapeHtml(s.email)}
            </span>
          </td>
          <td>
            <span class="badge" style="${badgeStyle}">
              ${reportCount} Laporan
            </span>
          </td>
          <td style="font-size: 0.85rem; color: var(--text-muted);">${createdDate}</td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openSiswaModal(${s.id})">Edit</button>
            <button class="btn btn-sm" style="color: #e11d48; border-color: #fecdd3; background: #fff1f2; margin-left: 4px;" onclick="deleteSiswa(${s.id}, '${escapeJs(s.nama)}')">Hapus</button>
          </td>
        </tr>
      `;
    }).join('');
  }

  function openSiswaModal(id = null) {
    const form = document.getElementById('siswaForm');
    const title = document.getElementById('modal-title');
    const passwordInput = document.getElementById('passwordInput');
    const passwordHelp = document.getElementById('passwordHelp');
    const passwordLabel = document.getElementById('passwordLabel');

    if (id) {
      const siswa = siswaData.find(x => x.id === id);
      if (!siswa) return;
      title.innerText = 'Edit Data Siswa';
      document.getElementById('siswa-id').value = siswa.id;
      document.getElementById('namaInput').value = siswa.nama;
      document.getElementById('emailInput').value = siswa.email;
      passwordInput.value = '';
      passwordInput.required = false;
      passwordLabel.innerText = 'Password Baru (Opsional)';
      passwordHelp.innerText = 'Kosongkan jika Anda tidak ingin mengganti password akun ini.';
    } else {
      title.innerText = 'Tambah Siswa Baru';
      form.reset();
      document.getElementById('siswa-id').value = '';
      passwordInput.value = '';
      passwordInput.required = true;
      passwordLabel.innerText = 'Password *';
      passwordHelp.innerText = 'Minimal 6 karakter.';
    }

    document.getElementById('siswaModal').classList.add('active');
  }

  function closeSiswaModal() {
    document.getElementById('siswaModal').classList.remove('active');
  }

  async function handleSiswaSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('siswa-id').value;
    const submitBtn = document.getElementById('submitBtn');

    const nama = document.getElementById('namaInput').value.trim();
    const email = document.getElementById('emailInput').value.trim();

    if (!/^[a-zA-Z\s]+$/.test(nama)) {
      alert('Nama siswa hanya boleh berisi huruf dan spasi.');
      return;
    }

    if (!/^[0-9]{10}$/.test(email)) {
      alert('NISN siswa harus tepat 10 angka numerik.');
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
      const url = id ? `/api/siswa/${id}` : '/api/siswa';

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
        showToast(json.message || 'Data siswa berhasil disimpan!');
        closeSiswaModal();
        fetchSiswa();
      } else {
        let errMessage = json.message || 'Gagal menyimpan data siswa.';
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
      submitBtn.innerText = 'Simpan Data Siswa';
    }
  }

  async function deleteSiswa(id, nama) {
    if (confirm(`Apakah Anda yakin ingin menghapus akun siswa "${nama}"?\nSeluruh pengaduan yang diajukan oleh akun ini juga akan terhapus.`)) {
      try {
        const response = await fetch(`/api/siswa/${id}`, {
          method: 'DELETE',
          headers: { 'Accept': 'application/json' },
        });
        const json = await response.json();
        if (json.success) {
          showToast('Akun siswa berhasil dihapus.');
          fetchSiswa();
        } else {
          alert(json.message || 'Gagal menghapus siswa.');
        }
      } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan jaringan.');
      }
    }
  }

  function showToast(message) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
      <span>${escapeHtml(message)}</span>
    `;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function(m) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
    });
  }

  function escapeJs(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
  }

  document.addEventListener('DOMContentLoaded', fetchSiswa);
</script>
@endsection
