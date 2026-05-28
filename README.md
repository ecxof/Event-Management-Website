# Event-Management-Website
An anime event management web application for Taylor’s University, created using PHP, JavaScript, and MongoDB

Tools Used:
1. XAMPP
2. Frontend: HTML, CSS
3. Backend: PHP
4. Database: MongoDB



## Connect PHP with MongoDB
To connect PHP with MongoDB, follow these steps:

### 1. Install PHP MongoDB Extension

#### macOS / XAMPP

```bash
brew install autoconf
sudo /Applications/XAMPP/xamppfiles/bin/pecl install mongodb
```

Here are the selections you can choose when installing the extension:

```bash
Enable developer flags? [no] :  Enter
Enable code coverage? [no] :  Enter
Use system libraries...? [no] :  Enter
Enable client-side encryption? [auto] : no
Enable crypto and TLS? [auto] : darwin
```

After installing the extension, edit this XAMPP `php.ini` file:

```bash
/Applications/XAMPP/xamppfiles/etc/php.ini
```

Add this line at the end of the file:

```ini
extension=mongodb.so
```

Restart XAMPP Apache after editing `php.ini`.

To check whether the extension is enabled:

```bash
/Applications/XAMPP/xamppfiles/bin/php -m | grep mongodb
```

#### Windows / XAMPP

First, check your XAMPP PHP version and extension folder:

```bat
C:\xampp\php\php -v
C:\xampp\php\php -i | findstr /i "Thread Architecture extension_dir"
```

Download the matching MongoDB PHP extension DLL from the official PECL MongoDB package page. Make sure the DLL matches your PHP version, architecture, and thread safety setting.

Copy the downloaded file into:

```text
C:\xampp\php\ext
```

The file should be named like this:

```text
php_mongodb.dll
```

After that, edit this XAMPP `php.ini` file:

```text
C:\xampp\php\php.ini
```

Add this line at the end of the file:

```ini
extension=php_mongodb.dll
```

Restart XAMPP Apache after editing `php.ini`.

To check whether the extension is enabled:

```bat
C:\xampp\php\php -m | findstr mongodb
```

### 2. Install Composer

#### macOS / XAMPP

Change the directory to your project and download the Composer installer:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/Event-Management-Website
curl -sS https://getcomposer.org/installer -o composer-setup.php
```

Install Composer into this project:

```bash
/Applications/XAMPP/xamppfiles/bin/php composer-setup.php
```

#### Windows / XAMPP

The recommended way is to install Composer globally using the official Windows installer from the Composer website.
- [Get Composer Installer](https://getcomposer.org/doc/00-intro.md#globally)

After installation, open Command Prompt or PowerShell and check:

```bat
composer --version
```

Then go to the project folder:

```bat
cd C:\xampp\htdocs\Event-Management-Website
```

### 3. Install PHP Dependencies

This project already includes `composer.json` and `composer.lock`, so run this command to install the PHP dependencies and generate the `vendor/` folder.

#### macOS / project Composer

```bash
php composer.phar install
```

#### Windows / global Composer

```bat
composer install
```

The required PHP packages include:

```json
{
    "mongodb/mongodb": "^2.3",
    "cloudinary/cloudinary_php": "^3.1"
}
```

If the Cloudinary package is missing in your local `composer.json`, install it through Composer:

#### macOS / project Composer

```bash
php composer.phar require cloudinary/cloudinary_php
```

#### Windows / global Composer

```bat
composer require cloudinary/cloudinary_php
```

After installing or changing Composer packages, make sure the `vendor/` folder exists because the PHP APIs load dependencies through `vendor/autoload.php`.

### 4. Create connect_db/config.php

`connect_db/config.php` is ignored by Git because it contains the MongoDB connection string and Cloudinary credentials. Each developer needs to create this file locally.

Create this file:

```text
connect_db/config.php
```

Then add this code:

```php
<?php

return [
    'mongodb_uri' => 'mongodb+srv://username:password@cluster.mongodb.net/',
    'database' => 'event_management',

    'cloudinary' => [
        'cloud_name' => 'your_cloud_name',
        'api_key' => 'your_api_key',
        'api_secret' => 'your_api_secret',
    ],
];
```

Replace `mongodb_uri` with the connection string copied from MongoDB Atlas. If the password contains special characters such as `@`, `#`, `/`, `:`, `?`, `&`, or `%`, encode the password before using it in the URI.

Replace the Cloudinary values with the credentials from your Cloudinary dashboard:

- `cloud_name`: your Cloudinary cloud name
- `api_key`: your Cloudinary API key
- `api_secret`: your Cloudinary API secret

Do not commit real Cloudinary credentials to Git.

## Using Cloudinary Uploads

The project uploads images through this backend endpoint:

```text
api/uploads/image.php
```

The endpoint reads Cloudinary credentials from `connect_db/config.php`, uploads the image to Cloudinary, and returns the permanent HTTPS image URL. The frontend can then save that returned URL in MongoDB.

Send a `POST` request using `multipart/form-data`:

```text
api/uploads/image.php?type=post
```

The image file must be sent in the field named:

```text
image
```

Supported upload types:

| Type | Cloudinary folder |
| --- | --- |
| `avatar` | `event-management/avatars` |
| `event` | `event-management/events` |
| `post` | `event-management/posts` |

Supported image formats:

- JPG
- PNG
- WEBP
- GIF

Maximum file size:

```text
5MB
```

Successful upload response example:

```json
{
    "success": true,
    "image_url": "https://res.cloudinary.com/your_cloud_name/image/upload/...",
    "public_id": "event-management/posts/..."
}
```

Use `image_url` as the value to store in MongoDB for avatars, events, or posts.

## References

- PHP MongoDB extension installation: https://www.php.net/manual/en/mongodb.installation.php
- MongoDB PHP Library documentation: https://www.mongodb.com/docs/php-library/current/
- MongoDB PHP driver overview: https://www.mongodb.com/docs/drivers/php-drivers/
- Composer download and installation: https://getcomposer.org/download/
- Cloudinary PHP SDK: https://cloudinary.com/documentation/php_integration
- XAMPP downloads and documentation: https://www.apachefriends.org/
