@php
    $isFacility = ($pengaduan->kategori?->jenis ?? '') === 'fasilitas';
    $urgencyLabel = match($pengaduan->urgency ?? 'medium') {
        'urgent' => 'Mendesak',
        'low' => 'Rendah',
        default => 'Sedang'
    };
    $urgencyBg = match($pengaduan->urgency ?? 'medium') {
        'urgent' => '#fee2e2',
        'low' => '#dcfce7',
        default => '#fef3c7'
    };
    $urgencyColor = match($pengaduan->urgency ?? 'medium') {
        'urgent' => '#b91c1c',
        'low' => '#047857',
        default => '#b45309'
    };
@endphp

<div>
    <h3>Verifikasi & Teruskan Laporan</h3>
    <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
        Teruskan tiket pekerjaan ini ke {{ $isFacility ? 'Teknisi Lapangan' : 'Guru BK' }}
    </p>
</div>

<div style="margin: 12px 0;">
    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
        <span style="display:inline-block;padding:3px 9px;border-radius:999px;background:{{ $urgencyBg }};color:{{ $urgencyColor }};font-size:.72rem;font-weight:700;">
            Urgensi: {{ $urgencyLabel }}
        </span>
        <span class="badge {{ $isFacility ? 'badge-facility' : 'badge-ethics' }}">
            {{ $isFacility ? 'Fasilitas' : 'Kode Etik' }}
        </span>
    </div>
    <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--text-main);">{{ $pengaduan->judul }}</h2>
    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">{{ $pengaduan->deskripsi }}</p>
</div>

<form id="dispatchForm" onsubmit="handleDispatchSubmit(event)">
    <input type="hidden" id="dispatch-report-id" value="{{ $pengaduan->id }}">

    <div class="form-group">
        <label class="form-label" for="technicianSelect">
            {{ $isFacility ? 'Pilih Teknisi Lapangan (Fasilitas & Sarpras) *' : 'Pilih Guru BK (Penanggung Jawab Kode Etik) *' }}
        </label>
        <select id="technicianSelect" class="form-control" required>
            <option value="">-- Pilih {{ $isFacility ? 'Teknisi Lapangan' : 'Guru BK' }} --</option>
            @foreach($teknisi as $t)
                @php
                    $isBk = ($t->bidang === 'etik' || $t->role === 'guru_bk' || stripos($t->nama, 'bk') !== false || stripos($t->nama, 'guru') !== false);
                    $matches = $isFacility ? !$isBk : $isBk;
                    $isFull = ($t->tugas_aktif_count ?? 0) >= 5;
                @endphp
                @if($matches)
                    <option value="{{ $t->id }}" {{ $isFull ? 'disabled' : '' }}>
                        {{ $t->nama }} ({{ $t->tugas_aktif_count ?? 0 }}/5 tugas aktif){{ $isFull ? ' - [PENUH]' : '' }}
                    </option>
                @endif
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="adminNotesInput">Catatan / Instruksi Kerja Admin</label>
        <textarea id="adminNotesInput" class="form-control" rows="3"
            placeholder="Contoh: Bawa peralatan cadangan, koordinasikan dengan wali kelas / penanggung jawab lab..."></textarea>
    </div>

    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--text-muted);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="16" x2="12" y2="12" />
            <line x1="12" y1="8" x2="12.01" y2="8" />
        </svg>
        Status laporan akan otomatis berganti ke 'Diproses' dan notifikasi dikirim ke perangkat penanggung jawab terkait.
    </div>

    <div class="modal-footer" style="margin-top: 20px; padding: 12px 0 0 0; background: transparent; display: flex; justify-content: space-between;">
        <button type="button" class="btn btn-outline" onclick="history.back()">Kembali</button>
        <button type="submit" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13" />
                <polygon points="22 2 15 22 11 13 2 9 22 2" />
            </svg>
            Verifikasi & Teruskan
        </button>
    </div>
</form>
