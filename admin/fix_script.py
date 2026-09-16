import sys

content = open('resources/views/laporan.blade.php', 'r', encoding='utf-8').read()

start_idx = content.find('<script>')
end_idx = content.find('</script>') + 9

new_script = '''<script>
  let laporanData = [];
  let currentTypeFilter = 'all';
  let currentStatusFilter = 'all';
  
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

  function renderReportsTable() {
    const tbody = document.getElementById('reports-table-body');
    const searchQuery = document.getElementById('searchInput').value.toLowerCase();
    
    let filtered = laporanData;
    
    if (currentTypeFilter === 'fasilitas') {
      filtered = filtered.filter(x => x.kategori?.jenis === 'fasilitas');
    } else if (currentTypeFilter === 'kode_etik') {
      filtered = filtered.filter(x => x.kategori?.jenis !== 'fasilitas');
    }
    
    if (currentStatusFilter !== 'all') {
      filtered = filtered.filter(x => {
        if (currentStatusFilter === 'Menunggu Verifikasi') return x.status === 'menunggu';
        if (currentStatusFilter === 'Diteruskan ke Teknisi') return x.status === 'diproses';
        if (currentStatusFilter === 'Selesai') return x.status === 'selesai';
        return true;
      });
    }

    if (searchQuery) {
      filtered = filtered.filter(x => 
        (x.judul && x.judul.toLowerCase().includes(searchQuery)) ||
        (x.lokasi_kejadian && x.lokasi_kejadian.toLowerCase().includes(searchQuery)) ||
        (x.id && x.id.toString().includes(searchQuery))
      );
    }

    if (filtered.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding:30px; color: #94a3b8;">Tidak ada data laporan yang sesuai filter</td></tr>';
      return;
    }

    tbody.innerHTML = filtered.map(item => {
      let badgeClass = 'badge-resolved';
      let statusLabel = 'Selesai';
      if(item.status === 'menunggu') {
        badgeClass = 'badge-ethics';
        statusLabel = 'Menunggu';
      } else if(item.status === 'diproses') {
        badgeClass = 'badge-pending';
        statusLabel = 'Diproses Teknisi';
      }
      
      const judul = item.judul || '-';
      const type = item.kategori?.jenis === 'fasilitas' ? 'Fasilitas Sekolah' : 'Kode Etik';

      return \
        <tr>
          <td><strong>RPT-\</strong><br><span style="font-size:0.75rem; color:#94a3b8;">\</span></td>
          <td><span class="badge \">\</span></td>
          <td style="font-weight:600; color:var(--text-main);">\</td>
          <td style="font-size:0.85rem;">\</td>
          <td>\</td>
          <td><span class="badge" style="background:#fef3c7; color:#d97706;">Sedang</span></td>
          <td><span class="badge \">\</span></td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openDisposisiModal(\)">Lihat / Disposisi</button>
          </td>
        </tr>
      \;
    }).join('');
  }

  function showToast(message) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = \
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
      <span>\</span>
    \;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  let selectedId = null;
  function openDisposisiModal(id) {
    selectedId = id;
    const item = laporanData.find(x => x.id === id);
    if(!item) return;

    document.getElementById('modal-report-id').innerText = \RPT-\ - \\;
    document.getElementById('modal-report-title').innerText = item.judul;
    document.getElementById('modal-report-location').innerText = \?? \\;
    document.getElementById('modal-report-desc').innerText = item.deskripsi;
    
    document.getElementById('dispatchModal').classList.add('active');
  }

  function closeDispatchModal() {
    document.getElementById('dispatchModal').classList.remove('active');
    selectedId = null;
  }

  async function handleDispatchSubmit(e) {
    e.preventDefault();
    if (!selectedId) return;

    try {
      const response = await fetch('/api/penugasan', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          pengaduan_id: selectedId,
          teknisi_id: 2, // Hardcode Bambang Teknisi (ID 2) for now
          catatan_admin: document.getElementById('adminNotesInput')?.value || ''
        })
      });

      const json = await response.json();
      if (json.success) {
        showToast('Laporan berhasil didisposisikan ke tim terkait!');
        closeDispatchModal();
        fetchLaporan(); // refresh table
      } else {
        alert('Gagal disposisi');
      }
    } catch (err) {
      console.error(err);
      alert('Terjadi kesalahan');
    }
  }

  document.addEventListener('DOMContentLoaded', fetchLaporan);
</script>'''

new_content = content[:start_idx] + new_script + content[end_idx:]

with open('resources/views/laporan.blade.php', 'w', encoding='utf-8') as f:
    f.write(new_content)
