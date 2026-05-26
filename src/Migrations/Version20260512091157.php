<?php

declare(strict_types=1);

namespace Mpemba\Crud\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use Mpemba\Utils\Utility;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512091157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Import initial data from JSON files';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        Utility::safeQuery('SET FOREIGN_KEY_CHECKS = 0', [], 'UPDATE');

        $dataDir = __DIR__ . '/../../data/';

        $loadJson = function($file) use ($dataDir) {
            $filePath = $dataDir . $file;
            if (!file_exists($filePath)) return [];
            return json_decode(file_get_contents($filePath), true) ?: [];
        };

        $categoriesData = $loadJson('categories.json');
        $categoryMap = [];
        foreach ($categoriesData as $cat) {
            if (isset($cat['slug']) && isset($cat['id'])) {
                $categoryMap[$cat['slug']] = $cat['id'];
            }
        }

        $import = function($table, $data, $mappings = [], $omitColumns = []) {
            if (empty($data)) return;

            $schemaColumns = [
                'users' => ['id', 'username', 'email', 'password', 'created_at'],
                'products' => ['id', 'name', 'description', 'price', 'image', 'category', 'stock', 'status'],
                'categories' => ['id', 'name', 'description', 'created_at'],
                'orders' => ['id', 'order_number', 'user_id', 'total', 'tax', 'shipping', 'status', 'created_at'],
                'order_items' => ['id', 'order_id', 'product_id', 'quantity', 'price', 'created_at'],
                'cart_items' => ['id', 'user_id', 'product_id', 'quantity', 'created_at'],
                'subscribers' => ['id', 'email', 'subscribed_at'],
                'contact_messages' => ['id', 'name', 'email', 'message', 'created_at'],
                'customer_comments' => ['id', 'user_id', 'product_id', 'comment', 'rating', 'created_at'],
                'role_permissions' => ['role', 'permissions'],
                'admin_settings' => ['id', 'setting_key', 'setting_value', 'updated_at'],
            ];

            if (!isset($schemaColumns[$table])) return;

            $columns = $schemaColumns[$table];
            $processedData = [];

            foreach ($data as $item) {
                
                foreach ($mappings as $jsonKey => $dbKey) {
                    if (isset($item[$jsonKey])) {
                        $item[$dbKey] = $item[$jsonKey];
                    }
                }

                
                foreach ($omitColumns as $col) {
                    unset($item[$col]);
                }

                $row = [];
                foreach ($columns as $col) {
                    $row[$col] = $item[$col] ?? null;
                }
                $processedData[] = $row;
            }

            Utility::bulkInsert($table, $columns, $processedData);
        };

        
        $import('users', $loadJson('users.json'), [], ['role']);
        $import('categories', $categoriesData);

        $productsData = $loadJson('products.json');
        foreach ($productsData as &$p) {
            if (isset($p['category']) && isset($categoryMap[$p['category']])) {
                $p['category'] = $categoryMap[$p['category']];
            }
        }
        $import('products', $productsData, [
            'stock_quantity' => 'stock',
            'image_url' => 'image'
        ]);

        $import('orders', $loadJson('orders.json'), [
            'shipping_cost' => 'shipping'
        ]);

        $import('order_items', $loadJson('order_items.json'), [
            'unit_price' => 'price'
        ]);

        $import('cart_items', $loadJson('cart_items.json'));
        $import('subscribers', $loadJson('subscribers.json'));
        $import('contact_messages', $loadJson('contact_messages.json'));
        $import('customer_comments', $loadJson('customer_comments.json'), [
            'message' => 'comment'
        ]);
        $rolePermissionsRaw = $loadJson('role_permissions.json');
        
        
        $allPermissionKeys = [];
        foreach ($rolePermissionsRaw as $role => $perms) {
            foreach ($perms as $perm => $value) {
                $allPermissionKeys[$perm] = true;
            }
        }
        
        $permissionIdMap = [];
        foreach (array_keys($allPermissionKeys) as $permKey) {
            $permissionId = Utility::insert('permissions', [
                'name' => ucwords(str_replace(['.', '_'], ' ', $permKey)),
                'keyword' => $permKey
            ]);
            $permissionIdMap[$permKey] = $permissionId;
        }
        
        foreach ($rolePermissionsRaw as $role => $perms) {
            $groupId = Utility::insert('groups', [
                'name' => ucfirst($role),
                'keyword' => $role
            ]);
            
            foreach ($perms as $perm => $value) {
                if ($value === true) {
                    Utility::insert('group_permissions', [
                        'group_id' => $groupId,
                        'permission_id' => $permissionIdMap[$perm]
                    ]);
                }
            }
            
            Utility::insert('role_permissions', [
                'role' => $role,
                'permissions' => json_encode($perms)
            ]);
        }

        $adminSettingsRaw = $loadJson('admin_settings.json');
        $adminSettingsData = [];
        foreach ($adminSettingsRaw as $key => $value) {
            $adminSettingsData[] = [
                'setting_key' => $key,
                'setting_value' => is_array($value) ? json_encode($value) : $value
            ];
        }
        $import('admin_settings', $adminSettingsData);

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
        Utility::safeQuery('SET FOREIGN_KEY_CHECKS = 1', [], 'UPDATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('TRUNCATE TABLE `users`');
        $this->addSql('TRUNCATE TABLE `products`');
        $this->addSql('TRUNCATE TABLE `categories`');
        $this->addSql('TRUNCATE TABLE `orders`');
        $this->addSql('TRUNCATE TABLE `order_items`');
        $this->addSql('TRUNCATE TABLE `cart_items`');
        $this->addSql('TRUNCATE TABLE `subscribers`');
        $this->addSql('TRUNCATE TABLE `contact_messages`');
        $this->addSql('TRUNCATE TABLE `customer_comments`');
        $this->addSql('TRUNCATE TABLE `role_permissions`');
        $this->addSql('TRUNCATE TABLE `admin_settings`');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
