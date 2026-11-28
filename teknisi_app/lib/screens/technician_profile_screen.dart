import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../models/task_model.dart';
import '../services/api_config.dart';
import '../services/auth_service.dart';
import '../theme/app_theme.dart';
import 'auth/login_screen.dart';

class TechnicianProfileScreen extends StatefulWidget {
  const TechnicianProfileScreen({super.key});

  @override
  State<TechnicianProfileScreen> createState() =>
      _TechnicianProfileScreenState();
}

class _TechnicianProfileScreenState extends State<TechnicianProfileScreen> {
  bool _isLoadingStats = false;
  int _totalTasks = 0;
  int _completedTasks = 0;
  int _activeTasks = 0;

  bool get _isOnDuty => AuthService.instance.isOnDuty;

  @override
  void initState() {
    super.initState();
    _loadAll();
  }

  Future<void> _loadAll() async {
    await AuthService.instance.reloadProfile();
    await _fetchTaskStats();
    if (mounted) setState(() {});
  }

  Future<void> _fetchTaskStats() async {
    final userId = AuthService.instance.userId;
    if (userId == null) return;

    if (mounted) setState(() => _isLoadingStats = true);

    try {
      final uri = Uri.parse('${ApiConfig.baseUrl}/api/penugasan')
          .replace(queryParameters: {'teknisi_id': userId.toString()});
      final res = await http.get(uri);

      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (data is Map && data['success'] == true && data['data'] is List) {
          final list = data['data'] as List;
          final tasks = list
              .map((item) =>
                  PenugasanModel.fromJson(item as Map<String, dynamic>))
              .toList();

          int completed = 0;
          int active = 0;

          for (final t in tasks) {
            if (t.statusPenugasan == 'selesai') {
              completed++;
            } else if (t.statusPenugasan == 'ditugaskan' ||
                t.statusPenugasan == 'diterima' ||
                t.statusPenugasan == 'menunggu_konfirmasi') {
              active++;
            }
          }

          if (mounted) {
            setState(() {
              _totalTasks = tasks.length;
              _completedTasks = completed;
              _activeTasks = active;
            });
          }
        }
      }
    } catch (e) {
      debugPrint('Error fetching technician stats: $e');
    } finally {
      if (mounted) setState(() => _isLoadingStats = false);
    }
  }

  String _getRoleLabel() {
    final bidang = AuthService.instance.bidang?.toLowerCase() ?? '';
    final role = AuthService.instance.role?.toLowerCase() ?? '';

    if (bidang == 'guru_bk' || role == 'guru_bk') {
      return 'Guru Bimbingan Konseling (BK)';
    }
    return 'Teknisi Sarana & Prasarana';
  }

  Color _getRoleBadgeColor() {
    final bidang = AuthService.instance.bidang?.toLowerCase() ?? '';
    final role = AuthService.instance.role?.toLowerCase() ?? '';

    if (bidang == 'guru_bk' || role == 'guru_bk') {
      return const Color(0xFF7C3AED); // Purple for BK
    }
    return AppColors.primary; // Blue for Sarpras
  }

  Future<void> _editNameDialog() async {
    final currentName = AuthService.instance.nama ?? '';
    final nameController = TextEditingController(text: currentName);
    final formKey = GlobalKey<FormState>();
    bool isSaving = false;

    await showDialog<void>(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (dialogCtx, setDialogState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20)),
              title: const Row(
                children: [
                  Icon(Icons.edit_rounded, color: AppColors.primary, size: 22),
                  SizedBox(width: 10),
                  Text(
                    'Ubah Nama Lengkap',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textMain,
                    ),
                  ),
                ],
              ),
              content: Form(
                key: formKey,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text(
                      'Masukkan nama lengkap petugas yang baru.',
                      style:
                          TextStyle(fontSize: 13, color: AppColors.textMuted),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: nameController,
                      decoration: InputDecoration(
                        labelText: 'Nama Lengkap',
                        prefixIcon: const Icon(Icons.person_outline),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      validator: (val) {
                        if (val == null || val.trim().isEmpty) {
                          return 'Nama tidak boleh kosong.';
                        }
                        if (val.trim().length < 3) {
                          return 'Nama minimal 3 karakter.';
                        }
                        return null;
                      },
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: isSaving ? null : () => Navigator.pop(ctx),
                  child: const Text('Batal',
                      style: TextStyle(color: AppColors.textMuted)),
                ),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: isSaving
                      ? null
                      : () async {
                          if (!formKey.currentState!.validate()) return;
                          setDialogState(() => isSaving = true);

                          final error =
                              await AuthService.instance.updateProfile(
                            nama: nameController.text.trim(),
                          );

                          if (!dialogCtx.mounted) return;
                          setDialogState(() => isSaving = false);

                          if (error != null) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(error),
                                backgroundColor: AppColors.statusDangerText,
                              ),
                            );
                          } else {
                            Navigator.pop(ctx);
                            if (mounted) {
                              setState(() {});
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Nama berhasil diperbarui!'),
                                  backgroundColor: AppColors.statusSuccessText,
                                ),
                              );
                            }
                          }
                        },
                  child: isSaving
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Text('Simpan'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  Future<void> _changePasswordDialog() async {
    final currentPasswordController = TextEditingController();
    final newPasswordController = TextEditingController();
    final confirmPasswordController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    bool obscureOld = true;
    bool obscureNew = true;
    bool obscureConfirm = true;
    bool isSaving = false;

    await showDialog<void>(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (dialogCtx, setDialogState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20)),
              title: const Row(
                children: [
                  Icon(Icons.lock_reset_rounded,
                      color: AppColors.primary, size: 22),
                  SizedBox(width: 10),
                  Text(
                    'Ganti Kata Sandi',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textMain,
                    ),
                  ),
                ],
              ),
              content: SingleChildScrollView(
                child: Form(
                  key: formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      TextFormField(
                        controller: currentPasswordController,
                        obscureText: obscureOld,
                        decoration: InputDecoration(
                          labelText: 'Kata Sandi Saat Ini',
                          prefixIcon: const Icon(Icons.lock_outline),
                          suffixIcon: IconButton(
                            icon: Icon(obscureOld
                                ? Icons.visibility_off
                                : Icons.visibility),
                            onPressed: () => setDialogState(
                                () => obscureOld = !obscureOld),
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        validator: (val) {
                          if (val == null || val.isEmpty) {
                            return 'Kata sandi saat ini wajib diisi.';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: newPasswordController,
                        obscureText: obscureNew,
                        decoration: InputDecoration(
                          labelText: 'Kata Sandi Baru',
                          prefixIcon: const Icon(Icons.key_rounded),
                          suffixIcon: IconButton(
                            icon: Icon(obscureNew
                                ? Icons.visibility_off
                                : Icons.visibility),
                            onPressed: () => setDialogState(
                                () => obscureNew = !obscureNew),
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        validator: (val) {
                          if (val == null || val.length < 6) {
                            return 'Kata sandi minimal 6 karakter.';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: confirmPasswordController,
                        obscureText: obscureConfirm,
                        decoration: InputDecoration(
                          labelText: 'Konfirmasi Kata Sandi Baru',
                          prefixIcon: const Icon(Icons.check_circle_outline),
                          suffixIcon: IconButton(
                            icon: Icon(obscureConfirm
                                ? Icons.visibility_off
                                : Icons.visibility),
                            onPressed: () => setDialogState(
                                () => obscureConfirm = !obscureConfirm),
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        validator: (val) {
                          if (val != newPasswordController.text) {
                            return 'Konfirmasi kata sandi tidak cocok.';
                          }
                          return null;
                        },
                      ),
                    ],
                  ),
                ),
              ),
              actions: [
                TextButton(
                  onPressed: isSaving ? null : () => Navigator.pop(ctx),
                  child: const Text('Batal',
                      style: TextStyle(color: AppColors.textMuted)),
                ),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: isSaving
                      ? null
                      : () async {
                          if (!formKey.currentState!.validate()) return;
                          setDialogState(() => isSaving = true);

                          final currentName =
                              AuthService.instance.nama ?? 'Teknisi';
                          final error =
                              await AuthService.instance.updateProfile(
                            nama: currentName,
                            currentPassword:
                                currentPasswordController.text.trim(),
                            newPassword: newPasswordController.text.trim(),
                          );

                          if (!dialogCtx.mounted) return;
                          setDialogState(() => isSaving = false);

                          if (error != null) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(error),
                                backgroundColor: AppColors.statusDangerText,
                              ),
                            );
                          } else {
                            Navigator.pop(ctx);
                            if (mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content:
                                      Text('Kata sandi berhasil diperbarui!'),
                                  backgroundColor: AppColors.statusSuccessText,
                                ),
                              );
                            }
                          }
                        },
                  child: isSaving
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Text('Simpan'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _showSopDialog() {
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Row(
          children: [
            Icon(Icons.rule_rounded, color: AppColors.primary, size: 24),
            SizedBox(width: 10),
            Text(
              'SOP Penanganan Petugas',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: AppColors.textMain,
              ),
            ),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildSopStep(
                number: '1',
                title: 'Penerimaan Tugas',
                desc: 'Tinjau penugasan masuk dari Admin dan terima tugas untuk memulai penanganan.',
              ),
              const SizedBox(height: 10),
              _buildSopStep(
                number: '2',
                title: 'Pemeriksaan Lapangan',
                desc: 'Kunjungi lokasi insiden/fasilitas dan lakukan perbaikan atau konseling yang diperlukan.',
              ),
              const SizedBox(height: 10),
              _buildSopStep(
                number: '3',
                title: 'Unggah Bukti Selesai',
                desc: 'Ambil foto bukti nyata penyelesaian dan tuliskan catatan teknisi dengan jelas.',
              ),
              const SizedBox(height: 10),
              _buildSopStep(
                number: '4',
                title: 'Konfirmasi Admin',
                desc: 'Tugas yang selesai akan diverifikasi akhir oleh Admin sekolah sebelum ditutup.',
              ),
            ],
          ),
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Mengerti'),
          ),
        ],
      ),
    );
  }

  Widget _buildSopStep({
    required String number,
    required String title,
    required String desc,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 24,
          height: 24,
          decoration: BoxDecoration(
            color: AppColors.primaryLight,
            borderRadius: BorderRadius.circular(6),
          ),
          alignment: Alignment.center,
          child: Text(
            number,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: AppColors.primary,
            ),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textMain,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                desc,
                style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.textMuted,
                  height: 1.3,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Future<void> _confirmLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.logout_rounded, color: AppColors.statusDangerText),
            SizedBox(width: 10),
            Text(
              'Keluar Akun',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: AppColors.textMain,
              ),
            ),
          ],
        ),
        content: const Text(
          'Apakah Anda yakin ingin keluar dari akun petugas SafeEdu? Anda perlu login kembali untuk mengakses data penugasan.',
          style: TextStyle(fontSize: 13, color: AppColors.textMuted),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal',
                style: TextStyle(color: AppColors.textMuted)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.statusDangerText,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Ya, Keluar'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      AuthService.instance.logout();
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (context) => const LoginScreen()),
        (route) => false,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final nama = AuthService.instance.nama ?? 'Petugas SafeEdu';
    final email = AuthService.instance.email ?? 'teknisi@safeedu.sch.id';
    final roleLabel = _getRoleLabel();
    final roleColor = _getRoleBadgeColor();

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: const Text('Profil Petugas'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'Perbarui Data',
            onPressed: _loadAll,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadAll,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // 1. Profile Header Card
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: AppColors.border),
                  boxShadow: const [
                    BoxShadow(
                      color: Color(0x060F172A),
                      blurRadius: 16,
                      offset: Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  children: [
                    Row(
                      children: [
                        // Avatar Circle
                        Container(
                          width: 60,
                          height: 60,
                          decoration: BoxDecoration(
                            color: roleColor,
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: roleColor.withOpacity(0.3),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          alignment: Alignment.center,
                          child: const Icon(
                            Icons.engineering_rounded,
                            color: Colors.white,
                            size: 32,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                nama,
                                style: const TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.textMain,
                                ),
                              ),
                              const SizedBox(height: 3),
                              Text(
                                email,
                                style: const TextStyle(
                                  fontSize: 13,
                                  color: AppColors.textMuted,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: roleColor.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(6),
                                  border: Border.all(
                                      color: roleColor.withOpacity(0.2)),
                                ),
                                child: Text(
                                  roleLabel,
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                    color: roleColor,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),
                    const Divider(height: 1, color: AppColors.border),
                    const SizedBox(height: 14),

                    // Operational Status (Dipindah ke Profil)
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        color: _isOnDuty
                            ? AppColors.statusSuccessBg.withOpacity(0.5)
                            : AppColors.subtle,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: _isOnDuty
                              ? AppColors.statusSuccessText.withOpacity(0.2)
                              : AppColors.border,
                        ),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Container(
                                width: 10,
                                height: 10,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: _isOnDuty
                                      ? AppColors.statusSuccessText
                                      : AppColors.textLight,
                                ),
                              ),
                              const SizedBox(width: 10),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Status Kesiagaan',
                                    style: TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.textMain,
                                    ),
                                  ),
                                  Text(
                                    _isOnDuty
                                        ? 'On Duty · Siap Menerima Tugas'
                                        : 'Off Duty · Sedang Istirahat',
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: _isOnDuty
                                          ? AppColors.statusSuccessText
                                          : AppColors.textMuted,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                          Switch(
                            value: _isOnDuty,
                            activeThumbColor: Colors.white,
                            activeTrackColor: AppColors.statusSuccessText,
                            inactiveThumbColor: Colors.white,
                            inactiveTrackColor: AppColors.textLight,
                            onChanged: (val) {
                              setState(() {
                                AuthService.instance.isOnDuty = val;
                              });
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text(
                                    val
                                        ? 'Status operasional diatur ke: Siap Bertugas (On Duty)'
                                        : 'Status operasional diatur ke: Istirahat (Off Duty)',
                                  ),
                                  duration: const Duration(seconds: 2),
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // 2. Statistics Section
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 4),
                child: Text(
                  'Statistik Penugasan',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textMain,
                  ),
                ),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: _buildStatCard(
                      label: 'Total Tugas',
                      value: _isLoadingStats ? '-' : '$_totalTasks',
                      icon: Icons.assignment_outlined,
                      iconColor: AppColors.primary,
                      bgColor: AppColors.primaryLight,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _buildStatCard(
                      label: 'Tugas Selesai',
                      value: _isLoadingStats ? '-' : '$_completedTasks',
                      icon: Icons.check_circle_outline_rounded,
                      iconColor: AppColors.statusSuccessText,
                      bgColor: AppColors.statusSuccessBg,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _buildStatCard(
                      label: 'Sedang Aktif',
                      value: _isLoadingStats ? '-' : '$_activeTasks',
                      icon: Icons.pending_actions_rounded,
                      iconColor: AppColors.statusPendingText,
                      bgColor: AppColors.statusPendingBg,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 24),

              // 3. Account Settings Section (Ganti Sandi & Ubah Nama)
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 4),
                child: Text(
                  'Pengaturan Akun & Keamanan',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textMain,
                  ),
                ),
              ),
              const SizedBox(height: 10),
              Container(
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  children: [
                    _buildSettingsTile(
                      icon: Icons.lock_reset_rounded,
                      title: 'Ganti Kata Sandi',
                      subtitle: 'Ubah kata sandi login untuk keamanan akun',
                      trailingWidget: Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.primaryLight,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Text(
                          'Ubah',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      onTap: _changePasswordDialog,
                    ),
                    const Divider(height: 1, indent: 56, color: AppColors.border),
                    _buildSettingsTile(
                      icon: Icons.badge_outlined,
                      title: 'Ubah Nama Lengkap',
                      subtitle: 'Perbarui nama tampilan akun petugas',
                      onTap: _editNameDialog,
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 24),

              // 4. Panduan SOP Section (Tentang Aplikasi Dihapus)
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 4),
                child: Text(
                  'Panduan Petugas',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textMain,
                  ),
                ),
              ),
              const SizedBox(height: 10),
              Container(
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border),
                ),
                child: _buildSettingsTile(
                  icon: Icons.rule_rounded,
                  title: 'SOP Penanganan Petugas',
                  subtitle: 'Panduan tata cara verifikasi dan tindak lanjut tugas',
                  onTap: _showSopDialog,
                ),
              ),

              const SizedBox(height: 28),

              // 5. Logout Button (Dipindah ke Profil)
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  icon: const Icon(Icons.logout_rounded,
                      color: AppColors.statusDangerText, size: 20),
                  label: const Text(
                    'Keluar dari Akun Petugas',
                    style: TextStyle(
                      color: AppColors.statusDangerText,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(
                        color: Color(0xFFFCA5A5), width: 1.2),
                    backgroundColor: const Color(0xFFFEF2F2),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  onPressed: _confirmLogout,
                ),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatCard({
    required String label,
    required String value,
    required IconData icon,
    required Color iconColor,
    required Color bgColor,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: iconColor, size: 20),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: AppColors.textMain,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              color: AppColors.textMuted,
              fontWeight: FontWeight.w500,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildSettingsTile({
    required IconData icon,
    required String title,
    required String subtitle,
    Widget? trailingWidget,
    required VoidCallback onTap,
  }) {
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: AppColors.subtle,
          borderRadius: BorderRadius.circular(10),
        ),
        child: Icon(icon, color: AppColors.textMain, size: 20),
      ),
      title: Text(
        title,
        style: const TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: AppColors.textMain,
        ),
      ),
      subtitle: Text(
        subtitle,
        style: const TextStyle(
          fontSize: 12,
          color: AppColors.textMuted,
        ),
      ),
      trailing: trailingWidget ??
          const Icon(
            Icons.chevron_right_rounded,
            color: AppColors.textLight,
            size: 20,
          ),
      onTap: onTap,
    );
  }
}
