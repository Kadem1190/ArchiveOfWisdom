import 'package:flutter/material.dart';
import 'package:library_app/models/borrowing.dart';
import 'package:library_app/services/api_service.dart';

class BorrowingProvider extends ChangeNotifier {
  final ApiService _apiService = ApiService();
  List<Borrowing> _borrowings = [];
  bool _isLoading = false;
  String? _error;

  List<Borrowing> get borrowings => _borrowings;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchUserBorrowings(int anggotaId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _borrowings = await _apiService.getUserBorrowings(anggotaId);
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = 'Failed to load borrowings: $e';
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> borrowBook(
    int anggotaId,
    int bookId,
    DateTime borrowDate,
    String password,
  ) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.borrowBook(
        anggotaId,
        bookId,
        borrowDate,
        password,
      );
      
      _isLoading = false;
      notifyListeners();
      
      if (response['success'] == true) {
        // Refresh borrowings list
        await fetchUserBorrowings(anggotaId);
        return {
          'success': true,
          'message': response['message'] ?? 'Book borrowed successfully',
        };
      } else {
        return {
          'success': false,
          'message': response['message'] ?? 'Failed to borrow book',
        };
      }
    } catch (e) {
      _error = 'Failed to borrow book: $e';
      _isLoading = false;
      notifyListeners();
      return {
        'success': false,
        'message': 'An error occurred while borrowing the book',
      };
    }
  }
}
