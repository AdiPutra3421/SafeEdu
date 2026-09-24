

<?php $__env->startSection('title', 'Berita & Panduan Sekolah'); ?>

<?php $__env->startSection('content'); ?>
<header class="topbar">
  <div class="page-title">
    <h2>Berita & Panduan Sekolah</h2>
    <p>Kelola informasi dan panduan yang tampil di beranda aplikasi siswa.</p>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-primary" onclick="openContentModal()">+ Tambah Konten</button>
  </div>
</header>

<main class="content-body">
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Jenis</th>
            <th>Judul</th>
            <th>Ringkasan</th>
            <th>Status</th>
            <th>Diubah</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="content-table-body"></tbody>
      </table>
    </div>
  </div>
</main>

<div class="modal-overlay" id="contentModal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 id="content-modal-title">Tambah Konten</h3>
      <button class="close-btn" onclick="closeContentModal()">&times;</button>
    </div>
    <div class="modal-body">
      <form id="contentForm" onsubmit="handleContentSubmit(event)">
        <input type="hidden" id="content-id">
        <div class="form-group">
          <label class="form-label" for="content-type">Jenis Konten *</label>
          <select id="content-type" class="form-control" required>
            <option value="berita">Berita</option>
            <option value="panduan">Panduan</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="content-title">Judul *</label>
          <input type="text" id="content-title" class="form-control" maxlength="180" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="content-summary">Ringkasan</label>
          <textarea id="content-summary" class="form-control" rows="2" maxlength="500"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label" for="content-body">Isi Konten *</label>
          <textarea id="content-body" class="form-control" rows="7" required></textarea>
        </div>
        <label style="display:flex; gap:8px; align-items:center; font-size:.85rem;">
          <input type="checkbox" id="content-active" checked> Tampilkan di aplikasi siswa
        </label>
        <div class="modal-footer" style="margin-top:20px; padding:12px 0 0 0;">
          <button type="button" class="btn btn-outline" onclick="closeContentModal()">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Konten</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="toast-container" id="toastContainer"></div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
  let contentData = [];

  async function fetchContent() {
    const response = await fetch('/api/konten-sekolah?admin=1');
    const json = await response.json();
    if (json.success) {
      contentData = json.data;
      renderContent();
    }
  }

  function renderContent() {
    const tbody = document.getElementById('content-table-body');
    if (!contentData.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8">Belum ada konten</td></tr>';
      return;
    }
    tbody.innerHTML = contentData.map(item => `
      <tr>
        <td><span class="badge ${item.tipe === 'berita' ? 'badge-facility' : 'badge-ethics'}">${item.tipe === 'berita' ? 'Berita' : 'Panduan'}</span></td>
        <td style="font-weight:600;color:var(--text-main)">${item.judul}</td>
        <td style="font-size:.82rem;color:var(--text-muted)">${item.ringkasan || '-'}</td>
        <td><span class="badge ${item.aktif ? 'badge-resolved' : 'badge-ethics'}">${item.aktif ? 'Tampil' : 'Disembunyikan'}</span></td>
        <td style="font-size:.8rem;color:var(--text-muted)">${new Date(item.updated_at).toLocaleDateString('id-ID')}</td>
        <td>
          <button class="btn btn-outline btn-sm" onclick="openContentModal(${item.id})">Edit</button>
          <button class="btn btn-sm" style="color:#e11d48;border-color:#fecdd3;background:#fff1f2;margin-left:4px" onclick="deleteContent(${item.id})">Hapus</button>
        </td>
      </tr>`).join('');
  }

  function openContentModal(id = null) {
    document.getElementById('contentForm').reset();
    document.getElementById('content-active').checked = true;
    if (id) {
      const item = contentData.find(content => content.id === id);
      document.getElementById('content-modal-title').innerText = 'Edit Konten';
      document.getElementById('content-id').value = item.id;
      document.getElementById('content-type').value = item.tipe;
      document.getElementById('content-title').value = item.judul;
      document.getElementById('content-summary').value = item.ringkasan || '';
      document.getElementById('content-body').value = item.isi;
      document.getElementById('content-active').checked = !!item.aktif;
    } else {
      document.getElementById('content-modal-title').innerText = 'Tambah Konten';
      document.getElementById('content-id').value = '';
    }
    document.getElementById('contentModal').classList.add('active');
  }

  function closeContentModal() {
    document.getElementById('contentModal').classList.remove('active');
  }

  async function handleContentSubmit(event) {
    event.preventDefault();
    const id = document.getElementById('content-id').value;
    const payload = {
      tipe: document.getElementById('content-type').value,
      judul: document.getElementById('content-title').value,
      ringkasan: document.getElementById('content-summary').value,
      isi: document.getElementById('content-body').value,
      aktif: document.getElementById('content-active').checked
    };
    const response = await fetch(id ? `/api/konten-sekolah/${id}` : '/api/konten-sekolah', {
      method: id ? 'PUT' : 'POST',
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: JSON.stringify(payload)
    });
    const json = await response.json();
    if (!json.success) {
      alert('Gagal menyimpan: ' + JSON.stringify(json.errors || json.message));
      return;
    }
    closeContentModal();
    await fetchContent();
  }

  async function deleteContent(id) {
    if (!confirm('Hapus konten ini?')) return;
    const response = await fetch(`/api/konten-sekolah/${id}`, {method: 'DELETE', headers: {'Accept': 'application/json'}});
    const json = await response.json();
    if (json.success) await fetchContent();
  }

  document.addEventListener('DOMContentLoaded', fetchContent);
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\SafeEdu\admin\resources\views/konten-sekolah.blade.php ENDPATH**/ ?>