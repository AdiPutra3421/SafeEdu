@extends('layouts.admin')

@section('title', 'Manajemen Fasilitas')

@section('content')
<header class="topbar">
  <div class="page-title">
    <h2>Manajemen Fasilitas & Katalog Sarana</h2>
    <p>Tambah, edit, atau hapus fasilitas yang tersedia di sekolah/sekolah untuk dapat dilaporkan oleh pengguna.</p>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-primary" onclick="openFasilitasModal()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -3px; margin-right: 4px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      Tambah Fasilitas Baru
    </button>
  </div>
</header>

<main class="content-body">
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID Fasilitas</th>
            <th>Nama Fasilitas / Sarana</th>
            <th>Lokasi</th>
            <th>Status Kondisi</th>
            <th>Deskripsi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="fasilitas-table-body">
          <!-- Dynamically Rendered -->
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal Add/Edit Fasilitas -->
<div class="modal-overlay" id="fasilitasModal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div>
        <h3 id="modal-title">Tambah Fasilitas Baru</h3>
      </div>
      <button class="close-btn" onclick="closeFasilitasModal()">&times;</button>
    </div>
    <div class="modal-body">
      <form id="fasilitasForm" onsubmit="handleFasilitasSubmit(event)">
        <input type="hidden" id="fasilitas-id">
        
        <div class="form-group">
          <label class="form-label" for="namaInput">Nama Fasilitas / Sarana *</label>
          <input type="text" id="namaInput" class="form-control" required placeholder="Contoh: AC Split 1.5 PK Lab Komputer 2">
        </div>

        <div class="form-group">
          <label class="form-label" for="lokasiInput">Lokasi *</label>
          <input type="text" id="lokasiInput" class="form-control" required maxlength="150" placeholder="Contoh: Gedung A, Lantai 2">
        </div>

        <div class="form-group">
          <label class="form-label" for="statusKondisiInput">Status Kondisi *</label>
          <select id="statusKondisiInput" class="form-control" required>
            <option value="Bisa Digunakan">Bisa Digunakan</option>
            <option value="Dalam Perbaikan">Dalam Perbaikan</option>
            <option value="Tidak Bisa Digunakan">Tidak Bisa Digunakan</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="deskripsiInput">Deskripsi Tambahan</label>
          <textarea id="deskripsiInput" class="form-control" rows="2" placeholder="Contoh: Terakhir diservis bulan lalu..."></textarea>
        </div>

        <div class="modal-footer" style="margin-top: 20px; padding: 12px 0 0 0;">
          <button type="button" class="btn btn-outline" onclick="closeFasilitasModal()">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Data Fasilitas</button>
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
  let fasilitasData = [];

  async function fetchFasilitas() {
    try {
      const response = await fetch('/api/fasilitas');
      const json = await response.json();
      if (json.success) {
        fasilitasData = json.data;
        renderTable();
      }
    } catch (err) {
      console.error(err);
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
      <span>${message}</span>
    `;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function renderTable() {
    const tbody = document.getElementById('fasilitas-table-body');
    if (fasilitasData.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding:30px; color: #94a3b8;">Tidak ada data fasilitas</td></tr>`;
      return;
    }

    tbody.innerHTML = fasilitasData.map(f => {
      const nama = escapeHtml(f.nama || '-');
      const lokasi = escapeHtml(f.lokasi || '-');
      const deskripsi = escapeHtml(f.deskripsi || '-');
      return `
        <tr>
          <td><strong>FAS-${f.id}</strong></td>
          <td style="font-weight:600; color:var(--text-main);">${nama}</td>
          <td>${lokasi}</td>
          <td>${escapeHtml(f.status_kondisi || '-')}</td>
          <td style="font-size:0.8rem; color:var(--text-muted);">${deskripsi}</td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openFasilitasModal(${f.id})">Edit</button>
            <button class="btn btn-sm" style="color:#e11d48; border-color:#fecdd3; background:#fff1f2; margin-left:4px;" onclick="deleteFasilitas(${f.id})">Hapus</button>
          </td>
        </tr>
      `;
    }).join('');
  }

  function openFasilitasModal(id = null) {
    if (id) {
      const f = fasilitasData.find(x => x.id === id);
      document.getElementById('modal-title').innerText = 'Edit Fasilitas';
      document.getElementById('fasilitas-id').value = f.id;
      document.getElementById('namaInput').value = f.nama;
      document.getElementById('lokasiInput').value = f.lokasi || '';
      document.getElementById('statusKondisiInput').value = f.status_kondisi || 'Bisa Digunakan';
      document.getElementById('deskripsiInput').value = f.deskripsi || '';
    } else {
      document.getElementById('modal-title').innerText = 'Tambah Fasilitas Baru';
      document.getElementById('fasilitasForm').reset();
      document.getElementById('fasilitas-id').value = '';
    }
    document.getElementById('fasilitasModal').classList.add('active');
  }

  function closeFasilitasModal() {
    document.getElementById('fasilitasModal').classList.remove('active');
  }

  async function handleFasilitasSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('fasilitas-id').value;
    const newData = {
      nama: document.getElementById('namaInput').value,
      lokasi: document.getElementById('lokasiInput').value,
      status_kondisi: document.getElementById('statusKondisiInput').value,
      deskripsi: document.getElementById('deskripsiInput').value,
    };

    try {
      const method = id ? 'PUT' : 'POST';
      const url = id ? `/api/fasilitas/${id}` : '/api/fasilitas';
      
      const response = await fetch(url, {
        method: method,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(newData)
      });
      
      const json = await response.json();
      if (json.success) {
        showToast(id ? 'Fasilitas berhasil diperbarui!' : 'Fasilitas baru berhasil ditambahkan!');
        closeFasilitasModal();
        fetchFasilitas(); // Refresh table
      } else {
        const validationErrors = json.errors
          ? Object.values(json.errors).flat().join(' ')
          : '';
        alert('Gagal menyimpan: ' + (json.message || validationErrors || 'Silakan coba kembali.'));
      }
    } catch (err) {
      console.error(err);
      alert("Terjadi kesalahan jaringan.");
    }
  }

  async function deleteFasilitas(id) {
    if(confirm('Apakah Anda yakin ingin menghapus fasilitas ini?')) {
      try {
        const response = await fetch(`/api/fasilitas/${id}`, {
          method: 'DELETE',
          headers: { 'Accept': 'application/json' }
        });
        const json = await response.json();
        if (json.success) {
          showToast('Fasilitas berhasil dihapus!');
          fetchFasilitas();
        }
      } catch (err) {
        console.error(err);
      }
    }
  }

  // Initial load
  document.addEventListener('DOMContentLoaded', () => {
    fetchFasilitas();
  });
</script>

@endsection
