<div>
    <h3>Verifikasi & Teruskan ke Teknisi</h3>
    <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">Teruskan tiket pekerjaan ini ke aplikasi
        petugas teknisi lapangan</p>
</div>

<h1>Judul {{ $pengaduan->judul }}</h1>
<p>deskripsi {{ $pengaduan->deskripsi }}</p>

<form id="dispatchForm" onsubmit="handleDispatchSubmit(event)">
    <input type="hidden" id="dispatch-report-id">

    <div class="form-group">
        <label class="form-label" for="technicianSelect">Pilih Teknisi / Divisi Penanggung Jawab *</label>
        <select id="technicianSelect" class="form-control" required>
            <option value="">-- Pilih Petugas / Teknisi --</option>
            @foreach($teknisi as $t)
                <option value="{{ $t->id }}">{{ $t->nama }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="adminNotesInput">Catatan / Instruksi Kerja untuk Teknisi</label>
        <textarea id="adminNotesInput" class="form-control" rows="3"
            placeholder="Contoh: Bawa peralatan cadangan, koordinasikan dengan penanggung jawab lab, prioritaskan sebelum jam kuliah..."></textarea>
    </div>

    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--text-muted);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="16" x2="12" y2="12" />
            <line x1="12" y1="8" x2="12.01" y2="8" />
        </svg>
        Status laporan akan otomatis berganti ke 'Diteruskan ke Teknisi' dan notifikasi dikirim ke perangkat
        teknisi.
    </div>

    <div class="modal-footer" style="margin-top: 20px; padding: 12px 0 0 0; background: transparent;">
        <button type="button" class="btn btn-outline" onclick="closeDispatchModal()">Batal</button>
        <button type="submit" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13" />
                <polygon points="22 2 15 22 11 13 2 9 22 2" />
            </svg>
            Verifikasi & Kirim ke Teknisi
        </button>
    </div>
</form>