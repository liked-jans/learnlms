<?php
// includes/layout.php - Shared layout components

function renderHead($title = 'BlendEd LMS') {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . htmlspecialchars($title) . ' | BlendEd LMS</title>
<link rel="stylesheet" href="' . BASE_URL . 'assets/css/style.css">
<style>/* Sidebar Brand Styling */
.sidebar-brand { 
    display: flex; 
    align-items: center; 
    gap: 14px; 
    padding: 24px 20px; 
    border-bottom: 1px solid rgba(255,255,255,.07); 
}

.brand-icon { 
    width: 42px; 
    height: 42px; 
    background: var(--primary-mid); 
    border-radius: 12px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 18px;
    color: white;
}

.brand-text { 
    display: flex; 
    flex-direction: column; 
    justify-content: center;
}

.brand-name { 
    font-family: var(--font-display); 
    font-size: 18px; 
    font-weight: 600; 
    color: white;
    line-height: 1.2;
}

.brand-sub { 
    font-size: 10px; 
    opacity: 0.55; 
    letter-spacing: 1.5px; 
    text-transform: uppercase; 
    font-weight: 700;
    margin-top: 2px;
}</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>';
}

function renderSidebar($role) {
    $user = $_SESSION;
    $initial = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
    $base = BASE_URL;
    
    if ($role === 'admin') {
        $nav = [
            ['Dashboard', 'fa-gauge', $base . 'admin/dashboard.php'],
            ['Users', 'fa-users', $base . 'admin/users.php'],
            ['Departments', 'fa-building', $base . 'admin/departments.php'],
            ['Subjects', 'fa-book', $base . 'admin/subjects.php'],
            ['Classes', 'fa-chalkboard-teacher', $base . 'admin/classes.php'],
            ['Enrollments', 'fa-user-plus', $base . 'admin/enrollments.php'],
            ['Announcements', 'fa-bullhorn', $base . 'admin/announcements.php'],
            ['Reports', 'fa-chart-bar', $base . 'admin/reports.php'],
            ['Activity Logs', 'fa-list-check', $base . 'admin/logs.php'],
        ];
    } elseif ($role === 'teacher') {
        $nav = [
            ['Dashboard', 'fa-gauge', $base . 'teacher/dashboard.php'],
            ['My Classes', 'fa-chalkboard', $base . 'teacher/classes.php'],
            ['Syllabus Builder', 'fa-map', $base . 'teacher/syllabus.php'],
            ['Materials', 'fa-folder-open', $base . 'teacher/materials.php'],
            ['Assignments', 'fa-file-alt', $base . 'teacher/assignments.php'],
            ['Attendance', 'fa-calendar-check', $base . 'teacher/attendance.php'],
            ['Announcements', 'fa-bullhorn', $base . 'teacher/announcements.php'],
            ['Grades', 'fa-star', $base . 'teacher/grades.php'],
        ];
    } else {
        $nav = [
            ['Dashboard', 'fa-gauge', $base . 'student/dashboard.php'],
            ['My Classes', 'fa-chalkboard', $base . 'student/classes.php'],
            ['Syllabus', 'fa-map', $base . 'student/syllabus.php'],
            ['Materials', 'fa-folder-open', $base . 'student/materials.php'],
            ['Assignments', 'fa-file-alt', $base . 'student/assignments.php'],
            ['Attendance', 'fa-calendar-check', $base . 'student/attendance.php'],
            ['Grades', 'fa-star', $base . 'student/grades.php'],
            ['Announcements', 'fa-bullhorn', $base . 'student/announcements.php'],
        ];
    }
    
    $current = basename($_SERVER['PHP_SELF']);
    
    echo '<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="fa fa-graduation-cap"></i></div>
            <div>
                <h2>BlendEd</h2>
                <span>Learning System</span>
            </div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-label">Navigation</div>';
    
    foreach ($nav as $item) {
        $file = basename($item[2]);
        $isActive = ($current === $file) ? 'active' : '';
        echo '<a href="' . $item[2] . '" class="nav-item ' . $isActive . '">
            <i class="fa ' . $item[1] . ' nav-icon"></i>
            <span>' . $item[0] . '</span>
        </a>';
    }
    
    echo '</div></nav>
    <div class="sidebar-footer">
        <div class="user-card-sidebar">
            <div class="user-avatar">' . $initial . '</div>
            <div class="user-info">
                <div class="user-name">' . htmlspecialchars($user['full_name'] ?? '') . '</div>
                <div class="user-role">' . ucfirst($role) . '</div>
            </div>
            <a href="' . $base . 'logout.php" class="logout-btn" title="Logout">
                <i class="fa fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
    </aside>';
}

function renderTopbar($title, $subtitle = '') {
    echo '<div class="topbar">
        <button class="btn btn-secondary btn-sm" id="sidebarToggle" style="display:none">
            <i class="fa fa-bars"></i>
        </button>
        <div class="topbar-title">
            <h1>' . htmlspecialchars($title) . '</h1>
            ' . ($subtitle ? '<p>' . htmlspecialchars($subtitle) . '</p>' : '') . '
        </div>
        <div class="topbar-actions">
            <button class="notif-btn" title="Notifications"><i class="fa fa-bell"></i><span class="notif-dot"></span></button>
        </div>
    </div>';
}

function closeLayout() {
    echo '<script src="' . BASE_URL . 'assets/js/main.js"></script>
    </body></html>';
}
?>
