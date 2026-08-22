# Event-Management-Website

An anime event management web application for Taylor's University, created using PHP,
JavaScript, and MongoDB.

Students browse and join anime events; admins create and manage them. The project also
includes a social feed with posts, images, likes, comments, and shares, plus user
profiles.

## Tools Used

| Layer | Technology |
| --- | --- |
| Server | XAMPP (Apache + PHP) |
| Frontend | HTML, CSS, vanilla JavaScript (`frontend/js/app.js`) |
| Backend | PHP (JSON API, one file per endpoint) |
| Database | MongoDB Atlas |
| Image hosting | Cloudinary |

## Prerequisites

- **PHP 8.1 or newer.** The code uses array spread with string keys
  (`api/pagination.php`), plus `match` expressions and `str_contains`. PHP 8.0 and
  below fails at runtime, not at install time. Check with `php -v`
- **XAMPP** (or any Apache + PHP setup)
- **Composer**
- **A MongoDB Atlas cluster** and its connection string
- **A Cloudinary account** for image uploads



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

### 5. Seed the Database

MongoDB creates collections on first write, so a brand-new cluster starts empty and the
event and post pages have nothing to show. `scripts/seed_database.php` builds every
collection the API uses, adds the indexes, and fills them with sample users, events,
registrations, posts, likes, comments, and shares.

Run it from the project root with the CLI PHP that has the mongodb extension:

```bash
php scripts/seed_database.php
```

It reads the connection string and database name from `connect_db/config.php`, the same
file the website uses, so create that file first.

The script refuses to run when the target database already holds records. Pass
`--force` to drop those collections and rebuild them from scratch:

```bash
php scripts/seed_database.php --force
```

`--force` permanently deletes the existing `users`, `events`, `registrations`, `posts`,
`postLikes`, `postComments`, and `postShares` collections.

The seeded accounts all use placeholder passwords, printed in the script output. The
admin account is:

```text
admin@taylors.edu.my / Admin1234
```

Change these passwords before showing the site to anyone outside the group.

### 6. Run the Application

Place the project inside the XAMPP web root so Apache can serve it:

```text
macOS:   /Applications/XAMPP/xamppfiles/htdocs/Event-Management-Website
Windows: C:\xampp\htdocs\Event-Management-Website
```

Start Apache from the XAMPP control panel, then open the login page:

```text
http://localhost/Event-Management-Website/frontend/Login.html
```

Register an account there, or log in with an existing one. After login the app
redirects to `HomePage.html`. The other pages are `Event.html`, `Post.html`,
`Profile.html`, and `Register.html`.

The frontend calls the API through the relative path `../api`, so the project folder
name must stay `Event-Management-Website` unless you also update `API_BASE` at the top
of `frontend/js/app.js`.

### 7. Create an Admin Account

`api/auth/register.php` always creates users with `role` set to `user`, so an admin
cannot be created through the interface. Every endpoint under `api/admin/events/`
requires an admin session, which means event creation and management stay unreachable
until a user is promoted directly in MongoDB.

The seed script in step 5 already creates one admin account, so this step is only needed
for an unseeded database or to promote a second admin.

Register a normal account first, then promote it from the MongoDB Atlas UI or `mongosh`:

```javascript
use event_management

db.users.updateOne(
    { email: "your_email@example.com" },
    { $set: { role: "admin" } }
)
```

Log out and log back in so the new role is written into the session. Admins are
redirected to the event management page after login.

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

## API Reference

Every endpoint is a standalone PHP file, so the file path is the route. They all return
JSON shaped as `{ "success": bool, "message": string, ... }` and accept either a JSON
request body or ordinary form fields.

Access levels: **public** needs no session, **login** requires a logged-in user, and
**admin** requires a user whose `role` is `admin`.

### Authentication

| Method | Endpoint | Access | Purpose |
| --- | --- | --- | --- |
| POST | `api/auth/register.php` | public | Create an account and start a session |
| POST | `api/auth/login.php` | public | Log in |
| POST | `api/auth/logout.php` | public | Destroy the session |

### Events

| Method | Endpoint | Access | Purpose |
| --- | --- | --- | --- |
| GET | `api/events/list.php` | public | Paginated event list with joined counts and free slots |
| GET | `api/events/detail.php` | public | One event; includes the caller's registration when logged in |

