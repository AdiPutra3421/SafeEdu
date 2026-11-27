import 'package:flutter/material.dart';
import '../models/report_model.dart';
import '../services/api_config.dart';
import '../services/auth_service.dart';
import '../theme/app_theme.dart';
import '../utils/formatters.dart';
import 'profile_settings_screen.dart';

class ProfileScreen extends StatefulWidget {
  final List<PengaduanModel> userReports;
  final Future<void> Function()? onRefresh;

  const ProfileScreen({super.key, required this.userReports, this.onRefresh});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Future<void> _openProfileSettings() async {
    final updated = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => const ProfileSettingsScreen(),
      ),
    );

    if (updated == true || mounted) {
      await AuthService.instance.reloadProfile();
      if (mounted) {
        setState(() {});
      }
      if (widget.onRefresh != null) {
        await widget.onRefresh!();
      }
    }
  }

  void _showReportTrackingDetail(BuildContext context, PengaduanModel report) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
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
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'ID: ${report.id}',
                    style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                        color: AppColors.primary),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: AppColors.textMuted),
                    onPressed: () => Navigator.pop(ctx),
                  ),
                ],
              ),
              Text(
                report.judul,
                style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textMain),
              ),
              const SizedBox(height: 6),
              Text(
                  '📍 ${report.lokasiKejadian} • ${formatTimestamp(report.createdAt)}',
                  style: const TextStyle(
                      fontSize: 12, color: AppColors.textMuted)),
              const SizedBox(height: 20),
              const Text('Tahapan Pelacakan Status',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
              const SizedBox(height: 12),
              if (report.status == 'ditolak') ...[
                _buildTimelineStep(
                  title: 'Laporan Masuk',
                  subtitle:
                      'Laporan Anda telah berhasil dicatat ke sistem SafeEdu',
                  timestamp: report.createdAt,
                  isDone: true,
                  isCurrent: false,
                ),
                _buildTimelineStep(
                  title: 'Laporan Ditolak',
                  subtitle: (report.alasanPenolakan != null &&
                          report.alasanPenolakan!.isNotEmpty)
                      ? 'Alasan: ${report.alasanPenolakan}'
                      : (report.ulasanPelapor != null &&
                              report.ulasanPelapor!.isNotEmpty)
                          ? 'Alasan: ${report.ulasanPelapor}'
                          : 'Laporan tidak dapat diproses lebih lanjut oleh admin.',
                  timestamp: report.completedAt ??
                      report.assignedAt ??
                      report.verifiedAt ??
                      report.createdAt,
                  isDone: true,
                  isCurrent: true,
                  isLast: true,
                ),
                const SizedBox(height: 16),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEE2E2),
                    borderRadius: BorderRadius.circular(10),
                    border: const Border(
                        left: BorderSide(color: Color(0xFFDC2626), width: 3)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Catatan Penolakan:',
                          style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFFDC2626))),
                      const SizedBox(height: 2),
                      Text(
                        (report.alasanPenolakan != null &&
                                report.alasanPenolakan!.isNotEmpty)
                            ? report.alasanPenolakan!
                            : (report.ulasanPelapor != null &&
                                    report.ulasanPelapor!.isNotEmpty)
                                ? report.ulasanPelapor!
                                : 'Laporan tidak memenuhi kriteria verifikasi.',
                        style: const TextStyle(
                            fontSize: 12, color: AppColors.textMain),
                      ),
                    ],
                  ),
                ),
              ] else ...[
                _buildTimelineStep(
                  title: 'Laporan Masuk',
                  subtitle:
                      'Laporan Anda telah berhasil dicatat ke sistem SafeEdu',
                  timestamp: report.createdAt,
                  isDone: true,
                  isCurrent: report.status == 'menunggu',
                ),
                _buildTimelineStep(
                  title: 'Verifikasi Tim Admin (Disposisi)',
                  subtitle:
                      'Admin telah memverifikasi dan menugaskan teknisi untuk insiden ini',
                  timestamp: report.assignedAt,
                  isDone:
                      report.status == 'diproses' || report.status == 'selesai',
                  isCurrent: report.status == 'diproses' &&
                      report.penugasanStatus == 'ditugaskan',
                ),
                _buildTimelineStep(
                  title: 'Penanganan oleh Teknisi / Satgas',
                  subtitle: report.penugasanStatus == 'menunggu_konfirmasi'
                      ? 'Teknisi telah mengirim bukti penyelesaian pekerjaan'
                      : 'Petugas lapangan menerima tugas dan sedang melakukan perbaikan',
                  timestamp: report.workedAt,
                  isDone: report.status == 'selesai' ||
                      report.penugasanStatus == 'menunggu_konfirmasi',
                  isCurrent: report.status == 'diproses' &&
                      report.penugasanStatus == 'diterima',
                ),
                _buildTimelineStep(
                  title: 'Menunggu Konfirmasi Admin',
                  subtitle:
                      'Bukti penyelesaian sedang diperiksa oleh admin sebelum laporan ditutup',
                  isDone: report.status == 'selesai',
                  isCurrent: report.status == 'diproses' &&
                      report.penugasanStatus == 'menunggu_konfirmasi',
                ),
                _buildTimelineStep(
                  title: 'Selesai & Dikonfirmasi',
                  subtitle:
                      'Tiket ditutup setelah admin mengonfirmasi bukti pekerjaan',
                  timestamp: report.completedAt,
                  isDone: report.status == 'selesai',
                  isCurrent: report.status == 'selesai',
                  isLast: true,
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  String? _reportImageUrl(String? fileUrl) {
    if (fileUrl == null || fileUrl.isEmpty) return null;

    final baseUri = Uri.parse(ApiConfig.baseUrl);
    final attachmentUri = Uri.tryParse(fileUrl);
    if (attachmentUri == null) return null;

    final segments = List<String>.from(attachmentUri.pathSegments);
    if (segments.isNotEmpty && segments.first == 'storage') {
      segments.removeAt(0);
    }
    if (segments.length < 2 ||
        segments.first != 'bukti' ||
        segments.any((segment) =>
            segment.isEmpty ||
            segment == '.' ||
            segment == '..' ||
            segment.contains('/') ||
            segment.contains(r'\'))) {
      return null;
    }

    final extension = segments.last.split('.').last.toLowerCase();
    if (!const {'jpg', 'jpeg', 'png'}.contains(extension)) return null;

    final encodedPath = segments.map(Uri.encodeComponent).join('/');
    return baseUri.resolve('/api/gambar/$encodedPath').toString();
  }

  void _showReportImage(String imageUrl) {
    showDialog<void>(
      context: context,
      builder: (dialogContext) => Dialog(
        child: SizedBox(
          width: MediaQuery.of(dialogContext).size.width * 0.9,
          height: MediaQuery.of(dialogContext).size.height * 0.75,
          child: Stack(
            children: [
              Positioned.fill(
                child: InteractiveViewer(
                  child: Image.network(
                    imageUrl,
                    fit: BoxFit.contain,
                    errorBuilder: (context, error, stackTrace) {
                      debugPrint('Error loading report image: $error');
                      return const Center(
                        child: Text('Foto bukti tidak dapat dimuat.'),
                      );
                    },
                  ),
                ),
              ),
              Positioned(
                top: 4,
                right: 4,
                child: IconButton(
                  tooltip: 'Tutup foto',
                  onPressed: () => Navigator.pop(dialogContext),
                  icon: const Icon(Icons.close),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTimelineStep({
    required String title,
    required String subtitle,
    String? timestamp,
    required bool isDone,
    required bool isCurrent,
    bool isLast = false,
  }) {
    Color iconColor = isDone
        ? const Color(0xFF10B981)
        : (isCurrent ? AppColors.primary : AppColors.textLight);
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 22,
              height: 22,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: isDone
                    ? const Color(0xFFDCFCE7)
                    : (isCurrent ? AppColors.primaryLight : AppColors.subtle),
                border: Border.all(color: iconColor, width: 1.5),
              ),
              child: Icon(
                isDone
                    ? Icons.check
                    : (isCurrent ? Icons.circle : Icons.circle_outlined),
                size: 12,
                color: iconColor,
              ),
            ),
            if (!isLast)
              Container(
                width: 2,
                height: 36,
                color: isDone ? const Color(0xFF10B981) : AppColors.border,
              ),
          ],
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: isDone || isCurrent
                      ? AppColors.textMain
                      : AppColors.textMuted,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style:
                    const TextStyle(fontSize: 11, color: AppColors.textMuted),
              ),
              if (timestamp != null && timestamp.isNotEmpty) ...[
                const SizedBox(height: 3),
                Row(
                  children: [
                    Icon(
                      Icons.access_time_rounded,
                      size: 12,
                      color: isDone
                          ? const Color(0xFF047857)
                          : (isCurrent
                              ? AppColors.primary
                              : AppColors.textLight),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      formatTimestamp(timestamp),
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: isDone
                            ? const Color(0xFF047857)
                            : (isCurrent
                                ? AppColors.primary
                                : AppColors.textLight),
                      ),
                    ),
                  ],
                ),
              ],
              const SizedBox(height: 12),
            ],
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: const Text('Profil & Riwayat'),
        actions: [
          IconButton(
            icon:
                const Icon(Icons.settings_outlined, color: AppColors.textMain),
            tooltip: 'Pengaturan Profil',
            onPressed: _openProfileSettings,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await AuthService.instance.reloadProfile();
          if (widget.onRefresh != null) {
            await widget.onRefresh!();
          }
          if (mounted) {
            setState(() {});
          }
        },
        color: AppColors.primary,
        child: _buildContent(context),
      ),
    );
  }

  Widget _buildContent(BuildContext context) {
    final userName = AuthService.instance.nama?.isNotEmpty == true
        ? AuthService.instance.nama!
        : 'User SafeEdu';
    final email = AuthService.instance.email ?? '-';

    final nameParts = userName.trim().split(RegExp(r'\s+'));
    final initials = nameParts.length > 1
        ? '${nameParts[0][0]}${nameParts[1][0]}'.toUpperCase()
        : (nameParts.isNotEmpty && nameParts[0].isNotEmpty
            ? nameParts[0][0].toUpperCase()
            : 'US');

    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // User Profile Card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: AppColors.border),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.04),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              children: [
                Row(
                  children: [
                    // Avatar
                    Container(
                      width: 64,
                      height: 64,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: AppColors.primaryLight,
                        border: Border.all(color: AppColors.primary, width: 2),
                      ),
                      child: Center(
                        child: Text(
                          initials,
                          style: const TextStyle(
                            fontSize: 22,
                            fontWeight: FontWeight.w800,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  userName,
                                  style: const TextStyle(
                                      fontSize: 17,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.textMain),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: AppColors.statusSuccessBg,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: const Text(
                                  'User Aktif',
                                  style: TextStyle(
                                      fontSize: 10,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.statusSuccessText),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(email,
                              style: const TextStyle(
                                  fontSize: 12, color: AppColors.textMuted)),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),

          // Section: Riwayat Laporan Saya
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Riwayat Laporan Saya',
                style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textMain),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '${widget.userReports.length} Laporan',
                  style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary),
                ),
              ),
            ],
          ),

          const SizedBox(height: 12),

          widget.userReports.isEmpty
              ? Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(28),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: const Column(
                    children: [
                      Icon(Icons.inbox_outlined,
                          size: 36, color: AppColors.primary),
                      SizedBox(height: 12),
                      Text(
                        'Belum ada laporan yang dikirimkan',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textMain,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: 4),
                      Text(
                        'Laporan yang Anda buat akan muncul di sini.',
                        style: TextStyle(
                          fontSize: 12,
                          color: AppColors.textMuted,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                )
              : ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: widget.userReports.length,
                  itemBuilder: (context, index) {
                    final rep = widget.userReports[index];
                    final reportImageUrl = _reportImageUrl(rep.fileBuktiUrl);

                    Color statusBg = AppColors.statusPendingBg;
                    Color statusText = AppColors.statusPendingText;
                    if (rep.status == 'diproses') {
                      statusBg = AppColors.statusProcessBg;
                      statusText = AppColors.statusProcessText;
                    } else if (rep.status == 'selesai') {
                      statusBg = AppColors.statusSuccessBg;
                      statusText = AppColors.statusSuccessText;
                    } else if (rep.status == 'ditolak') {
                      statusBg = AppColors.statusDangerBg;
                      statusText = AppColors.statusDangerText;
                    }

                    return Card(
                      child: ListTile(
                        leading: reportImageUrl == null
                            ? null
                            : Tooltip(
                                message: 'Lihat foto bukti',
                                child: InkWell(
                                  onTap: () => _showReportImage(reportImageUrl),
                                  borderRadius: BorderRadius.circular(8),
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: Image.network(
                                      reportImageUrl,
                                      width: 56,
                                      height: 56,
                                      fit: BoxFit.cover,
                                      errorBuilder:
                                          (context, error, stackTrace) {
                                        debugPrint(
                                            'Error loading report thumbnail: $error');
                                        return Container(
                                          width: 56,
                                          height: 56,
                                          color: AppColors.subtle,
                                          alignment: Alignment.center,
                                          child: const Icon(
                                            Icons.broken_image_outlined,
                                            color: AppColors.textMuted,
                                          ),
                                        );
                                      },
                                    ),
                                  ),
                                ),
                              ),
                        contentPadding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 8),
                        title: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: rep.kategoriJenis == 'fasilitas'
                                    ? AppColors.facilityBg
                                    : AppColors.ethicsBg,
                              ),
                              child: Text(
                                rep.kategoriJenis == 'fasilitas'
                                    ? 'Fasilitas'
                                    : 'Kode Etik',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  color: rep.kategoriJenis == 'fasilitas'
                                      ? AppColors.facilityText
                                      : AppColors.ethicsText,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                rep.judul,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 13.5),
                              ),
                            ),
                          ],
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(height: 4),
                            Text('📍 ${rep.lokasiKejadian}',
                                style: const TextStyle(
                                    fontSize: 12, color: AppColors.textMuted)),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                Text(formatTimestamp(rep.createdAt),
                                    style: const TextStyle(
                                        fontSize: 11,
                                        color: AppColors.textLight)),
                                Spacer(),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(
                                    color: statusBg,
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Text(
                                    rep.penugasanStatus == 'menunggu_konfirmasi'
                                        ? 'MENUNGGU ADMIN'
                                        : rep.status.toUpperCase(),
                                    style: TextStyle(
                                        fontSize: 10,
                                        fontWeight: FontWeight.w600,
                                        color: statusText),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                        onTap: () => _showReportTrackingDetail(context, rep),
                      ),
                    );
                  },
                ),

          const SizedBox(height: 24),

          const SizedBox(height: 32),
        ],
      ),
    );
  }

  Widget _buildHelpItem({
    required IconData icon,
    required String title,
    required String subtitle,
    required String phone,
  }) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: AppColors.primaryLight,
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, color: AppColors.primary, size: 20),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title,
                  style: const TextStyle(
                      fontWeight: FontWeight.w700, fontSize: 13)),
              const SizedBox(height: 2),
              Text(subtitle,
                  style: const TextStyle(
                      fontSize: 11, color: AppColors.textMuted)),
              const SizedBox(height: 2),
              Text(phone,
                  style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary)),
            ],
          ),
        ),
      ],
    );
  }
}
