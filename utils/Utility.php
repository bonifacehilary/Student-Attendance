<?php
namespace Mpemba\Utils;

class Utility{

    public static function safeQuery($query, $params = [], $query_type = 'SELECT', $one_row = null)
    {
        $conn = $GLOBALS['db'] ?? null;
        if ($conn === null) {
            throw new \RuntimeException('Database connection not initialized. Ensure config/bootstrap.php is required before using Utility.');
        }

        try {
            if ($query_type == 'SELECT') {
                $result = $conn->executeQuery($query, $params);
            } else {
                $affectedRows = $conn->executeStatement($query, $params);
            }
        } catch (\Throwable $e) {
            error_log("Failed Query: $query. Error: " . $e->getMessage());
            throw new \Exception($e->getMessage());
        }

        if ($query_type == 'INSERT') {
            try {
                return $conn->lastInsertId();
            } catch (\Throwable $e) {
                return null;
            }
        } elseif ($query_type == 'SELECT') {
            return $one_row ? $result->fetchAssociative() : $result->fetchAllAssociative();
        } else {
            return true;
        }
    }

     /**
     *
     * Prepare data before inserting, the output of this function is expected to be a
     * clean insert statement. General assumption is that field names on post corresponds to
     * table columns on the database
     * @param string $table
     * @param array $post_array
     * @return array
     */
    public static function safePrepareInsertQuery(string $table, array $post_array)
    {
        $fields = [];
        $params = [];

        foreach ($post_array as $key => $value) {
            $value = strval($value);

            if ($value === '0' || (!empty($value) && $value !== 'null')) {
                $fields[] = "`$key` = ?";
                $params[] = $value;
            }
        }

        $fieldsStr = implode(', ', $fields);

        $query = "INSERT INTO `$table` SET $fieldsStr";

        return [
            'query' => $query,
            'params' => $params
        ];
    }

    public static function insert($table, $array_data)
    {
        $q = self::safePrepareInsertQuery($table, $array_data);
        return self::safeQuery($q['query'], $q['params'], 'INSERT');
    }

    /**
     * Insert multiple rows into a table
     * @param $table String Name of the table
     * @param $columns Array of columns to be inserted
     * @param $data Array of all rows to tbe inserted
     * @return bool
     * @throws JsonException
     */
    public static function bulkInsert($table, $columns, $data)
    {
        if (empty($data)) return true;

        $q = "INSERT INTO `{$table}` (" . implode(', ', $columns) . ') VALUES ';
        $rows = [];
        foreach ($data as $datum) {
            $escapedValues = array_map(function($value) {
                if ($value === null || $value === '') return 'NULL';
                if (is_numeric($value) && !is_string($value)) return $value;
                return "'" . addslashes((string)$value) . "'";
            }, array_values($datum));
            $rows[] = "(" . implode(', ', $escapedValues) . ")";
        }
        $q .= implode(', ', $rows);
        
        return self::safeQuery($q, [], 'INSERT');
    }


    /**
    * @param string $table
    * @param mixed $id
    * @param array $post_array
    * @param string $id_column
    * @return array
     */
    public static function safePrepareUpdateQuery($table, $id, $post_array, $id_column = 'id')
    {
        $fields = [];
        $params = [];

        foreach ($post_array as $key => $value) {

            if ($value !== null) {
                $fields[] = "`$key` = ?";
                $params[] = $value;
            } else {
                $fields[] = "`$key` = NULL";
            }
        }

        $setClause = implode(', ', $fields);

        $query = "UPDATE `$table` SET $setClause WHERE `$id_column` = ?";
        $params[] = $id;

        return [
            'query' => $query,
            'params' => $params
        ];
    }

