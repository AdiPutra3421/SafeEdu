import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/report_model.dart';
import '../theme/app_theme.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class ReportScreen extends StatefulWidget {
  final Function(PengaduanModel) onReportSubmitted;

  const ReportScreen({super.key, required this.onReportSubmitted});

  @override
  State<ReportScreen> createState() => _ReportScreenState();
}

class _ReportScreenState extends State<ReportScreen> {
  final _formKey = GlobalKey<FormState>();

  String _reportType = 'fasilitas'; // 'fasilitas' or 'kode_etik'
  final _titleController = TextEditingController();
  final _locationController = TextEditingController();
  final _descController = TextEditingController();

  List<dynamic> _apiFasilitas = [];
  bool _isLoadingFasilitas = false;
  int? _selectedFasilitasId;
  String _selectedCategory = 'Pendingin Ruangan (AC)';

  String _selectedUrgency = 'Sedang';
  bool _isAnonymous = false;
  String? _attachedFileName;

  final List<String> _facilityCategories = [
    'Pendingin Ruangan (AC)',
    'Komputer & Jaringan IT',
    'Multimedia & Proyektor',
    'Sanitasi & Perpipaan Air',
    'Kelistrikan & Penerangan',
    'Mebel (Meja & Kursi Rusak)',
    'Pintu & Jendela Gedung',
    'Lainnya (Sertakan Deskripsi)',
  ];

  final List<String> _ethicsCategories = [
    'Perundungan / Bullying',
    'Pelecehan Verbal / Fisik',
    'Kecurangan Akademik',
    'Merokok & Vandalisme',
    'Pelanggaran Tata Tertib',
    'Diskriminasi & Intimidasi',
    'Lainnya (Sertakan Kronologi)',
  ];

  @override
  void initState() {
    super.initState();
    _fetchFasilitas();
  }

