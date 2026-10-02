<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Reports & Analytics';

$totalUsers = $conn->query("SELECT role, COUNT(*) c FROM users WHERE role!='admin' GROUP BY role")->fetch_all(MYSQLI_ASSOC);
// Syllabi status counts
$statusCounts = [
    'published' => (int)($conn->query("SELECT COUNT(*) c FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.status='published'")->fetch_assoc()['c'] ?? 0),
    'draft'     => (int)($conn->query("SELECT COUNT(*) c FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.status='draft'")->fetch_assoc()['c'] ?? 0),
    'archived'  => (int)($conn->query("SELECT COUNT(*) c FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.status='archived'")->fetch_assoc()['c'] ?? 0),
];
$totalSyllabiCount = array_sum($statusCounts);

// Curriculum Coverage
$syllabiWithMaterials = (int)($conn->query("SELECT COUNT(DISTINCT s.id) c FROM syllabi s JOIN courses c ON s.course_id=c.id JOIN learning_materials lm ON lm.syllabus_id=s.id")->fetch_assoc()['c'] ?? 0);
$syllabiWithAssessments = (int)($conn->query("SELECT COUNT(DISTINCT s.id) c FROM syllabi s JOIN courses c ON s.course_id=c.id JOIN assessments a ON a.syllabus_id=s.id")->fetch_assoc()['c'] ?? 0);

