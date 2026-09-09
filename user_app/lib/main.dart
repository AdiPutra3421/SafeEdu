import 'package:flutter/material.dart';
import 'models/report_model.dart';
import 'screens/home_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/report_screen.dart';
import 'theme/app_theme.dart';

void main() {
  runApp(const SafeEduUserApp());
}

class SafeEduUserApp extends StatelessWidget {
  const SafeEduUserApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'SafeEdu Siswa',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: const MainNavigationShell(),
    );
  }
}

class MainNavigationShell extends StatefulWidget {
  const MainNavigationShell({super.key});

  @override
  State<MainNavigationShell> createState() => _MainNavigationShellState();
}

class _MainNavigationShellState extends State<MainNavigationShell> {
  int _currentIndex = 0;

  // Student reports state
  final List<UserReport> _reports = [
    UserReport(
      id: 'REP-2026-001',
      type: 'fasilitas',
      title: 'AC Bocor & Tidak Dingin di Lab Komputer 2',
      category: 'Pendingin Ruangan (AC)',
      location: 'Gedung B, Lantai 3, Lab 302',
      urgency: 'Tinggi',
      description: 'Pendingin ruangan meneteskan air deras tepat di atas meja PC server unit 4.',
      date: '07 Sep 2026, 08:30',
      isAnonymous: false,
      status: 'Menunggu Verifikasi',
      responseNote: null,
    ),
    UserReport(
      id: 'REP-2026-003',
      type: 'fasilitas',
      title: 'Kran Air Wastafel Toilet Siswa Patah',
      category: 'Sanitasi & Perpipaan Air',
      location: 'Gedung A, Toilet Pria Lantai 1',
      urgency: 'Sedang',
      description: 'Kran air patah dan terus mengalir membanjiri lantai selasar koridor musholla.',
      date: '06 Sep 2026, 14:00',
      isAnonymous: false,
      status: 'Diteruskan ke Teknisi',
      responseNote: 'Teknisi Bambang Sudirjo sedang mengganti katup fitting pipa.',
    ),
  ];

  void _handleNewReport(UserReport report) {
    setState(() {
      _reports.insert(0, report);
      _currentIndex = 2; // Auto switch to Profile / History tab to view submitted report
    });
  }

  void _switchTab(int index) {
    setState(() {
      _currentIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    final List<Widget> pages = [
      HomeScreen(onNavigateToTab: _switchTab),
      ReportScreen(onReportSubmitted: _handleNewReport),
      ProfileScreen(userReports: _reports),
    ];

    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: pages,
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: _switchTab,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home_outlined),
            activeIcon: Icon(Icons.home_rounded),
            label: 'Beranda',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.add_circle_outline_rounded),
            activeIcon: Icon(Icons.add_circle_rounded),
            label: 'Pelaporan',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person_outline_rounded),
            activeIcon: Icon(Icons.person_rounded),
            label: 'Profil Siswa',
          ),
        ],
      ),
    );
  }
}
