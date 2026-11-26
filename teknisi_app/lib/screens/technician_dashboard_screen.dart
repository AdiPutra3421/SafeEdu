import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:image_picker/image_picker.dart';
import '../models/task_model.dart';
import '../services/api_config.dart';
import '../services/auth_service.dart';
import '../theme/app_theme.dart';
import 'auth/login_screen.dart';

class TechnicianDashboardScreen extends StatefulWidget {
  const TechnicianDashboardScreen({super.key});

  @override
  State<TechnicianDashboardScreen> createState() =>
      _TechnicianDashboardScreenState();
}

class _TechnicianDashboardScreenState extends State<TechnicianDashboardScreen> {
  bool isOnDuty = true;
  String selectedFilter = 'Semua';
  String selectedUrgencyFilter = 'Semua';
  String? _lastStatusError;

  List<PenugasanModel> _tasks = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchTasks();
  }

  Future<void> _fetchTasks() async {
    if (!mounted) return;
    setState(() => _isLoading = true);

    try {
      final teknisiId = AuthService.instance.userId;
      final queryParameters = <String, String>{};
      if (teknisiId != null) {
        queryParameters['teknisi_id'] = teknisiId.toString();
      }
      if (selectedUrgencyFilter != 'Semua') {
        queryParameters['urgency'] = selectedUrgencyFilter;
      }
      final uri = Uri.parse('${ApiConfig.baseUrl}/api/penugasan').replace(
          queryParameters: queryParameters.isEmpty ? null : queryParameters);
      final res = await http.get(uri);

      if (!mounted) return;

      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (data is Map && data['success'] == true && data['data'] is List) {
          final list = data['data'] as List;
          setState(() {
            _tasks = list
                .map((item) =>
                    PenugasanModel.fromJson(item as Map<String, dynamic>))
                .toList();
          });
          return;
        }
      }

      setState(() => _tasks = const []);
    } catch (e) {
      if (!mounted) return;
      setState(() => _tasks = const []);
      debugPrint('Error fetching tasks: $e');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<bool> _updateTaskStatus(
    int id,
    String status,
    String keterangan, {
    XFile? photo,
    Uint8List? photoBytes,
  }) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('${ApiConfig.baseUrl}/api/penugasan/$id/status'),
      )
        ..headers['Accept'] = 'application/json'
        ..fields['status'] = status
        ..fields['catatan_teknisi'] = keterangan;
      if (photo != null) {
        request.files.add(http.MultipartFile.fromBytes(
          'photo',
          photoBytes ?? await photo.readAsBytes(),
          filename: photo.name,
        ));
      }

      final streamed = await request.send();
      final res = await http.Response.fromStream(streamed);

      if (res.statusCode == 200) {
        _lastStatusError = null;
        await _fetchTasks();
        return true;
      }

      debugPrint(res.body);
      _lastStatusError = _responseMessage(res.body);
      return false;
    } catch (e) {
      debugPrint('Error update task: $e');
      _lastStatusError =
          'Gagal mengirim pembaruan. Periksa koneksi lalu coba lagi.';
      return false;
    }
  }

  String _responseMessage(String body) {
    try {
      final response = json.decode(body);
      if (response is Map) {
        final errors = response['errors'];
        if (errors is Map) {
          final messages = errors.values
              .expand((value) => value is List ? value : [value])
              .join(' ');
          if (messages.isNotEmpty) return messages;
        }
        if (response['message'] is String) return response['message'];
      }
    } on FormatException {
      debugPrint('Server returned a non-JSON error response.');
    }
    return 'Permintaan gagal. Silakan coba kembali.';
  }

  int get countNew =>
      _tasks.where((t) => t.statusPenugasan == 'ditugaskan').length;
  int get countProcess =>
      _tasks.where((t) => t.statusPenugasan == 'diterima').length;
  int get countFinished =>
      _tasks.where((t) => t.statusPenugasan == 'selesai').length;

  List<PenugasanModel> get filteredTasks {
    return _tasks.where((task) {
      final matchesStatus = switch (selectedFilter) {
        'Tugas Baru' => task.statusPenugasan == 'ditugaskan',
        'Dalam Pengerjaan' => task.statusPenugasan == 'diterima',
        'Menunggu Admin' => task.statusPenugasan == 'menunggu_konfirmasi',
        'Selesai' => task.statusPenugasan == 'selesai',
        _ => true,
      };
      final matchesUrgency = selectedUrgencyFilter == 'Semua' ||
          task.urgency == selectedUrgencyFilter;
      return matchesStatus && matchesUrgency;
    }).toList();
  }

  void _showConfirmationSheet(PenugasanModel task) {
    final notesController =
        TextEditingController(text: task.keteranganTeknisi ?? '');
    XFile? proofPhoto;
    Uint8List? proofPhotoBytes;
    String chosenStatus = task.statusPenugasan == 'ditugaskan'
        ? 'diterima'
        : task.statusPenugasan;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Container(
              padding: EdgeInsets.only(
                top: 20,
                left: 20,
                right: 20,
                bottom: MediaQuery.of(context).viewInsets.bottom + 24,
              ),
              decoration: const BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Center(
                      child: Container(
                        width: 44,
                        height: 4,
                        decoration: BoxDecoration(
                          color: AppColors.border,
                          borderRadius: BorderRadius.circular(4),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'ID Penugasan: ${task.idPenugasan}',
                              style: const TextStyle(
                                color: AppColors.primary,
                                fontWeight: FontWeight.w700,
                                fontSize: 13,
                              ),
                            ),
                            const Text(
                              'Kirim Konfirmasi ke Admin',
                              style: TextStyle(
                                fontSize: 17,
                                fontWeight: FontWeight.w700,
                                color: AppColors.textMain,
                              ),
                            ),
                          ],
                        ),
                        IconButton(
                          icon: const Icon(Icons.close,
                              color: AppColors.textMuted),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Card(
                      elevation: 0,
                      color: AppColors.subtle,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(10),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.06),
                              blurRadius: 10,
                              offset: const Offset(0, 3),
                            ),
                          ],
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Text(
                                    task.judulLaporan,
                                    style: const TextStyle(
                                        fontWeight: FontWeight.w600,
                                        fontSize: 14),
                                  ),
                                ),
                                _urgencyChip(task.urgency),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '📍 ${task.lokasi}',
                              style: const TextStyle(
                                  fontSize: 12, color: AppColors.textMuted),
                            ),
                            if (task.catatanAdmin != null &&
                                task.catatanAdmin!.isNotEmpty) ...[
                              const SizedBox(height: 6),
                              Text(
                                'Pesan Admin: "${task.catatanAdmin}"',
                                style: const TextStyle(
                                    fontSize: 12,
                                    fontStyle: FontStyle.italic,
                                    color: AppColors.secondaryMuted),
                              ),
                            ]
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 18),
                    const Text(
                      'Pilih Status Pengerjaan',
                      style:
                          TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      children: [
                        ChoiceChip(
                          avatar: Icon(
                            Icons.handyman_outlined,
                            size: 16,
                            color: chosenStatus == 'diterima'
                                ? AppColors.statusProcessText
                                : AppColors.textMuted,
                          ),
                          label: const Text('Dalam Pengerjaan'),
                          selected: chosenStatus == 'diterima',
                          selectedColor: AppColors.statusProcessBg,
                          showCheckmark: false,
                          side: BorderSide(
                            color: chosenStatus == 'diterima'
                                ? AppColors.statusProcessText
                                : AppColors.border,
                          ),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                          labelStyle: TextStyle(
                            color: chosenStatus == 'diterima'
                                ? AppColors.statusProcessText
                                : AppColors.textMain,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                          onSelected: (val) {
                            if (val) {
                              setModalState(() => chosenStatus = 'diterima');
                            }
                          },
                        ),
                        ChoiceChip(
                          avatar: Icon(
                            Icons.check_circle_outline_rounded,
                            size: 16,
                            color: chosenStatus == 'selesai'
                                ? AppColors.statusSuccessText
                                : AppColors.textMuted,
                          ),
                          label: const Text('Pekerjaan Selesai'),
                          selected: chosenStatus == 'selesai',
                          selectedColor: AppColors.statusSuccessBg,
                          showCheckmark: false,
                          side: BorderSide(
                            color: chosenStatus == 'selesai'
                                ? AppColors.statusSuccessText
                                : AppColors.border,
                          ),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                          labelStyle: TextStyle(
                            color: chosenStatus == 'selesai'
                                ? AppColors.statusSuccessText
                                : AppColors.textMain,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                          onSelected: (val) {
                            if (val) {
                              setModalState(() => chosenStatus = 'selesai');
                            }
                          },
                        ),
                      ],
                    ),
                    if (chosenStatus == 'selesai') ...[
                      const SizedBox(height: 16),
                      const Text(
                        'Foto Bukti Penyelesaian *',
                        style: TextStyle(
                            fontWeight: FontWeight.w600, fontSize: 13),
                      ),
                      const SizedBox(height: 6),
                      OutlinedButton.icon(
                        onPressed: () async {
                          try {
                            final photo = await ImagePicker().pickImage(
                              source: ImageSource.gallery,
                              imageQuality: 80,
                              maxWidth: 1600,
                              maxHeight: 1600,
                            );
                            if (photo != null) {
                              final photoBytes = await photo.readAsBytes();
                              if (!ctx.mounted) return;
                              setModalState(() {
                                proofPhoto = photo;
                                proofPhotoBytes = photoBytes;
                              });
                            }
                          } catch (error) {
                            debugPrint('Error selecting proof photo: $error');
                            if (mounted) {
                              ScaffoldMessenger.of(this.context).showSnackBar(
                                const SnackBar(
                                  content: Text('Gagal memilih foto bukti.'),
                                ),
                              );
                            }
                          }
                        },
                        icon: const Icon(Icons.add_a_photo_outlined),
                        label: Text(proofPhoto == null
                            ? 'Pilih Foto'
                            : proofPhoto!.name),
                      ),
                      if (proofPhoto != null && proofPhotoBytes != null) ...[
                        const SizedBox(height: 8),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: Image.memory(
                            proofPhotoBytes!,
                            height: 160,
                            width: double.infinity,
                            fit: BoxFit.cover,
                          ),
                        ),
                      ],
                    ],
                    const SizedBox(height: 16),
                    const Text(
                      'Catatan Lapangan / Tindakan Perbaikan',
                      style:
                          TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                    ),
                    const SizedBox(height: 6),
                    TextField(
                      controller: notesController,
                      maxLines: 3,
                      decoration: const InputDecoration(
                        hintText:
                            'Tuliskan tindakan yang diambil, suku cadang yang diganti, atau kendala lapangan...',
                        hintStyle:
                            TextStyle(fontSize: 13, color: AppColors.textLight),
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        icon: const Icon(Icons.send_rounded, size: 18),
                        label: const Text('Kirim Konfirmasi ke Portal Admin'),
                        onPressed: () async {
                          if (chosenStatus == 'selesai' && proofPhoto == null) {
                            ScaffoldMessenger.of(this.context).showSnackBar(
                              const SnackBar(
                                content: Text(
                                    'Foto bukti wajib dipilih untuk menyelesaikan tugas.'),
                                backgroundColor: AppColors.statusDangerText,
                              ),
                            );
                            return;
                          }
                          final success = await _updateTaskStatus(
                            task.idPenugasan,
                            chosenStatus,
                            notesController.text.trim(),
                            photo: proofPhoto,
                            photoBytes: proofPhotoBytes,
                          );

                          if (!mounted || !context.mounted) return;

                          if (success) {
                            Navigator.pop(ctx);
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
                                        'Konfirmasi Penugasan #${task.idPenugasan} terkirim ke Admin!',
                                        style: const TextStyle(
                                            fontWeight: FontWeight.w500),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          } else {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(_lastStatusError ??
                                    'Gagal mengirim konfirmasi.'),
                                backgroundColor: AppColors.statusDangerText,
                              ),
                            );
                          }
                        },
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    ).whenComplete(notesController.dispose);
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.logout_rounded, color: AppColors.statusDangerText),
            SizedBox(width: 10),
            Text(
              'Konfirmasi Keluar',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 17),
            ),
          ],
        ),
        content: const Text(
          'Apakah Anda yakin ingin keluar dari akun Teknisi? Anda perlu login kembali untuk menerima dan memproses penugasan.',
          style: TextStyle(fontSize: 13.5, color: AppColors.textMuted),
        ),
        actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        actions: [
          OutlinedButton(
            onPressed: () => Navigator.pop(ctx, false),
            style: OutlinedButton.styleFrom(
              side: const BorderSide(color: AppColors.border),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Batal',
                style: TextStyle(color: AppColors.textMain)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.statusDangerText,
              foregroundColor: Colors.white,
              elevation: 0,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Ya, Keluar',
                style: TextStyle(fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      AuthService.instance.logout();
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (route) => false,
      );
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Anda telah berhasil keluar dari akun Teknisi.'),
          backgroundColor: AppColors.secondary,
        ),
      );
    }
  }

  void _showProfileSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        final nama = AuthService.instance.nama ?? 'Teknisi Lapangan';
        final email = AuthService.instance.email ?? 'teknisi@safeedu.sch.id';
        return Container(
          padding: const EdgeInsets.all(24),
          decoration: const BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 44,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppColors.border,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              Row(
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: AppColors.primary,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.engineering_rounded,
                        color: Colors.white, size: 28),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          nama,
                          style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w700,
                              color: AppColors.textMain),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          email,
                          style: const TextStyle(
                              fontSize: 12, color: AppColors.textMuted),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.canvas,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: AppColors.border),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Status Operasional',
                        style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: AppColors.textMain)),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: isOnDuty
                            ? AppColors.statusSuccessBg
                            : AppColors.subtle,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        isOnDuty ? 'On Duty' : 'Off Duty',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: isOnDuty
                              ? AppColors.statusSuccessText
                              : AppColors.textMuted,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  icon: const Icon(Icons.logout_rounded, size: 18),
                  label: const Text('Keluar dari Akun Teknisi'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.statusDangerText,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  onPressed: () {
                    Navigator.pop(ctx);
                    _confirmLogout(context);
                  },
                ),
              ),
              const SizedBox(height: 8),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: InkWell(
          borderRadius: BorderRadius.circular(8),
          onTap: () => _showProfileSheet(context),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 4),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  padding: const EdgeInsets.all(7),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [AppColors.primary, AppColors.accentTeal],
                    ),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.engineering_rounded,
                      color: Colors.white, size: 20),
                ),
                const SizedBox(width: 8),
                const Text(
                  'SafeEdu Teknisi',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                ),
              ],
            ),
          ),
        ),
        actions: [
          Tooltip(
            message: isOnDuty
                ? 'Nonaktifkan status bertugas'
                : 'Aktifkan status bertugas',
            child: Switch(
              value: isOnDuty,
              activeThumbColor: AppColors.accentTeal,
              onChanged: (v) => setState(() => isOnDuty = v),
            ),
          ),
          IconButton(
            icon: const Icon(Icons.logout_rounded,
                color: AppColors.statusDangerText),
            tooltip: 'Keluar dari Akun Teknisi',
            onPressed: () => _confirmLogout(context),
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : CustomScrollView(
              slivers: [
                // Header Summary Metrics
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Quick greeting banner
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [
                                AppColors.primaryDark,
                                AppColors.primary,
                              ],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(20),
                            boxShadow: const [
                              BoxShadow(
                                color: Color(0x262563EB),
                                blurRadius: 20,
                                offset: Offset(0, 8),
                              ),
                            ],
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text(
                                      'Selamat datang,',
                                      style: TextStyle(
                                        color: Color(0xFFDCEBFF),
                                        fontSize: 12,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      AuthService.instance.nama ??
                                          'Teknisi SafeEdu',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 18,
                                        fontWeight: FontWeight.w700,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    const SizedBox(height: 10),
                                    Row(
                                      children: [
                                        Container(
                                          width: 7,
                                          height: 7,
                                          decoration: BoxDecoration(
                                            shape: BoxShape.circle,
                                            color: isOnDuty
                                                ? const Color(0xFF4ADE80)
                                                : const Color(0xFF94A3B8),
                                          ),
                                        ),
                                        const SizedBox(width: 6),
                                        Expanded(
                                          child: Text(
                                            isOnDuty
                                                ? 'On Duty · Siap menerima tugas'
                                                : 'Off Duty',
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: const TextStyle(
                                              color: Color(0xFFDCEBFF),
                                              fontSize: 12,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.14),
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                child: const Icon(Icons.engineering_rounded,
                                    color: Colors.white, size: 26),
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(height: 16),

                        // Metrics Cards
                        Row(
                          children: [
                            _buildMetricTile(
                                'Tugas Baru',
                                countNew,
                                AppColors.statusPendingBg,
                                AppColors.statusPendingText),
                            const SizedBox(width: 8),
                            _buildMetricTile(
                                'Diproses',
                                countProcess,
                                AppColors.statusProcessBg,
                                AppColors.statusProcessText),
                            const SizedBox(width: 8),
                            _buildMetricTile(
                                'Selesai',
                                countFinished,
                                AppColors.statusSuccessBg,
                                AppColors.statusSuccessText),
                          ],
                        ),

                        const SizedBox(height: 20),

                        const Text(
                          'Status pengerjaan',
                          style: TextStyle(
                            color: AppColors.textMain,
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 8),
                        SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: Row(
                            children: [
                              _buildFilterChip('Semua'),
                              const SizedBox(width: 8),
                              _buildFilterChip('Tugas Baru'),
                              const SizedBox(width: 8),
                              _buildFilterChip('Dalam Pengerjaan'),
                              const SizedBox(width: 8),
                              _buildFilterChip('Menunggu Admin'),
                              const SizedBox(width: 8),
                              _buildFilterChip('Selesai'),
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                        const Row(
                          children: [
                            Icon(
                              Icons.filter_alt_outlined,
                              color: AppColors.textMuted,
                              size: 17,
                            ),
                            SizedBox(width: 6),
                            Text(
                              'Urgensi',
                              style: TextStyle(
                                color: AppColors.textMain,
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: Row(
                            children: [
                              _buildUrgencyFilterChip(
                                'Semua',
                                'Semua',
                                AppColors.primary,
                                AppColors.primaryLight,
                                Icons.tune_rounded,
                              ),
                              const SizedBox(width: 8),
                              _buildUrgencyFilterChip(
                                'Mendesak',
                                'urgent',
                                AppColors.statusDangerText,
                                AppColors.statusDangerBg,
                                Icons.priority_high_rounded,
                              ),
                              const SizedBox(width: 8),
                              _buildUrgencyFilterChip(
                                'Sedang',
                                'medium',
                                AppColors.statusPendingText,
                                AppColors.statusPendingBg,
                                Icons.remove_rounded,
                              ),
                              const SizedBox(width: 8),
                              _buildUrgencyFilterChip(
                                'Rendah',
                                'low',
                                AppColors.statusSuccessText,
                                AppColors.statusSuccessBg,
                                Icons.south_rounded,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                // Task List
                filteredTasks.isEmpty
                    ? const SliverFillRemaining(
                        hasScrollBody: false,
                        child: Center(
                          child: Padding(
                            padding: EdgeInsets.all(24),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                DecoratedBox(
                                  decoration: BoxDecoration(
                                    color: AppColors.primaryLight,
                                    borderRadius: BorderRadius.all(
                                      Radius.circular(22),
                                    ),
                                  ),
                                  child: Padding(
                                    padding: EdgeInsets.all(18),
                                    child: Icon(
                                      Icons.assignment_outlined,
                                      size: 34,
                                      color: AppColors.primary,
                                    ),
                                  ),
                                ),
                                SizedBox(height: 16),
                                Text(
                                  'Belum ada tugas di sini',
                                  style: TextStyle(
                                    color: AppColors.textMain,
                                    fontSize: 16,
                                    fontWeight: FontWeight.w700,
                                  ),
                                  textAlign: TextAlign.center,
                                ),
                                SizedBox(height: 6),
                                Text(
                                  'Coba pilih filter lain atau periksa kembali nanti.',
                                  style: TextStyle(
                                    color: AppColors.textMuted,
                                    fontSize: 13,
                                  ),
                                  textAlign: TextAlign.center,
                                ),
                              ],
                            ),
                          ),
                        ),
                      )
                    : SliverPadding(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 8),
                        sliver: SliverList(
                          delegate: SliverChildBuilderDelegate(
                            (context, index) {
                              final task = filteredTasks[index];
                              return _buildTaskCard(task);
                            },
                            childCount: filteredTasks.length,
                          ),
                        ),
                      ),
              ],
            ),
    );
  }

  Widget _buildMetricTile(String label, int value, Color bg, Color textColor) {
    final icon = switch (label) {
      'Tugas Baru' => Icons.fiber_new_rounded,
      'Diproses' => Icons.handyman_outlined,
      _ => Icons.task_alt_rounded,
    };

    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 12),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: textColor.withValues(alpha: 0.2)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '$value',
                  style: TextStyle(
                    fontSize: 21,
                    fontWeight: FontWeight.w800,
                    color: textColor,
                  ),
                ),
                Icon(icon, size: 18, color: textColor.withValues(alpha: 0.8)),
              ],
            ),
            const SizedBox(height: 4),
            FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerLeft,
              child: Text(
                label,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: textColor,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(String label) {
    final isSelected = selectedFilter == label;
    return ChoiceChip(
      label: Text(
        label,
        style: TextStyle(
          color: isSelected ? AppColors.primaryDark : AppColors.textMain,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
      selected: isSelected,
      showCheckmark: false,
      backgroundColor: AppColors.surface,
      selectedColor: AppColors.primaryLight,
      side: BorderSide(
        color: isSelected ? AppColors.primary : AppColors.border,
      ),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      onSelected: (selected) {
        if (selected) setState(() => selectedFilter = label);
      },
    );
  }

  Widget _buildUrgencyFilterChip(
    String label,
    String value,
    Color color,
    Color backgroundColor,
    IconData icon,
  ) {
    final isSelected = selectedUrgencyFilter == value;

    return ChoiceChip(
      avatar: Icon(icon, size: 16, color: color),
      label: Text(
        label,
        style: TextStyle(
          color: isSelected ? color : AppColors.textMuted,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
      selected: isSelected,
      showCheckmark: false,
      backgroundColor: AppColors.surface,
      selectedColor: backgroundColor,
      side: BorderSide(
        color: isSelected ? color.withValues(alpha: 0.45) : AppColors.border,
      ),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      onSelected: (selected) {
        if (!selected || isSelected) return;
        setState(() => selectedUrgencyFilter = value);
        _fetchTasks();
      },
    );
  }

  Widget _urgencyChip(String urgency) {
    final (label, color, backgroundColor, icon) = switch (urgency) {
      'urgent' => (
          'Mendesak',
          AppColors.statusDangerText,
          AppColors.statusDangerBg,
          Icons.priority_high_rounded,
        ),
      'low' => (
          'Rendah',
          AppColors.statusSuccessText,
          AppColors.statusSuccessBg,
          Icons.south_rounded,
        ),
      _ => (
          'Sedang',
          AppColors.statusPendingText,
          AppColors.statusPendingBg,
          Icons.remove_rounded,
        ),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.22)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 13, color: color),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.w700,
              fontSize: 11,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTaskCard(PenugasanModel task) {
    final isNew = task.statusPenugasan == 'ditugaskan';
    final isInProgress = task.statusPenugasan == 'diterima';
    final isWaitingApproval = task.statusPenugasan == 'menunggu_konfirmasi';
    final isDone = task.statusPenugasan == 'selesai';
    final isRejected = task.statusPenugasan == 'ditolak';

    Color statusBg = AppColors.statusPendingBg;
    Color statusText = AppColors.statusPendingText;
    IconData statusIcon = Icons.fiber_new_rounded;
    if (isInProgress) {
      statusBg = AppColors.statusProcessBg;
      statusText = AppColors.statusProcessText;
      statusIcon = Icons.handyman_outlined;
    } else if (isWaitingApproval) {
      statusBg = AppColors.statusPendingBg;
      statusText = AppColors.statusPendingText;
      statusIcon = Icons.hourglass_top_rounded;
    } else if (isDone) {
      statusBg = AppColors.statusSuccessBg;
      statusText = AppColors.statusSuccessText;
      statusIcon = Icons.check_circle_outline_rounded;
    } else if (isRejected) {
      statusBg = AppColors.statusDangerBg;
      statusText = AppColors.statusDangerText;
      statusIcon = Icons.block_rounded;
    }

    String displayStatus = 'Baru';
    if (isInProgress) displayStatus = 'Dikerjakan';
    if (isWaitingApproval) displayStatus = 'Menunggu Admin';
    if (isDone) displayStatus = 'Selesai';
    if (isRejected) displayStatus = 'Ditolak';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Row: Ticket ID & Status
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: AppColors.primaryLight,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          'ID: ${task.idPenugasan}',
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        task.tanggalPenugasan,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                            fontSize: 11, color: AppColors.textLight),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 9, vertical: 5),
                      decoration: BoxDecoration(
                        color: statusBg,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: statusText.withValues(alpha: 0.2),
                        ),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(statusIcon, size: 13, color: statusText),
                          const SizedBox(width: 4),
                          Text(
                            displayStatus,
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: statusText,
                            ),
                          ),
                        ],
                      ),
                    ),
                    _urgencyChip(task.urgency),
                  ],
                ),
              ],
            ),

            const SizedBox(height: 12),

            // Title
            Text(
              task.judulLaporan,
              style: const TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w700,
                color: AppColors.textMain,
              ),
            ),

            const SizedBox(height: 8),

            // Location & Reporter
            Row(
              children: [
                const Icon(Icons.location_on_outlined,
                    size: 15, color: AppColors.textMuted),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(
                    task.lokasi,
                    style: const TextStyle(
                        fontSize: 12, color: AppColors.textMuted),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                const Icon(Icons.person_outline,
                    size: 15, color: AppColors.textMuted),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(
                    'Pelapor: ${task.pelapor}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textMuted,
                    ),
                  ),
                ),
              ],
            ),

            // Admin Disposed Notes
            if (task.catatanAdmin != null && task.catatanAdmin!.isNotEmpty) ...[
              const SizedBox(height: 12),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppColors.subtle,
                  borderRadius: BorderRadius.circular(8),
                  border: const Border(
                    left: BorderSide(color: AppColors.primary, width: 3),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Instruksi dari Admin:',
                      style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: AppColors.primaryDark),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      task.catatanAdmin!,
                      style: const TextStyle(
                          fontSize: 12, color: AppColors.textMain),
                    ),
                  ],
                ),
              ),
            ],

            // Technician's own note if already confirmed
            if (task.keteranganTeknisi != null &&
                task.keteranganTeknisi!.isNotEmpty) ...[
              const SizedBox(height: 10),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppColors.accentTealLight,
                  borderRadius: BorderRadius.circular(8),
                  border: const Border(
                    left: BorderSide(color: AppColors.accentTeal, width: 3),
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Catatan Lapangan Anda:',
                          style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: AppColors.accentTeal),
                        ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      task.keteranganTeknisi!,
                      style: const TextStyle(
                          fontSize: 12, color: AppColors.textMain),
                    ),
                  ],
                ),
              ),
            ],

            if (task.rejectionReason != null &&
                task.rejectionReason!.isNotEmpty) ...[
              const SizedBox(height: 10),
              Text(
                '${isRejected ? 'Alasan penolakan' : 'Catatan admin'}: ${task.rejectionReason}',
                style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.statusDangerText,
                ),
              ),
            ],

            const SizedBox(height: 14),

            // Action Button
            SizedBox(
              width: double.infinity,
              child: isNew
                  ? ElevatedButton.icon(
                      icon: const Icon(Icons.check_circle_outline, size: 18),
                      label: const Text('Terima Tugas'),
                      style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary),
                      onPressed: () async {
                        final success = await _updateTaskStatus(
                          task.idPenugasan,
                          'diterima',
                          'Teknisi telah menerima dan mengkonfirmasi tugas.',
                        );
                        if (!mounted) return;
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(success
                                ? 'Tugas dikonfirmasi! Status dikirim ke Admin.'
                                : _lastStatusError ??
                                    'Gagal mengirim konfirmasi tugas.'),
                            backgroundColor: success
                                ? const Color(0xFF10B981)
                                : AppColors.statusDangerText,
                          ),
                        );
                      },
                    )
                  : isInProgress
                      ? ElevatedButton.icon(
                          icon: const Icon(Icons.edit_note_rounded, size: 18),
                          label: const Text('Perbarui Status / Selesaikan'),
                          style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.accentTeal),
                          onPressed: () => _showConfirmationSheet(task),
                        )
                      : isWaitingApproval
                          ? OutlinedButton.icon(
                              icon: const Icon(Icons.hourglass_top_rounded,
                                  size: 18),
                              label: const Text('Menunggu Konfirmasi Admin'),
                              onPressed: null,
                            )
                          : OutlinedButton.icon(
                              icon: Icon(
                                isRejected ? Icons.block : Icons.task_alt,
                                size: 18,
                                color: isRejected
                                    ? AppColors.statusDangerText
                                    : AppColors.statusSuccessText,
                              ),
                              label: Text(isRejected
                                  ? 'Penugasan Ditolak'
                                  : 'Laporan Selesai & Terkirim'),
                              onPressed: null,
                            ),
            ),
          ],
        ),
      ),
    );
  }
}
