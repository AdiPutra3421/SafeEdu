<?php $__env->startSection('title', 'Manajemen Fasilitas'); ?>

<?php $__env->startSection('content'); ?>
<header class="topbar">
  <div class="page-title">
    <h2>Manajemen Fasilitas & Katalog Sarana</h2>
    <p>Tambah, edit, atau hapus fasilitas yang tersedia di sekolah untuk dapat dilaporkan oleh pengguna.</p>
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
            <th>Foto</th>
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
      <form id="fasilitasForm" onsubmit="handleFasilitasSubmit(event)" enctype="multipart/form-data">
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

        <!-- INPUT FOTO & PREVIEW REAL-TIME (DISAMAKAN DENGAN BERITA / KONTEN SEKOLAH) -->
        <div class="form-group" style="margin-top: 15px;">
          <label class="form-label" for="fotoInput">Foto Fasilitas (Opsional)</label>
          <input type="file" id="fotoInput" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(event)">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">JPG, JPEG, PNG, atau WEBP; maksimal 2 MB.</div>
          
          <!-- CONTAINER PREVIEW GAMBAR LAMA & BARU -->
          <div id="fotoPreviewContainer" style="display: none; margin-top: 12px;">
            <div style="display: flex; align-items: center; gap: 12px; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px dashed #cbd5e1;">
              <img id="fotoPreviewImage" src="" alt="Preview Foto" style="width: 80px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;">
              <button type="button" class="btn btn-sm" style="color:#e11d48; border-color:#fecdd3; background:#fff1f2;" onclick="removePhoto()">Hapus Gambar</button>
            </div>
          </div>
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>

<script>
  let fasilitasData = [];
  let removePhotoFlag = false;

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
      <span></span>
    `;
    toast.querySelector('span').textContent = message;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    })[character]);
  }

  function storageUrl(path) {
    if (typeof path !== 'string') return null;
    const segments = path.split('/');
    if (segments.length === 0 || segments.some(segment =>
      !segment || segment === '.' || segment === '..' || segment.includes('\\')
    )) {
      return null;
    }
    return `/storage/${segments.map(encodeURIComponent).join('/')}`;
  }

  function renderTable() {
    const tbody = document.getElementById('fasilitas-table-body');
    if (fasilitasData.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding:30px; color: #94a3b8;">Tidak ada data fasilitas</td></tr>`;
      return;
    }

    tbody.innerHTML = fasilitasData.map(f => {
      const photoUrl = storageUrl(f.foto_fasilitas);
      const imgHtml = photoUrl
        ? `<img src="${escapeHtml(photoUrl)}" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;" alt="Foto">`
        : `<span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">Tidak ada</span>`;

      return `
        <tr>
          <td><strong>FAS-${escapeHtml(f.id)}</strong></td>
          <td>${imgHtml}</td>
          <td style="font-weight:600; color:var(--text-main);">${escapeHtml(f.nama)}</td>
          <td>${escapeHtml(f.lokasi || '-')}</td>
          <td>${escapeHtml(f.status_kondisi || '-')}</td>
          <td style="font-size:0.8rem; color:var(--text-muted);">${escapeHtml(f.deskripsi || '-')}</td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openFasilitasModal(${Number(f.id)})">Edit</button>
            <button class="btn btn-sm" style="color:#e11d48; border-color:#fecdd3; background:#fff1f2; margin-left:4px;" onclick="deleteFasilitas(${Number(f.id)})">Hapus</button>
          </td>
        </tr>
      `;
    }).join('');
  }

  function openFasilitasModal(id = null) {
    document.getElementById('fotoInput').value = ''; 
    removePhotoFlag = false;
    const previewContainer = document.getElementById('fotoPreviewContainer');
    const previewImage = document.getElementById('fotoPreviewImage');

    if (id) {
      const f = fasilitasData.find(x => x.id === id);
      document.getElementById('modal-title').innerText = 'Edit Fasilitas';
      document.getElementById('fasilitas-id').value = f.id;
      document.getElementById('namaInput').value = f.nama;
      document.getElementById('lokasiInput').value = f.lokasi || '';
      document.getElementById('statusKondisiInput').value = f.status_kondisi || 'Bisa Digunakan';
      document.getElementById('deskripsiInput').value = f.deskripsi || '';
      
      // LOGIKA MENAMPILKAN GAMBAR LAMA DI ADMIN (DISAMAKAN DENGAN KONTEN SEKOLAH)
      const photoUrl = storageUrl(f.foto_fasilitas);
      if (photoUrl) {
        previewImage.src = photoUrl;
        previewContainer.style.display = 'block';
      } else {
        previewImage.src = '';
        previewContainer.style.display = 'none';
      }

    } else {
      document.getElementById('modal-title').innerText = 'Tambah Fasilitas Baru';
      document.getElementById('fasilitasForm').reset();
      document.getElementById('fasilitas-id').value = '';
      
      previewImage.src = '';
      previewContainer.style.display = 'none';
    }
    document.getElementById('fasilitasModal').classList.add('active');
  }

  function closeFasilitasModal() {
    document.getElementById('fasilitasModal').classList.remove('active');
  }

  function removePhoto() {
    document.getElementById('fotoInput').value = '';
    document.getElementById('fotoPreviewContainer').style.display = 'none';
    document.getElementById('fotoPreviewImage').src = '';
    removePhotoFlag = true;
  }

  // PREVIEW DARI PILIHAN LAPTOP (REAL-TIME)
  function previewImage(event) {
    const file = event.target.files[0];
    const previewContainer = document.getElementById('fotoPreviewContainer');
    const previewImage = document.getElementById('fotoPreviewImage');
    
    if (file) {
      removePhotoFlag = false;
      const reader = new FileReader();
      reader.onload = function(e) {
        previewImage.src = e.target.result;
        previewContainer.style.display = 'block';
      }
      reader.readAsDataURL(file);
    }
  }

 async function handleFasilitasSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('fasilitas-id').value;
    const formData = new FormData();
    formData.append('nama', document.getElementById('namaInput').value);
    formData.append('lokasi', document.getElementById('lokasiInput').value);
    formData.append('status_kondisi', document.getElementById('statusKondisiInput').value);
    formData.append('deskripsi', document.getElementById('deskripsiInput').value);

    const fotoFile = document.getElementById('fotoInput').files[0];
    if (fotoFile) {
      formData.append('foto_fasilitas', fotoFile);
    } else if (removePhotoFlag) {
      formData.append('remove_foto', '1');
    }

    try {
      if (id) {
        formData.append('_method', 'PUT');
      }

      const method = 'POST'; 
      const url = id ? `/api/fasilitas/${id}` : '/api/fasilitas';
      
      const response = await fetch(url, {
        method: method,
        headers: {
          'Accept': 'application/json'
        },
        body: formData 
      });
      
      const json = await response.json();
      if (json.success) {
        showToast(id ? 'Fasilitas berhasil diperbarui!' : 'Fasilitas baru berhasil ditambahkan!');
        closeFasilitasModal();
        fetchFasilitas(); 
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
          headers: { 'Accept': 'application/json' } // PERBAIKAN DI SINI (menggunakan tanda petik biasa)
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

  document.addEventListener('DOMContentLoaded', () => {
    fetchFasilitas();
  });
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Pongo\Documents\kuliah\semester 3\SafeEdu\admin\resources\views/fasilitas.blade.php ENDPATH**/ ?>