import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:library_app/models/book.dart';
import 'package:library_app/providers/auth_provider.dart';
import 'package:library_app/providers/borrowing_provider.dart';
import 'package:library_app/utils/theme.dart';
import 'package:library_app/widgets/custom_button.dart';
import 'package:library_app/widgets/custom_text_field.dart';

class BookDetailScreen extends StatefulWidget {
  final Book book;

  const BookDetailScreen({super.key, required this.book});

  @override
  State<BookDetailScreen> createState() => _BookDetailScreenState();
}

class _BookDetailScreenState extends State<BookDetailScreen> {
  DateTime _selectedDate = DateTime.now();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _isReserving = false;

  @override
  void dispose() {
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 30)),
    );
    if (picked != null && picked != _selectedDate) {
      setState(() {
        _selectedDate = picked;
      });
    }
  }

  Future<void> _showReservationDialog() async {
    return showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text('Reserve Book'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Book: ${widget.book.title}'),
                const SizedBox(height: 16),
                InkWell(
                  onTap: () => _selectDate(context),
                  child: InputDecorator(
                    decoration: InputDecoration(
                      labelText: 'Borrow Date',
                      prefixIcon: const Icon(Icons.calendar_today),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    child: Text(
                      DateFormat('yyyy-MM-dd').format(_selectedDate),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                CustomTextField(
                  controller: _passwordController,
                  labelText: 'Confirm Password',
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
                ),
              ],
            ),
          ),
          actions: <Widget>[
            TextButton(
              child: const Text('Cancel'),
              onPressed: () {
                Navigator.of(context).pop();
              },
            ),
            TextButton(
              child: _isReserving
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Text('Reserve'),
              onPressed: _isReserving ? null : _reserveBook,
            ),
          ],
        );
      },
    );
  }

  Future<void> _reserveBook() async {
    if (_passwordController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter your password')),
      );
      return;
    }

    setState(() {
      _isReserving = true;
    });

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final borrowingProvider = Provider.of<BorrowingProvider>(context, listen: false);

    try {
      final result = await borrowingProvider.borrowBook(
        authProvider.anggotaId!,
        widget.book.bookId!,
        _selectedDate,
        _passwordController.text,
      );

      if (!mounted) return;

      Navigator.of(context).pop(); // Close dialog

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']),
          backgroundColor: result['success'] ? AppTheme.successColor : AppTheme.errorColor,
        ),
      );

      if (result['success']) {
        Navigator.of(context).pop(); // Go back to books list
      }
    } catch (e) {
      if (!mounted) return;

      Navigator.of(context).pop(); // Close dialog

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to reserve book: $e'),
          backgroundColor: AppTheme.errorColor,
        ),
      );
    } finally {
      setState(() {
        _isReserving = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => BorrowingProvider(),
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Book Details'),
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Hero(
                  tag: 'book-${widget.book.bookId}',
                  child: Container(
                    height: 200,
                    width: 150,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(8),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.2),
                          blurRadius: 5,
                          offset: const Offset(0, 3),
                        ),
                      ],
                      image: widget.book.coverUrl != null
                          ? DecorationImage(
                              image: NetworkImage(widget.book.coverUrl!),
                              fit: BoxFit.cover,
                            )
                          : null,
                    ),
                    child: widget.book.coverUrl == null
                        ? Container(
                            color: Colors.grey[300],
                            child: const Icon(
                              Icons.book,
                              size: 64,
                              color: Colors.grey,
                            ),
                          )
                        : null,
                  ),
                ),
              ),
              const SizedBox(height: 24),
              Text(
                widget.book.title,
                style: const TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              if (widget.book.author != null) ...[
                Text(
                  'Author: ${widget.book.author}',
                  style: const TextStyle(
                    fontSize: 16,
                    color: Colors.grey,
                  ),
                ),
                const SizedBox(height: 4),
              ],
              if (widget.book.publisher != null) ...[
                Text(
                  'Publisher: ${widget.book.publisher}',
                  style: const TextStyle(
                    fontSize: 16,
                    color: Colors.grey,
                  ),
                ),
                const SizedBox(height: 4),
              ],
              if (widget.book.yearPublished != null) ...[
                Text(
                  'Year: ${widget.book.yearPublished}',
                  style: const TextStyle(
                    fontSize: 16,
                    color: Colors.grey,
                  ),
                ),
                const SizedBox(height: 4),
              ],
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: widget.book.isAvailable
                      ? AppTheme.successColor
                      : AppTheme.errorColor,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Text(
                  widget.book.isAvailable
                      ? 'Available (${widget.book.stock})'
                      : 'Out of Stock',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              const SizedBox(height: 32),
              CustomButton(
                text: 'Reserve Book',
                onPressed: widget.book.isAvailable ? _showReservationDialog : null,
                icon: Icons.bookmark_add,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
