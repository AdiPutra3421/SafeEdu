import 'package:flutter/material.dart';

import 'theme/app_theme.dart';

import 'screens/auth/login_screen.dart';

void main() {
  runApp(const SafeEduTechnicianApp());
}

class SafeEduTechnicianApp extends StatelessWidget {
  const SafeEduTechnicianApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'SafeEdu Teknisi',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: const LoginScreen(),
    );
  }
}
