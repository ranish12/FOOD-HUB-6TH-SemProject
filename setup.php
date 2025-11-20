<?php
require_once 'config/database.php';

try {


    // Create Categories table
    // Drop existing tables if they exist
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    $tables = ['password_reset', 'payments', 'orderitems', 'orders', 'menu', 'categories', 'contactinfo', 'aboutus', 'users'];
    foreach ($tables as $table) {
        $conn->exec("DROP TABLE IF EXISTS $table");
    }
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Create Users table first (as it's referenced by other tables)
    $sql = "CREATE TABLE IF NOT EXISTS users (
        user_id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        password_hash varchar(255) NOT NULL,
        phone varchar(20) DEFAULT NULL,
        address text DEFAULT NULL,
        is_admin tinyint(1) DEFAULT 0,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        otp varchar(6) DEFAULT NULL,
        otp_expiry datetime DEFAULT NULL,
        is_verified tinyint(1) DEFAULT 0,
        PRIMARY KEY (user_id),
        UNIQUE KEY email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->exec($sql);
    echo "Users table created successfully<br>";

    // Create Categories table
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        category_id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(50) NOT NULL,
        description text DEFAULT NULL,
        image_url varchar(255) DEFAULT NULL,
        is_active tinyint(1) DEFAULT 1,
        display_order int(11) DEFAULT 0,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->exec($sql);
    echo "Categories table created successfully<br>";

    // Create Menu table
    $sql = "CREATE TABLE IF NOT EXISTS menu (
        menu_id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        category_id int(11) DEFAULT NULL,
        price decimal(10,2) NOT NULL,
        description text DEFAULT NULL,
        image_url varchar(255) DEFAULT NULL,
        is_available tinyint(1) DEFAULT 1,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        is_featured tinyint(1) DEFAULT 0,
        is_deleted tinyint(1) DEFAULT 0,
        stock_quantity int(11) DEFAULT 100,
        PRIMARY KEY (menu_id),
        KEY category_id (category_id),
        CONSTRAINT menu_ibfk_1 FOREIGN KEY (category_id) REFERENCES categories (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->exec($sql);
    echo "Menu table created successfully<br>";

    // Create Orders table
    $sql = "CREATE TABLE IF NOT EXISTS orders (
        order_id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        subtotal decimal(10,2) NOT NULL,
        delivery_fee decimal(10,2) NOT NULL DEFAULT 0.00,
        tax_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        total_amount decimal(10,2) NOT NULL,
        status enum('Pending','Processing','Completed','Cancelled','Delivered') DEFAULT 'Pending',
        delivery_address text NOT NULL,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (order_id),
        KEY user_id (user_id),
        CONSTRAINT orders_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->exec($sql);
    echo "Orders table created successfully<br>";

    // Create OrderItems table
    $sql = "CREATE TABLE IF NOT EXISTS orderitems (
        order_item_id int(11) NOT NULL AUTO_INCREMENT,
        order_id int(11) NOT NULL,
        menu_id int(11) NOT NULL,
        quantity int(11) NOT NULL,
        price decimal(10,2) NOT NULL,
        PRIMARY KEY (order_item_id),
        KEY order_id (order_id),
        KEY menu_id (menu_id),
        CONSTRAINT orderitems_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (order_id),
        CONSTRAINT orderitems_ibfk_2 FOREIGN KEY (menu_id) REFERENCES menu (menu_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->exec($sql);
    echo "OrderItems table created successfully<br>";

    // Create Payments table
    $sql = "CREATE TABLE IF NOT EXISTS payments (
        payment_id int(11) NOT NULL AUTO_INCREMENT,
        order_id int(11) NOT NULL,
        payment_method enum('Cash','Card','eSewa') DEFAULT NULL,
        payment_status enum('Pending','Completed','Failed') DEFAULT NULL,
        payment_time datetime DEFAULT NULL,
        amount decimal(10,2) DEFAULT NULL,
        PRIMARY KEY (payment_id),
        KEY order_id (order_id),
        CONSTRAINT payments_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->exec($sql);
    echo "Payments table created successfully<br>";

    // Create AboutUs table
    $sql = "CREATE TABLE IF NOT EXISTS aboutus (
        id int(11) NOT NULL AUTO_INCREMENT,
        title varchar(100) NOT NULL,
        content text NOT NULL,
        image_url varchar(255) DEFAULT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->exec($sql);
    echo "AboutUs table created successfully<br>";

    // Create ContactInfo table
    $sql = "CREATE TABLE IF NOT EXISTS contactinfo (
        id int(11) NOT NULL AUTO_INCREMENT,
        title varchar(100) NOT NULL,
        address text NOT NULL,
        phone varchar(50) NOT NULL,
        email varchar(100) NOT NULL,
        working_hours text NOT NULL,
        map_embed text DEFAULT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->exec($sql);
    echo "ContactInfo table created successfully<br>";

    // Create Password Reset table
    $sql = "CREATE TABLE IF NOT EXISTS password_reset (
        reset_id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        otp varchar(6) NOT NULL,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        expires_at timestamp NULL DEFAULT NULL,
        is_used tinyint(1) DEFAULT 0,
        PRIMARY KEY (reset_id),
        KEY user_id (user_id),
        CONSTRAINT password_reset_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->exec($sql);
    echo "Password Reset table created successfully<br>";







    // Insert default admin user if not exists
    $stmt = $conn->query("SELECT COUNT(*) FROM Users WHERE is_admin = 1");
    if ($stmt->fetchColumn() == 0) {
        $admin_password = password_hash('Admin@123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO Users (name, email, password_hash, is_admin, is_verified) VALUES 
                ('Admin', 'admin@foodhub.com', ?, 1, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$admin_password]);
        echo "Default admin user created successfully<br>";
    }

    // Insert default categories if not exists
    $stmt = $conn->query("SELECT COUNT(*) FROM Categories");
    if ($stmt->fetchColumn() == 0) {
        $sql = "INSERT INTO Categories (name, description, display_order, is_active) VALUES 
                ('Momo', 'Delicious dumplings with various fillings', 1, 1),
                ('Chowmein', 'Stir-fried noodles with vegetables and meat', 2, 1),
                ('Thukpa', 'Hearty noodle soup', 3, 1),
                ('Biryani', 'Fragrant rice dish with spices', 4, 1),
                ('Drinks', 'Refreshing beverages', 5, 1),
                ('Snacks', 'Quick bites and appetizers', 6, 1)";
        $conn->exec($sql);
        echo "Default categories added successfully<br>";
    }

    // Insert default About Us content if not exists
    $stmt = $conn->query("SELECT COUNT(*) FROM AboutUs");
    if ($stmt->fetchColumn() == 0) {
        $sql = "INSERT INTO AboutUs (id, title, content, image_url) VALUES 
                (1, 'Welcome to Food Hub', 'Food Hub is more than just a restaurant - it\'s a culinary journey that brings the authentic flavors of Nepal right to your doorstep. Established in 2023, we\'ve quickly become Banepa\'s favorite destination for delicious Nepali and Asian cuisine.\n\nOur expert chefs craft each dish with passion, using locally-sourced ingredients and traditional recipes passed down through generations. From our signature momos to aromatic biryanis, every item on our menu tells a story of flavor and tradition.\n\nWe take pride in our quick delivery service and maintain the highest standards of food quality and hygiene. Whether you\'re craving a quick snack or planning a family feast, Food Hub is here to serve you with love and care.', 'assets/images/logo.png')";
        $conn->exec($sql);
        echo "Default About Us content added successfully<br>";
    }

    // Insert default Contact Info if not exists
    $stmt = $conn->query("SELECT COUNT(*) FROM ContactInfo");
    if ($stmt->fetchColumn() == 0) {
        $map_embed = '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3534.4097820400784!2d85.52120511501781!3d27.63337798280728!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb0a01b1ede92f%3A0x3d77e3730f46c50c!2sTindobato%2C%20Banepa%2045210!5e0!3m2!1sen!2snp!4v1645510148606!5m2!1sen!2snp" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>';
        
        $sql = "INSERT INTO ContactInfo (id, title, address, phone, email, working_hours, map_embed) VALUES 
                (1, 'Get in Touch', 'Tindobato, Near City Center\nBanepa-4, Kavre, Nepal', '+977-980-1234567\n+977-11-661234', 'info@foodhub.com\norders@foodhub.com', 'Sunday - Friday: 10:00 AM - 9:00 PM\nSaturday: 11:00 AM - 8:00 PM', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$map_embed]);
        echo "Default Contact Info added successfully<br>";
    }

    echo "<br>Setup completed successfully! You can now access the website.";
    echo "<br>Admin credentials:<br>Email: admin@foodhub.com<br>Password: Admin@123";

} catch(PDOException $e) {
    error_log("Database setup error: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}

$conn = null;
?> 