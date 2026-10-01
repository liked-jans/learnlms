<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$userRole = $_SESSION['role'] ?? '';
$userId = (int)$_SESSION['user_id'];
$isStudent = ($userRole === 'student');
$isTeacherOrAdmin = in_array($userRole, ['teacher', 'admin']);

if (!$isStudent && !$isTeacherOrAdmin) {
    redirect(getRoleDashboard($userRole));
}

$matId = (int)($_GET['id'] ?? 0);

if ($matId <= 0) {
    setFlash('error', 'Material not specified.');
    redirect($isStudent ? BASE_URL . 'student/materials.php' : ($userRole === 'admin' ? BASE_URL . 'admin/monitoring.php' : BASE_URL . 'teacher/materials.php'));
}

// Fetch material based on role (students require enrollment; teachers/admins preview directly)
if ($isStudent) {
    $stmt = $conn->prepare("
        SELECT m.*, s.id as syl_id, s.academic_year, s.semester, s.section_name,
               c.course_code, c.course_name, u.full_name as teacher_name,
               st.id as topic_id, st.week_number, st.topic_title, st.delivery_mode, st.ilo_code, st.learning_outcomes,
               tp.status as progress_status, COALESCE(tp.read_percentage, 0.00) as current_read_pct
        FROM learning_materials m
        JOIN syllabi s ON m.syllabus_id = s.id
        JOIN courses c ON s.course_id = c.id
        LEFT JOIN users u ON m.teacher_id = u.id
        JOIN enrollments e ON e.syllabus_id = s.id AND e.student_id = ? AND e.status = 'enrolled'
        LEFT JOIN syllabus_topics st ON m.syllabus_topic_id = st.id
        LEFT JOIN topic_progress tp ON tp.syllabus_topic_id = st.id AND tp.student_id = ?
        WHERE m.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('iii', $userId, $userId, $matId);
} else {
    $sqlT = "
        SELECT m.*, s.id as syl_id, s.academic_year, s.semester, s.section_name,
               c.course_code, c.course_name, u.full_name as teacher_name,
               st.id as topic_id, st.week_number, st.topic_title, st.delivery_mode, st.ilo_code, st.learning_outcomes,
               'not_started' as progress_status, 0.00 as current_read_pct
        FROM learning_materials m
        JOIN syllabi s ON m.syllabus_id = s.id
        JOIN courses c ON s.course_id = c.id
        LEFT JOIN users u ON m.teacher_id = u.id
        LEFT JOIN syllabus_topics st ON m.syllabus_topic_id = st.id
        WHERE m.id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sqlT);
    $stmt->bind_param('i', $matId);
}
$stmt->execute();
$mat = $stmt->get_result()->fetch_assoc();

if (!$mat) {
    setFlash('error', 'Learning material not found or access restricted.');
    redirect($isStudent ? BASE_URL . 'student/materials.php' : ($userRole === 'admin' ? BASE_URL . 'admin/monitoring.php' : BASE_URL . 'teacher/materials.php'));
}

$pageTitle = $mat['title'];
$estReadTime = max(1, (int)($mat['estimated_read_time'] ?: 5));
$currentReadPct = (float)$mat['current_read_pct'];
$isInitiallyCompleted = ($currentReadPct >= 90.0 || $mat['progress_status'] === 'completed');

if (!function_exists('getMediaEmbedInfo')) {
function getMediaEmbedInfo($mat) {
    $url = htmlspecialchars_decode(trim($mat['external_url'] ?? ''));
    $filePath = trim($mat['file_path'] ?? '');
    $type = $mat['type'] ?? 'module';

    // 1. YouTube check (comprehensive: watch?v=, shorts/, embed/, youtu.be/, live/, nocookie)
    if (!empty($url) && preg_match('/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:embed\/|v\/|shorts\/|live\/|watch\?(?:.*&)?v=))([a-zA-Z0-9_-]{11})/i', $url, $m)) {
        return [
            'category' => 'video',
            'provider' => 'youtube',
            'video_id' => $m[1],
            'embed_url' => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&enablejsapi=1',
            'original_url' => $url
        ];
    }

    // 2. Vimeo check
    if (!empty($url) && preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)/i', $url, $m)) {
        return [
            'category' => 'video',
            'provider' => 'vimeo',
            'video_id' => $m[3],
            'embed_url' => 'https://player.vimeo.com/video/' . $m[3],
            'original_url' => $url
        ];
    }

    // 3. Check uploaded file in file_path
    if (!empty($filePath)) {
        $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $fileUrl = BASE_URL . 'uploads/materials/' . rawurlencode($filePath);

        if (in_array($fileExt, ['pdf'])) {
            return [
                'category' => 'pdf',
                'file_url' => $fileUrl,
                'file_name' => basename($filePath),
                'ext' => $fileExt
            ];
        }
        if (in_array($fileExt, ['mp4', 'webm', 'ogg'])) {
            return [
                'category' => 'video',
                'provider' => 'html5',
                'embed_url' => $fileUrl,
                'file_url' => $fileUrl,
                'file_name' => basename($filePath),
                'ext' => $fileExt
            ];
        }
        if (in_array($fileExt, ['pptx', 'ppt', 'pps', 'ppsx'])) {
            return [
                'category' => 'presentation',
                'file_url' => $fileUrl,
                'file_name' => basename($filePath),
                'ext' => $fileExt
            ];
        }
        if (in_array($fileExt, ['docx', 'doc'])) {
            return [
                'category' => 'document',
                'file_url' => $fileUrl,
                'file_name' => basename($filePath),
                'ext' => $fileExt
            ];
        }
        if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return [
                'category' => 'image',
                'file_url' => $fileUrl,
                'file_name' => basename($filePath),
                'ext' => $fileExt
            ];
        }
    }

    // 4. External URL video or PDF check
    if (!empty($url)) {
        $urlExt = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        if (in_array($urlExt, ['mp4', 'webm', 'ogg'])) {
            return [
                'category' => 'video',
                'provider' => 'html5',
                'embed_url' => $url,
                'original_url' => $url
            ];
        }
        if (in_array($urlExt, ['pdf'])) {
            return [
                'category' => 'pdf',
                'file_url' => $url,
                'file_name' => basename(parse_url($url, PHP_URL_PATH) ?? 'Document.pdf'),
                'ext' => 'pdf'
            ];
        }
        return [
            'category' => 'link',
            'original_url' => $url
        ];
    }

    return null;
}
}

