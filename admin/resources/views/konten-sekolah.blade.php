@extends('layouts.admin')

@section('title', 'Berita & Panduan Sekolah')

@section('content')
<header class="topbar">
  <div class="page-title">
    <h2>Berita & Panduan Sekolah</h2>
    <p>Kelola informasi dan panduan yang tampil di beranda aplikasi user.</p>
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
            <th style="width:70px;">Gambar</th>
            <th>Jenis</th>
            <th>Judul</th>
            <th>Ringkasan</th>
            <th>Isi Konten</th>
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
        <input type="hidden" id="remove-gambar" value="0">
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
          <small id="content-title-hint" style="color:var(--text-muted);">Judul berita minimal 10-180 karakter, gunakan huruf/angka dan tanda baca umum.</small>
        </div>
        <div class="form-group">
          <label class="form-label" for="content-image">Gambar / Banner (Opsional)</label>
          <input type="file" id="content-image" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" onchange="handleImagePreview(event)">
          <small style="color:var(--text-muted);">JPG, JPEG, PNG, atau WEBP; maksimal 2 MB. Gunakan gambar relevan dan tidak mengandung unsur SARA/pornografi.</small>
          <div id="image-preview-wrapper" style="margin-top: 10px; display: none; align-items: center; gap: 12px;">
            <img id="image-preview-img" src="" alt="Preview" style="width: 100px; height: 65px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
            <button type="button" class="btn btn-outline btn-sm" style="color:#e11d48; border-color:#fecdd3; background:#fff1f2;" onclick="clearSelectedImage()">Hapus Gambar</button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="content-summary">Ringkasan</label>
          <textarea id="content-summary" class="form-control" rows="2" maxlength="300"></textarea>
          <small style="color:var(--text-muted);">Tulis gambaran singkat, bukan salinan seluruh isi.</small>
        </div>
        <div class="form-group">
          <label class="form-label" for="content-body">Isi Konten *</label>
          <textarea id="content-body" class="form-control" rows="7" required></textarea>
          <small style="color:var(--text-muted);">Minimal 50 karakter. Jangan masukkan kode atau script.</small>
        </div>
        <label style="display:flex; gap:8px; align-items:center; font-size:.85rem;">
          <input type="checkbox" id="content-active" checked> Tampilkan di aplikasi user
        </label>
        <div class="modal-footer" style="margin-top:20px; padding:12px 0 0 0;">
          <button type="button" class="btn btn-outline" onclick="closeContentModal()">Batal</button>
          <button type="submit" class="btn btn-primary" id="save-content-btn">Simpan Konten</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="toast-container" id="toastContainer"></div>
@endsection

