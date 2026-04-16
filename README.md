# PPG • Internship Protocol Management

A Laravel-based web application designed to support the management of internship protocols, academic documents, and administrative validation workflows in the ISCAP/PPG context.

The system allows students to register, complete dynamic forms, generate PDF or DOCX documents, upload signed final versions, and follow the validation process. The administrative area includes management of courses, users, documents, reports, and activity logs.

---

## Main features

### User area
- Registration and authentication
- Account verification by email
- Document selection by course and course type
- Dynamic form filling
- PDF and DOCX document generation and download
- Upload of the final signed PDF document

### Administrative area
- Management of administrators, users, and courses
- Upload, activation, and deactivation of document templates
- Acceptance, rejection, and validation of protocols
- Management of presidential email addresses for validation
- Viewing pending and validated documents
- Report generation by professor
- Excel data export
- Activity log tracking

---

## Technologies used

- PHP 8.2+
- Laravel 12
- Blade templates
- MySQL / MariaDB or SQLite
- PHPMailer
- Dompdf
- PhpWord
- PhpSpreadsheet
- Tailwind/static CSS for the interface

---

## Requirements

Before starting, make sure you have the following installed:

- PHP 8.2 or higher
- Composer
- A local web server such as XAMPP
- MySQL/MariaDB or SQLite
- Common Laravel PHP extensions such as OpenSSL, Mbstring, PDO, and Fileinfo

> Note: the project can run with the already generated stylesheet, so Node.js is not required for basic execution.

---

## Installation

### 1. Clone the project

```bash
git clone <repository-url>
cd estagio-ppg-laravel
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create the environment file

```bash
copy .env.example .env
```

If you are using PowerShell:

```powershell
Copy-Item .env.example .env
```

### 4. Configure the application

Generate the application key:

```bash
php artisan key:generate
```

Then configure the database in the .env file.

#### Example using MySQL / XAMPP

```env
APP_NAME=PPG
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=estagio_ppg
DB_USERNAME=root
DB_PASSWORD=
```

> The default .env.example file uses SQLite. If you are working with XAMPP, update these variables before running the migrations.

### 5. Run migrations

```bash
php artisan migrate
```

### 6. Create a local admin for testing

```bash
php artisan db:seed --class=AdminSeeder
```

> Recommended for development environments only.

### 7. Start the server

```bash
php artisan serve
```

Then open in your browser:

```text
http://127.0.0.1:8000
```

---

## Email configuration

The system sends verification, approval, rejection, and validation notifications. To enable this, configure an SMTP server in the .env file.

Example:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=username
MAIL_PASSWORD=password
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="PPG"
```

If SMTP is not configured, some email-related features may not work correctly.

---

## Main system flow

1. The user creates an account and confirms their email.
2. The user selects the document type for their course.
3. The required dynamic fields are filled in.
4. The system generates the protocol and allows export to PDF or DOCX.
5. The administration reviews the document and decides whether to accept, reject, or validate it.
6. After approval, the user uploads the final signed version.
7. The system keeps a history and logs of relevant actions.

---

## Project structure summary

```text
app/
 ├─ Http/Controllers/   # Application logic
 ├─ Models/             # Eloquent models
 └─ Services/           # Helper services such as email sending

routes/
 └─ web.php             # Main application routes

resources/views/        # Blade interfaces

database/
 ├─ migrations/         # Database structure
 └─ seeders/            # Seeders for local setup
```

---

## Useful commands

```bash
composer dev
composer test
php artisan migrate:fresh
php artisan config:clear
php artisan route:list
```

---

## Notes

- The project was designed for academic and administrative use.
- Some features depend on email delivery and valid PDF files.
- On Windows, running the project with XAMPP is suitable and compatible with MySQL/MariaDB.

---

## License

Project intended for academic/institutional context. Adjust the license according to the rules of the organization or responsible team.
