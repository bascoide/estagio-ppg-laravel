# Windows Installation

## Requirements

- PHP 8.2 or higher
- Composer
- XAMPP or another local Apache + MySQL/MariaDB setup
- PHP extensions: OpenSSL, Mbstring, PDO, Fileinfo
- Git (optional)

## Installation steps

1. Open Command Prompt or PowerShell.
2. Change to the directory where you want to install the project:

```powershell
cd C:\path\to\folder
```

3. Clone the repository or copy the project folder:

```powershell
git clone <repository-url>
cd estagio-ppg-laravel
```

4. Install PHP dependencies:

```powershell
composer install
```

5. Copy the environment file:

```powershell
Copy-Item .env.example .env
```

6. Open `.env` and configure the application and database settings. Example for XAMPP:

```env
APP_NAME=PPG
APP_URL=http://127.0.0.1

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=estagio_ppg
DB_USERNAME=root
DB_PASSWORD=
```

7. Generate the application key:

```powershell
php artisan key:generate
```

8. Create the database in MySQL/MariaDB using phpMyAdmin or the command line.

9. Run migrations:

```powershell
php artisan migrate
```

10. Seed the admin user for testing:

```powershell
php artisan db:seed --class=AdminSeeder
```

## Configure Apache and PHP-FPM

1. Ensure Apache is running in XAMPP.
2. Enable `mod_proxy_fcgi` and `mod_alias` in Apache, if available.
3. Configure Apache to use PHP-FPM by editing `httpd.conf` or creating a site file.

Example Apache config for PHP-FPM:

```apache
LoadModule proxy_module modules/mod_proxy.so
LoadModule proxy_fcgi_module modules/mod_proxy_fcgi.so

<VirtualHost *:80>
    ServerName ppg.local
    DocumentRoot "C:/path/to/estagio-ppg-laravel/public"

    <Directory "C:/path/to/estagio-ppg-laravel/public">
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch ".+\.php$">
        SetHandler "proxy:fcgi://127.0.0.1:9000"
    </FilesMatch>

    ErrorLog "logs/ppg-error.log"
    CustomLog "logs/ppg-access.log" common
</VirtualHost>
```

4. Update the `DocumentRoot` path to your project `public` folder.
5. Restart Apache.

## Running the application

After Apache and PHP-FPM are configured, open your browser:

```text
http://127.0.0.1
```

If you use a custom host name like `ppg.local`, add it to `C:\Windows\System32\drivers\etc\hosts`.

## Notes

- Make sure Apache can access the project folder and that `storage/` and `bootstrap/cache/` are writable.
- Set `MAIL_` variables in `.env` if you need email notifications.
- If you prefer the built-in PHP server, use `php artisan serve` for local testing, but Apache + PHP-FPM is recommended for production.
