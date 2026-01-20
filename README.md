# School Management System

A comprehensive Laravel-based school management system designed for yeshivas and educational institutions. Built with modern PHP practices and a clean, responsive UI.

## Features

### Student Management
- Complete student profiles with photos, contact info, and medical notes
- Guardian/parent linking with multiple guardians per student
- Academic grade assignment and enrollment tracking
- Student-specific permission/leave periods

### Class & Schedule Management
- Flexible class scheduling with day-of-week specific times
- Teaching groups within classes (e.g., "Shiur Aleph", "Shiur Beis")
- Primary teacher and attendance taker assignments per student
- Class cancellation tracking

### Attendance System
- Real-time attendance marking with present/late/absent statuses
- Excused vs unexcused tracking
- Minutes late recording with time-based percentage calculations
- Left early tracking
- Class-based student permissions (excused from specific classes)
- Grid view showing all classes at a glance
- Bulk import/export via CSV

### Grades & Assessment
- Subject management with weighted scoring
- Test score tracking with automatic letter grade conversion
- Per-subject and overall GPA calculations

### Reporting
- Attendance statistics by student, class, or date range
- Time-based attendance percentages (accounts for class duration)
- Visual attendance history grid
- CSV export for all reports

### Access Control
- Role-based permissions (Admin, Teacher, Parent, Student)
- Teachers see only their assigned students
- Parents see only their children
- Students see only their own profile

### Additional Features
- Hebrew date display integration (via Hebcal API)
- Responsive design for mobile and desktop
- Dark/light mode support
- Absence reason categorization

## Tech Stack

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js
- **Database**: MySQL/MariaDB
- **Authentication**: Laravel Breeze with Spatie Permissions

## Quick Start

```bash
# Clone repository
git clone https://github.com/YOUR_USERNAME/school-management.git
cd school-management

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with your database credentials

# Setup database
php artisan migrate --seed

# Build assets
npm run build

# Start development server
php artisan serve
```

See [INSTALLATION.md](INSTALLATION.md) for detailed setup instructions.

## Default Login

After seeding:
- **Email**: admin@example.com
- **Password**: password

## Screenshots

*Coming soon*

## License

Proprietary - All rights reserved.

## Support

For issues or feature requests, please open an issue on GitHub.
