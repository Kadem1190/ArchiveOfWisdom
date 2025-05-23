import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:library_app/models/anggota.dart';
import 'package:library_app/models/user.dart';
import 'package:library_app/services/api_service.dart';
import 'package:library_app/utils/constants.dart';

class AuthProvider extends ChangeNotifier {
  final ApiService _apiService = ApiService();
  bool _isLoading = false;
  User? _currentUser;
  Anggota? _currentAnggota;
  String? _token;
  int? _anggotaId;
  String? _errorMessage;

  bool get isLoading => _isLoading;
  User? get currentUser => _currentUser;
  Anggota? get currentAnggota => _currentAnggota;
  String? get token => _token;
  int? get anggotaId => _anggotaId;
  String? get errorMessage => _errorMessage;
  bool get isLoggedIn => _token != null && _currentUser != null;
  bool get isPendingApproval => _currentUser != null && 
                               (_currentUser!.status == UserStatus.inactive || 
                                _currentUser!.registrationStatus == RegistrationStatus.pending);

  AuthProvider() {
    _loadUserData();
  }

  Future<void> _loadUserData() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      _token = prefs.getString(AppConstants.tokenKey);
      final userId = prefs.getInt(AppConstants.userIdKey);
      _anggotaId = prefs.getInt(AppConstants.anggotaIdKey);
      
      if (_token != null && userId != null && _anggotaId != null) {
        // For now, we'll just set some basic data from SharedPreferences
        // In a production app, you might want to validate the token with the server
        _currentUser = User(
          userId: userId,
          anggotaId: _anggotaId!,
          username: prefs.getString(AppConstants.usernameKey) ?? '',
          role: UserRole.values.firstWhere(
            (e) => e.toString().split('.').last == prefs.getString(AppConstants.userRoleKey),
            orElse: () => UserRole.user,
          ),
          status: UserStatus.values.firstWhere(
            (e) => e.toString().split('.').last == prefs.getString(AppConstants.userStatusKey),
            orElse: () => UserStatus.inactive,
          ),
          registrationStatus: RegistrationStatus.values.firstWhere(
            (e) => e.toString().split('.').last == prefs.getString(AppConstants.registrationStatusKey),
            orElse: () => RegistrationStatus.pending,
          ),
          createdAt: DateTime.now(),
        );
      }
    } catch (e) {
      debugPrint('Error loading user data: $e');
      _errorMessage = 'Failed to load user data';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.login(username, password);
      
      if (response['success'] == true) {
        final userData = response['user'];
        final anggotaData = response['anggota'];
        
        _currentUser = User.fromJson(userData);
        _currentAnggota = Anggota.fromJson(anggotaData);
        _token = response['token'];
        _anggotaId = _currentUser!.anggotaId;
        
        // Save to SharedPreferences
        final prefs = await SharedPreferences.getInstance();
        prefs.setString(AppConstants.tokenKey, _token!);
        prefs.setInt(AppConstants.userIdKey, _currentUser!.userId!);
        prefs.setInt(AppConstants.anggotaIdKey, _anggotaId!);
        prefs.setString(AppConstants.usernameKey, _currentUser!.username);
        prefs.setString(AppConstants.userRoleKey, _currentUser!.role.toString().split('.').last);
        prefs.setString(AppConstants.userStatusKey, _currentUser!.status.toString().split('.').last);
        prefs.setString(AppConstants.registrationStatusKey, _currentUser!.registrationStatus.toString().split('.').last);
        
        _isLoading = false;
        notifyListeners();
        
        // Check if user can login
        if (!_currentUser!.canLogin) {
          _errorMessage = 'Your account is not active or pending approval';
          return false;
        }
        
        return true;
      } else {
        _errorMessage = response['message'] ?? 'Login failed';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      debugPrint('Login error: $e');
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<Map<String, dynamic>> register(
    Anggota anggota,
    String username,
    String password,
  ) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.register(anggota, username, password);
      
      _isLoading = false;
      notifyListeners();
      
      if (response['success'] == true) {
        // Convert anggota_id to int if it's a string
        if (response['anggota_id'] is String) {
          _anggotaId = int.parse(response['anggota_id']);
        } else {
          _anggotaId = response['anggota_id'];
        }
        
        // Save anggota_id to SharedPreferences for status checking
        final prefs = await SharedPreferences.getInstance();
        prefs.setInt(AppConstants.anggotaIdKey, _anggotaId!);
        
        return {
          'success': true,
          'anggota_id': _anggotaId,
          'message': response['message'],
        };
      } else {
        _errorMessage = response['message'] ?? 'Registration failed';
        return {
          'success': false,
          'message': _errorMessage,
        };
      }
    } catch (e) {
      debugPrint('Registration error: $e');
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return {
        'success': false,
        'message': 'An error occurred during registration: $_errorMessage',
      };
    }
  }

  Future<Map<String, dynamic>> checkRegistrationStatus() async {
    if (_anggotaId == null) {
      return {
        'success': false,
        'message': 'No registration in progress',
      };
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.checkRegistrationStatus(_anggotaId!);
      
      _isLoading = false;
      notifyListeners();
      
      if (response['success'] == true) {
        // Update SharedPreferences with latest status
        final prefs = await SharedPreferences.getInstance();
        prefs.setString(AppConstants.userStatusKey, response['status']);
        prefs.setString(AppConstants.registrationStatusKey, response['registration_status']);
        
        return response;
      } else {
        _errorMessage = response['message'] ?? 'Failed to check registration status';
        return {
          'success': false,
          'message': _errorMessage,
        };
      }
    } catch (e) {
      debugPrint('Check registration status error: $e');
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return {
        'success': false,
        'message': 'Failed to check registration status: $_errorMessage',
      };
    }
  }

  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.clear();
      
      _currentUser = null;
      _currentAnggota = null;
      _token = null;
      _anggotaId = null;
      _errorMessage = null;
    } catch (e) {
      debugPrint('Logout error: $e');
      _errorMessage = 'Error during logout';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}
