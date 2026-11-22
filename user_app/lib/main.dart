import 'package:flutter/material.dart';
import 'models/report_model.dart';
import 'screens/home_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/report_screen.dart';
import 'services/api_config.dart';
import 'services/auth_service.dart';
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
      title: 'SafeEdu',
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
    final userId = AuthService.instance.userId;
    if (userId == null) return;
    try {
      // user_id dari hasil login (AuthService), bukan hardcoded
      final res = await http
          .get(Uri.parse('${ApiConfig.baseUrl}/api/pengaduan?user_id=$userId'));
      if (res.statusCode == 200) {
        final data = json.decode(res.body);
        if (data['success'] == true) {
          final List<dynamic> list = data['data'];
          setState(() {
            _reports =
                list.map((item) => PengaduanModel.fromMap(item)).toList();
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
      ReportScreen(
        onReportSubmitted: _handleNewReport,
        existingReports: _reports,
      ),
      ProfileScreen(userReports: _reports, onRefresh: _fetchReports),
    ];

    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: pages,
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: _switchTab,
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home_rounded),
            label: 'Beranda',
          ),
          NavigationDestination(
            icon: Icon(Icons.add_circle_outline_rounded),
            selectedIcon: Icon(Icons.add_circle_rounded),
            label: 'Pelaporan',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline_rounded),
            selectedIcon: Icon(Icons.person_rounded),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}
