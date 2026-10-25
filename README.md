 **ShopEase – Online Shopping Website**

ShopEase is a web-based e-commerce application built using PHP, MySQL, HTML, and CSS. It provides an online shopping experience with product browsing, user authentication, cart management, wishlists, checkout, and order history.

# 1. Features

- **User Authentication:** Registration, login, and logout.
- **Product Catalog:** Browse products across multiple categories.
- **Product Search:** Search for products.
- **Shopping Cart:** Add products, update quantities, and remove items.
- **Wishlist:** Save favorite products for later.
- **Checkout:** Place orders through the checkout workflow.
- **Order History:** View previously placed orders.
- **Admin Dashboard:** Add, edit, and delete products.
- **Product Images:** Display product images in the catalog.

# 2. Technologies Used

- **Frontend:** HTML, CSS
- **Backend:** PHP
- **Database:** MySQL
- **Development Environment:** XAMPP
- **Version Control:** Git and GitHub

# 3. Project Structure

ShopEase/
├── admin/                 # Admin dashboard and product management
├── css/                   # Stylesheets
├── images/                # Product images
├── includes/              # Database connection
├── pages/                 # Cart, checkout, login, orders, etc.
├── index.php              # Homepage and product catalog
├── wishlist_action.php    # Wishlist actions
└── README.md              # Project documentation


# 4. Getting Started

# 4.1. Prerequisites

- [XAMPP](https://www.apachefriends.org/)
- A web browser

# 4.2. Installation

1. Clone this repository:

   
   git clone https://github.com/lasya125/ShopEase.git
   

2. Place the project folder inside your XAMPP `htdocs` directory.

3. Start Apache and MySQL using the XAMPP Control Panel.

4. Open phpMyAdmin at `http://localhost/phpmyadmin/`.

5. Create a database named `ecommerce1`.

6. Create or import the database tables required by the application.

7. Configure `includes/db.php` with your local database settings.

8. Open the website in your browser:

   `http://localhost/ShopEase/`

   If your local folder is named `ecommerce`, use `http://localhost/ecommerce/` instead.

# 5. Security

- Do not commit database passwords, API keys, or private configuration files.
- Keep `.env` files and database backups out of public repositories.
- Configure production database credentials securely through the hosting provider.
- Use prepared statements and secure password hashing.

# 6. Future Improvements

- Deploy the application to a public PHP/MySQL hosting service.
- Integrate a secure payment gateway.
- Improve mobile responsiveness.
- Add product ratings and reviews.

# 7. Author

Lasya

GitHub: [lasya125](https://github.com/lasya125)

# 8. Repository

(https://github.com/lasya125/ShopEase)
