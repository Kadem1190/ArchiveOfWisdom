import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:library_app/providers/auth_provider.dart';
import 'package:library_app/screens/auth/auth_screen.dart';
import 'package:library_app/utils/theme.dart';
import 'package:library_app/widgets/custom_button.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final anggota = authProvider.currentAnggota;
    final user = authProvider.currentUser;

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Profile'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await authProvider.logout();
              if (context.mounted) {
                Navigator.of(context).pushReplacement(
                  MaterialPageRoute(builder: (_) => const AuthScreen()),
                );
              }
            },
          ),
        ],
      ),
      body: authProvider.isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Column(
                      children: [
                        CircleAvatar(
                          radius: 50,
                          backgroundColor: AppTheme.primaryColor,
                          child: Text(
                            anggota?.name.substring(0, 1).toUpperCase() ?? 'U',
                            style: const TextStyle(
                              fontSize: 40,
                              color: Colors.white,
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          anggota?.name ?? 'User',
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          user?.username ?? '',
                          style: const TextStyle(
                            fontSize: 16,
                            color: Colors.grey,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 32),
                  const Text(
                    'Personal Information',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 16),
                  _buildInfoItem('NIM', anggota?.nim ?? 'N/A'),
                  _buildInfoItem('Class', anggota?.className ?? 'N/A'),
                  _buildInfoItem('Gender', anggota?.gender ?? 'N/A'),
                  _buildInfoItem(
                    'Date of Birth',
                    anggota?.dateOfBirth != null
                        ? DateFormat('yyyy-MM-dd').format(anggota!.dateOfBirth)
                        : 'N/A',
                  ),
                  _buildInfoItem('Place of Birth', anggota?.placeOfBirth ?? 'N/A'),
                  _buildInfoItem('Address', anggota?.address ?? 'N/A'),
                  const SizedBox(height: 32),
                  const Text(
                    'Account Information',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 16),
                  _buildInfoItem(
                    'Account Status',
                    user?.status.toString().split('.').last.toUpperCase() ?? 'N/A',
                    valueColor: _getStatusColor(user?.status.toString().split('.').last ?? ''),
                  ),
                  _buildInfoItem(
                    'Registration Status',
                    user?.registrationStatus.toString().split('.').last.toUpperCase() ?? 'N/A',
                  ),
                  _buildInfoItem(
                    'Account Created',
                    user?.createdAt != null
                        ? DateFormat('yyyy-MM-dd').format(user!.createdAt)
                        : 'N/A',
                  ),
                  const SizedBox(height: 32),
                  CustomButton(
                    text: 'Logout',
                    icon: Icons.logout,
                    onPressed: () async {
                      await authProvider.logout();
                      if (context.mounted) {
                        Navigator.of(context).pushReplacement(
                          MaterialPageRoute(builder: (_) => const AuthScreen()),
                        );
                      }
                    },
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildInfoItem(String label, String value, {Color? valueColor}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 16,
                color: Colors.grey,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w500,
                color: valueColor,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Color? _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'active':
        return AppTheme.successColor;
      case 'inactive':
        return Colors.orange;
      case 'banned':
        return AppTheme.errorColor;
      default:
        return null;
    }
  }
}
