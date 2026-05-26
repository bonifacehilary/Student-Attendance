<?php
namespace Mpemba\Utils;

/**
 * ActivityLogger - Logs all user activities to the database
 */
class ActivityLogger
{
    /**
     * Log an activity
     * 
     * @param int|null $userId User ID (null for anonymous)
     * @param string $activity Activity type (login, logout, view_product, add_to_cart, remove_from_cart, checkout, page_view, etc.)
     * @param string|null $entityType Type of entity affected (product, order, page, etc.)
     * @param int|null $entityId ID of the entity
     * @param array|null $data Additional data to log
     * @return bool Success status
     */
    public static function log(
        ?int $userId,
        string $activity,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $data = null
    ): bool {
        try {
            $conn = $GLOBALS['db'] ?? null;
            if ($conn === null) {
                return false;
            }

            $ipAddress = self::getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $dataJson = $data ? json_encode($data) : null;

            $query = "
                INSERT INTO user_activities 
                (user_id, activity, entity_type, entity_id, data, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ";

            $params = [
                $userId,
                $activity,
                $entityType,
                $entityId,
                $dataJson,
                $ipAddress,
                $userAgent
            ];

            $conn->executeStatement($query, $params);
            return true;
        } catch (\Exception $e) {
            error_log("ActivityLogger error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log login activity
     */
    public static function logLogin(int $userId): bool
    {
        return self::log($userId, 'login', 'user', $userId);
    }

    /**
     * Log logout activity
     */
    public static function logLogout(int $userId): bool
    {
        return self::log($userId, 'logout', 'user', $userId);
    }

    /**
     * Log product view
     */
    public static function logProductView(int $productId, ?int $userId = null): bool
    {
        return self::log($userId, 'view_product', 'product', $productId);
    }

    /**
     * Log add to cart
     */
    public static function logAddToCart(int $userId, int $productId, int $quantity): bool
    {
        return self::log($userId, 'add_to_cart', 'product', $productId, ['quantity' => $quantity]);
    }

    /**
     * Log remove from cart
     */
    public static function logRemoveFromCart(int $userId, int $productId): bool
    {
        return self::log($userId, 'remove_from_cart', 'product', $productId);
    }

    /**
     * Log update cart item
     */
    public static function logUpdateCartItem(int $userId, int $productId, int $oldQty, int $newQty): bool
    {
        return self::log($userId, 'update_cart', 'product', $productId, [
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty
        ]);
    }

    /**
     * Log checkout
     */
    public static function logCheckout(int $userId, int $orderId, float $total): bool
    {
        return self::log($userId, 'checkout', 'order', $orderId, ['total' => $total]);
    }

    /**
     * Log page view
     */
    public static function logPageView(?int $userId, string $page, ?string $referrer = null): bool
    {
        return self::log($userId, 'page_view', 'page', null, [
            'page' => $page,
            'referrer' => $referrer
        ]);
    }

    /**
     * Log feedback/comment
     */
    public static function logFeedback(int $userId, int $productId, string $comment, int $rating): bool
    {
        return self::log($userId, 'submit_feedback', 'product', $productId, [
            'comment_length' => strlen($comment),
            'rating' => $rating
        ]);
    }

    /**
     * Log search query
     */
    public static function logSearch(?int $userId, string $query, int $resultsCount): bool
    {
        return self::log($userId, 'search', null, null, [
            'query' => $query,
            'results_count' => $resultsCount
        ]);
    }

    /**
     * Get client IP address
     */
    private static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return 'unknown';
    }

    /**
     * Get user activity history
     */
    public static function getUserActivities(int $userId, int $limit = 50, int $offset = 0): array
    {
        try {
            $conn = $GLOBALS['db'] ?? null;
            if ($conn === null) {
                return [];
            }

            $query = "
                SELECT * FROM user_activities 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ";

            $result = $conn->executeQuery($query, [$userId, $limit, $offset]);
            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            error_log("Error fetching user activities: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activity statistics for a user
     */
    public static function getUserActivityStats(int $userId, string $period = '7'): array
    {
        try {
            $conn = $GLOBALS['db'] ?? null;
            if ($conn === null) {
                return [];
            }

            $query = "
                SELECT 
                    activity,
                    COUNT(*) as count,
                    MAX(created_at) as last_activity
                FROM user_activities 
                WHERE user_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY activity
                ORDER BY count DESC
            ";

            $result = $conn->executeQuery($query, [$userId, $period]);
            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            error_log("Error fetching activity stats: " . $e->getMessage());
            return [];
        }
    }
}
