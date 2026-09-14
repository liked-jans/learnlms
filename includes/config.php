<?php
/**
 * Core Configuration - BlendEd LMS
 */
// define('DB_HOST', 'sql305.infinityfree.com');
// define('DB_USER', 'if0_42325974');
// define('DB_PASS', 'u2JBrN66mb3ym4');
// define('DB_NAME', 'if0_42325974_learnlms');
// define('SITE_NAME', 'BlendEd LMS');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'learnlms');
define('SITE_NAME', 'BlendEd LMS');

// Figure out the site's URL root by comparing the filesystem path of the
// script that's actually running (SCRIPT_FILENAME) against the filesystem
// path of this file's parent directory — both come from the same PHP/OS
// path resolution, so this stays correct even when DOCUMENT_ROOT is
// reported with different letter-casing than __FILE__ (common on Windows/
// XAMPP and the reason style.css and other assets can silently 404).
$_siteRootFs = str_replace('\\', '/', dirname(__DIR__));
$_scriptFs   = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$_scriptUrl  = $_SERVER['SCRIPT_NAME'] ?? '/';

if ($_scriptFs !== '' && strlen($_scriptFs) >= strlen($_siteRootFs)) {
    $_tail = substr($_scriptFs, strlen($_siteRootFs)); // e.g. /admin/dashboard.php
    $_relPath = substr($_scriptUrl, 0, strlen($_scriptUrl) - strlen($_tail));
} else {
    // Fallback to the old DOCUMENT_ROOT diff if SCRIPT_FILENAME is unavailable
    $_docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $_relPath = str_replace($_docRoot, '', rtrim($_siteRootFs, '/'));
}

$_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443 ? 'https' : 'http';
define('BASE_URL', $_scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim($_relPath, '/') . '/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isMaintenanceMode() {
    global $conn;
    static $cached = null;
    if ($cached !== null) return $cached;

    $result = $conn->query("SELECT maintenance_mode FROM system_settings WHERE id = 1 LIMIT 1");
    $row = $result ? $result->fetch_assoc() : null;
    $cached = $row ? (bool)$row['maintenance_mode'] : false;
    return $cached;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }

    if (isMaintenanceMode() && ($_SESSION['role'] ?? '') !== 'admin') {
        header("Location: " . BASE_URL . "maintenance.php");
        exit();
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', (array)$roles)) {
        header("Location: " . BASE_URL . "dashboard.php");
        exit();
    }
}

function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data))));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function setFlash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function ensureColumnExists($table, $column, $definition) {
    global $conn;
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if ($table === '' || $column === '') return false;

    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows > 0) return true;

    return (bool)$conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
}

function getRoleDashboard($role) {
    switch($role) {
        case 'admin': return BASE_URL . 'admin/dashboard.php';
        case 'teacher': return BASE_URL . 'teacher/dashboard.php';
        case 'student': return BASE_URL . 'student/dashboard.php';
        default: return BASE_URL . 'index.php';
    }
}

// ---------------------------------------------------------------------
// Activity logging (login, logout, timeout, enrollments, etc.)
// ---------------------------------------------------------------------
// Auto-creates activity_logs the first time it's needed — no manual SQL
// setup required for this part.
// Usage: logActivity($_SESSION['user_id'], 'Logged in', 'Authentication');
function logActivity($user_id, $description, $category) {
    global $conn;

    $conn->query("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            description VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_activity_user (user_id),
            INDEX idx_activity_category (category),
            INDEX idx_activity_created (created_at)
        )
    ");

    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, description, category) VALUES (?,?,?)");
    $stmt->bind_param('iss', $user_id, $description, $category);
    $stmt->execute();
}

// Maintenance Mode Logic & Auto-Logout for Student/Teacher
if (isMaintenanceMode()) {
    if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['student', 'teacher'])) {
        $wasLoggedIn = isLoggedIn();
        $uid = $_SESSION['user_id'] ?? null;

        if ($wasLoggedIn && $uid) {
            logActivity($uid, 'Auto-logged out (maintenance mode enabled)', 'Authentication');
        }

        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        header("Location: " . BASE_URL . "maintenance.php");
        exit();
    }
    $maintenanceExempt = ['maintenance.php', 'login.php', 'logout.php', 'auth.php'];
    $currentScript = basename($_SERVER['SCRIPT_NAME']);
    if (!in_array($currentScript, $maintenanceExempt, true) && ($_SESSION['role'] ?? '') !== 'admin') {
        redirect(BASE_URL . 'maintenance.php');
    }
}
