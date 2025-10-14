# CloudCanvas

A modern image sharing and gallery application built with PHP and MySQL.

## Features

- User registration and authentication
- Image upload with size and type validation
- Public/private image sharing
- Responsive gallery view
- User dashboard with image management
- Secure file storage in database

## Installation

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your database
4. Run `php setup_database.php` to create tables
5. Configure your web server to point to the project root

## Requirements

- PHP 7.4+
- MySQL 5.7+
- PDO MySQL extension
- GD extension for image processing

## Security Features

- Prepared statements to prevent SQL injection
- CSRF protection
- Input sanitization
- Secure session management
- File type validation
