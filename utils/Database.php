<?php
namespace Mpemba\Utils;

Use Mpemba\Utils\Utility;

class Database {
    
    public static function generateOrderNumber(): string {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
    
    public static function calculateTax($subtotal, $taxRate = 0.1): float {
        return round($subtotal * $taxRate, 2);
    }
    
    public static function calculateTotal($subtotal, $tax = 0, $shippingCost = 0): float {
        return round($subtotal + $tax + $shippingCost, 2);
    }
    
    public static function formatPrice($price): string {
        return '$' . number_format($price, 2);
    }

    public static function hasUsersTable(): bool {
        global $db;
        if ($db === null) {
            return false;
        }
        try {
            $db->executeQuery('SELECT 1 FROM `users` LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getUsersFromDb(): array {
        if (!self::hasUsersTable()) {
            return [];
        }
        try {
            return Utility::safeQuery('SELECT * FROM `users`', [], 'SELECT');
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function getUserByIdFromDb($id) {
        if (!self::hasUsersTable()) {
            return null;
        }
        try {
            $res = Utility::safeQuery('SELECT * FROM `users` WHERE `id` = ?', [$id], 'SELECT', true);
            return $res ? $res : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getUserByUsernameFromDb($username) {
        if (!self::hasUsersTable()) {
            return null;
        }
        try {
            $res = Utility::safeQuery('SELECT * FROM `users` WHERE `username` = ?', [$username], 'SELECT', true);
            return $res ? $res : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getUserByEmailFromDb($email) {
        if (!self::hasUsersTable()) {
            return null;
        }
        try {
            $res = Utility::safeQuery('SELECT * FROM `users` WHERE `email` = ?', [$email], 'SELECT', true);
            return $res ? $res : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getUserByLoginFromDb($login) {
        $loginNormalized = trim((string) $login);
        if ($loginNormalized === '') {
            return null;
        }
        $user = self::getUserByUsernameFromDb($loginNormalized);
        if ($user !== null) {
            return $user;
        }
        return self::getUserByEmailFromDb($loginNormalized);
    }

    public static function hasGroupsTable(): bool {
        global $db;
        if ($db === null) {
            return false;
        }
        try {
            $db->executeQuery('SELECT 1 FROM `groups` LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getGroupsFromDb(): array {
        if (!self::hasGroupsTable()) {
            return [];
        }
        try {
            return Utility::safeQuery('SELECT * FROM `groups` ORDER BY `name` ASC', [], 'SELECT');
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function getGroupByKeyword(string $keyword): ?array {
        $keywordNormalized = strtolower(trim($keyword));
        if ($keywordNormalized === '') {
            return null;
        }
        if (!self::hasGroupsTable()) {
            return null;
        }
        try {
            $result = Utility::safeQuery('SELECT * FROM `groups` WHERE LOWER(`keyword`) = ? LIMIT 1', [$keywordNormalized], 'SELECT', true);
            return $result ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getGroupLabel(string $roleOrKeyword): string {
        $normalized = strtolower(trim($roleOrKeyword));
        if ($normalized === '' || $normalized === 'customer') {
            return 'User';
        }
        $group = self::getGroupByKeyword($normalized);
        if ($group !== null && !empty($group['name'])) {
            return (string) $group['name'];
        }
        if ($normalized === 'user') {
            return 'User';
        }
        return ucwords(str_replace(['_', '-'], ' ', $normalized));
    }

    public static function insertUserToDb(array $user) {
        if (!self::hasUsersTable()) {
            return null;
        }
        $insertData = $user;
        if (isset($insertData['id'])) {
            unset($insertData['id']);
        }
        try {
            return Utility::insert('users', $insertData);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function isValidEmail($email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function isValidPassword($password): bool {
        return strlen($password) >= 6;
    }
    
    public static function sanitize($input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function generateSlug($text): string {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
    
    public static function getIPAddress(): string {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    
    public static function logAudit($userId, $action, $entityType, $entityId, $changes = null): void {
        global $db;
        try {
           Utility::insert('audit_logs', [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'changes' => $changes ? json_encode($changes) : null,
                'ip_address' => self::getIPAddress(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);
        } catch (\Exception $e) {
            // Fail silently for audit logging
        }
    }
    
    public static function respondJson($status, $data = null, $message = null, $httpCode = 200): void {
        header('Content-Type: application/json');
        http_response_code($httpCode);
        
        $response = ['status' => $status];
        if ($data !== null) $response['data'] = $data;
        if ($message !== null) $response['message'] = $message;
        
        echo json_encode($response);
    }
    
    public static function respondError($message, $httpCode = 400): void {
        self::respondJson('error', null, $message, $httpCode);
    }
    
    public static function getUsers(): array {
        // Return users from the database only. Do not fallback to JSON storage.
        return self::getUsersFromDb();
    }

    public static function saveUsers(array $users): bool {
        // Persist users to the database. This performs upsert-like behavior per user.
        if (!self::hasUsersTable()) {
            // No DB available - refuse to write to JSON as per project policy.
            error_log('saveUsers: users table missing; aborting save to avoid JSON fallback');
            return false;
        }

        try {
            foreach ($users as $u) {
                $id = isset($u['id']) ? (int) $u['id'] : null;

                // Prepare allowed fields only
                $data = [];
                if (isset($u['username'])) $data['username'] = $u['username'];
                if (isset($u['email'])) $data['email'] = $u['email'];
                if (isset($u['password'])) $data['password'] = $u['password'];
                if (isset($u['first_name'])) $data['first_name'] = $u['first_name'];
                if (isset($u['last_name'])) $data['last_name'] = $u['last_name'];
                if (isset($u['role'])) $data['role'] = $u['role'];

                if ($id) {
                    $existing = self::getUserByIdFromDb($id);
                    if ($existing !== null) {
                        // Update existing row
                        $q = Utility::safePrepareUpdateQuery('users', $id, $data);
                        Utility::safeQuery($q['query'], $q['params'], 'UPDATE');
                        continue;
                    }
                    // If id provided but not found, fall through to insert
                }

                // Insert new row
                Utility::insert('users', $data);
            }

            return true;
        } catch (\Throwable $e) {
            error_log('saveUsers failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function getUserById($id) {
        $user = self::getUserByIdFromDb($id);
        if ($user !== null) {
            return $user;
        }

        $users = self::getUsers();
        foreach ($users as $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }
        return null;
    }

    public static function getUserByUsername($username) {
        $user = self::getUserByUsernameFromDb($username);
        if ($user !== null) {
            return $user;
        }

        $users = self::getUsers();
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }
        return null;
    }

    public static function getUserByEmail($email) {
        $user = self::getUserByEmailFromDb($email);
        if ($user !== null) {
            return $user;
        }

        $users = self::getUsers();
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }
        return null;
    }

    public static function getUserByLogin($login) {
        $loginNormalized = trim((string) $login);
        if ($loginNormalized === '') {
            return null;
        }

        $user = self::getUserByUsername($loginNormalized);
        if ($user !== null) {
            return $user;
        }

        return self::getUserByEmail($loginNormalized);
    }
}
?>