@section('scripts')
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
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;color:#94a3b8">Belum ada konten</td></tr>';
      return;
    }
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    })[character]);
    tbody.innerHTML = contentData.map(item => `
      <tr>
        <td>
          ${item.gambar_url
            ? `<img src="${escapeHtml(item.gambar_url)}" alt="${escapeHtml(item.judul)}" style="width:52px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;display:block;">`
            : `<div style="width:52px;height:40px;border-radius:6px;background:#f1f5f9;border:1px dashed #cbd5e1;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:0.65rem;">No Img</div>`
          }
        </td>
        <td><span class="badge ${item.tipe === 'berita' ? 'badge-facility' : 'badge-ethics'}">${item.tipe === 'berita' ? 'Berita' : 'Panduan'}</span></td>
        <td style="font-weight:600;color:var(--text-main)">${escapeHtml(item.judul)}</td>
        <td style="font-size:.82rem;color:var(--text-muted)">${escapeHtml(item.ringkasan || '-')}</td>
        <td style="font-size:.82rem;color:var(--text-muted);min-width:240px;max-width:420px;"><div style="white-space:pre-wrap;max-height:120px;overflow:auto;">${escapeHtml(item.isi)}</div></td>
        <td><span class="badge ${item.aktif ? 'badge-resolved' : 'badge-ethics'}">${item.aktif ? 'Tampil' : 'Disembunyikan'}</span></td>
        <td style="font-size:.8rem;color:var(--text-muted)">${new Date(item.updated_at).toLocaleDateString('id-ID')}</td>
        <td style="white-space:nowrap;">
          <div style="display:flex;align-items:center;gap:4px;">
          <button class="btn btn-outline btn-sm" onclick="openContentModal(${item.id})">Edit</button>
          <button class="btn btn-sm" style="color:#e11d48;border-color:#fecdd3;background:#fff1f2" onclick="deleteContent(${item.id})">Hapus</button>
          </div>
        </td>
      </tr>`).join('');
  }

  function updateContentRequirements() {
    const isBerita = document.getElementById('content-type').value === 'berita';
    const title = document.getElementById('content-title');
    const summary = document.getElementById('content-summary');
    const body = document.getElementById('content-body');

    title.minLength = isBerita ? 10 : 1;
    summary.minLength = isBerita ? 20 : 0;
    summary.maxLength = isBerita ? 300 : 500;
    body.minLength = isBerita ? 50 : 1;
  }

  function cleanNewsTitle(value) {
    return value.replace(/[^\p{L}\p{N}\s.,!?‘():\/-]/gu, '');
  }

  function sanitizeNewsTitle() {
    const title = document.getElementById('content-title');
    if (document.getElementById('content-type').value !== 'berita') return;

    const original = title.value;
    const cursor = title.selectionStart;
    const sanitized = cleanNewsTitle(original);
    if (sanitized !== original) {
      title.value = sanitized;
      const cleanCursor = cleanNewsTitle(original.slice(0, cursor)).length;
      title.setSelectionRange(cleanCursor, cleanCursor);
    }
  }

  function handleNewsTitleBeforeInput(event) {
    if (document.getElementById('content-type').value !== 'berita' || event.isComposing || event.data === null) return;
    if (cleanNewsTitle(event.data) !== event.data) event.preventDefault();
  }

  function handleNewsTitlePaste(event) {
    if (document.getElementById('content-type').value !== 'berita') return;

    event.preventDefault();
    const title = document.getElementById('content-title');
    const pastedText = cleanNewsTitle(event.clipboardData.getData('text'));
    title.setRangeText(pastedText, title.selectionStart, title.selectionEnd, 'end');
    title.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function handleImagePreview(event) {
    const file = event.target.files[0];
    if (file) {
      if (!/\.(jpe?g|png|webp)$/i.test(file.name) || file.size > 2 * 1024 * 1024) {
        alert('Gambar harus berformat JPG, JPEG, PNG, atau WEBP dan berukuran maksimal 2 MB.');
        event.target.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('image-preview-img').src = e.target.result;
        document.getElementById('image-preview-wrapper').style.display = 'flex';
        document.getElementById('remove-gambar').value = '0';
      };
      reader.readAsDataURL(file);
    }
  }

  function clearSelectedImage() {
    document.getElementById('content-image').value = '';
    document.getElementById('image-preview-img').src = '';
    document.getElementById('image-preview-wrapper').style.display = 'none';
    document.getElementById('remove-gambar').value = '1';
  }

  function openContentModal(id = null) {
    document.getElementById('contentForm').reset();
    document.getElementById('content-active').checked = true;
    document.getElementById('remove-gambar').value = '0';
    document.getElementById('content-image').value = '';

    if (id) {
      const item = contentData.find(content => content.id === id);
      document.getElementById('content-modal-title').innerText = 'Edit Konten';
      document.getElementById('content-id').value = item.id;
      document.getElementById('content-type').value = item.tipe;
      document.getElementById('content-title').value = item.judul;
      document.getElementById('content-summary').value = item.ringkasan || '';
      document.getElementById('content-body').value = item.isi;
      document.getElementById('content-active').checked = !!item.aktif;

      if (item.gambar_url) {
        document.getElementById('image-preview-img').src = item.gambar_url;
        document.getElementById('image-preview-wrapper').style.display = 'flex';
      } else {
        document.getElementById('image-preview-wrapper').style.display = 'none';
      }
    } else {
      document.getElementById('content-modal-title').innerText = 'Tambah Konten';
      document.getElementById('content-id').value = '';
      document.getElementById('image-preview-wrapper').style.display = 'none';
    }
    updateContentRequirements();
    document.getElementById('contentModal').classList.add('active');
  }

  function closeContentModal() {
    document.getElementById('contentModal').classList.remove('active');
  }

  async function handleContentSubmit(event) {
    event.preventDefault();
    const id = document.getElementById('content-id').value;
    const submitBtn = document.getElementById('save-content-btn');
    submitBtn.disabled = true;
    submitBtn.innerText = 'Menyimpan...';

    const formData = new FormData();
    formData.append('tipe', document.getElementById('content-type').value);
    formData.append('judul', document.getElementById('content-title').value);
    formData.append('ringkasan', document.getElementById('content-summary').value);
    formData.append('isi', document.getElementById('content-body').value);
    formData.append('aktif', document.getElementById('content-active').checked ? '1' : '0');

    if (document.getElementById('remove-gambar').value === '1') {
      formData.append('remove_gambar', '1');
    }

    const fileInput = document.getElementById('content-image');
    if (fileInput.files.length > 0) {
      formData.append('gambar', fileInput.files[0]);
    }

    try {
      const url = id ? `/api/konten-sekolah/${id}` : '/api/konten-sekolah';
      const response = await fetch(url, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
      });
      const json = await response.json();
      if (!json.success) {
        alert('Gagal menyimpan: ' + JSON.stringify(json.errors || json.message));
        return;
      }
      closeContentModal();
      await fetchContent();
    } catch (e) {
      alert('Terjadi kesalahan koneksi saat menyimpan konten.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerText = 'Simpan Konten';
    }
  }

  async function deleteContent(id) {
    if (!confirm('Hapus konten ini?')) return;
    const response = await fetch(`/api/konten-sekolah/${id}`, {method: 'DELETE', headers: {'Accept': 'application/json'}});
    const json = await response.json();
    if (json.success) await fetchContent();
  }

  document.getElementById('content-type').addEventListener('change', updateContentRequirements);
  document.getElementById('content-title').addEventListener('beforeinput', handleNewsTitleBeforeInput);
  document.getElementById('content-title').addEventListener('input', sanitizeNewsTitle);
  document.getElementById('content-title').addEventListener('paste', handleNewsTitlePaste);
  document.addEventListener('DOMContentLoaded', fetchContent);
</script>
@endsection
