<?php

declare(strict_types=1);

namespace Mpemba\Crud\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512090320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Users table
        $this->addSql('CREATE TABLE `users` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT "user",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )');

        // Products table
        $this->addSql('CREATE TABLE `products` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            image VARCHAR(255),
            category INT,
            stock INT DEFAULT 0,
            status VARCHAR(50) DEFAULT "active",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )');

        // Categories table
        $this->addSql('CREATE TABLE `categories` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        // Orders table
        $this->addSql('CREATE TABLE `orders` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50) NOT NULL UNIQUE,
            user_id INT,
            total DECIMAL(10,2) NOT NULL,
            tax DECIMAL(10,2) DEFAULT 0,
            shipping DECIMAL(10,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT "pending",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES `users`(id)
        )');

        // Order items table
        $this->addSql('CREATE TABLE `order_items` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT,
            product_id INT,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES `orders`(id),
            FOREIGN KEY (product_id) REFERENCES `products`(id)
        )');

        // Cart items table
        $this->addSql('CREATE TABLE `cart_items` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            product_id INT,
            quantity INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES `users`(id),
            FOREIGN KEY (product_id) REFERENCES `products`(id)
        )');

        // Subscribers table
        $this->addSql('CREATE TABLE `subscribers` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        // Contact messages table
        $this->addSql('CREATE TABLE `contact_messages` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        // Customer comments table
        $this->addSql('CREATE TABLE `customer_comments` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            product_id INT,
            comment TEXT NOT NULL,
            rating INT DEFAULT 5,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES `users`(id),
            FOREIGN KEY (product_id) REFERENCES `products`(id)
        )');

        // Role permissions table
        $this->addSql('CREATE TABLE `role_permissions` (
            role VARCHAR(50) PRIMARY KEY,
            permissions JSON NOT NULL
        )');

        // Admin settings table
        $this->addSql('CREATE TABLE `admin_settings` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )');

        // Audit logs table
        $this->addSql('CREATE TABLE `audit_logs` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(255) NOT NULL,
            entity_type VARCHAR(100),
            entity_id INT,
            changes JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES `users`(id)
        )');

        // Permissions table
        $this->addSql('CREATE TABLE `permissions` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            keyword VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )');

        // Groups table
        $this->addSql('CREATE TABLE `groups` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            keyword VARCHAR(100) NOT NULL UNIQUE,
            status VARCHAR(50) DEFAULT "active",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )');

        // Menu table
        $this->addSql('CREATE TABLE `menu` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            keyword VARCHAR(100) NOT NULL UNIQUE,
            url VARCHAR(255),
            icon VARCHAR(100),
            order_index INT DEFAULT 0,
            parent_id INT,
            status VARCHAR(50) DEFAULT "active",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES `menu`(id) ON DELETE CASCADE
        )');

        // Junction table: Group - Permission relationship
        $this->addSql('CREATE TABLE `group_permissions` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            permission_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_group_permission (group_id, permission_id),
            FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES `permissions`(id) ON DELETE CASCADE
        )');

        // Junction table: Group - Menu relationship
        $this->addSql('CREATE TABLE `group_menus` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            menu_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_group_menu (group_id, menu_id),
            FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
            FOREIGN KEY (menu_id) REFERENCES `menu`(id) ON DELETE CASCADE
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("SET FOREIGN_KEY_CHECKS = 0");
        $this->addSql('DROP TABLE IF EXISTS `group_menus`');
        $this->addSql('DROP TABLE IF EXISTS `group_permissions`');
        $this->addSql('DROP TABLE IF EXISTS `menu`');
        $this->addSql('DROP TABLE IF EXISTS `groups`');
        $this->addSql('DROP TABLE IF EXISTS `permissions`');
        $this->addSql('DROP TABLE IF EXISTS `audit_logs`');
        $this->addSql('DROP TABLE IF EXISTS `admin_settings`');
        $this->addSql('DROP TABLE IF EXISTS `role_permissions`');
        $this->addSql('DROP TABLE IF EXISTS `customer_comments`');
        $this->addSql('DROP TABLE IF EXISTS `contact_messages`');
        $this->addSql('DROP TABLE IF EXISTS `subscribers`');
        $this->addSql('DROP TABLE IF EXISTS `cart_items`');
        $this->addSql('DROP TABLE IF EXISTS `order_items`');
        $this->addSql('DROP TABLE IF EXISTS `orders`');
        $this->addSql('DROP TABLE IF EXISTS `categories`');
        $this->addSql('DROP TABLE IF EXISTS `products`');
        $this->addSql('DROP TABLE IF EXISTS `users`');
        $this->addSql("SET FOREIGN_KEY_CHECKS = 1");
    }
}
