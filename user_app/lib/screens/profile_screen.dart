import 'package:flutter/material.dart';
import '../models/report_model.dart';
import '../theme/app_theme.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class ProfileScreen extends StatelessWidget {
  final List<PengaduanModel> userReports;

  final Future<void> Function()? onRefresh;

  const ProfileScreen({super.key, required this.userReports, this.onRefresh});

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
              Text('📍 ${report.lokasiKejadian} • ${report.createdAt}',
                  style: const TextStyle(
                      fontSize: 12, color: AppColors.textMuted)),
              const SizedBox(height: 20),
              const Text('Tahapan Pelacakan Status',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
              const SizedBox(height: 12),
              _buildTimelineStep(
                title: 'Laporan Masuk',
                subtitle:
                    'Laporan Anda telah berhasil dicatat ke sistem SafeEdu',
                isDone: true,
                isCurrent: report.status == 'menunggu',
              ),
              _buildTimelineStep(
                title: 'Verifikasi Tim Admin (Disposisi)',
                subtitle:
                    'Admin telah memverifikasi dan menugaskan teknisi untuk insiden ini',
                isDone:
                    report.status == 'diproses' || report.status == 'selesai',
                isCurrent: report.status == 'diproses' &&
                    report.penugasanStatus == 'ditugaskan',
              ),
              _buildTimelineStep(
                title: 'Penanganan oleh Teknisi / Satgas',
                subtitle:
                    'Petugas lapangan menerima tugas dan sedang melakukan perbaikan',
                isDone: report.status == 'selesai',
                isCurrent: report.status == 'diproses' &&
                    report.penugasanStatus == 'diterima',
              ),
              _buildTimelineStep(
                title: 'Selesai & Dikonfirmasi',
                subtitle:
                    'Tiket ditutup setelah teknisi menyelesaikan penanganan',
                isDone: report.status == 'selesai',
                isCurrent: report.status == 'selesai',
                isLast: true,
              ),
              const SizedBox(height: 16),
              if (report.ulasanPelapor != null &&
                  report.ulasanPelapor!.isNotEmpty) ...[
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppColors.subtle,
                    borderRadius: BorderRadius.circular(10),
                    border: const Border(
                        left: BorderSide(color: AppColors.primary, width: 3)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Catatan Penolakan:',
                          style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: AppColors.primary)),
                      const SizedBox(height: 2),
                      Text(report.ulasanPelapor!,
                          style: const TextStyle(
                              fontSize: 12, color: AppColors.textMain)),
                    ],
                  ),
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  Widget _buildTimelineStep({
    required String title,
    required String subtitle,
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
        title: const Text('Profil Siswa & Riwayat'),
        actions: [
          IconButton(
            icon:
                const Icon(Icons.settings_outlined, color: AppColors.textMain),
            onPressed: () {},
          ),
        ],
      ),
      body: onRefresh == null
          ? _buildContent()
          : RefreshIndicator(
              onRefresh: onRefresh!,
              color: AppColors.primary,
              child: _buildContent(),
            ),
    );
  }

  Widget _buildContent() {
    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Student ID Card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(16),
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
                      child: const Center(
                        child: Text(
                          'AF',
                          style: TextStyle(
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
                              const Text(
                                'Ahmad Fauzi',
                                style: TextStyle(
                                    fontSize: 17,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.textMain),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: AppColors.statusSuccessBg,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: const Text(
                                  'Siswa Aktif',
                                  style: TextStyle(
                                      fontSize: 10,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.statusSuccessText),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          const Text('NISN: 20240911',
                              style: TextStyle(
                                  fontSize: 12, color: AppColors.textMuted)),
                          const SizedBox(height: 2),
                          const Text('XII Rekayasa Perangkat Lunak 1',
                              style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w500,
                                  color: AppColors.secondaryMuted)),
                          const SizedBox(height: 2),
                          const Text('ahmad.fauzi@safeedu.sch.id',
                              style: TextStyle(
                                  fontSize: 11, color: AppColors.textLight)),
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
                  '${userReports.length} Laporan',
                  style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary),
                ),
              ),
            ],
          ),

          const SizedBox(height: 12),

          userReports.isEmpty
              ? Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(32),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: const Column(
                    children: [
                      Icon(Icons.inbox_outlined,
                          size: 40, color: AppColors.textLight),
                      SizedBox(height: 8),
                      Text('Belum ada laporan yang dikirimkan',
                          style: TextStyle(
                              fontSize: 13, color: AppColors.textMuted)),
                    ],
                  ),
                )
              : ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: userReports.length,
                  itemBuilder: (context, index) {
                    final rep = userReports[index];

                    Color statusBg = AppColors.statusPendingBg;
                    Color statusText = AppColors.statusPendingText;
                    if (rep.status == 'dalam pengerjaan') {
                      statusBg = AppColors.statusProcessBg;
                      statusText = AppColors.statusProcessText;
                    } else if (rep.status == 'selesai') {
                      statusBg = AppColors.statusSuccessBg;
                      statusText = AppColors.statusSuccessText;
                    }

                    return Card(
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 8),
                        title: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: rep.kategoriId == 1
                                    ? AppColors.facilityBg
                                    : AppColors.ethicsBg,
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                rep.kategoriId == 1 ? 'Fasilitas' : 'Kode Etik',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                  color: rep.kategoriId == 1
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
                                Text(rep.createdAt ?? '',
                                    style: const TextStyle(
                                        fontSize: 11,
                                        color: AppColors.textLight)),
                                const Spacer(),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(
                                    color: statusBg,
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Text(
                                    rep.status.toUpperCase(),
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

          // Emergency & Campus Helpline
          const Text(
            'Layanan Bantuan Darurat Sekolah',
            style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: AppColors.textMain),
          ),
          const SizedBox(height: 12),

          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              children: [
                _buildHelpItem(
                  icon: Icons.shield_rounded,
                  title: 'Satgas Anti-Kekerasan & BK',
                  subtitle:
                      'Layanan darurat penanganan perundungan / kekerasan',
                  phone: '0812-SAFE-EDU (Bebas Pulsa)',
                ),
                const Divider(height: 20, color: AppColors.border),
                _buildHelpItem(
                  icon: Icons.handyman_rounded,
                  title: 'Unit Teknisi & Sarpras Sekolah',
                  subtitle: 'Penanganan cepat bahaya fasilitas fisik & listrik',
                  phone: 'Ext. 104 (Sarpras Sentral)',
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),
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
