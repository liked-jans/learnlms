<?php
$role = $_SESSION['role'];
$baseUrl = BASE_URL;
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-layer-group"></i></div>
        <div class="brand-text">
            <span class="brand-name">BlendEd</span>
            <span class="brand-sub">LMS</span>
        </div>
    </div>
    <style>
    /* Sidebar Brand Styling */
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
}
    </style>
    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
        <a href="<?= $baseUrl ?>admin/dashboard.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'admin/dashboard') !== false ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
        <a href="<?= $baseUrl ?>admin/users.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'users') !== false ? 'active' : '' ?>">
            <i class="fas fa-users"></i><span>Users</span></a>
        <a href="<?= $baseUrl ?>admin/departments.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'departments') !== false ? 'active' : '' ?>">
            <i class="fas fa-building"></i><span>Departments</span></a>
        <a href="<?= $baseUrl ?>admin/courses.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'courses') !== false ? 'active' : '' ?>">
            <i class="fas fa-book"></i><span>Courses</span></a>
        <a href="<?= $baseUrl ?>admin/syllabi.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'admin/syllabi') !== false ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i><span>Syllabi</span></a>
        <a href="<?= $baseUrl ?>admin/grades.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'admin/grades') !== false ? 'active' : '' ?>">
            <i class="fas fa-graduation-cap"></i><span>Master Grades</span></a>
        <a href="<?= $baseUrl ?>admin/announcements.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'announcements') !== false ? 'active' : '' ?>">
            <i class="fas fa-bullhorn"></i><span>Announcements</span></a>
        <a href="<?= $baseUrl ?>admin/reports.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'reports') !== false ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i><span>Reports</span></a>
        <a href="<?= $baseUrl ?>admin/logs.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'logs') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-font-awesome"></i><span>Logs</span></a>


        <?php elseif ($role === 'teacher'): ?>
        <a href="<?= $baseUrl ?>teacher/dashboard.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'teacher/dashboard') !== false ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
        <a href="<?= $baseUrl ?>teacher/syllabi.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'teacher/syllabi') !== false ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i><span>My Syllabi</span></a>
        <a href="<?= $baseUrl ?>teacher/topics.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'topics') !== false ? 'active' : '' ?>">
            <i class="fas fa-list-ul"></i><span>Topics / Mapping</span></a>
        <a href="<?= $baseUrl ?>teacher/materials.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'materials') !== false ? 'active' : '' ?>">
            <i class="fas fa-folder-open"></i><span>Materials</span></a>
        <a href="<?= $baseUrl ?>teacher/assessments.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'assessments') !== false ? 'active' : '' ?>">
            <i class="fas fa-tasks"></i><span>Assessments</span></a>
        <a href="<?= $baseUrl ?>teacher/students.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'teacher/students') !== false ? 'active' : '' ?>">
            <i class="fas fa-user-graduate"></i><span>Students</span></a>
        <a href="<?= $baseUrl ?>teacher/grades.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'grades') !== false ? 'active' : '' ?>">
            <i class="fas fa-star-half-alt"></i><span>Grades</span></a>
        <a href="<?= $baseUrl ?>teacher/announcements.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'announcements') !== false ? 'active' : '' ?>">
            <i class="fas fa-bullhorn"></i><span>Announcements</span></a>

        <?php elseif ($role === 'student'): ?>
        <a href="<?= $baseUrl ?>student/dashboard.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'student/dashboard') !== false ? 'active' : '' ?>">
            <i class="fas fa-home"></i><span>Dashboard</span></a>
        <a href="<?= $baseUrl ?>student/courses.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'student/courses') !== false ? 'active' : '' ?>">
            <i class="fas fa-book-open"></i><span>My Courses</span></a>
        <a href="<?= $baseUrl ?>student/syllabus.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'student/syllabus') !== false ? 'active' : '' ?>">
            <i class="fas fa-map"></i><span>Syllabus Map</span></a>
        <a href="<?= $baseUrl ?>student/materials.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'student/materials') !== false ? 'active' : '' ?>">
            <i class="fas fa-folder"></i><span>Materials</span></a>
        <a href="<?= $baseUrl ?>student/assessments.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'student/assessments') !== false ? 'active' : '' ?>">
            <i class="fas fa-pencil-alt"></i><span>Assessments</span></a>
        <a href="<?= $baseUrl ?>student/progress.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'progress') !== false ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i><span>My Progress</span></a>
        <a href="<?= $baseUrl ?>student/announcements.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'student/announcements') !== false ? 'active' : '' ?>">
            <i class="fas fa-bullhorn"></i><span>Announcements</span></a>
        <?php endif; ?>

        <div class="nav-divider"></div>
        <a href="<?= $baseUrl ?>profile.php" class="nav-item">
            <i class="fas fa-user-cog"></i><span>Profile</span></a>
        <a href="<?= $baseUrl ?>logout.php" class="nav-item nav-logout">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </nav>
</aside>
<style>
/* Sidebar Brand Styling */
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
}
</style>