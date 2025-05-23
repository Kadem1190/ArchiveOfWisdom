class Book {
  final int? bookId;
  final String title;
  final String? author;
  final String? publisher;
  final int? yearPublished;
  final String? coverUrl;
  final int stock;

  Book({
    this.bookId,
    required this.title,
    this.author,
    this.publisher,
    this.yearPublished,
    this.coverUrl,
    required this.stock,
  });

  factory Book.fromJson(Map<String, dynamic> json) {
    return Book(
      bookId: json['book_id'] is String ? int.tryParse(json['book_id']) : json['book_id'],
      title: json['title'] ?? '',
      author: json['author'],
      publisher: json['publisher'],
      yearPublished: json['year_published'] is String ? int.tryParse(json['year_published']) : json['year_published'],
      coverUrl: json['cover_url'],
      stock: json['stock'] is String ? int.tryParse(json['stock']) ?? 0 : json['stock'] ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'book_id': bookId,
      'title': title,
      'author': author,
      'publisher': publisher,
      'year_published': yearPublished,
      'cover_url': coverUrl,
      'stock': stock,
    };
  }

  bool get isAvailable => stock > 0;
}
