# SWGunpla E-commerce Project

This is a full-stack e-commerce website for "SWGunpla", a Gundam model kit shop. It was built as a course project for Open Source Programming (HUIT), using Laravel 12, Bootstrap 5, and MySQL.

The application features a complete user workflow (product browsing, filtering, shopping cart, checkout, and reviews) and a full admin panel (CRUD operations, order management, voucher system, and a statistics dashboard).

## Features

* **Public Facing:** Homepage, Product Shop, Product Details, Cart, Checkout.
* **User Account:** Order History, Address Management, Profile Settings, Product Reviews.
* **Admin Panel:** Dashboard, CRUD Management for Products, Categories, Brands, Vouchers. Order Management and Review Moderation.
* **Core Logic:** Authentication (Breeze), Role-based Authorization (Admin/User), JSON/Image Seeding.

---

## UPDATE v2.0
Leveraged Claude AI to polish the project and implement new features.

---

## Default Admin Credentials

* **Email:** `admin@swgunpla.test`
* **Password:** `password`

---

## Installation and Setup Guide

Follow these steps to get the application running on your local machine.

### 1. Clone the Repository
```bash
git clone [https://github.com/YourUsername/swgunpla-project.git](https://github.com/YourUsername/swgunpla-project.git)
cd swgunpla-project
```
(Remember to replace YourUsername/swgunpla-project.git with your actual repository URL)

### 2. Install Composer Dependencies (Backend)
```bash
composer install
```

### 3. Install NPM Dependencies and Build (Frontend)
```bash
npm install
npm run build
```

### 4. Configure Environment
Copy the example environment file:
```bash
cp .env.example .env
```
Generate the application key:
```bash
php artisan key:generate
```
Open the .env file in your code editor and configure your database connection (set DB_DATABASE, DB_USERNAME, DB_PASSWORD).

### 5. Run Migrations and Seed Database (CRITICAL)
This command will create the database structure and load all data (products, images, categories, etc.) from the JSON files.
```bash
php artisan migrate:fresh --seed
```

### 6. Link the Storage Directory
This command is required to make the seeded product images publicly accessible.
```bash
php artisan storage:link
```

### 7. Run the Development Server
```bash
php artisan serve
```
The application will be available at http://127.0.0.1:8000.
