<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Notifikasi;
use App\Models\Pengaduan;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLaporanFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $reporter;
    private User $teknisiLapangan;
    private User $guruBk;
    private Kategori $kategoriFasilitas;
    private Kategori $kategoriEtik;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->reporter = User::factory()->create(['role' => 'siswa']);

        $this->teknisiLapangan = User::factory()->create([
            'nama' => 'Teknisi Sarpras Budi',
            'role' => 'teknisi',
            'bidang' => 'fasilitas',
        ]);

        $this->guruBk = User::factory()->create([
            'nama' => 'Ibu Siti Guru BK',
            'role' => 'teknisi',
            'bidang' => 'etik',
        ]);

        $this->kategoriFasilitas = Kategori::create([
            'nama' => 'Kerusakan Meja Kursi',
            'jenis' => 'fasilitas',
        ]);

        $this->kategoriEtik = Kategori::create([
            'nama' => 'Perundungan / Bullying',
            'jenis' => 'etik',
        ]);
    }

    public function test_admin_can_reject_waiting_report(): void
    {
        $report = Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriEtik->id,
            'judul' => 'Laporan Pengaduan Siswa A',
            'deskripsi' => 'Kronologi lengkap laporan insiden di lingkungan sekolah.',
            'lokasi_kejadian' => 'Kantin Belakang Gedung C',
            'status' => 'menunggu',
            'urgency' => 'medium',
        ]);

        $response = $this->postJson("/api/pengaduan/{$report->id}/reject", [
            'alasan_penolakan' => 'Data bukti laporan tidak cukup jelas.',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ditolak')
            ->assertJsonPath('data.alasan_penolakan', 'Data bukti laporan tidak cukup jelas.');

        $this->assertDatabaseHas('pengaduan', [
            'id' => $report->id,
            'status' => 'ditolak',
            'alasan_penolakan' => 'Data bukti laporan tidak cukup jelas.',
        ]);

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->reporter->id,
            'pengaduan_id' => $report->id,
            'type' => 'danger',
            'judul' => 'Laporan Ditolak',
        ]);
    }

    public function test_cannot_reject_already_completed_or_rejected_report(): void
    {
        $completedReport = Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Laporan Pengaduan Selesai',
            'deskripsi' => 'Kerusakan pada proyektor lab komputer sekolah.',
            'lokasi_kejadian' => 'Lab Komputer 2',
            'status' => 'selesai',
        ]);

        $this->postJson("/api/pengaduan/{$completedReport->id}/reject", [
            'alasan_penolakan' => 'Tolak laporan ini',
        ])->assertStatus(409);

        $rejectedReport = Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Laporan Sudah Ditolak',
            'deskripsi' => 'Laporan mengenai papan tulis ruang kelas rusak.',
            'lokasi_kejadian' => 'Ruang Kelas 12 IPA 1',
            'status' => 'ditolak',
        ]);

        $this->postJson("/api/pengaduan/{$rejectedReport->id}/reject", [
            'alasan_penolakan' => 'Tolak lagi',
        ])->assertStatus(409);
    }

    public function test_facility_report_must_be_assigned_to_field_technician_not_guru_bk(): void
    {
        $report = Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Laporan Pintu Kelas Rusak',
            'deskripsi' => 'Engsel pintu kelas 10 patah dan pintu tidak bisa tertutup.',
            'lokasi_kejadian' => 'Gedung A Lantai 2',
            'status' => 'menunggu',
        ]);

        // Trying to assign facility report to Guru BK -> must fail
        $response = $this->postJson('/api/penugasan', [
            'pengaduan_id' => $report->id,
            'teknisi_id' => $this->guruBk->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Laporan fasilitas harus ditugaskan ke Teknisi Lapangan, bukan Guru BK.');

        // Assigning to Teknisi Lapangan -> must succeed
        $validResponse = $this->postJson('/api/penugasan', [
            'pengaduan_id' => $report->id,
            'teknisi_id' => $this->teknisiLapangan->id,
            'urgency' => 'urgent',
        ]);

        $validResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('pengaduan', [
            'id' => $report->id,
            'status' => 'diproses',
            'urgency' => 'urgent',
        ]);
    }

    public function test_ethics_report_must_be_assigned_to_guru_bk_not_teknisi_lapangan(): void
    {
        $report = Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriEtik->id,
            'judul' => 'Laporan Insiden Perundungan',
            'deskripsi' => 'Terjadi tindakan bullying verbal dan intimidasi di lorong.',
            'lokasi_kejadian' => 'Lorong Belakang Perpustakaan',
            'status' => 'menunggu',
        ]);

        // Trying to assign ethics report to Teknisi Lapangan -> must fail
        $response = $this->postJson('/api/penugasan', [
            'pengaduan_id' => $report->id,
            'teknisi_id' => $this->teknisiLapangan->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Laporan pelanggaran kode etik harus ditugaskan ke Guru BK, bukan Teknisi Lapangan.');

        // Assigning to Guru BK -> must succeed
        $validResponse = $this->postJson('/api/penugasan', [
            'pengaduan_id' => $report->id,
            'teknisi_id' => $this->guruBk->id,
        ]);

        $validResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('pengaduan', [
            'id' => $report->id,
            'status' => 'diproses',
        ]);

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->reporter->id,
            'pengaduan_id' => $report->id,
            'judul' => 'Laporan Diproses',
        ]);
    }
}
