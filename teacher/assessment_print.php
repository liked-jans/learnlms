<?php
require_once '../includes/config.php';
requireRole(['teacher', 'admin']);
$userRole = $_SESSION['role'] ?? '';
$tid = $_SESSION['user_id'];
$assId = (int)($_GET['id'] ?? 0);

// Fetch assessment with course, teacher, and topic details
if ($userRole === 'admin') {
    $stmt = $conn->prepare("
        SELECT a.*, s.course_id, c.course_code, c.course_name, c.units,
               t.week_number, t.topic_title,
               u.full_name as teacher_name, u.email as teacher_email,
               d.name as department_name
        FROM assessments a
        LEFT JOIN syllabi s ON a.syllabus_id = s.id
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN users u ON a.teacher_id = u.id
        LEFT JOIN departments d ON c.department_id = d.id
        LEFT JOIN syllabus_topics t ON a.topic_id = t.id
        WHERE a.id = ?
    ");
    $stmt->bind_param('i', $assId);
} else {
    $stmt = $conn->prepare("
        SELECT a.*, s.course_id, c.course_code, c.course_name, c.units,
               t.week_number, t.topic_title,
               u.full_name as teacher_name, u.email as teacher_email,
               d.name as department_name
        FROM assessments a
        LEFT JOIN syllabi s ON a.syllabus_id = s.id
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN users u ON a.teacher_id = u.id
        LEFT JOIN departments d ON c.department_id = d.id
        LEFT JOIN syllabus_topics t ON a.topic_id = t.id
        WHERE a.id = ? AND (a.teacher_id = ? OR s.teacher_id = ?)
    ");
    $stmt->bind_param('iii', $assId, $tid, $tid);
}
$stmt->execute();
$assessment = $stmt->get_result()->fetch_assoc();

if (!$assessment) {
    setFlash('error', 'Assessment not found or you do not have permission to view it.');
    redirect(BASE_URL . 'teacher/assessments.php');
}

// Fetch all questions for this assessment
$qStmt = $conn->prepare("SELECT * FROM assessment_questions WHERE assessment_id = ? ORDER BY sort_order ASC, id ASC");
$qStmt->bind_param('i', $assId);
$qStmt->execute();
$questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$totalPoints = 0;
$mcqCount = 0;
$tfCount = 0;
$essayCount = 0;
foreach ($questions as $q) {
    $totalPoints += (float)$q['points'];
    if ($q['question_type'] === 'multiple_choice') $mcqCount++;
    elseif ($q['question_type'] === 'true_false') $tfCount++;
    elseif ($q['question_type'] === 'essay') $essayCount++;
}

// Check requested view mode (default student paper)
$viewMode = ($_GET['mode'] ?? 'student') === 'key' ? 'key' : 'student';

$pageTitle = 'Print Assessment - ' . $assessment['course_code'] . ' ' . $assessment['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($assessment['course_code']) ?> - <?= htmlspecialchars($assessment['title']) ?> (Print)</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;500;600;700;800&family=Lora:ital,wght@0,400;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        background: #f1f5f9;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: #0f172a;
        line-height: 1.5;
        padding-bottom: 60px;
    }

    /* Screen Action Bar */
    .screen-actions-bar {
        position: sticky;
        top: 0;
        z-index: 100;
        background: #0f172a;
        color: white;
        padding: 12px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 14px rgba(0,0,0,0.18);
        gap: 16px;
        flex-wrap: wrap;
    }
    .screen-actions-bar .bar-left, .screen-actions-bar .bar-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .screen-actions-bar a, .screen-actions-bar button {
        text-decoration: none;
        padding: 7px 14px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        border: none;
        transition: all 0.15s ease;
    }
    .btn-bar-back {
        background: rgba(255,255,255,0.1);
        color: #cbd5e1;
    }
    .btn-bar-back:hover { background: rgba(255,255,255,0.2); color: #fff; }
    .btn-bar-print {
        background: #10b981;
        color: white;
        font-weight: 700;
    }
    .btn-bar-print:hover { background: #059669; }
    .mode-switch {
        background: rgba(255,255,255,0.12);
        padding: 3px;
        border-radius: 8px;
        display: inline-flex;
        gap: 4px;
    }
    .mode-switch a {
        padding: 5px 12px;
        font-size: 12px;
        border-radius: 5px;
        color: #94a3b8;
    }
    .mode-switch a.active {
        background: #2563eb;
        color: white;
    }
    .mode-switch a:hover:not(.active) {
        color: white;
    }

    /* Paper Container */
    .paper-container {
        max-width: 850px;
        margin: 28px auto;
        background: #ffffff;
        padding: 48px 56px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #e2e8f0;
        border-radius: 4px;
    }

    /* Header Styling */
    .inst-header {
        text-align: center;
        border-bottom: 2px solid #0f172a;
        padding-bottom: 16px;
        margin-bottom: 20px;
    }
    .inst-name {
        font-family: 'Cinzel', serif;
        font-size: 19px;
        font-weight: 700;
        letter-spacing: 1px;
        color: #0f172a;
        text-transform: uppercase;
    }
    .inst-sub {
        font-size: 12px;
        color: #475569;
        margin-top: 2px;
        letter-spacing: 0.5px;
    }
    .exam-title-badge {
        display: inline-block;
        margin-top: 10px;
        padding: 4px 16px;
        background: #0f172a;
        color: #ffffff;
        font-weight: 700;
        font-size: 13px;
        letter-spacing: 1px;
        text-transform: uppercase;
        border-radius: 3px;
    }

    /* Course and Exam Info Grid */
    .exam-meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px 24px;
        font-size: 13px;
        margin-bottom: 20px;
        padding: 12px 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
    }
    .meta-row {
        display: flex;
        align-items: baseline;
        gap: 6px;
    }
    .meta-label {
        font-weight: 700;
        color: #334155;
        min-width: 95px;
    }
    .meta-val {
        color: #0f172a;
        font-weight: 500;
    }

    /* Student Info Blank Lines */
    .student-info-box {
        border: 1px solid #cbd5e1;
        padding: 14px 18px;
        margin-bottom: 22px;
        border-radius: 4px;
    }
    .student-info-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        font-size: 13px;
    }
    .student-info-row + .student-info-row {
        margin-top: 12px;
    }
    .info-blank {
        flex: 1;
        display: flex;
        align-items: baseline;
        gap: 6px;
    }
    .info-line {
        flex: 1;
        border-bottom: 1px solid #64748b;
        min-height: 18px;
    }

    /* General Instructions */
    .instructions-box {
        background: #ffffff;
        border-left: 3px solid #0f172a;
        padding: 10px 14px;
        font-size: 12px;
        color: #334155;
        margin-bottom: 24px;
    }
    .instructions-box strong {
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Questions Styling */
    .questions-wrapper {
        display: flex;
        flex-direction: column;
        gap: 22px;
    }
    .question-item {
        page-break-inside: avoid;
        break-inside: avoid;
        border-bottom: 1px dashed #cbd5e1;
        padding-bottom: 18px;
    }
    .question-item:last-child {
        border-bottom: none;
    }
    .question-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
    }
    .question-number-prompt {
        display: flex;
        align-items: baseline;
        gap: 8px;
        font-size: 14px;
        color: #0f172a;
        line-height: 1.5;
        font-weight: 600;
    }
    .question-num {
        font-weight: 700;
        min-width: 22px;
    }
    .question-pts {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        white-space: nowrap;
    }

    /* Multiple Choice Options */
    .mc-options-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px 24px;
        margin-left: 28px;
        margin-top: 8px;
    }
    @media (max-width: 600px) {
        .mc-options-grid { grid-template-columns: 1fr; }
    }
    .mc-option {
        display: flex;
        align-items: baseline;
        gap: 10px;
        font-size: 13.5px;
        color: #1e293b;
        padding: 4px 6px;
        border-radius: 4px;
    }
    .mc-circle {
        display: inline-block;
        width: 15px;
        height: 15px;
        border: 1.5px solid #64748b;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .mc-letter {
        font-weight: 700;
        color: #334155;
        min-width: 18px;
    }
    .mc-option.is-correct-answer {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        font-weight: 600;
    }
    .key-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 700;
        color: #059669;
        margin-left: 6px;
        background: #d1fae5;
        padding: 2px 6px;
        border-radius: 4px;
    }

    /* True/False Choices */
    .tf-choices {
        display: flex;
        gap: 36px;
        margin-left: 28px;
        margin-top: 8px;
    }
    .tf-choice {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13.5px;
        font-weight: 500;
        padding: 4px 8px;
        border-radius: 4px;
    }
    .tf-box {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 1.5px solid #64748b;
        border-radius: 3px;
    }
    .tf-choice.is-correct-answer {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        font-weight: 700;
    }

    /* Essay Lines / Rubric */
    .essay-student-lines {
        margin-left: 28px;
        margin-top: 12px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .essay-line {
        border-bottom: 1px solid #cbd5e1;
        height: 1px;
    }
    .essay-key-rubric {
        margin-left: 28px;
        margin-top: 10px;
        background: #fffbeb;
        border: 1px solid #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 10px 14px;
        border-radius: 4px;
        font-size: 12px;
        color: #92400e;
    }
    .essay-key-rubric strong {
        color: #78350f;
    }

    /* Answer Key Alert Ribbon */
    .answer-key-banner {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 10px 16px;
        border-radius: 4px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        font-weight: 700;
    }

    /* Print Specific Styles */
    @page {
        size: A4 portrait;
        margin: 15mm 15mm 15mm 15mm;
    }
    @media print {
        body {
            background: #ffffff !important;
            padding: 0 !important;
            color: #000000 !important;
        }
        .screen-actions-bar {
            display: none !important;
        }
        .paper-container {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
        }
        .inst-header {
            border-bottom: 2px solid #000 !important;
        }
        .exam-title-badge {
            background: #000 !important;
            color: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .exam-meta-grid {
            background: #f8fafc !important;
            border: 1px solid #ccc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .instructions-box {
            border-left: 3px solid #000 !important;
        }
        .mc-circle, .tf-box {
            border-color: #000 !important;
        }
        .mc-option.is-correct-answer, .tf-choice.is-correct-answer {
            background: #e6f4ea !important;
            border: 1px solid #137333 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .key-badge {
            background: #ceead6 !important;
            color: #0d652d !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .answer-key-banner {
            border: 1px solid #991b1b !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .question-item {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>
</head>
<body>

<!-- Sticky Screen Actions Bar -->
<div class="screen-actions-bar">
    <div class="bar-left">
        <a href="assessment_questions.php?id=<?= $assId ?>" class="btn-bar-back">
            <i class="fas fa-arrow-left"></i> Back to Questions
        </a>
        <span style="font-size:13px;color:#94a3b8;margin-left:4px">
            <?= htmlspecialchars($assessment['course_code']) ?> &bull; <?= safeHtml($assessment['title']) ?>
        </span>
    </div>
    <div class="bar-right">
        <!-- View Mode Switcher -->
        <div class="mode-switch">
            <a href="?id=<?= $assId ?>&mode=student" class="<?= $viewMode === 'student' ? 'active' : '' ?>" title="Print student exam sheet without answers">
                <i class="fas fa-user-graduate"></i> Student Test Paper
            </a>
            <a href="?id=<?= $assId ?>&mode=key" class="<?= $viewMode === 'key' ? 'active' : '' ?>" title="Print instructor copy with highlighted correct answers">
                <i class="fas fa-key"></i> Teacher Answer Key
            </a>
        </div>
        <!-- Print Button -->
        <button onclick="window.print()" class="btn-bar-print">
            <i class="fas fa-print"></i> Print Assessment
        </button>
    </div>
</div>

<!-- Printable Paper Sheet -->
<div class="paper-container">

    <!-- Answer Key Warning Banner (shown only in Answer Key mode) -->
    <?php if ($viewMode === 'key'): ?>
    <div class="answer-key-banner">
        <i class="fas fa-shield-alt"></i>
        <span>INSTRUCTOR COPY &bull; TEACHER ANSWER KEY &amp; SCORING RUBRIC (Confidential)</span>
    </div>
    <?php endif; ?>

    <!-- Institutional / Course Header -->
    <header class="inst-header">
        <div class="inst-name"><?= SITE_NAME ?></div>
        <div class="inst-sub">
            <?= !empty($assessment['department_name']) ? htmlspecialchars($assessment['department_name']) . ' &bull; ' : '' ?>
            Curriculum &amp; Instruction Assessment
        </div>
        <div class="exam-title-badge">
            <?= strtoupper(htmlspecialchars($assessment['type'])) ?> EXAMINATION
        </div>
    </header>

    <!-- Assessment Metadata Details -->
    <div class="exam-meta-grid">
        <div class="meta-row">
            <span class="meta-label">Course:</span>
            <span class="meta-val"><strong><?= htmlspecialchars($assessment['course_code']) ?></strong> - <?= htmlspecialchars($assessment['course_name']) ?></span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Total Items:</span>
            <span class="meta-val"><?= count($questions) ?> Question<?= count($questions) != 1 ? 's' : '' ?></span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Instructor:</span>
            <span class="meta-val"><?= htmlspecialchars($assessment['teacher_name'] ?: 'Course Instructor') ?></span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Total Points:</span>
            <span class="meta-val"><strong><?= (float)$assessment['max_score'] ?></strong> Points (<?= (float)$totalPoints ?> pts mapped)</span>
        </div>
        <?php if (!empty($assessment['topic_title'])): ?>
        <div class="meta-row" style="grid-column: 1 / -1">
            <span class="meta-label">Topic / Unit:</span>
            <span class="meta-val">Week <?= $assessment['week_number'] ?? 1 ?>: <?= safeHtml($assessment['topic_title']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Student Information Fill-in Box (shown for Student Paper) -->
    <?php if ($viewMode === 'student'): ?>
    <div class="student-info-box">
        <div class="student-info-row">
            <div class="info-blank" style="flex: 2">
                <strong>NAME:</strong>
                <div class="info-line"></div>
            </div>
            <div class="info-blank" style="flex: 1">
                <strong>DATE:</strong>
                <div class="info-line"></div>
            </div>
        </div>
        <div class="student-info-row">
            <div class="info-blank" style="flex: 1.3">
                <strong>STUDENT ID:</strong>
                <div class="info-line"></div>
            </div>
            <div class="info-blank" style="flex: 1">
                <strong>SECTION:</strong>
                <div class="info-line"></div>
            </div>
            <div class="info-blank" style="flex: 0.9">
                <strong>SCORE:</strong>
                <div class="info-line" style="border-bottom: 2px solid #0f172a; text-align: right; padding-right: 4px; font-weight:700">
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/ <?= (float)$assessment['max_score'] ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- General Instructions -->
    <div class="instructions-box">
        <strong>General Instructions:</strong>
        <?= !empty($assessment['description']) ? safeHtml($assessment['description']) . ' ' : '' ?>
        Read each question carefully. For multiple-choice questions, encircle or write the letter corresponding to your answer. For True/False questions, mark the correct option. For essay items, formulate concise, substantive answers using the provided spaces. Erasures or superimpositions should be avoided.
        <?php if (!empty($assessment['time_limit_minutes'])): ?>
            <strong>Time Limit:</strong> <?= (int)$assessment['time_limit_minutes'] ?> minutes.
        <?php endif; ?>
    </div>

    <!-- Questions Body -->
    <?php if (empty($questions)): ?>
        <div style="text-align:center;padding:48px 20px;color:#64748b">
            <p>No questions have been configured for this assessment yet.</p>
        </div>
    <?php else: ?>
        <div class="questions-wrapper">
            <?php foreach ($questions as $idx => $q): 
                $num = $idx + 1;
                $pts = (float)$q['points'];
                $qType = $q['question_type'];
            ?>
            <div class="question-item">
                <div class="question-head">
                    <div class="question-number-prompt">
                        <span class="question-num"><?= $num ?>.</span>
                        <div><?= formatMultilineText($q['question_text']) ?></div>
                    </div>
                    <div class="question-pts">
                        [<?= $pts ?> pt<?= $pts != 1 ? 's' : '' ?>]
                    </div>
                </div>

                <!-- Multiple Choice Options -->
                <?php if ($qType === 'multiple_choice'): 
                    $opts = json_decode($q['options'], true) ?: [];
                    $rawCorrect = (string)($q['correct_answer'] ?? '');
                    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                    $optIdx = 0;
                ?>
                <div class="mc-options-grid">
                    <?php foreach ($opts as $oKey => $optText): 
                        $letter = (is_string($oKey) && preg_match('/^[A-Z]$/i', $oKey)) ? strtoupper($oKey) : ($letters[$optIdx] ?? chr(65 + $optIdx));
                        $isCorrect = (
                            strcasecmp((string)$oKey, $rawCorrect) === 0 ||
                            strcasecmp($letter, $rawCorrect) === 0 ||
                            (is_numeric($rawCorrect) && (int)$rawCorrect === $optIdx)
                        );
                        $highlight = ($viewMode === 'key' && $isCorrect);
                        $optIdx++;
                    ?>
                    <div class="mc-option <?= $highlight ? 'is-correct-answer' : '' ?>">
                        <span class="mc-circle"></span>
                        <span class="mc-letter"><?= $letter ?>.</span>
                        <span><?= safeHtml($optText) ?></span>
                        <?php if ($highlight): ?>
                            <span class="key-badge"><i class="fas fa-check"></i> Correct</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- True or False Options -->
                <?php elseif ($qType === 'true_false'): 
                    $correctVal = $q['correct_answer'] ?? 'True';
                    $tCorrect = ($viewMode === 'key' && strcasecmp($correctVal, 'True') === 0);
                    $fCorrect = ($viewMode === 'key' && strcasecmp($correctVal, 'False') === 0);
                ?>
                <div class="tf-choices">
                    <div class="tf-choice <?= $tCorrect ? 'is-correct-answer' : '' ?>">
                        <span class="tf-box"></span>
                        <span>TRUE</span>
                        <?php if ($tCorrect): ?>
                            <span class="key-badge"><i class="fas fa-check"></i> Correct</span>
                        <?php endif; ?>
                    </div>
                    <div class="tf-choice <?= $fCorrect ? 'is-correct-answer' : '' ?>">
                        <span class="tf-box"></span>
                        <span>FALSE</span>
                        <?php if ($fCorrect): ?>
                            <span class="key-badge"><i class="fas fa-check"></i> Correct</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Essay Prompt Lines or Rubric -->
                <?php elseif ($qType === 'essay'): ?>
                    <?php if ($viewMode === 'student'): ?>
                        <div class="essay-student-lines">
                            <div class="essay-line"></div>
                            <div class="essay-line"></div>
                            <div class="essay-line"></div>
                            <div class="essay-line"></div>
                            <div class="essay-line"></div>
                        </div>
                    <?php else: ?>
                        <div class="essay-key-rubric">
                            <strong><i class="fas fa-clipboard-check"></i> Rubric Guidance / Key Evaluation Criteria:</strong>
                            <div style="margin-top:4px">
                                <?= !empty($q['explanation']) ? formatMultilineText($q['explanation']) : 'Evaluate the student\'s depth of explanation, methodology, adherence to course standards, and conceptual clarity.' ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Document Footer -->
    <div style="margin-top:36px;padding-top:14px;border-top:1px solid #cbd5e1;display:flex;justify-content:space-between;align-items:center;font-size:11px;color:#64748b">
        <div><?= htmlspecialchars($assessment['course_code']) ?> &bull; <?= safeHtml($assessment['title']) ?></div>
        <div>Generated via BlendEd LMS &bull; Page 1</div>
    </div>
</div>

</body>
</html>
