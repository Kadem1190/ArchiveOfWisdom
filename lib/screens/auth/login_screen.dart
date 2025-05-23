import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:library_app/providers/auth_provider.dart';
import 'package:library_app/screens/home/home_screen.dart';
import 'package:library_app/screens/auth/registration_status_screen.dart';
import 'package:library_app/utils/theme.dart';
import 'package:library_app/widgets/custom_button.dart';
import 'package:library_app/widgets/custom_text_field.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final success = await authProvider.login(
      _usernameController.text.trim(),
      _passwordController.text,
    );

    if (!mounted) return;

    if (success) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
      );
    } else if (authProvider.isPendingApproval) {
      // If user is pending approval, show registration status screen
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const RegistrationStatusScreen()),
      );
    }
    // Error message is handled by the provider and displayed below
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);

    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Form(
        key: _formKey,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Icon(
              Icons.menu_book_rounded,
              size: 80,
              color: AppTheme.primaryColor,
            ),
            const SizedBox(height: 32),
            CustomTextField(
              controller: _usernameController,
              labelText: 'Username',
              prefixIcon: Icons.person,
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter your username';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),
            CustomTextField(
              controller: _passwordController,
              labelText: 'Password',
              prefixIcon: Icons.lock,
              obscureText: _obscurePassword,
              suffixIcon: IconButton(
                icon: Icon(
                  _obscurePassword ? Icons.visibility : Icons.visibility_off,
                ),
                onPressed: () {
                  setState(() {
                    _obscurePassword = !_obscurePassword;
                  });
                },
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter your password';
                }
                return null;
              },
            ),
            if (authProvider.errorMessage != null) ...[
              const SizedBox(height: 16),
              Text(
                authProvider.errorMessage!,
                style: const TextStyle(
                  color: AppTheme.errorColor,
                  fontSize: 14,
                ),
                textAlign: TextAlign.center,
              ),
            ],
            const SizedBox(height: 24),
            CustomButton(
              text: 'Login',
              isLoading: authProvider.isLoading,
              onPressed: _login,
            ),
          ],
        ),
      ),
    );
  }
}
