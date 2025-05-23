import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:library_app/models/borrowing.dart';
import 'package:library_app/providers/auth_provider.dart';
import 'package:library_app/providers/borrowing_provider.dart';
import 'package:library_app/utils/theme.dart';

class BorrowingsScreen extends StatefulWidget {
  const BorrowingsScreen({super.key});

  @override
  State<BorrowingsScreen> createState() => _BorrowingsScreenState();
}

class _BorrowingsScreenState extends State<BorrowingsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadBorrowings();
    });
  }

  Future<void> _loadBorrowings() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    if (authProvider.anggotaId != null) {
      final borrowingProvider = Provider.of<BorrowingProvider>(context, listen: false);
      await borrowingProvider.fetchUserBorrowings(authProvider.anggotaId!);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My Borrowings'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadBorrowings,
          ),
        ],
      ),
      body: Consumer<BorrowingProvider>(
        builder: (context, borrowingProvider, child) {
          if (borrowingProvider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }
          
          if (borrowingProvider.error != null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(
                    Icons.error_outline,
                    size: 64,
                    color: Colors.red,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Error loading borrowings: ${borrowingProvider.error}',
                    style: const TextStyle(
                      fontSize: 16,
                      color: Colors.red,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _loadBorrowings,
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }
          
          if (borrowingProvider.borrowings.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(
                    Icons.history,
                    size: 64,
                    color: Colors.grey,
                  ),
                  const SizedBox(height: 16),
                  const Text(
                    'No borrowing history found',
                    style: TextStyle(
                      fontSize: 16,
                      color: Colors.grey,
                    ),
                  ),
                ],
              ),
            );
          }
          
          return RefreshIndicator(
            onRefresh: _loadBorrowings,
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: borrowingProvider.borrowings.length,
              itemBuilder: (context, index) {
                final borrowing = borrowingProvider.borrowings[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 16),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          borrowing.bookTitle ?? 'Unknown Book',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        if (borrowing.bookAuthor != null) ...[
                          const SizedBox(height: 4),
                          Text(
                            'Author: ${borrowing.bookAuthor}',
                            style: const TextStyle(
                              fontSize: 14,
                              color: Colors.grey,
                            ),
                          ),
                        ],
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Borrow Date',
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.grey,
                                    ),
                                  ),
                                  Text(
                                    DateFormat('yyyy-MM-dd').format(borrowing.borrowDate),
                                    style: const TextStyle(
                                      fontSize: 16,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Due Date',
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.grey,
                                    ),
                                  ),
                                  Text(
                                    borrowing.dueDate != null
                                        ? DateFormat('yyyy-MM-dd').format(borrowing.dueDate!)
                                        : 'Not set',
                                    style: TextStyle(
                                      fontSize: 16,
                                      color: borrowing.isOverdue ? AppTheme.errorColor : null,
                                      fontWeight: borrowing.isOverdue ? FontWeight.bold : null,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Return Date',
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.grey,
                                    ),
                                  ),
                                  Text(
                                    borrowing.returnDate != null
                                        ? DateFormat('yyyy-MM-dd').format(borrowing.returnDate!)
                                        : 'Not returned',
                                    style: const TextStyle(
                                      fontSize: 16,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Expanded(
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                decoration: BoxDecoration(
                                  color: _getStatusColor(borrowing.status),
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                child: Text(
                                  _getStatusText(borrowing.status),
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                  ),
                                  textAlign: TextAlign.center,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }

  Color _getStatusColor(BorrowingStatus status) {
    switch (status) {
      case BorrowingStatus.borrowed:
        return AppTheme.primaryColor;
      case BorrowingStatus.returned:
        return AppTheme.successColor;
      case BorrowingStatus.overdue:
        return AppTheme.errorColor;
      default:
        return Colors.grey;
    }
  }

  String _getStatusText(BorrowingStatus status) {
    switch (status) {
      case BorrowingStatus.borrowed:
        return 'Borrowed';
      case BorrowingStatus.returned:
        return 'Returned';
      case BorrowingStatus.overdue:
        return 'Overdue';
      default:
        return 'Unknown';
    }
  }
}
