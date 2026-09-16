import 'package:flutter/material.dart';
import 'models/report_model.dart';
import 'screens/home_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/report_screen.dart';
import 'theme/app_theme.dart';
import 'screens/auth/login_screen.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

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
      home: const LoginScreen(),
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
  List<PengaduanModel> _reports = [];

  @override
  void initState() {
    super.initState();
    _fetchReports();
  }

  Future<void> _fetchReports() async {
    try {
      // Mock user id 1 (Budi Siswa)
      final res = await http.get(Uri.parse('http://127.0.0.1:8000/api/pengaduan?user_id=1'));
      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (data['success'] == true) {
          final List<dynamic> list = data['data'];
          setState(() {
            _reports = list.map((item) {
              return PengaduanModel(
                id: item['id'],
                userId: item['user_id'],
                kategoriId: item['kategori_id'],
                fasilitasId: item['fasilitas_id'],
                judul: item['judul'],
                deskripsi: item['deskripsi'],
                lokasiKejadian: item['lokasi_kejadian'],
                status: item['status'],
                isAnonim: item['is_anonim'] == 1 || item['is_anonim'] == true,
                createdAt: item['created_at'],
                namaKategori: item['kategori']?['nama'] ?? '-',
              );
            }).toList();
          });
        }
      }
    } catch (e) {
      print('Error fetching reports: $e');
    }
  }

  void _handleNewReport(PengaduanModel report) {
    _fetchReports(); // Refresh from DB
    setState(() {
      _currentIndex = 2; // Auto switch to Profile / History tab
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
      ProfileScreen(userReports: _reports, onRefresh: _fetchReports),
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
