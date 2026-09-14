<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Teacher Dashboard';
$tid = $_SESSION['user_id'];

$mySyllabi = $conn->query("SELECT COUNT(*) c FROM syllabi WHERE teacher_id=$tid")->fetch_assoc()['c'];
$myStudents = $conn->query("SELECT COUNT(DISTINCT e.student_id) c FROM enrollments e JOIN syllabi s ON e.syllabus_id=s.id WHERE s.teacher_id=$tid")->fetch_assoc()['c'];
$myTopics = $conn->query("SELECT COUNT(*) c FROM syllabus_topics st JOIN syllabi s ON st.syllabus_id=s.id WHERE s.teacher_id=$tid")->fetch_assoc()['c'];
$myMaterials = $conn->query("SELECT COUNT(*) c FROM learning_materials WHERE teacher_id=$tid")->fetch_assoc()['c'];

require_once '../includes/notifications.php';
$tNotifs = getTeacherNotifications($tid);

// Query for detailed syllabus mapping status across all assigned courses
$mappingSyllabi = $conn->query("SELECT s.*, c.course_name, c.course_code, c.units, c.prerequisite,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=s.id) as topic_count,
    (SELECT COUNT(DISTINCT ilo_code) FROM syllabus_topics st WHERE st.syllabus_id=s.id AND ilo_code IS NOT NULL AND ilo_code != '') as ilo_count,
    (SELECT COUNT(*) FROM learning_materials lm WHERE lm.syllabus_id=s.id) as material_count,
    (SELECT COUNT(*) FROM assessments a WHERE a.syllabus_id=s.id) as assessment_count,
    (SELECT COUNT(*) FROM enrollments e WHERE e.syllabus_id=s.id AND e.status='enrolled') as student_count
    FROM syllabi s 
    JOIN courses c ON s.course_id=c.id 
    WHERE s.teacher_id=$tid 
    ORDER BY s.created_at DESC");
$mappingList = [];
while ($row = $mappingSyllabi->fetch_assoc()) {
    $mappingList[] = $row;
}

$announcements = $conn->query("SELECT * FROM announcements WHERE target_role IN ('all','teacher') ORDER BY created_at DESC LIMIT 5");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<div style="margin-bottom:20px">
    <h2 style="font-size:22px;font-weight:800">Welcome back, <?= htmlspecialchars(explode(' ',$_SESSION['full_name'])[0]) ?>! 👋</h2>
    <p style="color:var(--text3)">Here's an overview of your teaching activity and syllabus mapping progress at I-Tech College.</p>
</div>

<?php if ($tNotifs['ungraded_count'] > 0): ?>
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--info);background:linear-gradient(to right, rgba(53,140,212,0.06), transparent)">
    <div class="card-body" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:12px">
            <span style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:var(--info);color:#fff;font-size:14px">
                <i class="fas fa-inbox"></i>
            </span>
            <div>
                <strong style="font-size:14px;color:var(--text)">Action Required: <?= $tNotifs['ungraded_count'] ?> Student Submissions Pending Review</strong>
                <div style="font-size:12px;color:var(--text3);margin-top:2px">Review student submissions and assign grades and qualitative feedback.</div>
            </div>
        </div>
        <a href="grades.php?assessment=all" class="btn btn-primary btn-sm">
            <i class="fas fa-star" style="margin-right:4px"></i> Open Gradebook
        </a>
    </div>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card green"><div class="stat-icon green"><i class="fas fa-file-alt"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $mySyllabi ?></div><div class="stat-label">My Syllabi</div></div></div>
    <div class="stat-card blue"><div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $myStudents ?></div><div class="stat-label">My Students</div></div></div>
    <div class="stat-card orange"><div class="stat-icon orange"><i class="fas fa-list-ul"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $myTopics ?></div><div class="stat-label">Topics Mapped</div></div></div>
    <div class="stat-card green"><div class="stat-icon green"><i class="fas fa-folder-open"></i></div>
    <div class="stat-info"><div class="stat-num"><?= $myMaterials ?></div><div class="stat-label">Materials</div></div></div>
</div>