// Department breakdown
$deptDistribution = $conn->query("
    SELECT d.name as dept_name, d.code as dept_code, COUNT(s.id) as syl_count, SUM(c.units) as total_units
    FROM syllabi s
    JOIN courses c ON s.course_id = c.id
    JOIN departments d ON c.department_id = d.id
    GROUP BY d.id
    ORDER BY syl_count DESC
")->fetch_all(MYSQLI_ASSOC);

$topicsByMode = $conn->query("SELECT st.delivery_mode, COUNT(*) c FROM syllabus_topics st JOIN syllabi s ON st.syllabus_id=s.id JOIN courses c ON s.course_id=c.id GROUP BY st.delivery_mode ORDER BY c DESC")->fetch_all(MYSQLI_ASSOC);
$enrollStats = (int)($conn->query("SELECT COUNT(*) c FROM enrollments e JOIN syllabi s ON e.syllabus_id=s.id JOIN courses c ON s.course_id=c.id WHERE e.status='enrolled'")->fetch_assoc()['c'] ?? 0);
$completedTopics = (int)($conn->query("SELECT COUNT(DISTINCT tp.id) c FROM topic_progress tp JOIN syllabus_topics st ON tp.syllabus_topic_id=st.id JOIN syllabi s ON st.syllabus_id=s.id JOIN courses c ON s.course_id=c.id JOIN enrollments e ON e.student_id=tp.student_id AND e.syllabus_id=s.id WHERE tp.status='completed' AND e.status='enrolled'")->fetch_assoc()['c'] ?? 0);
$totalTopics = (int)($conn->query("SELECT COUNT(*) c FROM syllabus_topics st JOIN syllabi s ON st.syllabus_id=s.id JOIN courses c ON s.course_id=c.id")->fetch_assoc()['c'] ?? 0);
$totalAssignedStudentTopics = (int)($conn->query("SELECT COUNT(*) c FROM enrollments e JOIN syllabi s ON e.syllabus_id=s.id JOIN courses c ON s.course_id=c.id JOIN syllabus_topics st ON st.syllabus_id=s.id WHERE e.status='enrolled'")->fetch_assoc()['c'] ?? 0);
$topCourses = $conn->query("SELECT c.course_name, c.course_code, COUNT(e.id) enroll FROM courses c LEFT JOIN syllabi s ON s.course_id=c.id LEFT JOIN enrollments e ON e.syllabus_id=s.id GROUP BY c.id ORDER BY enroll DESC LIMIT 5");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header"><div class="page-header-left"><h2>Reports & Analytics</h2><p>System-wide statistics and insights</p></div></div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
    <?php foreach($totalUsers as $u): ?>
    <div class="stat-card <?= $u['role']==='teacher'?'blue':'orange' ?>">
        <div class="stat-icon <?= $u['role']==='teacher'?'blue':'orange' ?>"><i class="fas fa-<?= $u['role']==='teacher'?'chalkboard-teacher':'user-graduate' ?>"></i></div>
        <div class="stat-info"><div class="stat-num"><?= $u['c'] ?></div><div class="stat-label"><?= ucfirst($u['role']) ?>s</div></div>
    </div>
    <?php endforeach; ?>
    <div class="stat-card green"><div class="stat-icon green"><i class="fas fa-users"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $enrollStats ?></div><div class="stat-label">Active Enrollments</div></div></div>
    <div class="stat-card blue"><div class="stat-icon blue"><i class="fas fa-check-circle"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $completedTopics ?></div><div class="stat-label">Student Topics Completed</div></div></div>
</div>

<div class="dash-grid">
<div>
<!-- Enhanced Curriculum & Syllabus Operations Card -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title"><i class="fas fa-book-open" style="color:var(--primary);margin-right:8px"></i> Curriculum & Syllabus Health</span>
        <span class="badge badge-green" style="font-size:11px;font-weight:700"><?= $totalSyllabiCount ?> Syllabi Active</span>
    </div>
    <div class="card-body">
        <!-- Status Badges Strip -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 12px;text-align:center">
                <div style="font-size:11px;color:#166534;font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Published</div>
                <div style="font-size:22px;font-weight:800;color:#15803d;margin-top:2px"><?= $statusCounts['published'] ?></div>
                <div style="font-size:10px;color:#16a34a;margin-top:2px"><i class="fas fa-check-circle"></i> Live for Students</div>
            </div>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;text-align:center">
                <div style="font-size:11px;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Draft</div>
                <div style="font-size:22px;font-weight:800;color:#334155;margin-top:2px"><?= $statusCounts['draft'] ?></div>
                <div style="font-size:10px;color:#64748b;margin-top:2px"><i class="fas fa-pencil-alt"></i> In Preparation</div>
            </div>
            <div style="background:#fffbeb;border:1px solid #fef3c7;border-radius:8px;padding:10px 12px;text-align:center">
                <div style="font-size:11px;color:#92400e;font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Archived</div>
                <div style="font-size:22px;font-weight:800;color:#b45309;margin-top:2px"><?= $statusCounts['archived'] ?></div>
                <div style="font-size:10px;color:#d97706;margin-top:2px"><i class="fas fa-archive"></i> Prior Semesters</div>
            </div>
        </div>

        <!-- Curriculum Readiness Indicators -->
        <div style="border-top:1px solid var(--border);padding-top:14px;margin-bottom:14px">
            <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:10px;text-transform:uppercase;letter-spacing:0.5px">Instructional Readiness</div>
            
            <?php 
                $matPct = $totalSyllabiCount > 0 ? round(($syllabiWithMaterials / $totalSyllabiCount) * 100) : 0;
                $assPct = $totalSyllabiCount > 0 ? round(($syllabiWithAssessments / $totalSyllabiCount) * 100) : 0;
            ?>
            <div style="margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:12px">
                    <span style="color:var(--text2);font-weight:600"><i class="fas fa-file-alt" style="color:#2563eb;margin-right:6px"></i> Learning Modules Uploaded</span>
                    <span style="font-weight:700;color:var(--text)"><?= $syllabiWithMaterials ?> / <?= $totalSyllabiCount ?> (<?= $matPct ?>%)</span>
                </div>
                <div class="progress-bar" style="height:7px;background:#e2e8f0;border-radius:99px;overflow:hidden">
                    <div class="progress-fill" style="width:<?= $matPct ?>%;background:#2563eb;height:100%;border-radius:99px"></div>
                </div>
            </div>

            <div style="margin-bottom:6px">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:12px">
                    <span style="color:var(--text2);font-weight:600"><i class="fas fa-tasks" style="color:#10b981;margin-right:6px"></i> Outcome Assessments Aligned</span>
                    <span style="font-weight:700;color:var(--text)"><?= $syllabiWithAssessments ?> / <?= $totalSyllabiCount ?> (<?= $assPct ?>%)</span>
                </div>
                <div class="progress-bar" style="height:7px;background:#e2e8f0;border-radius:99px;overflow:hidden">
                    <div class="progress-fill" style="width:<?= $assPct ?>%;background:#10b981;height:100%;border-radius:99px"></div>
                </div>
            </div>
        </div>

        <!-- Academic Program Summary -->
        <?php if (!empty($deptDistribution)): ?>
        <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-size:12px;color:var(--text2)">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <span style="font-weight:700;color:var(--text)"><?= htmlspecialchars($deptDistribution[0]['dept_name']) ?></span>
                    <span style="color:var(--text3);margin-left:4px">(<?= htmlspecialchars($deptDistribution[0]['dept_code']) ?>)</span>
                </div>
                <span class="badge badge-gray" style="font-size:10px;font-weight:700"><?= $deptDistribution[0]['total_units'] ?? 0 ?> Units Total</span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Topics by Delivery Mode with Vibrant, High-Contrast Visibility -->
<div class="card" style="margin-top:20px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-title"><i class="fas fa-chalkboard" style="color:#f59e0b;margin-right:8px"></i> Topics by Delivery Mode</span>
        <span style="font-size:12px;color:var(--text3);font-weight:600"><?= $totalTopics ?> Total Mapped Topics</span>
    </div>
    <div class="card-body">
    <?php 
    $total2 = array_sum(array_column($topicsByMode,'c'));
    $modeConfig = [
        'face-to-face' => ['color' => '#d97706', 'bar' => '#f59e0b', 'icon' => 'chalkboard-teacher', 'label' => 'Face-To-Face'],
        'online'       => ['color' => '#1d4ed8', 'bar' => '#2563eb', 'icon' => 'laptop',              'label' => 'Online'],
        'blended'      => ['color' => '#047857', 'bar' => '#10b981', 'icon' => 'layer-group',         'label' => 'Blended'],
        'asynchronous' => ['color' => '#6d28d9', 'bar' => '#8b5cf6', 'icon' => 'clock',               'label' => 'Asynchronous'],
        'synchronous'  => ['color' => '#0e7490', 'bar' => '#06b6d4', 'icon' => 'video',               'label' => 'Synchronous'],
    ];
    foreach($topicsByMode as $m):
        $rawMode = strtolower(trim($m['delivery_mode']));
        $pct2 = $total2 > 0 ? round($m['c'] / $total2 * 100) : 0;
        $cfg = $modeConfig[$rawMode] ?? ['color' => 'var(--primary)', 'bar' => '#1E5C42', 'icon' => 'book', 'label' => ucfirst($m['delivery_mode'])];
    ?>
    <div style="margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
            <span style="font-size:13px;font-weight:600;color:var(--text);display:inline-flex;align-items:center;gap:8px">
                <span style="width:24px;height:24px;border-radius:6px;background:<?= $cfg['bar'] ?>18;display:inline-flex;align-items:center;justify-content:center;color:<?= $cfg['color'] ?>;font-size:11px">
                    <i class="fas fa-<?= $cfg['icon'] ?>"></i>
                </span>
                <?= $cfg['label'] ?>
            </span>
            <span style="font-size:12px;font-weight:700;color:var(--text)">
                <?= $m['c'] ?> <span style="font-weight:500;color:var(--text3)">topics (<?= $pct2 ?>%)</span>
            </span>
        </div>
        <div class="progress-bar" style="height:9px;background:#e2e8f0;border-radius:99px;overflow:hidden">
            <div class="progress-fill" style="width:<?= $pct2 ?>%;background:<?= $cfg['bar'] ?>;height:100%;border-radius:99px"></div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
</div>

<div>
<div class="card">
    <div class="card-header"><span class="card-title">Top Courses by Enrollment</span></div>
    <div class="card-body" style="padding:0">
    <?php while($c=$topCourses->fetch_assoc()): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div>
            <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($c['course_code']) ?></div>
            <div style="font-size:12px;color:var(--text3)"><?= htmlspecialchars($c['course_name']) ?></div>
        </div>
        <span class="badge badge-green"><?= $c['enroll'] ?> enrolled</span>
    </div>
    <?php endwhile; ?>
    </div>
</div>

<?php 
$overallProgress = $totalAssignedStudentTopics > 0 ? min(100, round(($completedTopics / $totalAssignedStudentTopics) * 100)) : 0;
?>
<div class="card" style="margin-top:20px">
    <div class="card-header"><span class="card-title">Overall Topic Completion</span></div>
    <div class="card-body" style="text-align:center">
        <div style="font-size:52px;font-weight:800;color:var(--primary);line-height:1"><?= $overallProgress ?>%</div>
        <p style="color:var(--text3);margin:8px 0 20px">of all assigned topics completed by active students</p>
        <div class="progress-bar" style="height:12px"><div class="progress-fill" style="width:<?= $overallProgress ?>%"></div></div>
        <p style="font-size:12px;color:var(--text3);margin-top:8px">
            <strong><?= number_format($completedTopics) ?></strong> of <strong><?= number_format($totalAssignedStudentTopics) ?></strong> student milestones completed (<?= $totalTopics ?> syllabus topics across <?= $enrollStats ?> enrollments)
        </p>
    </div>
</div>
</div>
</div>
</div></div></div>
</body></html>