$mediaInfo = getMediaEmbedInfo($mat);
$rawText = trim(strip_tags($mat['content'] ?? ''));
$hasArticleText = (($mat['type'] ?? '') === 'module' && !empty($rawText) && mb_strlen($rawText) > 2);

// Fetch linked assessments for this topic
$linkedAssessments = [];
if (!empty($mat['topic_id'])) {
    $assStmt = $conn->prepare("
        SELECT a.id, a.title, a.type, a.max_score,
               sub.id as submission_id, sub.score as total_score, sub.status as sub_status
        FROM assessments a
        LEFT JOIN submissions sub ON sub.assessment_id = a.id AND sub.student_id = ?
        WHERE a.topic_id = ?
        ORDER BY a.created_at ASC
    ");
    $assStmt->bind_param('ii', $userId, $mat['topic_id']);
    $assStmt->execute();
    $linkedAssessments = $assStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Check other materials in same topic
$siblingMats = [];
if (!empty($mat['topic_id'])) {
    $sibStmt = $conn->prepare("SELECT id, title, type FROM learning_materials WHERE syllabus_topic_id = ? AND id != ?");
    $sibStmt->bind_param('ii', $mat['topic_id'], $matId);
    $sibStmt->execute();
    $siblingMats = $sibStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
/* ===== NATIVE WYSIWYG DOCS READER STYLING (Word / Google Docs Style) ===== */
.docs-reader-content {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: #1e293b;
    font-size: 15px;
    line-height: 1.8;
}
.docs-reader-content h1, .docs-reader-content h2, .docs-reader-content h3, .docs-reader-content h4 {
    margin-top: 24px;
    margin-bottom: 10px;
    color: #0f172a;
    font-weight: 700;
}
.docs-reader-content h2 { font-size: 22px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-top: 28px; }
.docs-reader-content h3 { font-size: 18px; color: #1e293b; margin-top: 22px; }
.docs-reader-content h4 { font-size: 15px; color: #334155; }
.docs-reader-content p {
    margin-bottom: 14px;
}
.docs-reader-content ul, .docs-reader-content ol {
    padding-left: 28px;
    margin: 14px 0;
}
.docs-reader-content li {
    margin-bottom: 6px;
    line-height: 1.7;
}
.docs-reader-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-size: 14px;
    background: #ffffff;
    border-radius: 6px;
    overflow: hidden;
}
.docs-reader-content table, .docs-reader-content th, .docs-reader-content td {
    border: 1px solid #cbd5e1;
}
.docs-reader-content th, .docs-reader-content td {
    padding: 10px 14px;
    text-align: left;
}
.docs-reader-content th {
    background: #f1f5f9;
    font-weight: 600;
    color: #1e293b;
}
.docs-reader-content tr:nth-child(even) td {
    background: #f8fafc;
}
.docs-reader-content blockquote {
    background: #f8fafc;
    border-left: 4px solid #3b82f6;
    padding: 14px 20px;
    margin: 18px 0;
    border-radius: 0 8px 8px 0;
    color: #334155;
}
.docs-reader-content a {
    color: #2563eb;
    text-decoration: underline;
}
</style>

<?php if ($isStudent): ?>
<!-- Top Fixed Progress Bar (Students Only) -->
<div id="readingProgressContainer" style="position:fixed;top:0;left:0;width:100%;height:4px;background:#e2e8f0;z-index:99999;">
    <div id="readingProgressBar" style="height:100%;width:<?= $currentReadPct ?>%;background:linear-gradient(90deg, #3b82f6, #10b981);transition:width 0.2s ease-out;"></div>
</div>
<?php endif; ?>

<div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once __DIR__ . '/../includes/topbar.php'; ?>

<div class="page-content" style="max-width:960px;margin:0 auto;padding-bottom:80px;">

    <?php if ($isTeacherOrAdmin): ?>
    <!-- Preview Mode Banner -->
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 18px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <div style="display:flex;align-items:center;gap:10px;color:#1e40af;font-size:13px;font-weight:600">
            <i class="fas <?= $userRole === 'admin' ? 'fa-user-shield' : 'fa-chalkboard-teacher' ?>" style="font-size:18px"></i>
            <span><?= $userRole === 'admin' ? 'Administrator Preview Mode &bull; Inspecting interactive lesson module as learners experience it.' : 'Teacher Preview Mode &bull; Previewing full-context lesson as students see it in the immersive reader.' ?></span>
        </div>
        <div style="display:flex;gap:8px">
            <?php if ($userRole === 'admin'): ?>
            <a href="<?= BASE_URL ?>admin/monitoring.php" class="btn btn-secondary btn-sm" style="font-size:12px"><i class="fas fa-arrow-left"></i> Back to Progress Monitor</a>
            <a href="<?= BASE_URL ?>admin/syllabi_view.php?id=<?= $mat['syl_id'] ?>" class="btn btn-secondary btn-sm" style="font-size:12px"><i class="fas fa-file-alt"></i> View Syllabus</a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>teacher/materials.php" class="btn btn-secondary btn-sm" style="font-size:12px"><i class="fas fa-arrow-left"></i> Back to Materials</a>
            <a href="<?= BASE_URL ?>teacher/topics.php?syl_id=<?= $mat['syl_id'] ?>" class="btn btn-secondary btn-sm" style="font-size:12px"><i class="fas fa-list-ul"></i> Syllabus Mapping</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation Bar -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <a href="<?= $isStudent ? BASE_URL . 'student/syllabus.php?syl=' . $mat['syl_id'] : ($userRole === 'admin' ? BASE_URL . 'admin/monitoring.php' : BASE_URL . 'teacher/materials.php') ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> <?= $isStudent ? 'Back to Syllabus' : ($userRole === 'admin' ? 'Back to Progress Monitor' : 'Back to Materials') ?>
        </a>
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:12px;color:var(--text3);font-weight:600">
                <i class="fas fa-clock"></i> <?= $estReadTime ?> min study
            </span>
            <?php if ($isStudent): ?>
            <button type="button" id="btnTopMarkDone" onclick="manualMarkCompleted()" class="btn <?= $isInitiallyCompleted ? 'btn-secondary' : 'btn-success' ?> btn-sm" style="display:inline-flex;align-items:center;gap:6px;font-weight:600;padding:6px 12px">
                <i class="fas <?= $isInitiallyCompleted ? 'fa-check-double' : 'fa-check-circle' ?>"></i>
                <span id="btnTopMarkDoneText"><?= $isInitiallyCompleted ? 'Completed' : 'Mark as Completed' ?></span>
            </button>
            <span id="readPercentBadge" class="badge <?= $isInitiallyCompleted ? 'badge-green' : 'badge-blue' ?>" style="font-size:12px;padding:4px 10px;">
                <i class="fas <?= $isInitiallyCompleted ? 'fa-check-circle' : 'fa-book-reader' ?>"></i> 
                <span id="readPercentText"><?= round($currentReadPct) ?>% Read</span>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reader Card -->
    <div class="card" style="box-shadow:var(--shadow-md);border-radius:16px;overflow:hidden;border:1px solid var(--border);">
        <!-- Module Cover / Header -->
        <div style="padding:32px 36px 24px;border-bottom:1px solid var(--border);background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;align-items:center">
                <span class="badge badge-purple" style="font-weight:700">Week <?= $mat['week_number'] ?? 1 ?></span>
                <?php if (!empty($mat['ilo_code'])): ?>
                <span class="badge badge-gray" style="font-weight:700"><?= htmlspecialchars($mat['ilo_code']) ?></span>
                <?php endif; ?>
                <span class="badge badge-outline" style="background:#ffffff;color:var(--text2);font-weight:600">
                    <?= htmlspecialchars($mat['course_code']) ?> &bull; <?= htmlspecialchars($mat['course_name']) ?>
                </span>
                <span class="badge badge-blue" style="text-transform:capitalize;font-weight:600">
                    <i class="fas <?= ['module'=>'fa-book-reader','document'=>'fa-file-pdf','presentation'=>'fa-file-powerpoint','video'=>'fa-video','link'=>'fa-link'][$mat['type']] ?? 'fa-file' ?>"></i> <?= htmlspecialchars($mat['type']) ?>
                </span>
            </div>

            <h1 style="font-family:'Fraunces', serif;font-size:28px;font-weight:700;color:var(--text);line-height:1.3;margin-bottom:14px">
                <?= safeHtml($mat['title']) ?>
            </h1>

            <div style="display:flex;align-items:center;gap:16px;color:var(--text3);font-size:13px;flex-wrap:wrap">
                <span><i class="fas fa-user-tie"></i> Prof. <?= htmlspecialchars($mat['teacher_name']) ?></span>
                <span>&bull;</span>
                <span><i class="fas fa-calendar-alt"></i> <?= date('F j, Y', strtotime($mat['created_at'])) ?></span>
                <span>&bull;</span>
                <span><i class="fas fa-layer-group"></i> Delivery: <?= ucfirst($mat['delivery_mode'] ?? 'Both') ?></span>
            </div>

            <?php if (!empty($mat['description'])): ?>
            <div style="margin-top:16px;padding:12px 16px;background:rgba(255,255,255,0.7);border-left:3px solid var(--primary);border-radius:6px;font-size:13px;color:var(--text2)">
                <?= formatMultilineText($mat['description']) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Material Main Body Content with Direct Media & Native Docs Styling -->
        <div id="materialArticleContent" class="docs-reader-content" style="padding:40px 36px;">
            <?php if (!empty($mediaInfo)): ?>
                <?php if ($mediaInfo['category'] === 'video'): ?>
                    <!-- Embedded Video Player -->
                    <div style="margin-bottom:30px">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px">
                            <span style="font-weight:700;font-size:15px;color:var(--text);display:flex;align-items:center;gap:8px">
                                <i class="fas fa-video" style="color:#ef4444"></i> Video Lesson / Lecture
                            </span>
                            <?php if (!empty($mediaInfo['original_url'])): ?>
                            <a href="<?= htmlspecialchars($mediaInfo['original_url']) ?>" target="_blank" onclick="recordExternalResourceClick()" class="btn btn-secondary btn-sm" style="font-size:11px">
                                <i class="fas fa-external-link-alt"></i> Watch on <?= ucfirst($mediaInfo['provider'] ?? 'Source') ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php if (in_array($mediaInfo['provider'] ?? '', ['youtube', 'vimeo'])): ?>
                        <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:12px;background:#000;box-shadow:0 6px 20px rgba(0,0,0,0.12)">
                            <iframe src="<?= $mediaInfo['embed_url'] ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                        </div>
                        <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
                            <?php if (!empty($mediaInfo['original_url'])): ?>
                            <a href="<?= htmlspecialchars($mediaInfo['original_url']) ?>" target="_blank" onclick="recordExternalResourceClick()" class="btn btn-secondary btn-sm" style="font-size:12px">
                                <i class="fab fa-youtube" style="color:#ef4444"></i> Watch on YouTube (External Link)
                            </a>
                            <?php else: ?>
                            <span></span>
                            <?php endif; ?>
                            <?php if ($isStudent): ?>
                            <button type="button" onclick="manualMarkCompleted()" class="btn <?= $isInitiallyCompleted ? 'btn-secondary' : 'btn-success' ?> btn-sm btn-mark-studied" style="display:inline-flex;align-items:center;gap:6px">
                                <i class="fas <?= $isInitiallyCompleted ? 'fa-check-double' : 'fa-check-circle' ?>"></i> <?= $isInitiallyCompleted ? 'Completed' : 'Mark Video as Watched' ?>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php elseif (($mediaInfo['provider'] ?? '') === 'html5'): ?>
                        <div style="background:#000;border-radius:12px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,0.12)">
                            <video controls style="width:100%;max-height:560px;display:block" id="materialVideoPlayer" onplay="recordMediaStart()" onended="manualMarkCompleted()">
                                <source src="<?= $mediaInfo['embed_url'] ?>">
                                Your browser does not support HTML5 video.
                            </video>
                        </div>
                        <?php if ($isStudent): ?>
                        <div style="margin-top:12px;display:flex;justify-content:flex-end">
                            <button type="button" onclick="manualMarkCompleted()" class="btn <?= $isInitiallyCompleted ? 'btn-secondary' : 'btn-success' ?> btn-sm btn-mark-studied" style="display:inline-flex;align-items:center;gap:6px">
                                <i class="fas <?= $isInitiallyCompleted ? 'fa-check-double' : 'fa-check-circle' ?>"></i> <?= $isInitiallyCompleted ? 'Completed' : 'Mark Video as Watched' ?>
                            </button>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>

                <?php elseif ($mediaInfo['category'] === 'pdf'): ?>
                    <!-- Embedded PDF Document Viewer -->
                    <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:30px;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,0.05)">
                        <div style="background:#f8fafc;padding:12px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <i class="fas fa-file-pdf" style="color:#ef4444;font-size:18px"></i>
                                <strong style="font-size:13px"><?= htmlspecialchars($mediaInfo['file_name'] ?? 'Lesson Document.pdf') ?></strong>
                            </div>
                            <div style="display:flex;gap:8px">
                                <a href="<?= $mediaInfo['file_url'] ?>" download onclick="recordExternalResourceClick()" class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> Download PDF</a>
                                <a href="<?= $mediaInfo['file_url'] ?>" target="_blank" onclick="recordExternalResourceClick()" class="btn btn-secondary btn-sm"><i class="fas fa-expand"></i> Open Fullscreen</a>
                                <?php if ($isStudent): ?>
                                <button type="button" onclick="manualMarkCompleted()" class="btn <?= $isInitiallyCompleted ? 'btn-secondary' : 'btn-success' ?> btn-sm btn-mark-studied"><i class="fas <?= $isInitiallyCompleted ? 'fa-check-double' : 'fa-check-circle' ?>"></i> <?= $isInitiallyCompleted ? 'Completed' : 'Mark as Read' ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <object data="<?= $mediaInfo['file_url'] ?>#toolbar=1" type="application/pdf" style="width:100%;height:800px;border:none;display:block">
                            <iframe src="<?= $mediaInfo['file_url'] ?>#toolbar=1" style="width:100%;height:800px;border:none;display:block">
                                <div style="text-align:center;padding:40px;color:var(--text3)">
                                    <p>Your browser does not support inline PDF viewing.</p>
                                    <a href="<?= $mediaInfo['file_url'] ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Download PDF</a>
                                </div>
                            </iframe>
                        </object>
                    </div>

                <?php elseif ($mediaInfo['category'] === 'presentation'): ?>
                    <!-- Presentation Slide Deck Viewer / Downloader -->
                    <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:30px;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,0.05)">
                        <div style="background:#fff7ed;padding:14px 20px;border-bottom:1px solid #fed7aa;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:38px;height:38px;border-radius:8px;background:#ea580c;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px">
                                    <i class="fas fa-file-powerpoint"></i>
                                </div>
                                <div>
                                    <strong style="font-size:14px;color:#9a3412"><?= htmlspecialchars($mediaInfo['file_name'] ?? 'Presentation Slides') ?></strong>
                                    <div style="font-size:12px;color:#c2410c">Course Lecture Slide Deck (<?= strtoupper($mediaInfo['ext'] ?? 'PPTX') ?>)</div>
                                </div>
                            </div>
                            <div style="display:flex;gap:8px">
                                <a href="<?= $mediaInfo['file_url'] ?>" download onclick="recordExternalResourceClick()" class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Download Slides</a>
                                <?php if ($isStudent): ?>
                                <button type="button" onclick="manualMarkCompleted()" class="btn <?= $isInitiallyCompleted ? 'btn-secondary' : 'btn-success' ?> btn-sm btn-mark-studied"><i class="fas <?= $isInitiallyCompleted ? 'fa-check-double' : 'fa-check-circle' ?>"></i> <?= $isInitiallyCompleted ? 'Completed' : 'Mark as Studied' ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="padding:28px 24px;text-align:center;background:#fff">
                            <p style="font-size:14px;color:var(--text2);margin-bottom:16px">
                                Download this presentation slide deck to review offline on your computer or mobile device.
                            </p>
                            <a href="<?= $mediaInfo['file_url'] ?>" download onclick="recordExternalResourceClick()" class="btn btn-secondary">
                                <i class="fas fa-download"></i> Download File (<?= strtoupper($mediaInfo['ext'] ?? 'PPTX') ?>)
                            </a>
                        </div>
                    </div>

                <?php elseif ($mediaInfo['category'] === 'document'): ?>
                    <!-- Document / Word Handout (with in-page DOCX renderer) -->
                    <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:30px;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,0.05)">
                        <div style="background:#eff6ff;padding:14px 20px;border-bottom:1px solid #bfdbfe;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:38px;height:38px;border-radius:8px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px">
                                    <i class="fas fa-file-word"></i>
                                </div>
                                <div>
                                    <strong style="font-size:14px;color:#1e40af"><?= htmlspecialchars($mediaInfo['file_name'] ?? 'Attached Document') ?></strong>
                                    <div style="font-size:12px;color:#3b82f6">Course Document File (<?= strtoupper($mediaInfo['ext'] ?? 'DOCX') ?>)</div>
                                </div>
                            </div>
                            <div style="display:flex;gap:8px">
                                <a href="<?= $mediaInfo['file_url'] ?>" download onclick="recordExternalResourceClick()" class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> Download File</a>
                                <?php if ($isStudent): ?>
                                <button type="button" onclick="manualMarkCompleted()" class="btn <?= $isInitiallyCompleted ? 'btn-secondary' : 'btn-success' ?> btn-sm btn-mark-studied"><i class="fas <?= $isInitiallyCompleted ? 'fa-check-double' : 'fa-check-circle' ?>"></i> <?= $isInitiallyCompleted ? 'Completed' : 'Mark as Studied' ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="padding:28px 24px;background:#f8fafc">
                            <?php if (($mediaInfo['ext'] ?? '') === 'docx'): ?>
                            <div id="docxPreviewContainer" style="background:#ffffff;border:1px solid var(--border);border-radius:10px;padding:36px 40px;box-shadow:0 2px 12px rgba(0,0,0,0.04);max-width:900px;margin:0 auto;color:#1e293b;line-height:1.75;font-size:15px;min-height:260px">
                                <div id="docxLoadingState" style="text-align:center;padding:40px 20px;color:var(--text3)">
                                    <i class="fas fa-spinner fa-spin" style="font-size:26px;margin-bottom:12px;display:block;color:var(--primary)"></i>
                                    <strong style="font-size:14px;color:var(--text)">Reading and Rendering Word Document...</strong>
                                    <div style="font-size:12px;color:var(--text3);margin-top:4px">Converting document structure for interactive reading...</div>
                                </div>
                                <div id="docxRenderedBody" style="display:none"></div>
                            </div>
                            <?php else: ?>
                            <div style="text-align:center;padding:24px;background:#fff;border-radius:10px;border:1px solid var(--border)">
                                <p style="font-size:14px;color:var(--text2);margin-bottom:16px">
                                    Download this attached document for study notes, laboratory sheets, or assignment references.
                                </p>
                                <a href="<?= $mediaInfo['file_url'] ?>" download onclick="recordExternalResourceClick()" class="btn btn-secondary">
                                    <i class="fas fa-download"></i> Download File (<?= strtoupper($mediaInfo['ext'] ?? 'DOC') ?>)
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($mediaInfo['category'] === 'link'): ?>
                    <!-- External Web Resource Launch Card -->
                    <div style="background:linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);border:2px solid #bfdbfe;border-radius:14px;padding:26px 30px;margin-bottom:30px">
                        <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px">
                            <div style="width:52px;height:52px;border-radius:12px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                            <div style="flex:1">
                                <span class="badge badge-blue" style="margin-bottom:4px">External Learning Resource / Link</span>
                                <h3 style="margin:0;font-size:18px;font-weight:700"><?= htmlspecialchars($mat['title']) ?></h3>
                                <div style="font-size:12px;color:var(--text3);margin-top:4px;word-break:break-all">
                                    <i class="fas fa-globe"></i> <?= htmlspecialchars($mediaInfo['original_url']) ?>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($mat['description'])): ?>
                            <p style="font-size:13px;color:var(--text2);margin:0 0 16px;line-height:1.5"><?= nl2br(htmlspecialchars($mat['description'])) ?></p>
                        <?php endif; ?>
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                            <a href="<?= htmlspecialchars($mediaInfo['original_url']) ?>" target="_blank" onclick="recordExternalResourceClick()" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;font-weight:700">
                                <i class="fas fa-external-link-alt"></i> Open Resource in New Tab
                            </a>
                            <?php if ($isStudent): ?>
                            <button type="button" onclick="manualMarkCompleted()" class="btn <?= $isInitiallyCompleted ? 'btn-secondary' : 'btn-success' ?> btn-mark-studied" style="display:inline-flex;align-items:center;gap:6px">
                                <i class="fas <?= $isInitiallyCompleted ? 'fa-check-double' : 'fa-check-circle' ?>"></i> <?= $isInitiallyCompleted ? 'Completed' : 'Mark as Studied' ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Article Body Text (if available) -->
            <?php if ($hasArticleText): ?>
                <div class="article-text-body">
                    <?= $mat['content'] ?>
                </div>
            <?php elseif (empty($mediaInfo)): ?>
                <div style="text-align:center;padding:50px 20px;color:var(--text3)">
                    <i class="fas fa-book-open" style="font-size:42px;opacity:0.3;margin-bottom:12px;display:block"></i>
                    <p style="font-size:15px;margin-bottom:6px;font-weight:600">No article text attached to this module.</p>
                    <p style="font-size:13px">Please download the companion materials or follow the reference links below.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Companion Materials & Attached Files -->
        <?php if (!empty($mat['file_path']) || !empty($mat['external_url']) || !empty($siblingMats)): ?>
        <div style="padding:24px 36px;border-top:1px solid var(--border);background:#fafafa;">
            <h4 style="font-size:14px;text-transform:uppercase;letter-spacing:0.5px;color:var(--text2);margin-bottom:12px">
                <i class="fas fa-paperclip"></i> Companion Learning Resources
            </h4>
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <?php if (!empty($mat['file_path'])): 
                    $ext = strtolower(pathinfo($mat['file_path'], PATHINFO_EXTENSION));
                    $icon = in_array($ext, ['pdf']) ? 'fa-file-pdf' : (in_array($ext, ['pptx','ppt']) ? 'fa-file-powerpoint' : 'fa-file-word');
                ?>
                <a href="<?= BASE_URL ?>uploads/materials/<?= rawurlencode($mat['file_path']) ?>" target="_blank" class="btn btn-secondary btn-sm">
                    <i class="fas <?= $icon ?>"></i> Download Attached Resource (.<?= strtoupper($ext) ?>)
                </a>
                <?php endif; ?>

                <?php if (!empty($mat['external_url'])): ?>
                <a href="<?= htmlspecialchars($mat['external_url']) ?>" target="_blank" class="btn btn-secondary btn-sm">
                    <i class="fas fa-external-link-alt"></i> External Reference Link
                </a>
                <?php endif; ?>

                <?php foreach ($siblingMats as $sm): ?>
                <a href="<?= BASE_URL ?>student/read_material.php?id=<?= $sm['id'] ?>" class="btn btn-outline btn-sm">
                    <i class="fas <?= $sm['type']==='module' ? 'fa-book' : 'fa-file' ?>"></i> <?= safeHtml($sm['title']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isStudent): ?>
        <!-- Completion Alert Banner (Students Only) -->
        <div id="readingCompleteBanner" style="display:<?= $isInitiallyCompleted ? 'block' : 'none' ?>;padding:24px 36px;background:#ecfdf5;border-top:1px solid #a7f3d0;text-align:center;">
            <div style="max-width:540px;margin:0 auto">
                <div style="font-size:28px;margin-bottom:8px">🎉</div>
                <h3 style="font-size:18px;font-weight:700;color:#065f46;margin-bottom:6px">Topic Reading Completed!</h3>
                <p style="font-size:13px;color:#047857;margin-bottom:16px">
                    Great work! You have completed reading this week's learning module. Your progress has been recorded in the syllabus mapping registry.
                </p>
            </div>
        </div>

        <!-- What's Next / Assessment Alignment Section (Students Only) -->
        <div style="padding:28px 36px;border-top:1px solid var(--border);background:#ffffff;">
            <h4 style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:8px">
                <i class="fas fa-flag-checkered" style="color:var(--primary)"></i> Next Steps: Assessment & Outcomes
            </h4>

            <?php if (!empty($linkedAssessments)): ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:14px">
                <?php foreach ($linkedAssessments as $ass): 
                    $isSubmitted = !empty($ass['submission_id']);
                ?>
                    <div style="border:1px solid var(--border);border-radius:10px;padding:16px;background:#f8fafc;display:flex;flex-direction:column;justify-content:space-between">
                        <div>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                                <span class="badge badge-purple" style="font-size:10px;text-transform:uppercase"><?= htmlspecialchars($ass['type']) ?></span>
                                <span style="font-size:12px;font-weight:700;color:var(--text2)"><?= number_format((float)$ass['max_score'], 1) ?> Pts</span>
                            </div>
                            <strong style="font-size:14px;color:var(--text);display:block;margin-bottom:6px">
                                <?= safeHtml($ass['title']) ?>
                            </strong>
                            <p style="font-size:12px;color:var(--text3);margin-bottom:12px">
                                Tests learning outcomes aligned with this reading module.
                            </p>
                        </div>
                        <div>
                            <?php if ($isStudent): ?>
                                <?php if ($isSubmitted): ?>
                                    <button class="btn btn-secondary btn-sm" disabled style="width:100%">
                                        <i class="fas fa-check-circle" style="color:#10b981"></i> Submitted (<?= $ass['total_score'] !== null ? number_format((float)$ass['total_score'], 1) . ' pts' : 'Under Review' ?>)
                                    </button>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>student/take_assessment.php?id=<?= $ass['id'] ?>" class="btn btn-primary btn-sm" style="width:100%;text-align:center">
                                        <i class="fas fa-pen"></i> Take Assessment Now
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>teacher/assessment_questions.php?id=<?= $ass['id'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" style="width:100%;text-align:center">
                                    <i class="fas fa-list-ol"></i> View Assessment Questions
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="padding:16px;background:#f1f5f9;border-radius:10px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
                    <div>
                        <strong style="font-size:13px;color:var(--text)">Self-Paced Learning Complete</strong>
                        <p style="font-size:12px;color:var(--text3);margin:2px 0 0">Review your notes and reflection points in the course timeline.</p>
                    </div>
                    <a href="<?= BASE_URL ?>student/syllabus.php?syl=<?= $mat['syl_id'] ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-check"></i> Return to Course Timeline
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

</div>
</div>
</div>

<script>
(function() {
    const topicId = <?= (int)($mat['topic_id'] ?? 0) ?>;
    const materialId = <?= (int)$mat['id'] ?>;
    let highestScrolled = <?= (float)$currentReadPct ?>;
    let isCompleted = <?= $isInitiallyCompleted ? 'true' : 'false' ?>;
    let saveTimeout = null;

    const progressBar = document.getElementById('readingProgressBar');
    const badgeText = document.getElementById('readPercentText');
    const badge = document.getElementById('readPercentBadge');
    const completeBanner = document.getElementById('readingCompleteBanner');

    function updateUICompleted() {
        isCompleted = true;
        if (progressBar) progressBar.style.width = '100%';
        if (completeBanner) completeBanner.style.display = 'block';
        if (badge) {
            badge.className = 'badge badge-green';
            badge.innerHTML = '<i class="fas fa-check-circle"></i> <span id="readPercentText">100% Read</span>';
        }
        const btnTop = document.getElementById('btnTopMarkDone');
        if (btnTop) {
            btnTop.className = 'btn btn-secondary btn-sm';
            btnTop.innerHTML = '<i class="fas fa-check-double"></i> <span id="btnTopMarkDoneText">Completed</span>';
        }
        document.querySelectorAll('.btn-mark-studied').forEach(btn => {
            btn.className = 'btn btn-secondary btn-sm';
            btn.innerHTML = '<i class="fas fa-check-double"></i> Completed';
        });
    }

    function calculateScrollPercent() {
        const docEl = document.documentElement;
        const body = document.body;
        const scrollTop = window.pageYOffset || docEl.scrollTop || body.scrollTop || 0;
        const scrollHeight = Math.max(docEl.scrollHeight, body.scrollHeight) - window.innerHeight;
        
        if (scrollHeight <= 0) return 100;
        let pct = Math.round((scrollTop / scrollHeight) * 100);
        return Math.min(100, Math.max(0, pct));
    }

    const isStudent = <?= $isStudent ? 'true' : 'false' ?>;

    function sendReadingBeacon(pct) {
        if (!isStudent || !topicId || topicId <= 0) return;
        
        fetch('<?= BASE_URL ?>student/save_reading_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                topic_id: topicId,
                material_id: materialId,
                percentage: pct
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.completed && !isCompleted) {
                updateUICompleted();
            }
        })
        .catch(err => console.error('Error saving reading progress:', err));
    }

    // Interactive Action Handlers (exposed globally)
    window.manualMarkCompleted = function() {
        highestScrolled = 100.0;
        updateUICompleted();
        sendReadingBeacon(100.0);
    };

    window.recordExternalResourceClick = function() {
        if (highestScrolled < 100.0) {
            highestScrolled = 100.0;
            updateUICompleted();
            sendReadingBeacon(100.0);
        }
    };

    window.recordMediaStart = function() {
        if (highestScrolled < 50.0) {
            highestScrolled = 50.0;
            if (progressBar) progressBar.style.width = '50%';
            if (badgeText) badgeText.innerText = '50% Read';
            sendReadingBeacon(50.0);
        }
    };

    window.addEventListener('scroll', function() {
        const currentPct = calculateScrollPercent();
        if (currentPct > highestScrolled) {
            highestScrolled = currentPct;
            if (progressBar) progressBar.style.width = highestScrolled + '%';
            if (badgeText) badgeText.innerText = Math.round(highestScrolled) + '% Read';

            if (highestScrolled >= 90 && !isCompleted) {
                updateUICompleted();
            }

            // Debounce save request every 600ms
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                sendReadingBeacon(highestScrolled);
            }, 600);
        }
    }, { passive: true });

    // Initial check on load
    window.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const hasMedia = <?= !empty($mediaInfo) ? 'true' : 'false' ?>;
            const initialPct = calculateScrollPercent();
            
            // If student opens non-module media resource (video/doc/link/ppt), give initial touchpoint
            if (hasMedia && highestScrolled < 25.0) {
                highestScrolled = 25.0;
                if (progressBar) progressBar.style.width = '25%';
                if (badgeText) badgeText.innerText = '25% Read';
                sendReadingBeacon(25.0);
            } else if (initialPct > highestScrolled) {
                highestScrolled = initialPct;
                if (progressBar) progressBar.style.width = highestScrolled + '%';
                if (badgeText) badgeText.innerText = Math.round(highestScrolled) + '% Read';
                sendReadingBeacon(highestScrolled);
            }
            // Render Word Document (.docx) inline if present
            const docxFileUrl = "<?= (!empty($mediaInfo['file_url']) && ($mediaInfo['ext'] ?? '') === 'docx') ? $mediaInfo['file_url'] : '' ?>";
            if (docxFileUrl && typeof mammoth !== 'undefined') {
                fetch(docxFileUrl)
                    .then(res => {
                        if (!res.ok) throw new Error('Could not fetch docx file: ' + res.statusText);
                        return res.arrayBuffer();
                    })
                    .then(ab => mammoth.convertToHtml({ arrayBuffer: ab }))
                    .then(result => {
                        const bodyEl = document.getElementById('docxRenderedBody');
                        const loadingEl = document.getElementById('docxLoadingState');
                        if (bodyEl && result && result.value) {
                            bodyEl.innerHTML = result.value;
                            bodyEl.style.display = 'block';
                            if (loadingEl) loadingEl.style.display = 'none';
                        }
                    })
                    .catch(err => {
                        console.error('Word Document Render Error:', err);
                        const loadingEl = document.getElementById('docxLoadingState');
                        if (loadingEl) {
                            loadingEl.innerHTML = '<div style="color:var(--text3);padding:20px 0"><i class="fas fa-file-word" style="font-size:32px;color:#2563eb;margin-bottom:8px;display:block"></i> Document ready for download using the button above.</div>';
                        }
                    });
            }
        }, 800);
    });
})();
</script>

