import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:library_app/providers/auth_provider.dart';
import 'package:library_app/providers/book_provider.dart';
import 'package:library_app/providers/borrowing_provider.dart';
import 'package:library_app/screens/auth/auth_screen.dart';
import 'package:library_app/screens/books/books_screen.dart';
import 'package:library_app/screens/borrowings/borrowings_screen.dart';
import 'package:library_app/screens/profile/profile_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _currentIndex = 0;
  final List<Widget> _screens = [
    const BooksScreen(),
    const BorrowingsScreen(),
    const ProfileScreen(),
  ];

  @override
  void initState() {
    super.initState();
    // Pre-fetch books and borrowings data
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      final bookProvider = Provider.of<BookProvider>(context, listen: false);
      final borrowingProvider = Provider.of<BorrowingProvider>(context, listen: false);
      
      // Load books
      bookProvider.fetchBooks();
      
      // Load borrowings if user is logged in
      if (authProvider.isLoggedIn && authProvider.anggotaId != null) {
        borrowingProvider.fetchUserBorrowings(authProvider.anggotaId!);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    
    if (!authProvider.isLoggedIn) {
      return const AuthScreen();
    }

    return Scaffold(
      body: _screens[_currentIndex],
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.menu_book),
            label: 'Books',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.history),
            label: 'Borrowings',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }
}
