import 'package:flutter/material.dart';
import '../models/task_model.dart';
import '../theme/app_theme.dart';

class TechnicianDashboardScreen extends StatefulWidget {
  const TechnicianDashboardScreen({super.key});

  @override
  State<TechnicianDashboardScreen> createState() => _TechnicianDashboardScreenState();
}

class _TechnicianDashboardScreenState extends State<TechnicianDashboardScreen> {
  bool isOnDuty = true;
  String selectedFilter = 'Semua';

  // Seed tasks dispatched from Admin
  final List<TechnicianTask> _tasks = [
    TechnicianTask(
      id: 'REP-2026-003',
      title: 'Kran Air Wastafel Toilet Siswa Patah',
      category: 'Sanitasi & Perpipaan',
      location: 'Gedung A, Toilet Pria Lantai 1',
      urgency: 'Tinggi',
      reporterName: 'Rian Pratama (X TKJ 2)',
      dateReceived: '07 Sep 2026, 14:00',
      adminNotes: 'Prioritaskan penutupan katup utama dahulu sebelum jam istirahat sholat.',
      status: 'Dalam Pengerjaan',
      technicianNotes: 'Katup utama telah dimatikan. Fitting pipa 1/2 inch sedang diambil di gudang sarpras.',
      estimatedCompletion: 'Hari ini 16:00',
      confirmedAt: '07 Sep 2026, 14:30',
    ),
    TechnicianTask(
      id: 'REP-2026-001',
      title: 'AC Bocor & Tidak Dingin di Lab Komputer 2',
      category: 'Kelistrikan & Pendingin',
      location: 'Gedung B, Lantai 3, Lab 302',
      urgency: 'Tinggi',
      reporterName: 'Ahmad Fauzi (XII RPL 1)',
      dateReceived: '07 Sep 2026, 09:15',
      adminNotes: 'Bahaya air menetes di atas meja server unit 4.',
      status: 'Tugas Baru',
      technicianNotes: null,
      estimatedCompletion: null,
      confirmedAt: null,
    ),
    TechnicianTask(
      id: 'REP-2026-004',
      title: 'Proyektor LCD Mati Total Saat Presentasi',
      category: 'Multimedia & IT',
      location: 'Gedung C, Ruang Kelas 104',
      urgency: 'Sedang',
      reporterName: 'Dewi Lestari (XI MIPA 3)',
      dateReceived: '06 Sep 2026, 09:20',
      adminNotes: 'Ganti lampu cadangan dari logistik sarpras.',
      status: 'Selesai',
      technicianNotes: 'Lampu halogen dan kabel HDMI telah diganti. Uji coba display 1080p normal.',
      estimatedCompletion: 'Selesai',
      confirmedAt: '06 Sep 2026, 13:45',
    ),
  ];

  int get countNew => _tasks.where((t) => t.status == 'Tugas Baru').length;
  int get countProcess => _tasks.where((t) => t.status == 'Dalam Pengerjaan').length;
  int get countFinished => _tasks.where((t) => t.status == 'Selesai').length;

  List<TechnicianTask> get filteredTasks {
    if (selectedFilter == 'Tugas Baru') {
      return _tasks.where((t) => t.status == 'Tugas Baru').toList();
    } else if (selectedFilter == 'Dalam Pengerjaan') {
      return _tasks.where((t) => t.status == 'Dalam Pengerjaan').toList();
    } else if (selectedFilter == 'Selesai') {
      return _tasks.where((t) => t.status == 'Selesai').toList();
    }
    return _tasks;
  }

