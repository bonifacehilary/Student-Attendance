<?php
namespace Mpemba\Controller;

use Mpemba\Utils\Database;
use Mpemba\Utils\Utility;

class UserController {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    private function getAdminUser(): array {
        return [
            'id' => 0,
            'username' => 'admin',
            'email' => 'admin@mpemba.local',
            'password' => 'Admin@123',
            'first_name' => 'Site',
            'last_name' => 'Admin',
            'role' => 'admin'
        ];
    }

    private function findUserByLogin($login): ?array {
        return Database::getUserByLogin($login);
    }

    public function loginAdmin($login, $password) {
        $adminUser = $this->getAdminUser();
        $loginNormalized = strtolower(trim($login));

        if ($loginNormalized === strtolower($adminUser['username']) || $loginNormalized === strtolower($adminUser['email'])) {
            $isValidPassword = hash_equals($adminUser['password'], $password) || strcasecmp($adminUser['password'], $password) === 0;

            if ($isValidPassword) {
                return $this->buildAdminSession($adminUser);
            }

            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        $user = $this->findUserByLogin($login);
        if ($user && strtolower((string) ($user['role'] ?? 'customer')) === 'admin') {
            if (!empty($user['password']) && password_verify($password, (string) $user['password'])) {
                return $this->buildAdminSession($user);
            }

            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        return null;
    }

    public function login($username, $password) {
        $adminLogin = $this->loginAdmin($username, $password);
        if ($adminLogin !== null) {
            return $adminLogin;
        }

        $user = $this->findUserByLogin($username);
        if ($user && !empty($user['password']) && password_verify($password, (string) $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
                'role' => $user['role'] ?? 'user'
            ];
            return ['success' => true, 'message' => 'Login successful', 'user' => $_SESSION['user']];
        }

        return ['success' => false, 'message' => 'Invalid username or password'];
    }

    private function buildAdminSession(array $user) {
        $fullName = trim(sprintf('%s %s', $user['first_name'] ?? '', $user['last_name'] ?? ''));
        if ($fullName === '') {
            $fullName = $user['username'] ?? 'Admin';
        }

        $userId = (int) ($user['id'] ?? 0);

        // Load all user groups from DB and include in session
        $allUserRoles = [];
        if ($userId > 0) {
            try {
                $userGroups = \Mpemba\Utils\Utility::safeQuery(
                    "SELECT DISTINCT LOWER(TRIM(g.keyword)) AS keyword FROM user_group_relations ugr 
                     JOIN groups g ON g.id = ugr.group_id 
                     WHERE ugr.user_id = ? AND g.status = 'active'",
                    [$userId],
                    'SELECT'
                );
                
                if (!empty($userGroups)) {
                    $allUserRoles = array_unique(array_map(function ($row) {
                        $keyword = strtolower((string) ($row['keyword'] ?? ''));
                        return $keyword === 'customer' ? 'user' : $keyword;
                    }, $userGroups));
                }
            } catch (\Throwable $e) {
                error_log('Failed to load user groups: ' . $e->getMessage());
            }
        }

        // Fallback to primary role if no groups assigned
        $primaryRole = strtolower((string) ($user['role'] ?? 'admin'));
        if ($primaryRole === 'customer') {
            $primaryRole = 'user';
        }
        
        if (!in_array($primaryRole, $allUserRoles, true)) {
            array_unshift($allUserRoles, $primaryRole);
        }

        $_SESSION['admin_user'] = [
            'id' => $userId,
            'username' => $user['username'] ?? 'admin',
            'email' => $user['email'] ?? '',
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'name' => $fullName,
            'role' => $primaryRole,
            'roles' => implode(',', array_values($allUserRoles)) // comma-separated string of all assigned groups
        ];

        $_SESSION['user'] = $_SESSION['admin_user'];
        $_SESSION['admin_logged_in'] = true;

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $_SESSION['user'],
            'redirect' => '/admin/index'
        ];
    }

    public function register($username, $email, $password, $firstName = '', $lastName = '') {
        $existingUsername = Database::getUserByUsername($username);
        if ($existingUsername !== null) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        $existingEmail = Database::getUserByEmail($email);
        if ($existingEmail !== null) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $newUser = [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => 'user'
        ];

        $insertId = Database::insertUserToDb($newUser);
        if ($insertId !== null) {
            $newUser['id'] = (int) $insertId;
            $data['group_id'] = self::getGroupIdByName('user');
            $data['user_id'] = $newUser['id'];
            Utility::insert('user_group_relations', $data);
        } else {
            $users = $this->db->getUsers();
            $newUser['id'] = count($users) + 1;
            $users[] = $newUser;
            $this->db->saveUsers($users);
        }

        $_SESSION['user'] = [
            'id' => $newUser['id'],
            'username' => $newUser['username'],
            'email' => $newUser['email'],
            'first_name' => $newUser['first_name'],
            'last_name' => $newUser['last_name'],
            'role' => $newUser['role']
        ];

        return ['success' => true, 'message' => 'Registration successful', 'user' => $_SESSION['user']];
    }

    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function getCurrentUser() {
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user']);
    }

     public static function getGroupIdByName(string $groupName): ?int
    {
       return Utility::safeQuery("SELECT id FROM `groups` WHERE keyword = ?", [$groupName],"SELECT", 1)['id'] ?? null;
    }
    
}
?>