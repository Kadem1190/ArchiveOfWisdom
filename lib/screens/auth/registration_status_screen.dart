import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:library_app/providers/auth_provider.dart';
import 'package:library_app/screens/auth/auth_screen.dart';
import 'package:library_app/utils/theme.dart';
import 'package:library_app/widgets/custom_button.dart';

class RegistrationStatusScreen extends StatefulWidget {
  const RegistrationStatusScreen({super.key});

  @override
  State<RegistrationStatusScreen> createState() => _RegistrationStatusScreenState();
}

class _RegistrationStatusScreenState extends State<RegistrationStatusScreen> {
  bool _isLoading = false;
  String _statusMessage = 'Your registration is pending approval.';
  bool _isApproved = false;
  bool _isError = false;

  @override
  void initState() {
    super.initState();
    _checkStatus();
  }

  Future<void> _checkStatus() async {
    setState(() {
      _isLoading = true;
      _isError = false;
    });

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final response = await authProvider.checkRegistrationStatus();

    setState(() {
      _isLoading = false;
      if (response['success'] == true) {
        final status = response['status'];
        final registrationStatus = response['registration_status'];
        
        if (status == 'active' && registrationStatus == 'approved') {
          _statusMessage = 'Your account has been approved! You can now log in.';
          _isApproved = true;
        } else if (status == 'banned') {
          _statusMessage = 'Your account has been banned. Please contact the administrator.';
          _isError = true;
        } else {
          _statusMessage = 'Your registration is still pending approval.';
        }
      } else {
        _statusMessage = response['message'] ?? 'Failed to check registration status.';
        _isError = true;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Registration Status'),
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                _isApproved 
                  ? Icons.check_circle
                  : (_isError ? Icons.error : Icons.hourglass_empty),
                size: 80,
                color: _isApproved 
                  ? AppTheme.successColor
                  : (_isError ? AppTheme.errorColor : AppTheme.primaryColor),
              ),
              const SizedBox(height: 24),
              Text(
                _statusMessage,
                style: const TextStyle(
                  fontSize: 18,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 32),
              if (_isApproved) ...[
                CustomButton(
                  text: 'Go to Login',
                  onPressed: () {
                    Navigator.of(context).pushReplacement(
                      MaterialPageRoute(builder: (_) => const AuthScreen(initialIndex: 0)),
                    );
                  },
                ),
              ] else ...[
                CustomButton(
                  text: 'Check Status',
                  isLoading: _isLoading,
                  onPressed: _checkStatus,
                ),
                const SizedBox(height: 16),
                const Text(
                  'Please wait for an administrator to approve your account. You will be able to log in once your account is approved.',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
