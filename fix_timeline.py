import sys

content = open('user_app/lib/screens/profile_screen.dart', 'r', encoding='utf-8').read()

old_timeline = """              const Text('Tahapan Pelacakan Status', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
              const SizedBox(height: 12),

              _buildTimelineStep(
                title: 'Laporan Masuk',
                subtitle: 'Laporan Anda telah berhasil dicatat ke sistem SafeEdu',
                isDone: true,
                isCurrent: report.status == 'menunggu',
              ),
              _buildTimelineStep(
                title: 'Verifikasi Tim Admin',
                subtitle: 'Admin kesiswaan/sarpras memeriksa validitas & urgensi insiden',
                isDone: report.status == 'dalam pengerjaan' || report.status == 'selesai',
                isCurrent: report.status == 'diverifikasi',
              ),
              _buildTimelineStep(
                title: 'Penanganan oleh Teknisi / Satgas',
                subtitle: 'Petugas lapangan sedang melakukan perbaikan atau investigasi',
                isDone: report.status == 'selesai',
                isCurrent: report.status == 'dalam pengerjaan',
              ),
              _buildTimelineStep(
                title: 'Selesai & Dikonfirmasi',
                subtitle: 'Tiket ditutup setelah teknisi menyelesaikan penanganan',
                isDone: report.status == 'selesai',
                isCurrent: false,
                isLast: true,
              ),"""

new_timeline = """              const Text('Tahapan Pelacakan Status', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
              const SizedBox(height: 12),

              _buildTimelineStep(
                title: 'Laporan Masuk',
                subtitle: 'Laporan Anda telah berhasil dicatat ke sistem SafeEdu',
                isDone: true,
                isCurrent: report.status == 'menunggu',
              ),
              _buildTimelineStep(
                title: 'Verifikasi Tim Admin (Disposisi)',
                subtitle: 'Admin telah memverifikasi dan menugaskan teknisi untuk insiden ini',
                isDone: report.status == 'diproses' || report.status == 'selesai',
                isCurrent: report.status == 'diproses' && report.penugasanStatus == 'ditugaskan',
              ),
              _buildTimelineStep(
                title: 'Penanganan oleh Teknisi / Satgas',
                subtitle: 'Petugas lapangan menerima tugas dan sedang melakukan perbaikan',
                isDone: report.status == 'selesai',
                isCurrent: report.status == 'diproses' && report.penugasanStatus == 'diterima',
              ),
              _buildTimelineStep(
                title: 'Selesai & Dikonfirmasi',
                subtitle: 'Tiket ditutup setelah teknisi menyelesaikan penanganan',
                isDone: report.status == 'selesai',
                isCurrent: report.status == 'selesai',
                isLast: true,
              ),"""

content = content.replace(old_timeline, new_timeline, 1)

with open('user_app/lib/screens/profile_screen.dart', 'w', encoding='utf-8') as f:
    f.write(content)
