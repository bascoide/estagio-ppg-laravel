# Linux Installation

## Requirements

- PHP 8.2 or higher
- Composer
- Apache with PHP-FPM
- MySQL/MariaDB or SQLite
- PHP extensions: OpenSSL, Mbstring, PDO, Fileinfo
- Git (optional)

## Installation steps

1. Open a terminal.
2. Change to the directory where you want to install the project:

```bash
cd ~/path/to/folder
```

3. Clone the repository or copy the project folder:

```bash
git clone <repository-url>
cd estagio-ppg-laravel
```

4. Install PHP dependencies:

```bash
composer install
```

5. Copy the environment file:

```bash
cp .env.example .env
```

6. Edit `.env` and configure the application and database settings. Example for MySQL:

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

If you prefer SQLite, set `DB_CONNECTION=sqlite` and create the database file at `database/database.sqlite`.

7. Generate the application key:

```bash
php artisan key:generate
```

8. Create the database in MySQL/MariaDB or prepare the SQLite file.

9. Run migrations:

```bash
php artisan migrate
```

10. Seed the admin user for testing:

```bash
php artisan db:seed --class=AdminSeeder
```

## Configure Apache and PHP-FPM

1. Install Apache and PHP-FPM if needed:

```bash
sudo apt update
sudo apt install apache2 php8.2-fpm libapache2-mod-fcgid
```

2. Enable required Apache modules:

```bash
sudo a2enmod proxy_fcgi setenvif rewrite
sudo a2enconf php8.2-fpm
```

3. Create or update the Apache site configuration. Example:

```apache
<VirtualHost *:80>
    ServerName ppg.local
    DocumentRoot /home/username/path/to/estagio-ppg-laravel/public

    <Directory /home/username/path/to/estagio-ppg-laravel/public>
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch ".+\.php$">
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/ppg-error.log
    CustomLog ${APACHE_LOG_DIR}/ppg-access.log common
</VirtualHost>
```

4. Enable the site and restart Apache:

```bash
sudo a2ensite ppg.conf
sudo systemctl restart apache2
```

5. If you use `ppg.local`, add it to `/etc/hosts`:

```bash
sudo nano /etc/hosts
```

Add:

```text
127.0.0.1 ppg.local
```

## Running the application

Open your browser and visit:

```text
http://127.0.0.1
```

or, if using the local hostname:

```text
http://ppg.local
```

## Notes

- Ensure Apache has access to the project folder and that `storage/` and `bootstrap/cache/` are writable.
- Configure `MAIL_` settings in `.env` if you need email notifications.
- The built-in PHP server (`php artisan serve`) is useful for testing, but Apache + PHP-FPM is recommended for a production-like setup.
