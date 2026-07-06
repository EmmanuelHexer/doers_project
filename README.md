# USTED-K Gym Center — Management System

A PHP + MySQL web application for managing a gym: members, instructors, training
sections, memberships, payments, and reports. Includes both an **admin panel** and a
**member portal**.

## Requirements
- PHP 7.4+ (tested on XAMPP with PHP 8 / MariaDB 10.4)
- MySQL / MariaDB
- A web server (Apache via XAMPP recommended)

## Setup

1. **Install XAMPP** (or any PHP + MySQL stack) and start **Apache** + **MySQL**.
2. **Get the code** into your web root, e.g. `C:\xampp\htdocs\deep`.
3. **Create the database** — open phpMyAdmin (http://localhost/phpmyadmin) →
   **Import** → choose `sql/schema.sql` → **Go**.
   This creates the `gym_database` database, all tables, and a default admin account.
4. **Check the DB credentials** in `config/database.php` match your MySQL setup
   (defaults: host `localhost`, user `root`, blank password, database `gym_database`).
5. Open **http://localhost/deep** in your browser.

## Default admin login
After importing `schema.sql`, log in at **`admin_login.php`**:

| Username | Password |
|----------|----------|
| `admin`  | `admin123` |

> Change this password after your first login.

## Members
Members self-register at **`register.php`**. New accounts start as **pending** and must
be **approved** by an admin (Admin -> Members -> approve) before they can log in.

## Features
- **Admin:** members (approve/suspend/edit/delete), instructors, training types &
  sections, memberships, payments, backups, and reports (CSV export + printable PDF).
- **Member:** dashboard, profile, weekly training schedule, book/cancel sessions,
  make payments, payment history.

## Currency & locale
Amounts are shown in Ghana Cedi; timezone is Africa/Accra.
