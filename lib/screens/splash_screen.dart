import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:library_app/providers/auth_provider.dart';
import 'package:library_app/screens/auth/auth_screen.dart';
import 'package:library_app/screens/auth/registration_status_screen.dart';
import 'package:library_app/screens/home/home_screen.dart';
import 'package:library_app/utils/constants.dart';
import 'package:library_app/utils/theme.dart';
class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _checkAuthStatus();
  }

  Future<void> _checkAuthStatus() async {
    await Future.delayed(const Duration(seconds: 2));
    
    if (!mounted) return;
    
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    
    if (authProvider.isLoggedIn) {
      if (authProvider.currentUser!.canLogin) {
        // User is logged in and approved, navigate to home screen
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const HomeScreen()),
        );
      } else {
        // User is logged in but not approved or active
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const RegistrationStatusScreen()),
        );
      }
    } else if (authProvider.anggotaId != null) {
      // User has registered but not logged in, check registration status
      final statusResponse = await authProvider.checkRegistrationStatus();
      
      if (!mounted) return;
      
      if (statusResponse['success'] == true) {
        final status = statusResponse['status'];
        final registrationStatus = statusResponse['registration_status'];
        
        if (status == 'active' && registrationStatus == 'approved') {
          // Registration approved, navigate to login
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const AuthScreen(initialIndex: 0)),
          );
        } else {
          // Registration pending, navigate to status screen
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const RegistrationStatusScreen()),
          );
        }
      } else {
        // Error checking status, navigate to auth screen
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const AuthScreen()),
        );
      }
    } else {
      // No registration in progress, navigate to auth screen
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const AuthScreen()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Image.asset(
              'assets/images/logo.png',
              width: 120,
              height: 120,
              errorBuilder: (context, error, stackTrace) {
                return const Icon(
                  Icons.menu_book_rounded,
                  size: 120,
                  color: AppTheme.primaryColor,
                );
              },
            ),
            const SizedBox(height: 24),
            const Text(
              AppConstants.appName,
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            const CircularProgressIndicator(),
          ],
        ),
      ),
    );
  }
}
