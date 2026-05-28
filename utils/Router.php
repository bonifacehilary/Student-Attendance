<?php
namespace StudentAttendance\Utils;

use StudentAttendance\Utils\Utility;

class Router {
    public static function load() {
        if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|ico|svg)$/', $_SERVER["REQUEST_URI"])) {
            echo "<!-- ROUTER: serving static file -->\n";
            return false; // serve the requested resource as-is
        }

        $rawPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $normalizedPath = self::normalizePath(self::removeBasePath($rawPath));

        // Check if it's a direct page request first
        $pageFile = __DIR__ . '/../pages/' . $normalizedPath . '.php';
        if (file_exists($pageFile)) {
            include $pageFile;
            return;
        }

        // Try to resolve nested routes using the menu route tree
        $route = self::getRouteByPath($normalizedPath);
        if ($route !== null) {
            $routePage = $route['page'] ?? null;
            if ($routePage && file_exists(__DIR__ . '/../pages/' . $routePage)) {
                self::injectRouteParameters($route, $normalizedPath);
                include __DIR__ . '/../pages/' . $routePage;
                return;
            }
        }

        // Fallback to admin nested page path
        $adminPageFile = __DIR__ . '/../pages/' . $normalizedPath . '.php';
        if (file_exists($adminPageFile)) {
            include $adminPageFile;
            return;
        }

        // Default to home page
        $homeFile = __DIR__ . '/../pages/home.php';
        if (file_exists($homeFile)) {
            include $homeFile;
        } else {
            http_response_code(404);
            echo '<h1>Page not found</h1><p>The requested page could not be found.</p>';
        }
    }

    public static function getPathName(string $default = 'EduAttend Marketplace'): string {
        $rawPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $normalizedPath = self::normalizePath(self::removeBasePath($rawPath));

        $route = self::getRouteByPath($normalizedPath);
        if ($route !== null && !empty($route['title'])) {
            return $route['title'];
        }

        $pageNames = [
            '' => 'Welcome - EduAttend Store',
            'splash' => 'Welcome - EduAttend Store',
            'home' => 'Home - EduAttend Store',
            'products' => 'All Products - EduAttend Store',
            'login' => 'Login - EduAttend Store',
            'register' => 'Register - EduAttend Store',
            'cart' => 'Shopping Cart - EduAttend Store',
            'category' => 'Categories - EduAttend Store',
            'product-details' => 'Product Details - EduAttend Store',
            'order-status' => 'Order Status - EduAttend Store',
            'user' => 'My Account - EduAttend Store',
            'natural-beauty' => 'Natural Beauty - EduAttend Store',
            'atelier-electronics' => 'Atelier Electronics - EduAttend Store',
            'heritage-fashion' => 'Heritage Fashion - EduAttend Store',
            'sanctuary-home' => 'Sanctuary Home - EduAttend Store',
            'lifestyle-essentials' => 'Lifestyle Essentials - EduAttend Store',
            'admin/index' => 'Admin Dashboard - EduAttend',
            'admin/inventory' => 'Inventory Console - EduAttend Heritage',
            'admin/orders' => 'Orders Registry - EduAttend Admin',
            'admin/customers' => 'Customers - EduAttend Admin',
            'admin/reports' => 'Reports - EduAttend Admin',
            'admin/settings' => 'Settings Hub - EduAttend Admin',
            'admin/add-product' => 'Add Product - EduAttend Admin',
            'about-us' => 'About Us - EduAttend Store',
            'privacy-policy' => 'Privacy Policy - EduAttend Store',
            'blog' => 'Blog - EduAttend Store',
            'contact' => 'Contact Us - EduAttend Store',
            'admin/messages' => 'Messages - EduAttend Admin',
            'admin/subscribers' => 'Subscribers - EduAttend Admin',
            'admin/categories' => 'Categories - EduAttend Admin',
            'admin/permissions' => 'Permissions - EduAttend Admin',
            'admin/shipping' => 'Shipping - EduAttend Admin',
            'role-menu-management' => 'Menu Management',
        ];

        return $pageNames[$normalizedPath] ?? $default;
    }

    public static function getCurrentRoute(): string {
        $rawPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $normalizedPath = self::normalizePath(self::removeBasePath($rawPath));

        if ($normalizedPath === 'admin') {
            return 'admin/index';
        }

        return $normalizedPath;
    }

    public static function getMenuRoutes(string $context = 'main'): array {
        try {
            $userGroups = Utility::safeQuery(
                "SELECT ugr.group_id FROM `user_group_relations` ugr WHERE ugr.user_id = ?;",
                [$_SESSION['user']['id'] ?? 0]
            ) ?? [];

            if (empty($userGroups)) {
                return self::getFallbackMenuRoutes($context);
            }

            $groupIds = array_unique(array_map('intval', array_column($userGroups, 'group_id')));
            if (empty($groupIds)) {
                return self::getFallbackMenuRoutes($context);
            }

            $routes = self::loadMenuRoutesFromDatabase($groupIds, $context);
            if (!empty($routes)) {
                return $routes;
            }
        } catch (\Throwable $e) {
            error_log('Router::getMenuRoutes fallback to static menu: ' . $e->getMessage());
        }

        return self::getFallbackMenuRoutes($context);
    }

    private static function loadMenuRoutesFromDatabase(array $groupIds, string $context): array {
        $contextCondition = $context === 'admin'
            ? "(m.url LIKE '/admin/%' OR m.url = '/admin' OR m.url IS NULL)"
            : "(m.url IS NULL OR (m.url NOT LIKE '/admin/%' AND m.url != '/admin'))";

        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $query = "
            SELECT DISTINCT
                m.id,
                m.name,
                m.keyword,
                m.url,
                m.icon,
                m.order_index,
                m.parent_id,
                m.status,
                g.keyword AS group_keyword,
                g.name AS group_name
            FROM menu m
            INNER JOIN group_menus gm ON gm.menu_id = m.id
            INNER JOIN groups g ON g.id = gm.group_id
            WHERE m.status = 'active'
              AND gm.group_id IN ({$placeholders})
             
            ORDER BY COALESCE(m.parent_id, m.id), m.order_index ASC, m.name ASC
        ";

        $rows = Utility::safeQuery($query, $groupIds);
        if (empty($rows)) {
            return [];
        }

        $rowsById = [];
        foreach ($rows as $row) {
            $rowsById[(int)$row['id']] = $row;
        }

        $parentIds = array_filter(array_unique(array_column($rows, 'parent_id')), function ($id) use ($rowsById) {
            return $id !== null && $id !== '' && !isset($rowsById[(int)$id]);
        });

        while (!empty($parentIds)) {
            $placeholders = implode(',', array_fill(0, count($parentIds), '?'));
            $parentQuery = "
                SELECT DISTINCT
                    m.id,
                    m.name,
                    m.keyword,
                    m.url,
                    m.icon,
                    m.order_index,
                    m.parent_id,
                    m.status,
                    NULL AS group_keyword,
                    NULL AS group_name
                FROM menu m
                WHERE m.status = 'active'
                  AND m.id IN ({$placeholders})
                  AND {$contextCondition}
                ORDER BY COALESCE(m.parent_id, m.id), m.order_index ASC, m.name ASC
            ";

            $parentRows = Utility::safeQuery($parentQuery, $parentIds);
            if (empty($parentRows)) {
                break;
            }

            $newParentIds = [];
            foreach ($parentRows as $parentRow) {
                $parentId = (int)$parentRow['id'];
                if (!isset($rowsById[$parentId])) {
                    $rowsById[$parentId] = $parentRow;
                    if (!empty($parentRow['parent_id'])) {
                        $candidate = (int)$parentRow['parent_id'];
                        if (!isset($rowsById[$candidate])) {
                            $newParentIds[] = $candidate;
                        }
                    }
                }
            }

            $parentIds = array_unique($newParentIds);
        }

        return self::buildMenuTreeFromRows(array_values($rowsById), $context);
    }

    private static function buildMenuTreeFromRows(array $rows, string $context): array {
        $nodes = [];
        foreach ($rows as $row) {
            $href = self::normalizeMenuHref($row);
            $key = self::normalizeRouteKey($href);

            $nodes[(int)$row['id']] = [
                'id' => (int)$row['id'],
                'parent_id' => $row['parent_id'] !== null ? (int)$row['parent_id'] : null,
                'key' => $key,
                'href' => $href,
                'label' => $row['name'] ?: ucfirst(str_replace(['-', '_'], [' ', ' '], $key)),
                'page' => self::resolvePageForUrl($href),
                'title' => self::resolveRouteTitle($row, $context),
                'icon' => $row['icon'] ?? null,
                'disabled' => ($row['status'] ?? '') !== 'active',
                'meta' => self::buildRouteMeta($row, $key),
                'children' => [],
                'order_index' => (int)($row['order_index'] ?? 0),
            ];
        }

        foreach ($nodes as $id => &$node) {
            if ($node['parent_id'] !== null && isset($nodes[$node['parent_id']])) {
                $nodes[$node['parent_id']]['children'][] = &$node;
            }
        }
        unset($node);

        $tree = [];
        foreach ($nodes as $node) {
            if ($node['parent_id'] === null || !isset($nodes[$node['parent_id']])) {
                $tree[] = $node;
            }
        }

        self::sortMenuTree($tree);

        return self::cleanupMenuNodes($tree);
    }

    private static function normalizeMenuHref(array $row): string {
        $href = trim((string)($row['url'] ?? ''), '/');
        if ($href === '') {
            $href = trim((string)($row['keyword'] ?? ''), '/');
        }

        if ($href === '') {
            return '/';
        }

        return '/' . $href;
    }

    private static function normalizeRouteKey(string $href): string {
        $key = trim($href, '/');
        if ($key === 'admin') {
            return 'admin/index';
        }

        return $key === '' ? 'splash' : $key;
    }

    private static function resolvePageForUrl(string $href): string {
        $path = trim(parse_url($href, PHP_URL_PATH), '/');
        if ($path === '') {
            return 'home.php';
        }

        return $path . '.php';
    }

    private static function resolveRouteTitle(array $row, string $context): string {
        $label = $row['name'] ?? null;
        if ($label === null || $label === '') {
            $label = trim((string)$row['keyword']);
        }

        $suffix = $context === 'admin' ? 'EduAttend Admin' : 'EduAttend Store';
        return trim($label . ' - ' . $suffix);
    }

    private static function buildRouteMeta(array $row, string $routeKey): array {
        $meta = [];
        if (!empty($row['keyword'])) {
            $meta['keyword'] = $row['keyword'];
        }
        if (!empty($row['group_keyword'])) {
            $meta['group_keyword'] = $row['group_keyword'];
        }
        if (str_starts_with($routeKey, 'category/')) {
            $meta['category_slug'] = basename($routeKey);
            $meta['category_label'] = $row['name'] ?? '';
        }

        return $meta;
    }

    private static function sortMenuTree(array &$routes): void {
        usort($routes, function ($left, $right) {
            $order = $left['order_index'] <=> $right['order_index'];
            return $order !== 0 ? $order : strcasecmp($left['label'], $right['label']);
        });

        foreach ($routes as &$route) {
            if (!empty($route['children'])) {
                self::sortMenuTree($route['children']);
            }
        }
        unset($route);
    }

    private static function cleanupMenuNodes(array $routes): array {
        return array_map(function ($route) {
            unset($route['id'], $route['parent_id'], $route['order_index']);
            if (!empty($route['children'])) {
                $route['children'] = self::cleanupMenuNodes($route['children']);
            }
            return $route;
        }, $routes);
    }

    private static function getFallbackMenuRoutes(string $context = 'main'): array {
        $mainRoutes = [
            [
                'key' => 'home',
                'href' => '/home',
                'label' => 'Home',
                'page' => 'home.php',
                'title' => 'Home - EduAttend Store',
                'disabled' => false,
                'children' => [],
            ],
            [
                'key' => 'products',
                'href' => '/products',
                'label' => 'Products',
                'page' => 'products.php',
                'title' => 'All Products - EduAttend Store',
                'disabled' => false,
                'children' => [],
            ],
            [
                'key' => 'category',
                'href' => '/category',
                'label' => 'Categories',
                'page' => 'category.php',
                'title' => 'Categories - EduAttend Store',
                'disabled' => false,
                'children' => [
                    [
                        'key' => 'category/natural-beauty',
                        'parent' => 'category',
                        'href' => '/category/natural-beauty',
                        'label' => 'Natural Beauty',
                        'page' => 'natural-beauty.php',
                        'title' => 'Natural Beauty - EduAttend Store',
                        'disabled' => false,
                        'meta' => [
                            'category_slug' => 'natural-beauty',
                            'category_label' => 'Natural Beauty',
                        ],
                        'children' => [],
                    ],
                    [
                        'key' => 'category/atelier-electronics',
                        'parent' => 'category',
                        'href' => '/category/atelier-electronics',
                        'label' => 'Atelier Electronics',
                        'page' => 'atelier-electronics.php',
                        'title' => 'Atelier Electronics - EduAttend Store',
                        'disabled' => false,
                        'meta' => [
                            'category_slug' => 'atelier-electronics',
                            'category_label' => 'Atelier Electronics',
                        ],
                        'children' => [],
                    ],
                    [
                        'key' => 'category/heritage-fashion',
                        'parent' => 'category',
                        'href' => '/category/heritage-fashion',
                        'label' => 'Heritage Fashion',
                        'page' => 'heritage-fashion.php',
                        'title' => 'Heritage Fashion - EduAttend Store',
                        'disabled' => false,
                        'meta' => [
                            'category_slug' => 'heritage-fashion',
                            'category_label' => 'Heritage Fashion',
                        ],
                        'children' => [],
                    ],
                    [
                        'key' => 'category/sanctuary-home',
                        'parent' => 'category',
                        'href' => '/category/sanctuary-home',
                        'label' => 'Sanctuary Home',
                        'page' => 'sanctuary-home.php',
                        'title' => 'Sanctuary Home - EduAttend Store',
                        'disabled' => false,
                        'meta' => [
                            'category_slug' => 'sanctuary-home',
                            'category_label' => 'Sanctuary Home',
                        ],
                        'children' => [],
                    ],
                    [
                        'key' => 'category/lifestyle-essentials',
                        'parent' => 'category',
                        'href' => '/category/lifestyle-essentials',
                        'label' => 'Lifestyle Essentials',
                        'page' => 'lifestyle-essentials.php',
                        'title' => 'Lifestyle Essentials - EduAttend Store',
                        'disabled' => false,
                        'meta' => [
                            'category_slug' => 'lifestyle-essentials',
                            'category_label' => 'Lifestyle Essentials',
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ];

        $adminRoutes = [
            [
                'key' => 'admin/index',
                'href' => '/admin/index',
                'label' => 'Dashboard',
                'icon' => 'dashboard',
                'page' => 'admin/index.php',
                'title' => 'Admin Dashboard - EduAttend',
                'disabled' => false,
                'children' => [],
            ],
            [
                'key' => 'admin/inventory',
                'href' => '/admin/inventory',
                'label' => 'Inventory',
                'icon' => 'inventory_2',
                'page' => 'admin/inventory.php',
                'title' => 'Inventory Console - EduAttend Heritage',
                'disabled' => false,
                'children' => [
                    [
                        'key' => 'admin/add-product',
                        'parent' => 'admin/inventory',
                        'href' => '/admin/add-product',
                        'label' => 'Add Product',
                        'page' => 'admin/add-product.php',
                        'title' => 'Add Product - EduAttend Admin',
                        'disabled' => false,
                        'children' => [],
                    ],
                ],
            ],
            [
                'key' => 'admin/orders',
                'href' => '/admin/orders',
                'label' => 'Orders',
                'icon' => 'shopping_cart',
                'page' => 'admin/orders.php',
                'title' => 'Orders Registry - EduAttend Admin',
                'disabled' => false,
                'children' => [],
            ],
            [
                'key' => 'admin/customers',
                'href' => '/admin/customers',
                'label' => 'Users',
                'icon' => 'group',
                'page' => 'admin/customers.php',
                'title' => 'Customers - EduAttend Admin',
                'disabled' => false,
                'children' => [],
            ],
            [
                'key' => 'admin/messages',
                'href' => '/admin/messages',
                'label' => 'Messages',
                'icon' => 'mail',
                'page' => 'admin/messages.php',
                'title' => 'Messages - EduAttend Admin',
                'disabled' => false,
                'children' => [
                    [
                        'key' => 'admin/feedback',
                        'parent' => 'admin/messages',
                        'href' => '/admin/feedback',
                        'label' => 'Feedback',
                        'page' => 'admin/feedback.php',
                        'title' => 'Feedback - EduAttend Admin',
                        'disabled' => false,
                        'children' => [],
                    ],
                    [
                        'key' => 'admin/subscribers',
                        'parent' => 'admin/messages',
                        'href' => '/admin/subscribers',
                        'label' => 'Subscribers',
                        'page' => 'admin/subscribers.php',
                        'title' => 'Subscribers - EduAttend Admin',
                        'disabled' => false,
                        'children' => [],
                    ],
                    [
                        'key' => 'admin/newsletters',
                        'parent' => 'admin/messages',
                        'href' => '/admin/newsletters',
                        'label' => 'Newsletters',
                        'page' => 'admin/newsletters.php',
                        'title' => 'Newsletters - EduAttend Admin',
                        'disabled' => false,
                        'children' => [],
                    ],
                ],
            ],
            [
                'key' => 'admin/reports',
                'href' => '/admin/reports',
                'label' => 'Analytics',
                'icon' => 'analytics',
                'page' => 'admin/reports.php',
                'title' => 'Reports - EduAttend Admin',
                'disabled' => false,
                'children' => [
                    [
                        'key' => 'admin/permissions',
                        'parent' => 'admin/reports',
                        'href' => '/admin/permissions',
                        'label' => 'Permissions',
                        'page' => 'admin/permissions.php',
                        'title' => 'Permissions - EduAttend Admin',
                        'disabled' => false,
                        'children' => [],
                    ],
                    [
                        'key' => 'admin/shipping',
                        'parent' => 'admin/reports',
                        'href' => '/admin/shipping',
                        'label' => 'Shipping',
                        'page' => 'admin/shipping.php',
                        'title' => 'Shipping - EduAttend Admin',
                        'disabled' => false,
                        'children' => [],
                    ],
                ],
            ],
            [
                'key' => 'admin/settings',
                'href' => '/admin/settings',
                'label' => 'Settings',
                'icon' => 'settings',
                'page' => 'admin/settings.php',
                'title' => 'Settings Hub - EduAttend Admin',
                'disabled' => false,
                'children' => [],
            ],
        ];

        return $context === 'admin' ? $adminRoutes : $mainRoutes;
    }

    public static function flattenRoutes(array $routes): array {
        $flattened = [];
        foreach ($routes as $route) {
            $flattened[$route['key']] = $route;
            if (!empty($route['children'])) {
                $flattened = array_merge($flattened, self::flattenRoutes($route['children']));
            }
        }

        return $flattened;
    }

    public static function getRouteByPath(string $path, string $context = 'main'): ?array {
        $normalizedPath = self::normalizePath($path);
        if ($normalizedPath === 'admin') {
            $normalizedPath = 'admin/index';
        }

        $routes = self::flattenRoutes(self::getMenuRoutes($context));

        if (isset($routes[$normalizedPath])) {
            return $routes[$normalizedPath];
        }

        foreach ($routes as $route) {
            $routeHref = trim($route['href'] ?? '', '/');
            if ($routeHref === $normalizedPath) {
                return $route;
            }
        }

        if ($context === 'main' && strpos($normalizedPath, 'admin/') === 0) {
            return self::getRouteByPath($normalizedPath, 'admin');
        }

        return null;
    }

    public static function injectRouteParameters(array $route, string $path): void {
        $_GET['route'] = $route['key'];
        $_GET['route_path'] = $path;
        if (!empty($route['meta']) && is_array($route['meta'])) {
            foreach ($route['meta'] as $metaKey => $metaValue) {
                $_GET['route_meta_' . $metaKey] = $metaValue;
            }
        }

        $_GET['route_segments'] = explode('/', trim($path, '/'));
    }

    public static function removeBasePath(string $path): string {
        $basePath = 'dee';
        if (strpos($path, $basePath) === 0) {
            return ltrim(substr($path, strlen($basePath)), '/');
        }

        return $path;
    }

    public static function normalizePath(string $path): string {
        $path = trim($path, '/');
        return $path === '' ? 'splash' : $path;
    }

    public static function isRouteActive(array $route, string $currentRoute): bool {
        if ($route['key'] === $currentRoute) {
            return true;
        }

        if (!empty($route['children'])) {
            foreach ($route['children'] as $child) {
                if (self::isRouteActive($child, $currentRoute)) {
                    return true;
                }
            }
        }

        return false;
    }


    public static function getChildrens($parentKey): array {
        if ($parentKey === null) {
            return Utility::safeQuery(
                "SELECT id, name AS title, keyword, url AS href, parent_id FROM menu WHERE parent_id IS NULL AND status = 'active' ORDER BY order_index ASC, name ASC"
            );
        }

        return Utility::safeQuery(
            "SELECT id, name AS title, keyword, url AS href, parent_id FROM menu WHERE parent_id = ? AND status = 'active' ORDER BY order_index ASC, name ASC",
            [$parentKey]
        );
    }

    public static function packChildrens($parentKey = null): array {
        $items = self::getChildrens($parentKey);
        if (empty($items)) {
            return [];
        }

        $tree = [];
        $indexed = [];
        foreach ($items as $item) {
            $item['children'] = [];
            $indexed[$item['id']] = $item;
        }

        foreach ($indexed as $id => $item) {
            if (!empty($item['parent_id']) && isset($indexed[$item['parent_id']])) {
                $indexed[$item['parent_id']]['children'][] = $item;
            } else {
                $tree[] = $item;
            }
        }

        return $tree;
    }
}
?>