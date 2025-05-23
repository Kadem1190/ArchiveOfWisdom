import 'package:flutter/material.dart';
import 'package:library_app/models/book.dart';
import 'package:library_app/services/api_service.dart';

class BookProvider extends ChangeNotifier {
  final ApiService _apiService = ApiService();
  List<Book> _books = [];
  Book? _selectedBook;
  bool _isLoading = false;
  String? _error;

  List<Book> get books => _books;
  Book? get selectedBook => _selectedBook;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchBooks() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      debugPrint('Fetching books...');
      _books = await _apiService.getBooks();
      debugPrint('Fetched ${_books.length} books');
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      debugPrint('Error fetching books: $e');
      _error = 'Failed to load books: $e';
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchBookDetails(int bookId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _selectedBook = await _apiService.getBookDetails(bookId);
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = 'Failed to load book details: $e';
      _isLoading = false;
      notifyListeners();
    }
  }

  void setSelectedBook(Book book) {
    _selectedBook = book;
    notifyListeners();
  }

  void clearSelectedBook() {
    _selectedBook = null;
    notifyListeners();
  }
}
