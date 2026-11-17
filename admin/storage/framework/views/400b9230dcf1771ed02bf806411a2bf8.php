<?php $__env->startSection('title', 'Berita & Panduan Sekolah'); ?>

<?php $__env->startSection('content'); ?>
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
          <small id="content-title-hint" style="color:var(--text-muted);">Judul berita minimal 10 karakter, gunakan huruf/angka dan tanda baca umum.</small>
          <small id="content-title-warning" role="status" aria-live="polite" style="display:none;color:#b45309;"></small>
        </div>
        <div class="form-group">
          <label class="form-label" for="content-image">Gambar / Banner (Opsional)</label>
          <input type="file" id="content-image" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" onchange="handleImagePreview(event)">
          <small style="color:var(--text-muted);">JPG, JPEG, PNG, atau WEBP; maksimal 2 MB. </small>
          <div id="image-preview-wrapper" style="margin-top: 10px; display: none; align-items: center; gap: 12px;">
            <img id="image-preview-img" src="" alt="Preview" style="width: 100px; height: 65px; object-fit: cover; border-radius: 8px; border: 1px solid #cbd5e1;">
            <button type="button" class="btn btn-outline btn-sm" style="color:#e11d48; border-color:#fecdd3; background:#fff1f2;" onclick="clearSelectedImage()">Hapus Gambar</button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="content-body">Isi Konten *</label>
          <textarea id="content-body" class="form-control" rows="7" required></textarea>
          <small style="color:var(--text-muted);">Minimal 50 karakter untuk berita. Tulis informasi yang jelas dan mudah dipahami; jangan masukkan kode atau script.</small>
          <small id="content-body-warning" role="status" aria-live="polite" style="display:none;color:#b45309;"></small>
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
<div class="modal-overlay" id="contentImageModal" onclick="closeContentImagePreview(event)">
  <div class="modal-dialog" style="width:min(900px, 90vw);">
    <div class="modal-header">
      <h3 id="content-image-title">Pratinjau Gambar</h3>
      <button type="button" class="close-btn" aria-label="Tutup pratinjau gambar" onclick="closeContentImagePreview()">&times;</button>
    </div>
    <div class="modal-body" style="text-align:center;">
      <img id="content-image-large" src="" alt="" style="max-width:100%;max-height:75vh;object-fit:contain;border-radius:8px;">
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
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;color:#94a3b8">Belum ada konten</td></tr>';
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
            ? `<button type="button" aria-label="Lihat gambar ${escapeHtml(item.judul)}" onclick="openContentImagePreview(${item.id})" style="padding:0;border:0;background:none;cursor:zoom-in;"><img src="${escapeHtml(item.gambar_url)}" alt="${escapeHtml(item.judul)}" style="width:52px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;display:block;"></button>`
            : `<div style="width:52px;height:40px;border-radius:6px;background:#f1f5f9;border:1px dashed #cbd5e1;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:0.65rem;">No Img</div>`
          }
        </td>
        <td><span class="badge ${item.tipe === 'berita' ? 'badge-facility' : 'badge-ethics'}">${item.tipe === 'berita' ? 'Berita' : 'Panduan'}</span></td>
        <td style="font-weight:600;color:var(--text-main)">${escapeHtml(item.judul)}</td>
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

  function openContentImagePreview(id) {
    const item = contentData.find(content => content.id === id);
    if (!item || !item.gambar_url) return;

    document.getElementById('content-image-title').textContent = item.judul;
    document.getElementById('content-image-large').src = item.gambar_url;
    document.getElementById('content-image-large').alt = item.judul;
    document.getElementById('contentImageModal').classList.add('active');
  }

  function closeContentImagePreview(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('contentImageModal').classList.remove('active');
    document.getElementById('content-image-large').src = '';
  }

  function updateContentRequirements() {
    const isBerita = document.getElementById('content-type').value === 'berita';
    const title = document.getElementById('content-title');
    const body = document.getElementById('content-body');

    title.minLength = isBerita ? 10 : 1;
    body.minLength = isBerita ? 50 : 1;
  }

  function isLikelyRandomText(value) {
    const text = value.trim().toLocaleLowerCase();
    if (!text) return false;

    if (/(\p{L})\1{3,}/iu.test(text)) return true;

    const letters = text.match(/\p{L}/gu) || [];
    const vowels = text.match(/[aeiouáéíóú]/giu) || [];
    return letters.length >= 10 && vowels.length / letters.length < 0.2;
  }

  function updateContentQualityWarnings() {
    const title = document.getElementById('content-title');
    const body = document.getElementById('content-body');
    const titleWarning = document.getElementById('content-title-warning');
    const bodyWarning = document.getElementById('content-body-warning');
    const warning = 'Teks ini tampak seperti input acak. Gunakan kalimat yang jelas dan relevan.';

    titleWarning.textContent = isLikelyRandomText(title.value) ? warning : '';
    titleWarning.style.display = titleWarning.textContent ? 'block' : 'none';
    bodyWarning.textContent = isLikelyRandomText(body.value) ? warning : '';
    bodyWarning.style.display = bodyWarning.textContent ? 'block' : 'none';

    return !titleWarning.textContent && !bodyWarning.textContent;
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
    updateContentQualityWarnings();
    document.getElementById('contentModal').classList.add('active');
  }

  function closeContentModal() {
    document.getElementById('contentModal').classList.remove('active');
  }

  async function handleContentSubmit(event) {
    event.preventDefault();
    if (!updateContentQualityWarnings()) {
      const firstInvalidField = document.getElementById('content-title-warning').textContent
        ? document.getElementById('content-title')
        : document.getElementById('content-body');
      firstInvalidField.focus();
      return;
    }

    const id = document.getElementById('content-id').value;
    const submitBtn = document.getElementById('save-content-btn');
    submitBtn.disabled = true;
    submitBtn.innerText = 'Menyimpan...';

    const formData = new FormData();
    formData.append('tipe', document.getElementById('content-type').value);
    formData.append('judul', document.getElementById('content-title').value);
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
  document.getElementById('content-title').addEventListener('input', updateContentQualityWarnings);
  document.getElementById('content-title').addEventListener('paste', handleNewsTitlePaste);
  document.getElementById('content-body').addEventListener('input', updateContentQualityWarnings);
  document.getElementById('content-type').addEventListener('change', updateContentQualityWarnings);
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeContentImagePreview();
  });
  document.addEventListener('DOMContentLoaded', fetchContent);
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Pongo\Documents\kuliah\semester 3\SafeEdu\admin\resources\views/konten-sekolah.blade.php ENDPATH**/ ?>