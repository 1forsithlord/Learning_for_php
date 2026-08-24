# Learning for PHP

A simple PHP and MySQL user-listing application demonstrating CRUD operations.

## Features

- Create users with name, email, gender, status, password, and profile picture
- Read and display active users from MySQL
- Edit users in an in-page modal
- Soft delete users using the `is_deleted` flag
- JavaScript and PHP validation
- Prepared MySQL statements
- JPG and PNG uploads up to 1 MB
- Same-page success messages and delete toast

## Requirements

- XAMPP
- Apache
- MySQL
- PHP 8 or later

## Setup

1. Copy this project into:

   ```text
   /Applications/XAMPP/xamppfiles/htdocs/Learning_for_php
   ```

2. Start **Apache** and **MySQL** in XAMPP.

3. Open phpMyAdmin at:

   ```text
   http://localhost/phpmyadmin
   ```

4. Select the existing `User listing` database.

5. Run the migration statements in `database.sql`.

6. Make sure the `uploads` directory exists and is writable by Apache.

7. Open the application:

   ```text
   http://localhost/Learning_for_php/index.php
   ```

## Database

The application uses the `users` table with these important fields:

- `id`: primary key
- `full_name`: user name
- `email_id`: user email
- `gender`: `M`, `F`, or `O`
- `password`: password value
- `status`: `1` for active or `0` for inactive
- `profile_picture`: uploaded image path
- `is_deleted`: `0` for visible or `1` for soft-deleted

`database.sql` contains migrations for the existing table. It does not create a new database or table.

## Project Files

- `index.php`: registration form, user list, JavaScript validation, edit modal, and same-page actions
- `ajax.php`: creates a user
- `edit.php`: updates a user
- `delete.php`: soft-deletes a user
- `connection.php`: MySQL connection and image-upload helper
- `database.sql`: database migrations
- `uploads/`: uploaded profile pictures

## CRUD Flow

### Create

The registration form sends data to `ajax.php` using JavaScript `fetch()`.

### Read

`index.php` displays users where `is_deleted = 0`.

### Update

Clicking **Edit** opens a modal. The form submits the changes to `edit.php`.

### Delete

Clicking **Delete** sends a background POST request to `delete.php`. The record is kept in the database and marked with `is_deleted = 1`.

## Password Warning

The current code temporarily stores passwords as plain text because password hashing was removed during development. Before using this project outside local testing, restore password hashing with:

```php
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
```

Store `$hashedPassword` instead of `$password` in the database.

## Validation

PHP syntax can be checked with:

```bash
php -l index.php
php -l ajax.php
php -l edit.php
php -l delete.php
php -l connection.php
```
