<?php
/**
 * Core Configuration - BlendEd LMS
 */
// define('DB_HOST', 'sql305.infinityfree.com');
// define('DB_USER', 'if0_42325974');
// define('DB_PASS', 'u2JBrN66mb3ym4');
// define('DB_NAME', 'if0_42325974_learnlms');
// define('SITE_NAME', 'BlendEd LMS');

// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'learnlms');
// Load local .env if present (used for local testing against Railway DB)
$_envFile = dirname(__DIR__) . '/.env';
if (file_exists($_envFile) && is_readable($_envFile)) {
    $_envLines = file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($_envLines as $_line) {
        $_line = trim($_line);
        if ($_line === '' || $_line[0] === '#') continue;
        if (strpos($_line, '=') !== false) {
            list($_key, $_val) = explode('=', $_line, 2);
            $_key = trim($_key);
            $_val = trim($_val, " \t\n\r\0\x0B\"'");
            if (getenv($_key) === false || getenv($_key) === '') {
                putenv("$_key=$_val");
                $_ENV[$_key] = $_val;
                $_SERVER[$_key] = $_val;
            }
        }
    }
}

define('DB_HOST', getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost'));
define('DB_USER', getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root'));
define('DB_PASS', getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));
define('DB_NAME', getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'learnlms'));
define('DB_PORT', (int)(getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306)));
define('SITE_NAME', getenv('SITE_NAME') ?: 'BlendEd LMS');

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

// Check for reverse proxy scheme (Railway, Cloudflare, etc.)
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $_scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
} elseif ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443) {
    $_scheme = 'https';
} else {
    $_scheme = 'http';
}

// Check for host header (prefer X-Forwarded-Host if available)
$_host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
if (strpos($_host, ',') !== false) {
    $_host = trim(explode(',', $_host)[0]);
}

// Strip internal container port (:8080, :8000, etc.) when on Railway or behind reverse proxy
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) || !empty($_SERVER['HTTP_X_FORWARDED_HOST']) || !empty(getenv('RAILWAY_ENVIRONMENT')) || $_scheme === 'https' || preg_match('/\.railway\.app/', $_host) || preg_match('/:\d+$/', $_host)) {
    // If not local development (localhost / 127.0.0.1 on custom port), strip port
    if (!preg_match('/^(localhost|127\.0\.0\.1):[0-9]+$/', $_host) || !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) || !empty(getenv('RAILWAY_ENVIRONMENT'))) {
        $_host = preg_replace('/:\d+$/', '', $_host);
    }
}

if (getenv('APP_URL')) {
    define('BASE_URL', rtrim(getenv('APP_URL'), '/') . '/');
} else {
    define('BASE_URL', $_scheme . '://' . $_host . rtrim($_relPath, '/') . '/');
}
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
$conn->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
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
        header("Location: " . getRoleDashboard($_SESSION['role'] ?? ''));
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
// Security Helpers (CSRF & Upload Validation)
// ---------------------------------------------------------------------
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    $token = htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verifyCsrfToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$token)) {
        return false;
    }
    return true;
}

function validateUploadedFile($file, $allowedExts = ['pdf','doc','docx','ppt','pptx','jpg','jpeg','png','zip'], $maxBytes = 26214400) {
    if (!isset($file) || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error code: ' . ($file['error'] ?? 'missing')];
    }
    if ($file['size'] > $maxBytes) {
        return ['valid' => false, 'error' => 'File exceeds maximum allowed size of ' . round($maxBytes / 1048576) . 'MB.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $disallowed = ['php','phtml','php3','php4','php5','php7','phps','cgi','pl','py','sh','bash','exe','bat','cmd','dll','js','html','htm'];
    if (in_array($ext, $disallowed, true) || !in_array($ext, $allowedExts, true)) {
        return ['valid' => false, 'error' => 'File format (.' . $ext . ') is not permitted.'];
    }
    return ['valid' => true, 'extension' => $ext];
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

// ---------------------------------------------------------------------
// Assessment & Questionnaire Helpers
// ---------------------------------------------------------------------
function getAssessmentOptionDisplay($optionsJson, $val) {
    if ($val === null || $val === '') return '';
    if (empty($optionsJson)) return (string)$val;
    $opts = is_array($optionsJson) ? $optionsJson : (json_decode($optionsJson, true) ?: []);
    if (empty($opts)) return (string)$val;

    if (isset($opts[$val])) {
        return $opts[$val];
    }

    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $keys = array_keys($opts);
    foreach ($keys as $idx => $k) {
        $letter = (is_string($k) && preg_match('/^[A-Z]$/i', $k)) ? strtoupper($k) : ($letters[$idx] ?? chr(65 + $idx));
        if (strcasecmp((string)$val, (string)$k) === 0 || 
            strcasecmp((string)$val, $letter) === 0 || 
            (is_numeric($val) && (int)$val === $idx)) {
            return $opts[$k];
        }
    }
    return (string)$val;
}

function isAssessmentAnswerCorrect($studentAns, $correctAns, $optionsJson = null) {
    if ($studentAns === null || $studentAns === '' || $correctAns === null || $correctAns === '') {
        return false;
    }
    $studentAns = trim((string)$studentAns);
    $correctAns = trim((string)$correctAns);

    if (strcasecmp($studentAns, $correctAns) === 0) {
        return true;
    }

    if (!empty($optionsJson)) {
        $opts = is_array($optionsJson) ? $optionsJson : (json_decode($optionsJson, true) ?: []);
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $keys = array_keys($opts);
        foreach ($keys as $idx => $k) {
            $letter = (is_string($k) && preg_match('/^[A-Z]$/i', $k)) ? strtoupper($k) : ($letters[$idx] ?? chr(65 + $idx));
            $isStudent = (strcasecmp($studentAns, (string)$k) === 0 || strcasecmp($studentAns, $letter) === 0 || (is_numeric($studentAns) && (int)$studentAns === $idx));
            $isCorrect = (strcasecmp($correctAns, (string)$k) === 0 || strcasecmp($correctAns, $letter) === 0 || (is_numeric($correctAns) && (int)$correctAns === $idx));
            if ($isStudent && $isCorrect) {
                return true;
            }
        }
    }

    return false;
}

