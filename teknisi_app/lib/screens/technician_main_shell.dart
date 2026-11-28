import 'package:flutter/material.dart';
import 'technician_dashboard_screen.dart';
import 'technician_profile_screen.dart';

class TechnicianMainShell extends StatefulWidget {
  const TechnicianMainShell({super.key});

  @override
  State<TechnicianMainShell> createState() => _TechnicianMainShellState();
}

class _TechnicianMainShellState extends State<TechnicianMainShell> {
  int _currentIndex = 0;

  void _switchTab(int index) {
    setState(() {
      _currentIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    final List<Widget> pages = [
      TechnicianDashboardScreen(
        onOpenProfile: () => _switchTab(1),
      ),
      const TechnicianProfileScreen(),
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
            icon: Icon(Icons.assignment_outlined),
            selectedIcon: Icon(Icons.assignment_rounded),
            label: 'Tugas Masuk',
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
