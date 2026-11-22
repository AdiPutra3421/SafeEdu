import 'package:flutter/material.dart';
import 'technician_dashboard_screen.dart';
import 'facility_status_screen.dart';

class TechnicianMainShell extends StatefulWidget {
  const TechnicianMainShell({super.key});

  @override
  State<TechnicianMainShell> createState() => _TechnicianMainShellState();
}

class _TechnicianMainShellState extends State<TechnicianMainShell> {
  int _currentIndex = 0;

  final List<Widget> _pages = [
    const TechnicianDashboardScreen(),
    const FacilityStatusScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: _pages,
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.assignment_outlined),
            selectedIcon: Icon(Icons.assignment_rounded),
            label: 'Tugas Masuk',
          ),
          NavigationDestination(
            icon: Icon(Icons.build_circle_outlined),
            selectedIcon: Icon(Icons.build_circle_rounded),
            label: 'Status Fasilitas',
          ),
        ],
      ),
    );
  }
}