### Admin - Events

| Method | Endpoint | Access | Purpose |
| --- | --- | --- | --- |
| POST | `api/admin/events/create.php` | admin | Create an event |
| POST | `api/admin/events/update.php` | admin | Update an event |
| POST | `api/admin/events/delete.php` | admin | Soft-delete an event |
| GET | `api/admin/events/detail.php` | admin | Event detail, including soft-deleted records |

### Registrations

| Method | Endpoint | Access | Purpose |
| --- | --- | --- | --- |
| POST | `api/registrations/join.php` | login | Join an event |
| POST | `api/registrations/cancel.php` | login | Cancel a registration |
| GET | `api/registrations/my_registrations.php` | login | The caller's registrations |

### Posts

| Method | Endpoint | Access | Purpose |
| --- | --- | --- | --- |
| GET | `api/posts/list.php` | public | Paginated feed |
| GET | `api/posts/detail.php` | public | One post with its comments |
| POST | `api/posts/create.php` | login | Create a post |
| POST | `api/posts/update.php` | login | Edit own post |
| POST | `api/posts/delete.php` | login | Delete own post; admins may delete any |
| POST | `api/posts/like.php` | login | Like a post |
| POST | `api/posts/unlike.php` | login | Remove a like |
| POST | `api/posts/comment.php` | login | Comment on a post |
| POST | `api/posts/share.php` | login | Increment the share count |

### Profile

| Method | Endpoint | Access | Purpose |
| --- | --- | --- | --- |
| GET | `api/profile/me.php` | login | The current session's user |
| GET | `api/profile/view.php` | login | Another user's public profile |
| POST | `api/profile/update.php` | login | Update own profile |
| GET | `api/profile/my_posts.php` | login | The caller's posts |
| GET | `api/profile/liked_posts.php` | login | Posts the caller liked |

### Uploads

| Method | Endpoint | Access | Purpose |
| --- | --- | --- | --- |
| POST | `api/uploads/image.php` | login | Upload an image to Cloudinary; see the section above |

### Pagination

List endpoints accept `?page=` and `?limit=` (default 6, maximum 50) and return a
`pagination` object with `page`, `limit`, `total`, `total_pages`, `has_previous`, and
`has_next`. A page number beyond the last page is clamped to the last page.

### Status Codes

| Code | Meaning |
| --- | --- |
| 401 | Not logged in |
| 403 | Logged in but not an admin |
| 405 | Wrong HTTP method |
| 409 | Email already registered |
| 422 | Validation failed |

## Feature Documentation

The `explaination/` folder contains detailed walkthroughs of how each feature works:

| Document | Topic |
| --- | --- |
| [1.database.md](explaination/1.database.md) | MongoDB collections and structure |
| [2.login.md](explaination/2.login.md) | Login flow |
| [3.register.md](explaination/3.register.md) | Registration and validation |
| [4.session.md](explaination/4.session.md) | Session handling and cookie settings |
| [5.userrole.md](explaination/5.userrole.md) | User and admin roles |
| [6.interaction_db.md](explaination/6.interaction_db.md) | How the API talks to MongoDB |
| [7.join_cancel_event.md](explaination/7.join_cancel_event.md) | Joining and cancelling events |
| [8.image.md](explaination/8.image.md) | Image uploads |
| [9.show_event_post.md](explaination/9.show_event_post.md) | Rendering events and posts |
| [10.pagination.md](explaination/10.pagination.md) | Pagination |
| [11.logout.md](explaination/11.logout.md) | Logout |
| [12.create_event.md](explaination/12.create_event.md) | Creating events |

## References

- PHP MongoDB extension installation: https://www.php.net/manual/en/mongodb.installation.php
- MongoDB PHP Library documentation: https://www.mongodb.com/docs/php-library/current/
- MongoDB PHP driver overview: https://www.mongodb.com/docs/drivers/php-drivers/
- Composer download and installation: https://getcomposer.org/download/
- Cloudinary PHP SDK: https://cloudinary.com/documentation/php_integration
- XAMPP downloads and documentation: https://www.apachefriends.org/
