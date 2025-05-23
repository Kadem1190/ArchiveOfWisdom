enum BorrowingStatus { borrowed, returned, overdue }

class Borrowing {
  final int? borrowingId;
  final int anggotaId;
  final int bookId;
  final DateTime borrowDate;
  final DateTime? dueDate;
  final DateTime? returnDate;
  final BorrowingStatus status;

  // For UI display
  final String? bookTitle;
  final String? bookAuthor;

  Borrowing({
    this.borrowingId,
    required this.anggotaId,
    required this.bookId,
    required this.borrowDate,
    this.dueDate,
    this.returnDate,
    required this.status,
    this.bookTitle,
    this.bookAuthor,
  });

  factory Borrowing.fromJson(Map<String, dynamic> json) {
    return Borrowing(
      borrowingId: json['borrowing_id'],
      anggotaId: json['anggota_id'],
      bookId: json['book_id'],
      borrowDate: DateTime.parse(json['borrow_date']),
      dueDate: json['due_date'] != null ? DateTime.parse(json['due_date']) : null,
      returnDate: json['return_date'] != null ? DateTime.parse(json['return_date']) : null,
      status: BorrowingStatus.values.firstWhere(
        (e) => e.toString().split('.').last == json['status'],
        orElse: () => BorrowingStatus.borrowed,
      ),
      bookTitle: json['book_title'],
      bookAuthor: json['book_author'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'borrowing_id': borrowingId,
      'anggota_id': anggotaId,
      'book_id': bookId,
      'borrow_date': borrowDate.toIso8601String().split('T')[0],
      'due_date': dueDate?.toIso8601String().split('T')[0],
      'return_date': returnDate?.toIso8601String().split('T')[0],
      'status': status.toString().split('.').last,
    };
  }

  bool get isOverdue {
    if (status == BorrowingStatus.returned) return false;
    if (dueDate == null) return false;
    return DateTime.now().isAfter(dueDate!);
  }
}
