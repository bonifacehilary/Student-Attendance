<?php
/**
 * Admin Database Utility Class
 * Provides database queries for the admin dashboard and admin pages
 * 
 * Purpose: Centralized database methods for admin operations and reporting
 * Namespace: Mpemba\Utils
 */

namespace Mpemba\Utils;

use Exception;
use Mpemba\Utils\Utility;

class AdminDatabase
{
    /**
     * Get total revenue from all orders
     * 
     * @return float
     */
    public static function getTotalRevenue(): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE payment_status = 'paid'";
            $result = Utility::safeQuery($sql, [], 'SELECT', true);
            return (float) ($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("AdminDatabase::getTotalRevenue - " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get total number of orders
     * 
     * @return int
     */
    public static function getTotalOrders(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM orders";
            $result = Utility::safeQuery($sql, [], 'SELECT', true);
            return (int) ($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("AdminDatabase::getTotalOrders - " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get count of orders by status
     * 
     * @return array
     */
    public static function getOrderStatuses(): array
    {
        try {
            $sql = "SELECT 
                        COALESCE(status, 'unknown') as status,
                        COUNT(*) as count
                    FROM orders 
                    GROUP BY status";
            $results = Utility::safeQuery($sql, [], 'SELECT', false);
            
            $statuses = [
                'pending' => 0,
                'processing' => 0,
                'shipped' => 0,
                'delivered' => 0,
                'completed' => 0,
                'cancelled' => 0
            ];
            
            foreach ($results as $row) {
                $status = strtolower($row['status'] ?? 'pending');
                if (isset($statuses[$status])) {
                    $statuses[$status] = (int) $row['count'];
                }
            }
            
            return $statuses;
        } catch (Exception $e) {
            error_log("AdminDatabase::getOrderStatuses - " . $e->getMessage());
            return ['pending' => 0, 'processing' => 0, 'shipped' => 0, 'delivered' => 0, 'completed' => 0, 'cancelled' => 0];
        }
    }

    /**
     * Get count of paid vs pending orders
     * 
     * @return array
     */
    public static function getPaymentStats(): array
    {
        try {
            $sql = "SELECT 
                        status AS payment_status,
                        COUNT(*) as count
                    FROM orders 
                    GROUP BY status";
            $results = Utility::safeQuery($sql, [], 'SELECT', false);
            
            $stats = ['paid' => 0, 'pending' => 0, 'failed' => 0];
            foreach ($results as $row) {
                $status = strtolower($row['payment_status'] ?? 'pending');
                if (isset($stats[$status])) {
                    $stats[$status] = (int) $row['count'];
                }
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("AdminDatabase::getPaymentStats - " . $e->getMessage());
            return ['paid' => 0, 'pending' => 0, 'failed' => 0];
        }
    }

    /**
     * Get total number of active users
     * 
     * @return int
     */
    public static function getTotalUsers(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM users WHERE role != 'admin'";
            $result = Utility::safeQuery($sql, [], 'SELECT', true);
            return (int) ($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("AdminDatabase::getTotalUsers - " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total number of products
     * 
     * @return int
     */
    public static function getTotalProducts(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM products";
            $result = Utility::safeQuery($sql, [], 'SELECT', true);
            return (int) ($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("AdminDatabase::getTotalProducts - " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get monthly traffic from user activities
     * 
     * @return int
     */
    public static function getMonthlyTraffic(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM user_activities 
                    WHERE activity IN ('page_view', 'view_product')
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $result = Utility::safeQuery($sql, [], 'SELECT', true);
            return (int) ($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("AdminDatabase::getMonthlyTraffic - " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent orders with user info
     * 
     * @param int $limit
     * @return array
     */
    public static function getRecentOrders(int $limit = 10): array
    {
        try {
            $sql = "SELECT 
                        o.id,
                        o.order_number,
                        o.user_id,
                        o.total,
                        o.status,
                        o.payment_status,
                        o.created_at,
                        u.email,
                        u.first_name,
                        u.last_name
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    ORDER BY o.created_at DESC
                    LIMIT ?";
          //  $results = $GLOBALS['db']->query($sql, [$limit])->fetchAll();
            $results = Utility::safeQuery($sql, [$limit], 'SELECT', false);
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getRecentOrders - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get daily revenue for last 30 days
     * 
     * @return array
     */
    public static function getDailyRevenue(): array
    {
        try {
            $sql = "SELECT 
                        DATE(created_at) as date,
                        SUM(total) as revenue,
                        COUNT(*) as orders
                    FROM orders 
                    WHERE payment_status = 'paid'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            $results = utility::safeQuery($sql, [], 'SELECT', false);
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getDailyRevenue - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get monthly revenue for last 12 months
     * 
     * @return array
     */
    public static function getMonthlyRevenue(): array
    {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        SUM(total) as revenue,
                        COUNT(*) as orders
                    FROM orders 
                    WHERE payment_status = 'paid'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY month ASC";
            $results = Utility::safeQuery($sql, [], 'SELECT', false);
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getMonthlyRevenue - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get top selling products
     * 
     * @param int $limit
     * @return array
     */
    public static function getTopSellingProducts(int $limit = 10): array
    {
        try {
            $sql = "SELECT 
                        p.id,
                        p.name,
                        p.price,
                        p.category,
                        SUM(oi.quantity) as total_sold,
                        SUM(oi.quantity * oi.price) as total_revenue
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    GROUP BY p.id
                    ORDER BY total_sold DESC
                    LIMIT ?";
            $results = utility::safeQuery($sql, [$limit], 'SELECT', false);
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getTopSellingProducts - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent user registrations
     * 
     * @param int $limit
     * @return array
     */
    public static function getRecentUsers(int $limit = 5): array
    {
        try {
            $sql = "SELECT 
                        id,
                        username,
                        email,
                        first_name,
                        last_name,
                        created_at
                    FROM users 
                    WHERE role != 'admin'
                    ORDER BY created_at DESC
                    LIMIT ?";
            $results = utility::safeQuery($sql, [$limit], 'SELECT', false);
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getRecentUsers - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user count trend for last 30 days
     * 
     * @return array
     */
    public static function getUserTrend(): array
    {
        try {
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as new_users
                    FROM users 
                    WHERE role != 'admin'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            $results = utility::safeQuery($sql, [], 'SELECT', false);
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getUserTrend - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all orders with pagination
     * 
     * @param int $limit
     * @param int $offset
     * @param string $status Filter by status (optional)
     * @return array
     */
    public static function getAllOrders(int $limit = 50, int $offset = 0, ?string $status = null): array
    {
        try {
            $sql = "SELECT 
                        o.id,
                        o.order_number,
                        o.user_id,
                        o.total,
                        o.status,
                        o.payment_status,
                        o.created_at,
                        u.email,
                        u.first_name,
                        u.last_name
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id";
            
            $params = [];
            if ($status) {
                $sql .= " WHERE o.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $results = Utility::safeQuery($sql, $params, 'SELECT', false);
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getAllOrders - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all users with pagination
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getAllUsers(int $limit = 50, int $offset = 0): array
    {
        try {
            $sql = "SELECT 
                        id,
                        username,
                        email,
                        first_name,
                        last_name,
                        role,
                        created_at
                    FROM users 
                    WHERE role != 'admin'
                    ORDER BY created_at DESC
                    LIMIT ? OFFSET ?";
            
            $results = utility::safeQuery($sql, [$limit, $offset], 'SELECT', false);
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getAllUsers - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all products with pagination
     * 
     * @param int $limit
     * @param int $offset
     * @param string|null $category Filter by category (optional)
     * @return array
     */
    public static function getAllProducts(int $limit = 50, int $offset = 0, ?string $category = null): array
    {
        try {
            $sql = "SELECT 
                        id,
                        name,
                        price,
                        category,
                        status,
                        created_at
                    FROM products";
            
            $params = [];
            if ($category) {
                $sql .= " WHERE category = ?";
                $params[] = $category;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $results = utility::safeQuery($sql, $params, 'SELECT', false);
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getAllProducts - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get category statistics
     * 
     * @return array
     */
    public static function getCategoryStats(): array
    {
        try {
            $sql = "SELECT 
                        category,
                        COUNT(*) as product_count,
                        AVG(price) as avg_price
                    FROM products 
                    GROUP BY category
                    ORDER BY product_count DESC";
            $results = $GLOBALS['db']->query($sql)->fetchAll();
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getCategoryStats - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user stats for customer dashboard
     * 
     * @param int $userId
     * @return array
     */
    public static function getUserStats(int $userId): array
    {
        try {
            $userSql = "SELECT * FROM users WHERE id = ?";
            $user = $GLOBALS['db']->query($userSql, [$userId])->fetch();
            
            if (!$user) {
                return [];
            }
            
            $ordersSql = "SELECT COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_spent 
                          FROM orders WHERE user_id = ? AND payment_status = 'paid'";
            $orders = utility::safeQuery($ordersSql, [$userId], 'SELECT', true);
            
            return [
                'user' => $user,
                'total_orders' => (int) ($orders['total_orders'] ?? 0),
                'total_spent' => (float) ($orders['total_spent'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log("AdminDatabase::getUserStats - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent activities for admin timeline
     * 
     * @param int $limit
     * @return array
     */
    public static function getRecentActivities(int $limit = 20): array
    {
        try {
            $sql = "SELECT 
                        ua.id,
                        ua.activity,
                        ua.entity_type,
                        ua.entity_id,
                        ua.user_id,
                        ua.data,
                        ua.created_at,
                        u.username,
                        u.email
                    FROM user_activities ua
                    LEFT JOIN users u ON ua.user_id = u.id
                    WHERE ua.activity IN ('checkout', 'add_to_cart', 'login')
                    ORDER BY ua.created_at DESC
                    LIMIT ?";
            
            $results = utility::safeQuery($sql, [$limit], 'SELECT', false);
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getRecentActivities - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue report for date range
     * 
     * @param string $startDate Y-m-d format
     * @param string $endDate Y-m-d format
     * @return array
     */
    public static function getRevenueReport(string $startDate, string $endDate): array
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(total) as total_revenue,
                        AVG(total) as avg_order_value,
                        MIN(total) as min_order,
                        MAX(total) as max_order,
                        DATE_FORMAT(created_at, '%Y-%m-%d') as date
                    FROM orders 
                    WHERE payment_status = 'paid'
                    AND DATE(created_at) BETWEEN ? AND ?
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            
            $results = Utility::safeQuery($sql, [$startDate, $endDate], 'SELECT', false);
            return is_array($results) ? $results : [];
        } catch (Exception $e) {
            error_log("AdminDatabase::getRevenueReport - " . $e->getMessage());
            return [];
        }
    }
}
