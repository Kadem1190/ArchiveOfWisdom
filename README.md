# Library Management System - Admin Panel

This is the admin panel for the Library Management System, designed to manage users, books, and borrowings.

## Installation

1. Clone or download lib-server and admin_panel branch  to your web server directory (e.g., `htdocs` for XAMPP).
2. Clone or download main branch to your Documents (this is the flutter app)
3. Make sure your web server (Apache) and MySQL are running.
4. Import the database on the main branch
5. Open your browser and navigate to `http://localhost/admin_panel/install.php` to set up the database.
6. Use `flutter run ` to run the app on your phone (make sure adb is connected)
7. After successful installation, you can log in with the default admin credentials:
   - Username: `admin`
   - Password: `admin123`
8. It's recommended to change the default password after your first login.

## Features

- User Management: Approve registrations, activate/deactivate users
- Book Management: Add, edit, and delete books
- Borrowing Management: Track book borrowings and returns
- Statistics: View library usage statistics
- Admin Users: Manage admin accounts

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache recommended)

## Troubleshooting

If you encounter any issues:

1. Make sure your database credentials in `includes/config.php` are correct.
2. Check that your web server has write permissions for the `uploads` directory.
3. If you see database errors, run the `install.php` script again to ensure all tables are properly created.

## Security Notes

- Change the default admin password immediately after installation.
- Consider implementing HTTPS for secure data transmission.
- Regularly backup your database.
"# ArchiveOfWisdom" 
