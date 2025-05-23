enum UserRole { admin, user }
enum UserStatus { inactive, active, banned }
enum RegistrationStatus { pending, approved }

class User {
  final int? userId;
  final int anggotaId;
  final String username;
  final String? passwordHash;
  final String? fullName;
  final UserRole role;
  final UserStatus status;
  final RegistrationStatus registrationStatus;
  final DateTime createdAt;

  User({
    this.userId,
    required this.anggotaId,
    required this.username,
    this.passwordHash,
    this.fullName,
    required this.role,
    required this.status,
    required this.registrationStatus,
    required this.createdAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      userId: json['user_id'],
      anggotaId: json['anggota_id'],
      username: json['username'],
      passwordHash: json['password_hash'],
      fullName: json['full_name'],
      role: UserRole.values.firstWhere(
        (e) => e.toString().split('.').last == json['role'],
        orElse: () => UserRole.user,
      ),
      status: UserStatus.values.firstWhere(
        (e) => e.toString().split('.').last == json['status'],
        orElse: () => UserStatus.inactive,
      ),
      registrationStatus: RegistrationStatus.values.firstWhere(
        (e) => e.toString().split('.').last == json['registration_status'],
        orElse: () => RegistrationStatus.pending,
      ),
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'user_id': userId,
      'anggota_id': anggotaId,
      'username': username,
      'password_hash': passwordHash,
      'full_name': fullName,
      'role': role.toString().split('.').last,
      'status': status.toString().split('.').last,
      'registration_status': registrationStatus.toString().split('.').last,
      'created_at': createdAt.toIso8601String(),
    };
  }

  bool get canLogin => status == UserStatus.active && registrationStatus == RegistrationStatus.approved;
}
