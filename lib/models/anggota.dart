class Anggota {
  final int? anggotaId;
  final String name;
  final String nim;
  final String className;
  final String? address;
  final String? placeOfBirth;
  final DateTime dateOfBirth;
  final String gender;

  Anggota({
    this.anggotaId,
    required this.name,
    required this.nim,
    required this.className,
    this.address,
    this.placeOfBirth,
    required this.dateOfBirth,
    required this.gender,
  });

  factory Anggota.fromJson(Map<String, dynamic> json) {
    return Anggota(
      anggotaId: json['anggota_id'],
      name: json['name'],
      nim: json['nim'],
      className: json['class'],
      address: json['address'],
      placeOfBirth: json['place_of_birth'],
      dateOfBirth: DateTime.parse(json['date_of_birth']),
      gender: json['gender'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'anggota_id': anggotaId,
      'name': name,
      'nim': nim,
      'class': className,
      'address': address,
      'place_of_birth': placeOfBirth,
      'date_of_birth': dateOfBirth.toIso8601String().split('T')[0],
      'gender': gender,
    };
  }
}
