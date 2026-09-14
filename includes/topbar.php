<?php
$roleBadge = ['admin'=>'badge-red','teacher'=>'badge-blue','student'=>'badge-green'];
$roleLabel = ['admin'=>'Administrator','teacher'=>'Teacher','student'=>'Student'];
$r = $_SESSION['role'] ?? 'student';
$rawName = trim($_SESSION['full_name'] ?? '');
$nameWords = preg_split('/[\s,]+/', $rawName, -1, PREG_SPLIT_NO_EMPTY);
$initials = '';
foreach ($nameWords as $w) {
    if (!empty($w)) {
        $initials .= strtoupper($w[0]);
    }
}
$initials = substr($initials, 0, 2);
if ($initials === '') {
    $initials = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2));
}

?>
<header class="topbar">
    <div class="topbar-left">
        <button class="btn btn-secondary btn-sm" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <span class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></span>
    </div>
    <div class="topbar-right">
        <div class="user-badge">
            <div class="user-avatar"><?= $initials ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <div class="user-role"><?= $roleLabel[$r] ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>logout.php" class="btn btn-secondary btn-sm"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</header>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script>
(function(){
    var toggle  = document.getElementById('sidebarToggle');
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (!toggle || !sidebar || !overlay) return;

    function openSidebar(){ sidebar.classList.add('open'); overlay.classList.add('show'); }
    function closeSidebar(){ sidebar.classList.remove('open'); overlay.classList.remove('show'); }

    toggle.addEventListener('click', function(){
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    overlay.addEventListener('click', closeSidebar);
    sidebar.querySelectorAll('.nav-item').forEach(function(a){
        a.addEventListener('click', closeSidebar);
    });
    window.addEventListener('resize', function(){
        if (window.innerWidth > 768) closeSidebar();
    });
})();
</script>