<!-- SYLLABUS MAPPING COMMAND CENTER -->
<div class="card" style="margin-bottom:24px;border:1px solid rgba(59,130,246,0.25);box-shadow:0 4px 12px rgba(0,0,0,0.03)">
    <div class="card-header" style="background:#fff;border-bottom:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <span class="card-title" style="font-size:16px;font-weight:800;display:flex;align-items:center;gap:8px">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:rgba(59,130,246,0.12);color:var(--primary);font-size:14px">
                    <i class="fas fa-sitemap"></i>
                </span>
                Syllabus Mapping Command Center
            </span>
            <div style="font-size:12px;color:var(--text3);margin-top:4px">
                Manage curriculum alignment: link ILOs, lesson topics, learning materials, and assessments.
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="topics.php" class="btn btn-primary btn-sm">
                <i class="fas fa-external-link-alt" style="margin-right:4px"></i> Open Full Mapping Hub
            </a>
        </div>
    </div>

    <!-- Visual Mapping Pipeline Banner -->
    <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);padding:12px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;color:#f8fafc;font-size:12px;font-weight:600">
        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:8px">
            <span style="color:#60a5fa"><i class="fas fa-bullseye"></i> 1. ILO</span>
            <i class="fas fa-chevron-right" style="color:#64748b;font-size:10px"></i>
            <span style="color:#34d399"><i class="fas fa-book-open"></i> 2. Topic</span>
            <i class="fas fa-chevron-right" style="color:#64748b;font-size:10px"></i>
            <span style="color:#38bdf8"><i class="fas fa-file-pdf"></i> 3. Material</span>
            <i class="fas fa-chevron-right" style="color:#64748b;font-size:10px"></i>
            <span style="color:#fbbf24"><i class="fas fa-laptop-code"></i> 4. Activity</span>
            <i class="fas fa-chevron-right" style="color:#64748b;font-size:10px"></i>
            <span style="color:#a78bfa"><i class="fas fa-file-alt"></i> 5. Assessment</span>
            <i class="fas fa-chevron-right" style="color:#64748b;font-size:10px"></i>
            <span style="color:#f472b6"><i class="fas fa-chart-line"></i> 6. Student Progress</span>
        </div>
        <span style="background:rgba(255,255,255,0.12);padding:3px 10px;border-radius:20px;font-size:11px;color:#cbd5e1">
            <i class="fas fa-check-circle" style="color:#34d399;margin-right:4px"></i> OBE Aligned
        </span>
    </div>

    <div class="card-body" style="padding:20px">
        <?php if (empty($mappingList)): ?>
            <div style="padding:32px;text-align:center;color:var(--text3)">
                <i class="fas fa-sitemap" style="font-size:36px;margin-bottom:12px;display:block;opacity:0.4"></i>
                No courses assigned yet. Contact your administrator to assign subjects.
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:16px">
                <?php foreach ($mappingList as $ms): 
                    $targetWeeks = 16;
                    $percent = min(100, round(($ms['topic_count'] / $targetWeeks) * 100));
                    $progressColor = $percent >= 80 ? '#10b981' : ($percent >= 40 ? '#3b82f6' : '#f59e0b');
                ?>
                <div style="border:1px solid var(--border);border-radius:10px;padding:16px;background:#fafbfc;transition:all 0.2s ease;display:flex;flex-direction:column;justify-content:space-between">
                    <div>
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px">
                            <div>
                                <span class="badge badge-blue" style="font-size:11px;font-weight:700"><?= htmlspecialchars($ms['course_code']) ?></span>
                                <span style="font-size:11px;color:var(--text3);margin-left:6px"><?= $ms['units'] ?? 3 ?> Units &bull; <?= htmlspecialchars($ms['academic_year'] ?? '2024-2025') ?></span>
                                <h4 style="font-size:15px;font-weight:700;margin:6px 0 2px;color:var(--text)"><?= htmlspecialchars($ms['course_name']) ?></h4>
                                <?php if (!empty($ms['prerequisite'])): ?>
                                    <div style="font-size:11px;color:var(--text3)"><i class="fas fa-link"></i> Prereq: <?= htmlspecialchars($ms['prerequisite']) ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="badge <?= $ms['status']==='published'?'badge-green':'badge-gray' ?>" style="text-transform:capitalize">
                                <?= $ms['status'] ?>
                            </span>
                        </div>

                        <!-- Progress Gauge Bar -->
                        <div style="margin:14px 0 12px">
                            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;margin-bottom:6px">
                                <span style="font-weight:600;color:var(--text2)">Syllabus Mapping Completion</span>
                                <strong style="color:<?= $progressColor ?>"><?= $percent ?>% (<?= $ms['topic_count'] ?>/<?= $targetWeeks ?> Wks)</strong>
                            </div>
                            <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
                                <div style="width:<?= $percent ?>%;height:100%;background:<?= $progressColor ?>;border-radius:4px;transition:width 0.4s ease"></div>
                            </div>
                        </div>

                        <!-- Pill Metrics -->
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
                            <span style="font-size:11px;background:#fff;border:1px solid var(--border);padding:4px 8px;border-radius:6px;color:var(--text2)">
                                <i class="fas fa-bullseye" style="color:var(--primary);margin-right:3px"></i> <?= $ms['ilo_count'] ?> ILOs
                            </span>
                            <span style="font-size:11px;background:#fff;border:1px solid var(--border);padding:4px 8px;border-radius:6px;color:var(--text2)">
                                <i class="fas fa-list-ul" style="color:var(--success);margin-right:3px"></i> <?= $ms['topic_count'] ?> Topics
                            </span>
                            <span style="font-size:11px;background:#fff;border:1px solid var(--border);padding:4px 8px;border-radius:6px;color:var(--text2)">
                                <i class="fas fa-file-alt" style="color:var(--warning);margin-right:3px"></i> <?= $ms['assessment_count'] ?> Assessments
                            </span>
                            <span style="font-size:11px;background:#fff;border:1px solid var(--border);padding:4px 8px;border-radius:6px;color:var(--text2)">
                                <i class="fas fa-user-graduate" style="color:var(--info);margin-right:3px"></i> <?= $ms['student_count'] ?> Students
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display:flex;gap:8px;border-top:1px solid var(--border);padding-top:12px;margin-top:4px">
                        <a href="topics.php?syl_id=<?= $ms['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;text-align:center;justify-content:center">
                            <i class="fas fa-sitemap" style="margin-right:4px"></i> Manage Mapping
                        </a>
                        <a href="topics.php?syl_id=<?= $ms['id'] ?>&add=1" class="btn btn-secondary btn-sm" title="Add Mapping Entry">
                            <i class="fas fa-plus"></i> Add
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="dash-grid">
<div><div class="card">
    <div class="card-header"><span class="card-title">My Recent Syllabi</span><a href="syllabi.php" class="btn btn-secondary btn-sm">View All</a></div>
    <div class="card-body" style="padding:0">
    <?php if (empty($mappingList)): ?>
    <div style="padding:32px;text-align:center;color:var(--text3)">
        <i class="fas fa-file-alt" style="font-size:32px;margin-bottom:10px;display:block"></i>
        No syllabi yet. <a href="syllabi.php">Create one</a> to get started.
    </div>
    <?php else: foreach (array_slice($mappingList, 0, 5) as $s):
        $sc=['draft'=>'badge-gray','published'=>'badge-green','archived'=>'badge-orange']; ?>
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
            <strong style="font-size:14px"><?= htmlspecialchars($s['course_code']) ?></strong>
            <span style="font-size:13px;color:var(--text3);margin-left:8px"><?= htmlspecialchars($s['course_name']) ?></span>
            <div style="font-size:12px;color:var(--text3);margin-top:3px"><?= $s['topic_count'] ?> topics &bull; <?= $s['student_count'] ?> students</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <span class="badge <?= $sc[$s['status']] ?? 'badge-gray' ?>"><?= $s['status'] ?></span>
            <a href="topics.php?syl_id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm" title="Syllabus Mapping"><i class="fas fa-sitemap"></i></a>
            <a href="syllabus_edit.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm" title="Edit Syllabus"><i class="fas fa-edit"></i></a>
        </div>
    </div>
    <?php endforeach; endif; ?>
    </div>
</div></div>
<div><div class="card">
    <div class="card-header"><span class="card-title">Announcements</span><a href="announcements.php" class="btn btn-secondary btn-sm">View All</a></div>
    <div class="card-body" style="padding:0">
    <?php if ($announcements->num_rows === 0): ?>
    <div style="padding:32px;text-align:center;color:var(--text3)">
        <i class="fas fa-bullhorn" style="font-size:32px;margin-bottom:10px;display:block"></i>
        No announcements yet.
    </div>
    <?php else: while($a=$announcements->fetch_assoc()): ?>
    <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
        <strong style="font-size:13px"><?= htmlspecialchars($a['title']) ?></strong>
        <p style="font-size:12px;color:var(--text3);margin-top:4px"><?= nl2br(htmlspecialchars(substr($a['content'],0,120))) ?>...</p>
        <small class="text-muted"><?= date('M d, Y', strtotime($a['created_at'])) ?></small>
    </div>
    <?php endwhile; endif; ?>
    </div>
</div></div>
</div>
</div></div></div>
</body></html>