  Future<void> _fetchFasilitas() async {
    setState(() => _isLoadingFasilitas = true);
    try {
      final res = await http.get(Uri.parse('http://127.0.0.1:8000/api/fasilitas'));
      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (data['success'] == true) {
          setState(() {
            _apiFasilitas = data['data'];
            if (_apiFasilitas.isNotEmpty) {
              _selectedFasilitasId = _apiFasilitas.first['id'];
            }
          });
        }
      }
    } catch (e) {
      print('Error fetching fasilitas: $e');
    } finally {
      setState(() => _isLoadingFasilitas = false);
    }
  }

  @override
  void dispose() {
    _titleController.dispose();
    _locationController.dispose();
    _descController.dispose();
    super.dispose();
  }

  void _switchType(String type) {
    setState(() {
      _reportType = type;
      _selectedCategory = type == 'fasilitas'
          ? _facilityCategories.first
          : _ethicsCategories.first;
      if (type == 'kode_etik') {
        _isAnonymous = true; // Default to anonymous for safety
      }
    });
  }

  void _simulatePickAttachment() {
    setState(() {
      _attachedFileName = _reportType == 'fasilitas'
          ? 'bukti_kerusakan_unit.jpg'
          : 'kronologi_kejadian.pdf';
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content:
            Text('Lampiran file "$_attachedFileName" berhasil ditambahkan!'),
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 2),
      ),
    );
  }

  Future<void> _handleSubmit() async {
    if (_formKey.currentState!.validate()) {
      // Tampilkan dialog konfirmasi
      showDialog(
        context: context,
        builder: (ctx) {
          return AlertDialog(
            title: Row(
              children: [
                Icon(
                  _reportType == 'fasilitas'
                      ? Icons.build_circle_outlined
                      : Icons.shield_outlined,
                  color: AppColors.primary,
                ),
                const SizedBox(width: 8),
                const Text('Kirim Laporan?'),
              ],
            ),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Apakah data laporan Anda sudah benar? Laporan akan langsung masuk ke antrean verifikasi Admin SafeEdu.',
                  style: TextStyle(
                      fontSize: 13, color: AppColors.secondaryMuted),
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppColors.subtle,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                          'Tipe: ${_reportType == 'fasilitas' ? 'Fasilitas Sekolah' : 'Pelanggaran Kode Etik'}',
                          style: const TextStyle(
                              fontSize: 12, fontWeight: FontWeight.w600)),
                      Text(_reportType == 'fasilitas' 
                            ? 'Fasilitas: ${_apiFasilitas.firstWhere((f) => f['id'] == _selectedFasilitasId, orElse: () => {'nama': '-'})['nama']}'
                            : 'Kategori: $_selectedCategory',
                          style: const TextStyle(fontSize: 12)),
                      Text(
                          'Pelapor: ${_isAnonymous ? 'Anonim (Dirahasiakan)' : 'Budi Siswa (Terbuka)'}',
                          style: const TextStyle(fontSize: 12)),
                    ],
                  ),
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx),
                child: const Text('Periksa Kembali'),
              ),
              ElevatedButton(
                onPressed: () async {
                  Navigator.pop(ctx);
                  await _sendReportToApi();
                },
                child: const Text('Kirim Sekarang'),
              ),
            ],
          );
        },
      );
    }
  }

  Future<void> _sendReportToApi() async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => const Center(child: CircularProgressIndicator()),
    );

    try {
      int kategoriId = _reportType == 'fasilitas' ? 1 : 2; 
      
      final response = await http.post(
        Uri.parse('http://127.0.0.1:8000/api/pengaduan'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'user_id': 1, // mock user id
          'kategori_id': kategoriId,
          'fasilitas_id': _reportType == 'fasilitas' ? _selectedFasilitasId : null,
          'judul': _titleController.text.trim(),
          'deskripsi': _descController.text.trim(),
          'lokasi_kejadian': _locationController.text.trim(),
          'is_anonim': _isAnonymous,
        }),
      );

      Navigator.pop(context); // close loading

      if (response.statusCode == 201 || response.statusCode == 200) {
        final data = json.decode(response.body)['data'];
        
        final newReport = PengaduanModel(
          id: data['id'],
          userId: data['user_id'],
          kategoriId: data['kategori_id'],
          fasilitasId: data['fasilitas_id'],
          judul: data['judul'],
          deskripsi: data['deskripsi'],
          lokasiKejadian: data['lokasi_kejadian'],
          status: data['status'],
          isAnonim: data['is_anonim'] == 1 || data['is_anonim'] == true,
          createdAt: data['created_at'],
          namaKategori: _reportType == 'fasilitas' 
            ? _apiFasilitas.firstWhere((f) => f['id'] == _selectedFasilitasId, orElse: () => {'nama': '-'})['nama'] 
            : _selectedCategory,
        );

        widget.onReportSubmitted(newReport);

        _titleController.clear();
        _locationController.clear();
        _descController.clear();
        setState(() {
          _attachedFileName = null;
        });

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.secondary,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(10)),
            content: Row(
              children: [
                const Icon(Icons.check_circle,
                    color: Color(0xFF4ADE80), size: 18),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Laporan REP-2026-${newReport.id} berhasil dikirim ke Admin!',
                    style: const TextStyle(fontWeight: FontWeight.w500),
                  ),
                ),
              ],
            ),
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Gagal mengirim laporan')),
        );
      }
    } catch (e) {
      Navigator.pop(context); // close loading
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isFacility = _reportType == 'fasilitas';
    final categories = isFacility ? _facilityCategories : _ethicsCategories;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: const Text('Buat Laporan Baru'),
        actions: [
          IconButton(
            icon: const Icon(Icons.info_outline_rounded,
                color: AppColors.textMuted),
            onPressed: () {
              showModalBottomSheet(
                context: context,
                builder: (_) => Container(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: const [
                      Text('Panduan Pengisian Laporan',
                          style: TextStyle(
                              fontWeight: FontWeight.w700, fontSize: 16)),
                      SizedBox(height: 10),
                      Text(
                          '1. Pilih jenis laporan yang tepat (Fasilitas atau Kode Etik).\n2. Cantumkan lokasi sedetail mungkin untuk mempermudah teknisi/tim respon.\n3. Khusus pelanggaran kode etik, Anda dapat memilih mode Anonim agar identitas tetap terlindungi.',
                          style: TextStyle(fontSize: 13, height: 1.5)),
                    ],
                  ),
                ),
              );
            },
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Segmented Report Type Selector
              Container(
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.border),
                ),
                padding: const EdgeInsets.all(4),
                child: Row(
                  children: [
                    Expanded(
                      child: GestureDetector(
                        onTap: () => _switchType('fasilitas'),
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 200),
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          decoration: BoxDecoration(
                            color: isFacility
                                ? AppColors.primary
                                : Colors.transparent,
                            borderRadius: BorderRadius.circular(8),
                            boxShadow: isFacility
                                ? [
                                    BoxShadow(
                                        color: AppColors.primary
                                            .withValues(alpha: 0.25),
                                        blurRadius: 4,
                                        offset: const Offset(0, 2))
                                  ]
                                : null,
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.build_rounded,
                                  size: 16,
                                  color: isFacility
                                      ? Colors.white
                                      : AppColors.textMuted),
                              const SizedBox(width: 6),
                              Text(
                                'Fasilitas Sekolah',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w700,
                                  color: isFacility
                                      ? Colors.white
                                      : AppColors.textMuted,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    Expanded(
                      child: GestureDetector(
                        onTap: () => _switchType('kode_etik'),
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 200),
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          decoration: BoxDecoration(
                            color: !isFacility
                                ? const Color(0xFFE11D48)
                                : Colors.transparent,
                            borderRadius: BorderRadius.circular(8),
                            boxShadow: !isFacility
                                ? [
                                    BoxShadow(
                                        color: const Color(0xFFE11D48)
                                            .withValues(alpha: 0.25),
                                        blurRadius: 4,
                                        offset: const Offset(0, 2))
                                  ]
                                : null,
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.shield_rounded,
                                  size: 16,
                                  color: !isFacility
                                      ? Colors.white
                                      : AppColors.textMuted),
                              const SizedBox(width: 6),
                              Text(
                                'Kode Etik & Tertib',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w700,
                                  color: !isFacility
                                      ? Colors.white
                                      : AppColors.textMuted,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 16),

              // Notification banner regarding category
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: isFacility ? AppColors.facilityBg : AppColors.ethicsBg,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(
                      color: isFacility
                          ? const Color(0xFFBFDBFE)
                          : const Color(0xFFFECDD3)),
                ),
                child: Row(
                  children: [
                    Icon(
                      isFacility
                          ? Icons.info_outline
                          : Icons.lock_outline_rounded,
                      size: 18,
                      color: isFacility
                          ? AppColors.facilityText
                          : AppColors.ethicsText,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        isFacility
                            ? 'Laporan fasilitas akan diverifikasi admin & diteruskan ke teknisi sarpras.'
                            : 'Laporan pelanggaran kode etik bersifat rahasia & ditangani langsung Satgas/BK.',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                          color: isFacility
                              ? AppColors.facilityText
                              : AppColors.ethicsText,
                        ),
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // Title Field
              const Text('Judul Laporan *',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              const SizedBox(height: 6),
              TextFormField(
                controller: _titleController,
                decoration: InputDecoration(
                  hintText: isFacility
                      ? 'Contoh: AC Meneteskan Air di Lab 302'
                      : 'Contoh: Dugaan Intimidasi di Kantin',
                  hintStyle:
                      const TextStyle(fontSize: 13, color: AppColors.textLight),
                ),
                validator: (val) => val == null || val.trim().isEmpty
                    ? 'Judul laporan wajib diisi'
                    : null,
              ),

              const SizedBox(height: 16),

              // Facility / Category Field
              Text(isFacility ? 'Pilih Fasilitas *' : 'Kategori Masalah *',
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              const SizedBox(height: 6),
              isFacility 
                ? DropdownButtonFormField<int>(
                    key: ValueKey('fasilitas_$_reportType'),
                    value: _selectedFasilitasId,
                    items: _apiFasilitas.map((f) {
                      return DropdownMenuItem<int>(
                          value: f['id'],
                          child: Text(f['nama'] ?? '-', style: const TextStyle(fontSize: 13)));
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) setState(() => _selectedFasilitasId = val);
                    },
                    decoration: const InputDecoration(
                      hintText: 'Pilih fasilitas yang rusak',
                      hintStyle: TextStyle(fontSize: 13, color: AppColors.textLight),
                    ),
                  )
                : DropdownButtonFormField<String>(
                    key: ValueKey('category_$_reportType'),
                    value: _selectedCategory,
                    items: categories.map((cat) {
                      return DropdownMenuItem(
                          value: cat,
                          child: Text(cat, style: const TextStyle(fontSize: 13)));
                    }).toList(),
                    onChanged: (val) {
                      if (val != null) setState(() => _selectedCategory = val);
                    },
                    decoration: const InputDecoration(),
                  ),

              const SizedBox(height: 16),

              // Location Field
              const Text('Lokasi Insiden / Sarana *',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              const SizedBox(height: 6),
              TextFormField(
                controller: _locationController,
                decoration: const InputDecoration(
                  hintText: 'Contoh: Gedung B, Lantai 2, Ruang Kelas 204',
                  hintStyle:
                      TextStyle(fontSize: 13, color: AppColors.textLight),
                  prefixIcon: Icon(Icons.location_on_outlined,
                      size: 18, color: AppColors.textMuted),
                ),
                validator: (val) => val == null || val.trim().isEmpty
                    ? 'Lokasi insiden wajib dicantumkan'
                    : null,
              ),

              const SizedBox(height: 16),

              // Urgency Selection
              const Text('Tingkat Urgensi *',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              const SizedBox(height: 8),
              Row(
                children: [
                  _buildUrgencyChip('Rendah', const Color(0xFF10B981)),
                  const SizedBox(width: 8),
                  _buildUrgencyChip('Sedang', const Color(0xFFF59E0B)),
                  const SizedBox(width: 8),
                  _buildUrgencyChip('Mendesak', const Color(0xFFEF4444)),
                ],
              ),

              const SizedBox(height: 16),

              // Description Field
              const Text('Rincian Kronologi & Keterangan *',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              const SizedBox(height: 6),
              TextFormField(
                controller: _descController,
                maxLines: 4,
                decoration: const InputDecoration(
                  hintText:
                      'Jelaskan kronologi kejadian secara runtut, tanda-tanda kerusakan, atau pihak yang terlibat...',
                  hintStyle:
                      TextStyle(fontSize: 13, color: AppColors.textLight),
                ),
                validator: (val) => val == null || val.trim().length < 10
                    ? 'Mohon jelaskan minimal 10 karakter'
                    : null,
              ),

              const SizedBox(height: 16),

              // Attachment Uploader
              const Text('Lampiran Bukti Foto / Dokumen',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              const SizedBox(height: 6),
              InkWell(
                onTap: _simulatePickAttachment,
                borderRadius: BorderRadius.circular(10),
                child: Container(
                  width: double.infinity,
                  padding:
                      const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                        color: AppColors.border, style: BorderStyle.solid),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        _attachedFileName != null
                            ? Icons.attach_file_rounded
                            : Icons.cloud_upload_outlined,
                        color: _attachedFileName != null
                            ? AppColors.accentTeal
                            : AppColors.primary,
                        size: 20,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          _attachedFileName ??
                              'Unggah foto kerusakan atau bukti pendukung',
                          style: TextStyle(
                            fontSize: 13,
                            color: _attachedFileName != null
                                ? AppColors.textMain
                                : AppColors.textMuted,
                            fontWeight: _attachedFileName != null
                                ? FontWeight.w600
                                : FontWeight.normal,
                          ),
                        ),
                      ),
                      if (_attachedFileName != null)
                        const Icon(Icons.check_circle,
                            color: Color(0xFF10B981), size: 18),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 18),

              // Anonymous Option Toggle
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: AppColors.border),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Icon(Icons.visibility_off_outlined,
                            size: 18,
                            color: _isAnonymous
                                ? AppColors.primary
                                : AppColors.textMuted),
                        const SizedBox(width: 10),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: const [
                            Text('Kirim sebagai Anonim',
                                style: TextStyle(
                                    fontSize: 13, fontWeight: FontWeight.w600)),
                            Text(
                                'Identitas nama & kelas disembunyikan dari publik',
                                style: TextStyle(
                                    fontSize: 11, color: AppColors.textMuted)),
                          ],
                        ),
                      ],
                    ),
                    Switch(
                      value: _isAnonymous,
                      activeThumbColor: AppColors.primary,
                      onChanged: (val) => setState(() => _isAnonymous = val),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 24),

              // Submit Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  icon: const Icon(Icons.send_rounded, size: 18),
                  label: const Text('Kirim Laporan ke SafeEdu'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: isFacility
                        ? AppColors.primary
                        : const Color(0xFFE11D48),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                  onPressed: _handleSubmit,
                ),
              ),

              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildUrgencyChip(String label, Color color) {
    final isSelected = _selectedUrgency == label;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _selectedUrgency = label),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color:
                isSelected ? color.withValues(alpha: 0.12) : AppColors.surface,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(
              color: isSelected ? color : AppColors.border,
              width: isSelected ? 1.5 : 1,
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 8,
                height: 8,
                decoration: BoxDecoration(shape: BoxShape.circle, color: color),
              ),
              const SizedBox(width: 6),
              Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: isSelected ? color : AppColors.textMain,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
