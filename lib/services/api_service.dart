import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:library_app/models/anggota.dart';
import 'package:library_app/models/book.dart';
import 'package:library_app/models/borrowing.dart';
import 'package:library_app/utils/constants.dart';

class ApiService {
  final String baseUrl = ApiConstants.baseUrl;
  final Map<String, String> headers = {
    'Content-Type': 'application/json',
  };

  // Helper method to log API responses for debugging
  void _logResponse(http.Response response) {
    debugPrint('Status Code: ${response.statusCode}');
    debugPrint('Headers: ${response.headers}');
    debugPrint('Body: ${response.body.substring(0, response.body.length > 500 ? 500 : response.body.length)}...');
  }

  // Helper method to safely parse JSON
  dynamic _parseJson(String body) {
    // Check if the response starts with HTML
    if (body.trim().startsWith('<')) {
      throw Exception('Server returned HTML instead of JSON: ${body.substring(0, body.length > 100 ? 100 : body.length)}...');
    }
    
    try {
      return jsonDecode(body);
    } catch (e) {
      throw Exception('Failed to parse JSON: $e\nResponse body: ${body.substring(0, body.length > 100 ? 100 : body.length)}...');
    }
  }

  // Auth endpoints
  Future<Map<String, dynamic>> login(String username, String password) async {
    try {
      debugPrint('Sending login request to: $baseUrl/login.php');
      
      final response = await http.post(
        Uri.parse('$baseUrl/login.php'),
        headers: headers,
        body: jsonEncode({
          'username': username,
          'password': password,
        }),
      );

      _logResponse(response);

      if (response.statusCode == 200) {
        return _parseJson(response.body);
      } else {
        final errorData = _parseJson(response.body);
        throw Exception(errorData['message'] ?? 'Failed to login');
      }
    } catch (e) {
      debugPrint('Login error: $e');
      if (e is Exception) {
        rethrow;
      }
      throw Exception('Failed to login: $e');
    }
  }

  Future<Map<String, dynamic>> register(Anggota anggota, String username, String password) async {
    try {
      debugPrint('Sending registration request to: $baseUrl/register.php');
      
      final response = await http.post(
        Uri.parse('$baseUrl/register.php'),
        headers: headers,
        body: jsonEncode({
          'anggota': anggota.toJson(),
          'username': username,
          'password': password,
        }),
      );

      _logResponse(response);

      if (response.statusCode == 200) {
        return _parseJson(response.body);
      } else {
        final errorData = _parseJson(response.body);
        throw Exception(errorData['message'] ?? 'Failed to register');
      }
    } catch (e) {
      debugPrint('Registration error: $e');
      if (e is Exception) {
        rethrow;
      }
      throw Exception('Failed to register: $e');
    }
  }

  Future<Map<String, dynamic>> checkRegistrationStatus(int anggotaId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/check_registration.php?anggota_id=$anggotaId'),
        headers: headers,
      );

      _logResponse(response);

      if (response.statusCode == 200) {
        return _parseJson(response.body);
      } else {
        final errorData = _parseJson(response.body);
        throw Exception(errorData['message'] ?? 'Failed to check registration status');
      }
    } catch (e) {
      debugPrint('Check registration status error: $e');
      if (e is Exception) {
        rethrow;
      }
      throw Exception('Failed to check registration status: $e');
    }
  }

  // Book endpoints
  Future<List<Book>> getBooks() async {
    try {
      debugPrint('Fetching books from: $baseUrl/books.php');
      
      final response = await http.get(
        Uri.parse('$baseUrl/books.php'),
        headers: headers,
      );

      _logResponse(response);

      if (response.statusCode == 200) {
        final dynamic data = _parseJson(response.body);
        
        // Check if the response is a list or has a success field
        if (data is List) {
          return data.map((json) => Book.fromJson(json)).toList();
        } else if (data is Map && data.containsKey('success') && data['success'] == false) {
          throw Exception(data['message'] ?? 'Failed to load books');
        } else {
          throw Exception('Unexpected response format');
        }
      } else {
        throw Exception('Failed to load books: HTTP ${response.statusCode}');
      }
    } catch (e) {
      debugPrint('Get books error: $e');
      if (e is Exception) {
        rethrow;
      }
      throw Exception('Failed to load books: $e');
    }
  }

  Future<Book> getBookDetails(int bookId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/book_details.php?book_id=$bookId'),
        headers: headers,
      );

      _logResponse(response);

      if (response.statusCode == 200) {
        final Map<String, dynamic> data = _parseJson(response.body);
        return Book.fromJson(data);
      } else {
        final errorData = _parseJson(response.body);
        throw Exception(errorData['message'] ?? 'Failed to load book details');
      }
    } catch (e) {
      debugPrint('Get book details error: $e');
      if (e is Exception) {
        rethrow;
      }
      throw Exception('Failed to load book details: $e');
    }
  }

  // Borrowing endpoints
  Future<List<Borrowing>> getUserBorrowings(int anggotaId) async {
    try {
      debugPrint('Fetching borrowings from: $baseUrl/user_borrowings.php?anggota_id=$anggotaId');
      
      final response = await http.get(
        Uri.parse('$baseUrl/user_borrowings.php?anggota_id=$anggotaId'),
        headers: headers,
      );

      _logResponse(response);

      if (response.statusCode == 200) {
        final dynamic data = _parseJson(response.body);
        
        // Check if the response is a list or has a success field
        if (data is List) {
          return data.map((json) => Borrowing.fromJson(json)).toList();
        } else if (data is Map && data.containsKey('success') && data['success'] == false) {
          throw Exception(data['message'] ?? 'Failed to load borrowings');
        } else {
          throw Exception('Unexpected response format');
        }
      } else {
        throw Exception('Failed to load borrowings: HTTP ${response.statusCode}');
      }
    } catch (e) {
      debugPrint('Get user borrowings error: $e');
      if (e is Exception) {
        rethrow;
      }
      throw Exception('Failed to load borrowings: $e');
    }
  }

  Future<Map<String, dynamic>> borrowBook(
    int anggotaId,
    int bookId,
    DateTime borrowDate,
    String password,
  ) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/borrow_book.php'),
        headers: headers,
        body: jsonEncode({
          'anggota_id': anggotaId,
          'book_id': bookId,
          'borrow_date': borrowDate.toIso8601String().split('T')[0],
          'password': password,
        }),
      );

      _logResponse(response);

      if (response.statusCode == 200) {
        return _parseJson(response.body);
      } else {
        final errorData = _parseJson(response.body);
        throw Exception(errorData['message'] ?? 'Failed to borrow book');
      }
    } catch (e) {
      debugPrint('Borrow book error: $e');
      if (e is Exception) {
        rethrow;
      }
      throw Exception('Failed to borrow book: $e');
    }
  }
}
