<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Pengaduan;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminLaporanFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $reporter;

    private User $teknisiLapangan;

    private User $teknisiKonseling;

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
            'bidang' => 'lapangan',
        ]);

        $this->teknisiKonseling = User::factory()->create([
            'nama' => 'Ibu Siti Konseling',
            'role' => 'teknisi',
            'bidang' => 'konseling',
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

    public function test_rejection_reason_column_is_available(): void
    {
        $this->assertTrue(Schema::hasColumn('pengaduan', 'alasan_penolakan'));
    }

    public function test_uploaded_report_attachment_can_be_opened_without_a_storage_symlink(): void
    {
        $paths = [
            'bukti/test-admin-lampiran.png',
            'photo_proofs/test-admin-proof.jpg',
        ];
        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'test attachment');
        }

        try {
            foreach ($paths as $path) {
                $this->get('/api/gambar/'.$path)->assertOk();
            }
        } finally {
            foreach ($paths as $path) {
                Storage::disk('public')->delete($path);
            }
        }
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

    public function test_facility_report_must_be_assigned_to_lapangan_subrole(): void
    {
        $report = Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Laporan Pintu Kelas Rusak',
            'deskripsi' => 'Engsel pintu kelas 10 patah dan pintu tidak bisa tertutup.',
            'lokasi_kejadian' => 'Gedung A Lantai 2',
            'status' => 'menunggu',
        ]);

        $response = $this->postJson('/api/penugasan', [
            'pengaduan_id' => $report->id,
            'teknisi_id' => $this->teknisiKonseling->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Laporan fasilitas hanya dapat ditugaskan ke teknisi sub-role Lapangan.');

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

    public function test_ethics_report_must_be_assigned_to_konseling_subrole(): void
    {
        $report = Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriEtik->id,
            'judul' => 'Laporan Insiden Perundungan',
            'deskripsi' => 'Terjadi tindakan bullying verbal dan intimidasi di lorong.',
            'lokasi_kejadian' => 'Lorong Belakang Perpustakaan',
            'status' => 'menunggu',
        ]);

        $response = $this->postJson('/api/penugasan', [
            'pengaduan_id' => $report->id,
            'teknisi_id' => $this->teknisiLapangan->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Laporan kode etik hanya dapat ditugaskan ke teknisi sub-role Konseling.');

        $validResponse = $this->postJson('/api/penugasan', [
            'pengaduan_id' => $report->id,
            'teknisi_id' => $this->teknisiKonseling->id,
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

    public function test_facility_report_urgency_is_set_by_admin_instead_of_reporter(): void
    {
        $response = $this->post('/api/pengaduan', [
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Kerusakan Fasilitas Ruang Belajar',
            'deskripsi' => 'Kerusakan fasilitas perlu diperiksa karena meja belajar sudah tidak dapat digunakan.',
            'lokasi_kejadian' => 'Ruang Belajar Gedung A',
            'urgency' => 'urgent',
            'file_bukti' => UploadedFile::fake()->create('fasilitas.jpg', 10, 'image/jpeg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.urgency', 'medium');

        $ethicsResponse = $this->postJson('/api/pengaduan', [
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriEtik->id,
            'judul' => 'Laporan Kode Etik Siswa',
            'deskripsi' => 'Laporan kode etik dengan kronologi yang harus diteruskan ke tim konseling.',
            'lokasi_kejadian' => 'Lorong Gedung B',
            'urgency' => 'urgent',
        ]);

        $ethicsResponse->assertCreated()
            ->assertJsonPath('data.urgency', 'urgent');
    }

    public function test_facility_reports_require_a_photo_but_ethics_reports_do_not_require_an_attachment(): void
    {
        Storage::fake('public');

        $facilityReport = [
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Kerusakan Fasilitas Kelas',
            'deskripsi' => 'Meja kelas mengalami kerusakan dan perlu diperiksa oleh petugas.',
            'lokasi_kejadian' => 'Ruang Kelas Gedung A',
        ];

        $this->postJson('/api/pengaduan', $facilityReport)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file_bukti');

        $this->post('/api/pengaduan', $facilityReport + [
            'file_bukti' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file_bukti');

        $this->post('/api/pengaduan', $facilityReport + [
            'file_bukti' => UploadedFile::fake()->create('fasilitas.jpg', 10, 'image/jpeg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.kategori.jenis', 'fasilitas');

        $this->postJson('/api/pengaduan', [
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriEtik->id,
            'judul' => 'Laporan Pelanggaran Kode Etik',
            'deskripsi' => 'Perlu ditindaklanjuti terkait perilaku yang terjadi di lingkungan sekolah.',
            'lokasi_kejadian' => 'Halaman Gedung B',
        ])->assertCreated()
            ->assertJsonPath('data.kategori.jenis', 'etik');
    }

    public function test_user_cannot_create_duplicate_report_while_previous_report_is_active(): void
    {
        Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Kerusakan jendela kelas',
            'deskripsi' => 'Pintu kelas mengalami kerusakan yang perlu diperbaiki segera.',
            'lokasi_kejadian' => 'Gedung A',
            'status' => 'menunggu',
        ]);

        $response = $this->post('/api/pengaduan', [
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => '  KERUSAKAN   JENDELA KELAS ',
            'deskripsi' => "PINTU KELAS mengalami\nkerusakan yang perlu diperbaiki segera.",
            'lokasi_kejadian' => 'Gedung B',
            'file_bukti' => UploadedFile::fake()->create('fasilitas.jpg', 10, 'image/jpeg'),
        ], ['Accept' => 'application/json']);

        $response->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Laporan dengan judul dan deskripsi yang sama sedang diproses atau belum selesai.');
    }

    public function test_user_can_create_report_with_same_title_and_description_when_previous_report_is_completed(): void
    {
        Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Kerusakan jendela kelas',
            'deskripsi' => 'Pintu kelas mengalami kerusakan yang perlu diperbaiki segera.',
            'lokasi_kejadian' => 'Gedung A',
            'status' => 'selesai',
        ]);

        $response = $this->post('/api/pengaduan', [
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => '  KERUSAKAN   JENDELA KELAS ',
            'deskripsi' => "PINTU KELAS mengalami\nkerusakan yang perlu diperbaiki segera.",
            'lokasi_kejadian' => 'Gedung B',
            'file_bukti' => UploadedFile::fake()->create('fasilitas.jpg', 10, 'image/jpeg'),
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_user_cannot_create_more_than_five_active_reports(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            Pengaduan::create([
                'user_id' => $this->reporter->id,
                'kategori_id' => $this->kategoriFasilitas->id,
                'judul' => "Kerusakan fasilitas nomor $i",
                'deskripsi' => "Deskripsi detail untuk laporan fasilitas aktif ke-$i di sekolah.",
                'lokasi_kejadian' => "Ruang $i",
                'status' => 'menunggu',
            ]);
        }

        for ($i = 4; $i <= 5; $i++) {
            Pengaduan::create([
                'user_id' => $this->reporter->id,
                'kategori_id' => $this->kategoriEtik->id,
                'judul' => "Pelanggaran kode etik nomor $i",
                'deskripsi' => "Deskripsi detail untuk laporan etika aktif ke-$i yang perlu ditangani.",
                'lokasi_kejadian' => "Halaman $i",
                'status' => 'diproses',
            ]);
        }

        $response = $this->post('/api/pengaduan', [
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Laporan fasilitas baru yang keenam',
            'deskripsi' => 'Mencoba membuat laporan keenam saat masih ada lima laporan yang aktif.',
            'lokasi_kejadian' => 'Laboratorium Kimia',
            'file_bukti' => UploadedFile::fake()->create('fasilitas.jpg', 10, 'image/jpeg'),
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Anda sudah memiliki 5 laporan aktif. Tunggu hingga laporan sebelumnya selesai sebelum membuat laporan baru.');
    }

    public function test_user_can_create_report_when_previous_reports_are_completed(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            Pengaduan::create([
                'user_id' => $this->reporter->id,
                'kategori_id' => $this->kategoriFasilitas->id,
                'judul' => "Kerusakan fasilitas nomor $i",
                'deskripsi' => "Deskripsi detail untuk laporan fasilitas aktif ke-$i di sekolah.",
                'lokasi_kejadian' => "Ruang $i",
                'status' => 'menunggu',
            ]);
        }

        for ($i = 5; $i <= 6; $i++) {
            Pengaduan::create([
                'user_id' => $this->reporter->id,
                'kategori_id' => $this->kategoriEtik->id,
                'judul' => "Pelanggaran kode etik selesai $i",
                'deskripsi' => "Deskripsi detail untuk laporan etika yang sudah selesai dikerjakan.",
                'lokasi_kejadian' => "Halaman $i",
                'status' => 'selesai',
            ]);
        }

        $response = $this->post('/api/pengaduan', [
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Laporan fasilitas aktif yang kelima',
            'deskripsi' => 'Mencoba membuat laporan kelima yang masih diperbolehkan oleh sistem.',
            'lokasi_kejadian' => 'Laboratorium Fisika',
            'file_bukti' => UploadedFile::fake()->create('fasilitas.jpg', 10, 'image/jpeg'),
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_report_with_different_title_and_description_is_not_rejected_as_duplicate(): void
    {
        Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Kerusakan jendela kelas',
            'deskripsi' => 'Pintu kelas mengalami kerusakan yang perlu diperbaiki segera.',
            'lokasi_kejadian' => 'Gedung A',
            'status' => 'menunggu',
        ]);

        $this->post('/api/pengaduan', [
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Kebocoran atap ruang belajar',
            'deskripsi' => 'Air hujan masuk ke ruang belajar melalui bagian atap yang bocor.',
            'lokasi_kejadian' => 'Gedung A',
            'file_bukti' => UploadedFile::fake()->create('fasilitas.jpg', 10, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertCreated();
    }

    public function test_technician_api_creates_accounts_with_one_of_the_two_subroles(): void
    {
        $response = $this->postJson('/api/teknisi', [
            'nama' => 'Petugas Konseling',
            'email' => 'konseling@safeedu.sch.id',
            'password' => 'password',
            'bidang' => 'konseling',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.role', 'teknisi')
            ->assertJsonPath('data.bidang', 'konseling');

        $this->putJson('/api/teknisi/'.$this->teknisiLapangan->id, [
            'nama' => $this->teknisiLapangan->nama,
            'email' => $this->teknisiLapangan->email,
            'bidang' => 'konseling',
        ])->assertOk()
            ->assertJsonPath('data.role', 'teknisi')
            ->assertJsonPath('data.bidang', 'konseling');

        $this->postJson('/api/teknisi', [
            'nama' => 'Sub-role Tidak Valid',
            'email' => 'invalid@safeedu.sch.id',
            'password' => 'password',
            'bidang' => 'etik',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('bidang');
    }

    public function test_technician_task_list_only_includes_the_technicians_subrole_category(): void
    {
        $facilityReport = Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriFasilitas->id,
            'judul' => 'Laporan Fasilitas',
            'deskripsi' => 'Kerusakan fasilitas yang perlu segera diperiksa oleh petugas.',
            'lokasi_kejadian' => 'Ruang Belajar Gedung A',
        ]);
        $ethicsReport = Pengaduan::create([
            'user_id' => $this->reporter->id,
            'kategori_id' => $this->kategoriEtik->id,
            'judul' => 'Laporan Kode Etik',
            'deskripsi' => 'Laporan kode etik yang perlu ditangani oleh petugas konseling.',
            'lokasi_kejadian' => 'Ruang Belajar Gedung A',
        ]);

        Penugasan::create([
            'pengaduan_id' => $facilityReport->id,
            'teknisi_id' => $this->teknisiLapangan->id,
            'admin_id' => $this->admin->id,
            'status' => 'ditugaskan',
        ]);
        $mismatchedTask = Penugasan::create([
            'pengaduan_id' => $ethicsReport->id,
            'teknisi_id' => $this->teknisiLapangan->id,
            'admin_id' => $this->admin->id,
            'status' => 'ditugaskan',
        ]);

        $this->getJson('/api/penugasan?teknisi_id='.$this->teknisiLapangan->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.pengaduan.kategori.jenis', 'fasilitas');

        $this->postJson("/api/penugasan/{$mismatchedTask->id}/status", [
            'status' => 'diterima',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Sub-role teknisi tidak sesuai dengan kategori laporan ini.');
    }
}
