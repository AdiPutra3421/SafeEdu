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
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.assignment_outlined),
            activeIcon: Icon(Icons.assignment_rounded),
            label: 'Tugas Masuk',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.build_circle_outlined),
            activeIcon: Icon(Icons.build_circle_rounded),
            label: 'Status Fasilitas',
          ),
        ],
      ),
    );
  }
}