    /**
     * Updates a a row with a given key-value pair
     * @param string $table
     * @param int $id
     * @param array $array_data
     * @return boolean
     * @throws '\Exception'?
     */
    public static function update($table, $id, $array_data)
    {
        $q = self::safePrepareUpdateQuery((string)$table, $id, $array_data);
        try {
            return self::safeQuery($q['query'], $q['params'], 'UPDATE');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public static function delete($table, $id, $id_column_name = 'id')
    {
        $q = "DELETE FROM {$table} WHERE {$id_column_name} = ?";
        return self::safeQuery($q, [$id], 'DELETE');
    }

      /**
     * UI Notifications (SWAL)
     * @param $text
     * @param string $type
     * @param callable|null $title
     * @param int $buttons
     */
    public static function notify($text, $type = 'success', $title = null, $buttons = 1)
    {
        if ($title === null) {
            echo '<script>Swal.fire(' . json_encode((string)$text) . ');</script>';
            return;
        }

        $options = [
            'html' => (string)$text,
            'title' => (string)$title,
            'icon' => (string)$type,
            'showConfirmButton' => $buttons !== null ? true : false,
        ];

        if ($buttons === null) {
            $options['allowOutsideClick'] = true;
            $options['showConfirmButton'] = false;
        }

        echo '<script>Swal.fire(' . json_encode($options) . ');</script>';
    }

    public static function getLoggedInUser()
    {
        if (isset($_SESSION['user'])) {
            $_SESSION['user_name'];
            $_SESSION['user_id'];
            return $_SESSION['user'];
        }
        return null;
    }

    public static function getProductImageUrl(array $product, ?string $categoryName = null): string
    {
        $imageUrl = trim((string) ($product['image_url'] ?? $product['image'] ?? ''));
        if ($imageUrl !== '') {
            return $imageUrl;
        }

        if ($categoryName !== null && $categoryName !== '') {
            return self::getCategoryImageUrl($categoryName, 900, 900);
        }

        return self::getCategoryImageUrl('', 900, 900);
    }

    public static function getCategoryImageUrl(string $categoryName, int $width = 1400, int $height = 900): string
    {
        $mapping = [
            'Heritage Fashion' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8',
            'Sanctuary Home' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7',
            'Atelier Electronics' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661',
            'Natural Beauty' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348',
            'Lifestyle Essentials' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b',
        ];

        $baseUrl = $mapping[$categoryName] ?? 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f';
        return sprintf('%s?auto=format&fit=crop&w=%d&h=%d&q=80', $baseUrl, $width, $height);
    }

    // Cart-related methods
    public static function getCartItems(int $userId): array
    {
        $query = "
            SELECT ci.id, ci.product_id, ci.quantity, p.name, p.price, p.image, p.category
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = ?
            ORDER BY ci.created_at ASC
        ";
        return self::safeQuery($query, [$userId]);
    }

    public static function addToCart(int $userId, int $productId, int $quantity = 1): bool
    {
        // Check if item already in cart
        $existing = self::safeQuery(
            "SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?",
            [$userId, $productId],
            'SELECT',
            true
        );

        if ($existing) {
            // Update quantity
            $newQty = $existing['quantity'] + $quantity;
            return self::safeQuery(
                "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?",
                [$newQty, $existing['id']],
                'UPDATE'
            );
        } else {
            // Insert new
            return self::safeQuery(
                "INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)",
                [$userId, $productId, $quantity],
                'INSERT'
            ) !== null;
        }
    }

    public static function updateCartItem(int $userId, int $productId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return self::removeFromCart($userId, $productId);
        }

        return self::safeQuery(
            "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?",
            [$quantity, $userId, $productId],
            'UPDATE'
        );
    }

    public static function removeFromCart(int $userId, int $productId): bool
    {
        return self::safeQuery(
            "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?",
            [$userId, $productId],
            'DELETE'
        );
    }

    public static function clearCart(int $userId): bool
    {
        return self::safeQuery(
            "DELETE FROM cart_items WHERE user_id = ?",
            [$userId],
            'DELETE'
        );
    }

    public static function createOrder(array $orderData): ?int
    {
        $fields = ['order_number', 'user_id', 'total', 'tax', 'shipping', 'status'];
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        $values = [];
        foreach ($fields as $field) {
            $values[] = $orderData[$field] ?? null;
        }

        $result = self::safeQuery(
            "INSERT INTO orders (" . implode(',', $fields) . ") VALUES ({$placeholders})",
            $values,
            'INSERT'
        );

        return $result ? self::getLastInsertId() : null;
    }

    public static function createOrderItem(int $orderId, array $itemData): bool
    {
        return self::safeQuery(
            "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)",
            [$orderId, $itemData['product_id'], $itemData['quantity'], $itemData['price']],
            'INSERT'
        ) !== null;
    }

    private static function getLastInsertId(): int
    {
        $conn = $GLOBALS['db'];
        return (int) $conn->lastInsertId();
    }

}