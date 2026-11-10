# SportZone

SportZone is a PHP/MySQL sports store with a customer shop, accounts, saved carts and delivery details, cash-on-delivery checkout, order history, and admin product/order management.

## Setup (XAMPP)
1. Install XAMPP and start **Apache** and **MySQL** from the control panel.
2. Copy the `sportzone` folder into `C:\xampp\htdocs\` (or `/opt/lampp/htdocs/`).
3. Open phpMyAdmin (`http://localhost/phpmyadmin`) and **Import** `database/sportzone.sql` to create the database and initial products. Only import this setup file for a fresh database; it resets the product tables and admin users.
4. For an existing SportZone database, back it up, then import `database/customer_accounts.sql`. This additive migration creates customer, persistent-cart, and order tables without dropping products or admin accounts.
5. Check `config/database.php` (host, port, database name, user, password) and make sure it matches your local MySQL setup.
6. Create the first Super Admin from a terminal inside the project folder if you have not already created one:
   ```
   php cli/create_admin.php "admin" "admin@example.com"
   ```
   You are prompted for the password. It must be at least 12 characters and is stored only as a hash.
7. Customer site: `http://localhost/sportzone/`
   Admin login: `http://localhost/sportzone/admin/login.php`
8. Customers can register, sign in, save their delivery details, and place cash-on-delivery orders. Order managers and Super Admins can update order and payment-collection status in the admin dashboard.
9. If the project folder has a different URL path, update `BASE_URL` in `config/app.php`.

### Payments
Checkout currently supports cash on delivery. The application does not collect or store payment-card numbers or security codes. Online payments require a hosted payment provider and merchant credentials; never add raw card data to this database.

## Troubleshooting
- **Database connection failed**: MySQL is not running, or the credentials in `config/database.php` are wrong.
- **Customer tables are missing**: import `database/customer_accounts.sql` in phpMyAdmin after selecting the existing `sportzone` database.
- **Image upload fails**: make sure `assets/images/products/` is writable by Apache.
- **Blank page**: PHP errors are hidden for security. Check `xampp/apache/logs/error.log`.
- **MySQL will not start**: another service is using port 3306. Stop it, or change the port in `config/database.php`.

## Current scope
Online card payments, customer email verification/password reset, and address-book management for multiple delivery addresses are not included yet. Real product photography is not included. Admins can upload images, and a placeholder is shown otherwise.
