# School Management System - Installation Guide

A comprehensive Laravel-based school management system for tracking students, classes, attendance, grades, and more.

## Features

- **Student Management**: Track student profiles, enrollment, guardians, and medical notes
- **Class Management**: Define classes with schedules, teaching groups, and teacher assignments
- **Attendance Tracking**: Mark attendance with present/late/absent statuses, excused options, and time-based percentage calculations
- **Grade Management**: Record test scores by subject with automatic letter grade conversion
- **Permission/Leave System**: Class-based permission tracking for student absences
- **Role-Based Access Control**: Admin, Teacher, Parent, and Student roles with appropriate permissions
- **Reporting**: Comprehensive attendance statistics with export capabilities
- **Hebrew Date Integration**: Automatic Hebrew date display via Hebcal API

## Requirements

- PHP 8.2 or higher
- Composer
- MySQL 8.0+ or MariaDB 10.4+
- Node.js 18+ and NPM (for asset compilation)
- Web server (Apache/Nginx)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/YOUR_USERNAME/school-management.git
cd school-management
```

### 2. Install PHP Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

For development:
```bash
composer install
```

### 3. Install Node Dependencies & Build Assets

```bash
npm install
npm run build
```

### 4. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:

```env
# Application
APP_NAME="Your School Name"
APP_URL=https://your-domain.com
APP_TIMEZONE=America/New_York  # Set your timezone

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Mail (for password resets)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_FROM_ADDRESS=admin@your-school.com
```

### 5. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed initial data (roles, permissions, admin user)
php artisan db:seed
```

### 6. Storage Link

```bash
php artisan storage:link
```

### 7. Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 8. Web Server Configuration

#### Apache (.htaccess included)
Point your virtual host to the `public` directory.

#### Nginx Example
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/school-management/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Default Admin Account

After seeding, login with:
- **Email**: admin@example.com
- **Password**: password

**Important**: Change this password immediately after first login!

## Post-Installation Setup

### 1. Create Academic Grades
Navigate to Settings > Academic Grades and create your grade levels (e.g., Grade 1, Grade 2, etc.)

### 2. Create Subjects
Navigate to Settings > Subjects and add your curriculum subjects

### 3. Create Classes
Navigate to Classes and create your class sections with schedules

### 4. Add Students
Navigate to Students and add student records, assigning them to grades and classes

### 5. Create Teacher Accounts
Navigate to Users and create teacher accounts with the "Teacher" role

### 6. Assign Teaching Groups
Edit each class to create teaching groups and assign primary teachers and students

## User Roles

| Role | Permissions |
|------|-------------|
| Super Admin | Full system access |
| Admin | Manage students, classes, attendance, grades |
| Billing Admin | View students, manage billing-related data |
| Teacher | View/mark attendance for assigned students, view assigned student profiles |
| Parent | View own children's profiles and attendance |
| Student | View own profile and attendance |

## Updating

```bash
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate
npm install
npm run build
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Backup

### Database
```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### Files
```bash
tar -czvf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public
```

## Troubleshooting

### Permission Errors
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Blank Page / 500 Error
```bash
php artisan config:clear
php artisan cache:clear
tail -f storage/logs/laravel.log
```

### Session Issues
Ensure the `sessions` table exists:
```bash
php artisan session:table
php artisan migrate
```

## Support

For issues or feature requests, please open an issue on GitHub.

## License

This software is proprietary. Contact the author for licensing information.