  void _showConfirmationSheet(TechnicianTask task) {
    final notesController = TextEditingController(text: task.technicianNotes ?? '');
    final estController = TextEditingController(text: task.estimatedCompletion ?? '2 Jam Kerja');
    String chosenStatus = task.status == 'Tugas Baru' ? 'Dalam Pengerjaan' : task.status;

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
                              task.id,
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
                          icon: const Icon(Icons.close, color: AppColors.textMuted),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppColors.subtle,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            task.title,
                            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            '📍 ${task.location}',
                            style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                          ),
                          if (task.adminNotes.isNotEmpty) ...[
                            const SizedBox(height: 6),
                            Text(
                              'Pesan Admin: "${task.adminNotes}"',
                              style: const TextStyle(fontSize: 12, fontStyle: FontStyle.italic, color: AppColors.secondaryMuted),
                            ),
                          ]
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),
                    const Text(
                      'Pilih Status Pengerjaan',
                      style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      children: [
                        ChoiceChip(
                          label: const Text('Dalam Pengerjaan'),
                          selected: chosenStatus == 'Dalam Pengerjaan',
                          selectedColor: AppColors.statusProcessBg,
                          labelStyle: TextStyle(
                            color: chosenStatus == 'Dalam Pengerjaan' ? AppColors.statusProcessText : AppColors.textMain,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                          onSelected: (val) {
                            if (val) setModalState(() => chosenStatus = 'Dalam Pengerjaan');
                          },
                        ),
                        ChoiceChip(
                          label: const Text('Pekerjaan Selesai'),
                          selected: chosenStatus == 'Selesai',
                          selectedColor: AppColors.statusSuccessBg,
                          labelStyle: TextStyle(
                            color: chosenStatus == 'Selesai' ? AppColors.statusSuccessText : AppColors.textMain,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                          onSelected: (val) {
                            if (val) setModalState(() => chosenStatus = 'Selesai');
                          },
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'Catatan Lapangan / Tindakan Perbaikan',
                      style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                    ),
                    const SizedBox(height: 6),
                    TextField(
                      controller: notesController,
                      maxLines: 3,
                      decoration: const InputDecoration(
                        hintText: 'Tuliskan tindakan yang diambil, suku cadang yang diganti, atau kendala lapangan...',
                        hintStyle: TextStyle(fontSize: 13, color: AppColors.textLight),
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'Estimasi Waktu Penyelesaian',
                      style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                    ),
                    const SizedBox(height: 6),
                    TextField(
                      controller: estController,
                      decoration: const InputDecoration(
                        hintText: 'Contoh: 30 Menit, Hari ini 17:00, atau Selesai',
                        prefixIcon: Icon(Icons.schedule, size: 18, color: AppColors.textMuted),
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        icon: const Icon(Icons.send_rounded, size: 18),
                        label: const Text('Kirim Konfirmasi ke Portal Admin'),
                        onPressed: () {
                          setState(() {
                            task.status = chosenStatus;
                            task.technicianNotes = notesController.text.trim();
                            task.estimatedCompletion = estController.text.trim();
                            task.confirmedAt = 'Baru saja';
                          });
                          Navigator.pop(ctx);
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              backgroundColor: AppColors.secondary,
                              behavior: SnackBarBehavior.floating,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              content: Row(
                                children: [
                                  const Icon(Icons.check_circle, color: Color(0xFF4ADE80), size: 18),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      'Konfirmasi ${task.id} terkirim ke Admin!',
                                      style: const TextStyle(fontWeight: FontWeight.w500),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
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
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(7),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [AppColors.primary, AppColors.accentTeal],
                ),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.engineering_rounded, color: Colors.white, size: 20),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'SafeEdu Teknisi',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                ),
                Text(
                  'Bambang S. (Sarpras & Sanitasi)',
                  style: TextStyle(fontSize: 11, color: AppColors.textMuted, fontWeight: FontWeight.normal),
                ),
              ],
            ),
          ],
        ),
        actions: [
          // On-duty Switch
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: isOnDuty ? AppColors.statusSuccessBg : AppColors.subtle,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 7,
                        height: 7,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: isOnDuty ? AppColors.statusSuccessText : AppColors.textMuted,
                        ),
                      ),
                      const SizedBox(width: 5),
                      Text(
                        isOnDuty ? 'On Duty' : 'Off Duty',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: isOnDuty ? AppColors.statusSuccessText : AppColors.textMuted,
                        ),
                      ),
                    ],
                  ),
                ),
                Switch(
                  value: isOnDuty,
                  activeThumbColor: AppColors.accentTeal,
                  onChanged: (v) => setState(() => isOnDuty = v),
                ),
              ],
            ),
          )
        ],
      ),
      body: CustomScrollView(
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
                      color: AppColors.secondary,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.06),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: const [
                            Text(
                              'Pusat Respon Lapangan',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 16,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            SizedBox(height: 4),
                            Text(
                              'Terima instruksi admin & laporkan progres kerja',
                              style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                            ),
                          ],
                        ),
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.1),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.sync_rounded, color: Colors.white, size: 20),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 16),

                  // Metrics Cards
                  Row(
                    children: [
                      _buildMetricTile('Tugas Baru', countNew, AppColors.statusPendingBg, AppColors.statusPendingText),
                      const SizedBox(width: 8),
                      _buildMetricTile('Diproses', countProcess, AppColors.statusProcessBg, AppColors.statusProcessText),
                      const SizedBox(width: 8),
                      _buildMetricTile('Selesai', countFinished, AppColors.statusSuccessBg, AppColors.statusSuccessText),
                    ],
                  ),

                  const SizedBox(height: 20),

                  // Filter Chips
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
                        _buildFilterChip('Selesai'),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
              ),
            ),
          ),

          // Task List
          filteredTasks.isEmpty
              ? SliverFillRemaining(
                  hasScrollBody: false,
                  child: Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: const [
                        Icon(Icons.assignment_turned_in_outlined, size: 50, color: AppColors.textLight),
                        SizedBox(height: 10),
                        Text('Tidak ada tiket pada kategori ini', style: TextStyle(color: AppColors.textMuted)),
                      ],
                    ),
                  ),
                )
              : SliverPadding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
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
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: textColor.withValues(alpha: 0.2)),
        ),
        child: Column(
          children: [
            Text(
              '$value',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: textColor,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: textColor,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(String label) {
    final isSelected = selectedFilter == label;
    return GestureDetector(
      onTap: () => setState(() => selectedFilter = label),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary : AppColors.surface,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.border,
          ),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.25),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  )
                ]
              : null,
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : AppColors.textMain,
            fontSize: 12,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    );
  }

  Widget _buildTaskCard(TechnicianTask task) {
    final isNew = task.status == 'Tugas Baru';
    final isInProgress = task.status == 'Dalam Pengerjaan';
    final isDone = task.status == 'Selesai';

    Color statusBg = AppColors.statusPendingBg;
    Color statusText = AppColors.statusPendingText;
    if (isInProgress) {
      statusBg = AppColors.statusProcessBg;
      statusText = AppColors.statusProcessText;
    } else if (isDone) {
      statusBg = AppColors.statusSuccessBg;
      statusText = AppColors.statusSuccessText;
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Row: Ticket ID & Status
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: AppColors.primaryLight,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        task.id,
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: AppColors.primary,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      task.dateReceived,
                      style: const TextStyle(fontSize: 11, color: AppColors.textLight),
                    ),
                  ],
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusBg,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    task.status,
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: statusText,
                    ),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 12),

            // Title
            Text(
              task.title,
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
                const Icon(Icons.location_on_outlined, size: 15, color: AppColors.textMuted),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(
                    task.location,
                    style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                const Icon(Icons.person_outline, size: 15, color: AppColors.textMuted),
                const SizedBox(width: 4),
                Text(
                  'Pelapor: ${task.reporterName}',
                  style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                ),
              ],
            ),

            // Admin Disposed Notes
            if (task.adminNotes.isNotEmpty) ...[
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
                      style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.primaryDark),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      task.adminNotes,
                      style: const TextStyle(fontSize: 12, color: AppColors.textMain),
                    ),
                  ],
                ),
              ),
            ],

            // Technician's own note if already confirmed
            if (task.technicianNotes != null && task.technicianNotes!.isNotEmpty) ...[
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
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Catatan Lapangan Anda:',
                          style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppColors.accentTeal),
                        ),
                        if (task.estimatedCompletion != null)
                          Text(
                            'Est: ${task.estimatedCompletion}',
                            style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: AppColors.accentTeal),
                          ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(
                      task.technicianNotes!,
                      style: const TextStyle(fontSize: 12, color: AppColors.textMain),
                    ),
                  ],
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
                      label: const Text('Terima & Konfirmasi Tugas'),
                      style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
                      onPressed: () => _showConfirmationSheet(task),
                    )
                  : isInProgress
                      ? ElevatedButton.icon(
                          icon: const Icon(Icons.edit_note_rounded, size: 18),
                          label: const Text('Perbarui Status / Selesaikan'),
                          style: ElevatedButton.styleFrom(backgroundColor: AppColors.accentTeal),
                          onPressed: () => _showConfirmationSheet(task),
                        )
                      : OutlinedButton.icon(
                          icon: const Icon(Icons.task_alt, size: 18, color: AppColors.statusSuccessText),
                          label: const Text('Laporan Selesai & Terkirim'),
                          onPressed: () => _showConfirmationSheet(task),
                        ),
            ),
          ],
        ),
      ),
    );
  }
}