<!-- Mammoth.js for client-side Word .docx to HTML conversion -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>

<style>
#docxRenderedBody h1, #docxRenderedBody h2, #docxRenderedBody h3, #docxRenderedBody h4 {
    color: var(--text);
    margin-top: 24px;
    margin-bottom: 12px;
    font-weight: 700;
}
#docxRenderedBody h1 { font-size: 24px; border-bottom: 1px solid var(--border); padding-bottom: 8px; }
#docxRenderedBody h2 { font-size: 20px; }
#docxRenderedBody h3 { font-size: 17px; }
#docxRenderedBody p { margin-bottom: 14px; line-height: 1.8; color: #334155; }
#docxRenderedBody table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
#docxRenderedBody table th, #docxRenderedBody table td { border: 1px solid #cbd5e1; padding: 10px 14px; text-align: left; }
#docxRenderedBody table th { background: #f1f5f9; font-weight: 700; color: #0f172a; }
#docxRenderedBody ul, #docxRenderedBody ol { padding-left: 28px; margin-bottom: 16px; color: #334155; }
#docxRenderedBody li { margin-bottom: 6px; line-height: 1.6; }
#docxRenderedBody img { max-width: 100%; height: auto; border-radius: 8px; margin: 16px 0; }
</style>

</body></html>
