<?php
require_once '../includes/config.php';
requireRole('student');
$stid = $_SESSION['user_id'];
$assId = (int)($_GET['id'] ?? 0);

// Verify enrollment and fetch assessment
$stmt = $conn->prepare("
    SELECT a.*, c.course_code, c.course_name, s.id as syl_id
    FROM assessments a
    JOIN syllabi s ON a.syllabus_id = s.id
    JOIN courses c ON s.course_id = c.id
    JOIN enrollments e ON e.syllabus_id = s.id
    WHERE a.id = ? AND e.student_id = ? AND e.status = 'enrolled'
");
$stmt->bind_param('ii', $assId, $stid);
$stmt->execute();
$assessment = $stmt->get_result()->fetch_assoc();

if (!$assessment) {
    setFlash('error', 'Assessment not found or you are not enrolled in this course.');
    redirect(BASE_URL . 'student/assessments.php');
}

$pageTitle = $assessment['title'];

// Check existing submission
$stmtSub = $conn->prepare("SELECT * FROM submissions WHERE assessment_id = ? AND student_id = ?");
$stmtSub->bind_param('ii', $assId, $stid);
$stmtSub->execute();
$submission = $stmtSub->get_result()->fetch_assoc();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_quiz') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token expired. Please try again.');
        redirect(BASE_URL . 'student/take_assessment.php?id=' . $assId);
    }

    if ($submission) {
        setFlash('error', 'You have already submitted this assessment.');
        redirect(BASE_URL . 'student/take_assessment.php?id=' . $assId);
    }

    // 1. Create or update submission
    $stmtSubInsert = $conn->prepare("
        INSERT INTO submissions (assessment_id, student_id, status, is_auto_graded, submitted_at)
        VALUES (?, ?, 'submitted', 0, NOW())
    ");
    $stmtSubInsert->bind_param('ii', $assId, $stid);
    $stmtSubInsert->execute();
    $subId = $stmtSubInsert->insert_id;

    // 2. Fetch all questions
    $questions = $conn->query("SELECT * FROM assessment_questions WHERE assessment_id = $assId")->fetch_all(MYSQLI_ASSOC);

    $answersGiven = $_POST['answers'] ?? [];
    $totalEarned = 0.00;
    $hasEssay = false;

    $stmtAns = $conn->prepare("
        INSERT INTO submission_answers (submission_id, question_id, student_answer, is_correct, points_awarded)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($questions as $q) {
        $qId = (int)$q['id'];
        $studentAns = trim($answersGiven[$qId] ?? '');
        $isCorrect = null;
        $pts = 0.00;

        if ($q['question_type'] === 'multiple_choice') {
            if (isAssessmentAnswerCorrect($studentAns, $q['correct_answer'], $q['options'])) {
                $isCorrect = 1;
                $pts = (float)$q['points'];
                $totalEarned += $pts;
            } else {
                $isCorrect = 0;
                $pts = 0.00;
            }
        } elseif ($q['question_type'] === 'true_false') {
            if (strcasecmp($studentAns, (string)$q['correct_answer']) === 0) {
                $isCorrect = 1;
                $pts = (float)$q['points'];
                $totalEarned += $pts;
            } else {
                $isCorrect = 0;
                $pts = 0.00;
            }
        } elseif ($q['question_type'] === 'essay') {
            $hasEssay = true;
            $isCorrect = null; // Pending teacher grading
            $pts = 0.00;
        }

        $stmtAns->bind_param('iisid', $subId, $qId, $studentAns, $isCorrect, $pts);
        $stmtAns->execute();
    }

    // 3. Finalize submission status
    if ($hasEssay) {
        $finalStatus = 'submitted';
        $isAuto = 0;
        $feedback = "Objective questions auto-graded. Essay questions are pending teacher evaluation.";
    } else {
        $finalStatus = 'graded';
        $isAuto = 1;
        $feedback = "All questions auto-graded successfully upon submission.";
    }

    $stmtFinal = $conn->prepare("
        UPDATE submissions 
        SET score = ?, status = ?, is_auto_graded = ?, feedback = ?, graded_at = " . ($finalStatus === 'graded' ? 'NOW()' : 'NULL') . "
        WHERE id = ?
    ");
    $stmtFinal->bind_param('dsisi', $totalEarned, $finalStatus, $isAuto, $feedback, $subId);
    $stmtFinal->execute();

    // 4. Update topic progress if linked
    if (!empty($assessment['topic_id'])) {
        $topId = (int)$assessment['topic_id'];
        $stmtProg = $conn->prepare("
            INSERT INTO topic_progress (student_id, syllabus_topic_id, status, completed_at, last_read_at)
            VALUES (?, ?, 'completed', NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                status = IF(read_percentage >= 90 OR status = 'completed', 'completed', 'in_progress'),
                completed_at = COALESCE(completed_at, NOW()),
                last_read_at = NOW()
        ");
        $stmtProg->bind_param('ii', $stid, $topId);
        $stmtProg->execute();
        $stmtProg->close();
    }

    if ($hasEssay) {
        setFlash('success', "Assessment submitted! Objective questions were auto-graded (Score: $totalEarned pts). Your essay response is awaiting teacher evaluation.");
    } else {
        setFlash('success', "Assessment completed and instantly graded! Score: $totalEarned / {$assessment['max_score']} pts.");
    }

    redirect(BASE_URL . 'student/take_assessment.php?id=' . $assId);
}

// If already submitted, fetch answers for review
$savedAnswers = [];
if ($submission) {
    $ansQuery = $conn->prepare("
        SELECT sa.*, q.question_text, q.question_type, q.points, q.options, q.correct_answer, q.explanation
        FROM submission_answers sa
        JOIN assessment_questions q ON sa.question_id = q.id
        WHERE sa.submission_id = ?
        ORDER BY q.sort_order ASC, q.id ASC
    ");
    $ansQuery->bind_param('i', $submission['id']);
    $ansQuery->execute();
    $savedAnswers = $ansQuery->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // Determine order: randomize if shuffle_questions is enabled
    $orderClause = !empty($assessment['shuffle_questions']) ? "RAND()" : "sort_order ASC, id ASC";
    $qQuery = $conn->prepare("SELECT * FROM assessment_questions WHERE assessment_id = ? ORDER BY $orderClause");
    $qQuery->bind_param('i', $assId);
    $qQuery->execute();
    $activeQuestions = $qQuery->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content" style="max-width:860px;margin:0 auto">

<!-- Breadcrumb -->
<div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text3);margin-bottom:16px">
    <a href="assessments.php" style="color:var(--primary);text-decoration:none">
        <i class="fas fa-arrow-left" style="margin-right:4px"></i> My Assessments
    </a>
    <span>/</span>
    <span style="color:var(--text)"><?= htmlspecialchars($assessment['course_code']) ?></span>
    <span>/</span>
    <span style="color:var(--text);font-weight:600"><?= htmlspecialchars($assessment['title']) ?></span>
</div>

<!-- Assessment Header Card -->
<div class="card" style="margin-bottom:24px;border:1px solid rgba(59,130,246,0.25);box-shadow:0 4px 14px rgba(0,0,0,0.03)">
    <div class="card-body" style="padding:22px 24px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="badge badge-blue" style="font-weight:700"><?= htmlspecialchars($assessment['course_code']) ?></span>
                    <span class="badge badge-purple" style="text-transform:uppercase;font-size:11px"><?= $assessment['type'] ?></span>
                    <?php if ($submission): ?>
                        <span class="badge <?= $submission['status']==='graded'?'badge-green':'badge-orange' ?>" style="text-transform:capitalize">
                            <?= $submission['status'] ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h2 style="font-size:22px;font-weight:800;color:var(--text);margin:0 0 6px"><?= htmlspecialchars($assessment['title']) ?></h2>
                <p style="font-size:13px;color:var(--text3);margin:0">
                    <?= htmlspecialchars($assessment['course_name']) ?>
                    <?php if (!empty($assessment['description'])): ?>
                        &bull; <?= htmlspecialchars($assessment['description']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div style="text-align:right">
                <div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase">Max Score</div>
                <div style="font-size:20px;font-weight:800;color:var(--primary)"><?= (float)($assessment['max_score'] ?? 0) ?> pts</div>
            </div>
        </div>

        <?php if (!empty($assessment['attachment_path'])): ?>
            <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
                <a href="<?= BASE_URL ?>uploads/assessments/<?= htmlspecialchars($assessment['attachment_path']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:6px">
                    <i class="fas fa-paperclip"></i> Download Assessment Material / Instructions
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($submission): ?>
<!-- ================= RESULTS & REVIEW VIEW ================= -->
<div class="card" style="margin-bottom:24px;background:linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);border:1px solid var(--border)">
    <div class="card-body" style="padding:24px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
            <div style="display:flex;align-items:center;gap:16px">
                <div style="width:56px;height:56px;border-radius:50%;background:<?= $submission['status']==='graded'?'#10b981':'#f59e0b' ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px">
                    <i class="fas <?= $submission['status']==='graded'?'fa-check-circle':'fa-hourglass-half' ?>"></i>
                </div>
                <div>
                    <h3 style="font-size:18px;font-weight:800;color:var(--text);margin:0 0 4px">
                        <?= $submission['status']==='graded' ? 'Assessment Completed & Graded' : 'Submission Received (Pending Teacher Review)' ?>
                    </h3>
                    <div style="font-size:13px;color:var(--text2)">
                        Submitted on <?= date('M d, Y g:i A', strtotime($submission['submitted_at'])) ?>
                    </div>
                </div>
            </div>
            <div style="background:#fff;padding:12px 20px;border-radius:10px;border:1px solid var(--border);text-align:center">
                <div style="font-size:11px;color:var(--text3);font-weight:700;text-transform:uppercase">Your Score</div>
                <div style="font-size:26px;font-weight:900;color:var(--primary)">
                    <?php if ($submission['score'] !== null && $submission['status'] === 'graded'): ?>
                        <?= (float)$submission['score'] ?> <span style="font-size:14px;color:var(--text3);font-weight:600">/ <?= (float)($assessment['max_score'] ?? 0) ?> pts</span>
                    <?php elseif ($submission['score'] !== null): ?>
                        <?= (float)$submission['score'] ?> <span style="font-size:13px;color:#d97706;font-weight:600">/ <?= (float)($assessment['max_score'] ?? 0) ?> pts (Essay Pending)</span>
                    <?php else: ?>
                        <span style="font-size:18px;color:#d97706;font-weight:700">Pending Evaluation</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($submission['feedback'])): ?>
            <div style="margin-top:16px;padding:12px 16px;background:#fff;border-left:4px solid var(--primary);border-radius:6px;font-size:13px">
                <strong><i class="fas fa-comment-dots" style="color:var(--primary);margin-right:4px"></i> Feedback / Remarks:</strong>
                <div style="color:var(--text2);margin-top:4px"><?= nl2br(htmlspecialchars($submission['feedback'])) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<h3 style="font-size:16px;font-weight:700;margin-bottom:14px">Your Answer Breakdown</h3>
<div style="display:flex;flex-direction:column;gap:14px">
    <?php foreach ($savedAnswers as $idx => $sa): 
        $num = $idx + 1;
        $qType = $sa['question_type'];
        $isCorrect = $sa['is_correct'];
    ?>
    <div class="card" style="border:1px solid <?= $qType === 'essay' ? ($submission['status'] === 'graded' ? '#10b981' : '#f59e0b') : ($isCorrect === 1 ? '#10b981' : ($isCorrect === 0 ? '#ef4444' : '#f59e0b')) ?>">
        <div class="card-body" style="padding:18px 20px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-weight:800;font-size:13px">Question <?= $num ?></span>
                    <span class="badge" style="background:#2563eb;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px">
                        <i class="fas fa-star" style="font-size:10px"></i> Declared: <?= (float)($sa['points'] ?? 0) ?> Point<?= ($sa['points'] ?? 0) != 1 ? 's' : '' ?><?= $qType === 'essay' ? ' for this Essay' : '' ?>
                    </span>
                </div>
                <div>
                    <?php if ($qType === 'essay'): ?>
                        <?php if ($submission['status'] === 'graded' && $sa['points_awarded'] !== null): ?>
                            <span class="badge <?= ((float)$sa['points_awarded'] > 0) ? 'badge-green' : 'badge-gray' ?>" style="font-size:11px;font-weight:700">
                                <i class="fas <?= ((float)$sa['points_awarded'] > 0) ? 'fa-check' : 'fa-pen' ?>"></i> Score: <?= (float)$sa['points_awarded'] ?> / <?= (float)($sa['points'] ?? 0) ?> pts
                            </span>
                        <?php else: ?>
                            <span class="badge badge-orange" style="font-size:11px;font-weight:700">
                                <i class="fas fa-clock"></i> Pending Teacher Evaluation
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($isCorrect === 1): ?>
                            <span class="badge badge-green" style="font-size:11px"><i class="fas fa-check"></i> Correct (+<?= (float)($sa['points_awarded'] ?? 0) ?>)</span>
                        <?php elseif ($isCorrect === 0): ?>
                            <span class="badge badge-red" style="font-size:11px"><i class="fas fa-times"></i> Incorrect (0 pts)</span>
                        <?php else: ?>
                            <span class="badge badge-orange" style="font-size:11px"><i class="fas fa-clock"></i> Pending Evaluation</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--text)">
                <?= nl2br(htmlspecialchars($sa['question_text'])) ?>
            </div>

            <!-- Student Answer Display -->
            <div style="padding:12px 14px;background:#f8fafc;border-radius:6px;border:1px solid var(--border);margin-bottom:8px">
                <div style="font-size:11px;color:var(--text3);font-weight:700;text-transform:uppercase;margin-bottom:4px">Your Response:</div>
                <div style="font-size:13px;color:var(--text);font-weight:600">
                    <?php if ($qType === 'multiple_choice'): 
                        $ansText = getAssessmentOptionDisplay($sa['options'], (string)($sa['student_answer'] ?? ''));
                        echo $ansText ? htmlspecialchars($ansText) : '<em class="text-muted">No answer selected</em>';
                    elseif ($qType === 'true_false'):
                        echo htmlspecialchars((string)($sa['student_answer'] ?? 'No answer selected'));
                    else:
                        echo nl2br(htmlspecialchars((string)($sa['student_answer'] ?? 'No response provided.')));
                    endif; ?>
                </div>
            </div>

            <!-- Show correct answer for objective questions if wrong -->
            <?php if ($isCorrect === 0 && $qType !== 'essay'): ?>
                <div style="padding:8px 12px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:6px;font-size:12px;color:#065f46">
                    <strong><i class="fas fa-check-circle"></i> Correct Answer:</strong>
                    <?php if ($qType === 'multiple_choice'): 
                        echo htmlspecialchars(getAssessmentOptionDisplay($sa['options'], (string)($sa['correct_answer'] ?? '')));
                    else:
                        echo htmlspecialchars((string)($sa['correct_answer'] ?? ''));
                    endif; ?>
                </div>
            <?php elseif ($qType === 'essay' && !empty($sa['explanation'])): ?>
                <div style="margin-top:8px;padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;color:#475569">
                    <strong><i class="fas fa-clipboard-list"></i> Scoring Rubric:</strong> <?= nl2br(htmlspecialchars((string)$sa['explanation'])) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($sa['teacher_feedback'])): ?>
                <div style="margin-top:8px;padding:8px 12px;background:#fffbeb;border:1px solid #fef3c7;border-radius:6px;font-size:12px;color:#92400e">
                    <strong><i class="fas fa-comment"></i> Teacher Comment:</strong> <?= htmlspecialchars($sa['teacher_feedback']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div style="margin-top:24px;text-align:center">
    <a href="assessments.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left" style="margin-right:6px"></i> Back to My Assessments
    </a>
</div>

<?php else: ?>
<!-- ================= QUIZ TAKING FORM VIEW ================= -->
<?php if (empty($activeQuestions)): ?>
<div class="card" style="padding:48px 24px;text-align:center;color:var(--text3)">
    <i class="fas fa-clipboard-list" style="font-size:36px;margin-bottom:12px;display:block;opacity:0.5"></i>
    <h3 style="font-size:16px;font-weight:700;color:var(--text)">Assessment Not Ready</h3>
    <p style="font-size:13px;max-width:440px;margin:4px auto 16px">Your teacher has not published questions for this assessment yet. Please check back later.</p>
    <a href="assessments.php" class="btn btn-secondary btn-sm">Return to Assessments</a>
</div>
<?php else: ?>
<form method="POST" id="takeQuizForm" onsubmit="return confirmSubmit()">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="submit_quiz">

    <div style="display:flex;flex-direction:column;gap:18px">
        <?php foreach ($activeQuestions as $idx => $q): 
            $num = $idx + 1;
            $qId = $q['id'];
        ?>
        <div class="card" style="border:1px solid var(--border);box-shadow:0 2px 8px rgba(0,0,0,0.02)">
            <div class="card-body" style="padding:20px 24px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--text);color:#fff;font-size:13px;font-weight:700">
                            <?= $num ?>
                        </span>
                        <span class="badge" style="background:#2563eb;color:#fff;font-size:11.5px;font-weight:700;padding:3px 9px;border-radius:6px">
                            <i class="fas fa-star" style="font-size:10px"></i> <?= (float)($q['points'] ?? 0) ?> Point<?= ($q['points'] ?? 0) != 1 ? 's' : '' ?><?= $q['question_type'] === 'essay' ? ' for this Essay' : '' ?>
                        </span>
                    </div>
                    <span style="font-size:11px;color:var(--text3);text-transform:uppercase;font-weight:700">
                        <?= str_replace('_', ' ', $q['question_type']) ?>
                    </span>
                </div>

                <!-- Prompt -->
                <div style="font-size:15px;font-weight:600;color:var(--text);line-height:1.5;margin-bottom:16px">
                    <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                </div>

                <!-- Multiple Choice Options -->
                <?php if ($q['question_type'] === 'multiple_choice'): 
                    $opts = json_decode($q['options'], true) ?: [];
                    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                    $optIdx = 0;
                ?>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <?php foreach ($opts as $oKey => $optText): 
                        $letter = (is_string($oKey) && preg_match('/^[A-Z]$/i', $oKey)) ? strtoupper($oKey) : ($letters[$optIdx] ?? chr(65 + $optIdx));
                        $choiceId = "q_{$qId}_opt_{$optIdx}";
                        $choiceVal = (string)$oKey;
                        $optIdx++;
                    ?>
                    <label for="<?= $choiceId ?>" style="padding:12px 16px;border-radius:8px;border:1px solid var(--border);background:#fff;display:flex;align-items:center;gap:12px;cursor:pointer;transition:all 0.15s ease">
                        <input type="radio" name="answers[<?= $qId ?>]" id="<?= $choiceId ?>" value="<?= htmlspecialchars($choiceVal) ?>" style="transform:scale(1.2);cursor:pointer">
                        <strong style="color:var(--primary);width:18px"><?= $letter ?>.</strong>
                        <span style="font-size:13.5px;color:var(--text)"><?= htmlspecialchars($optText) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- True / False Options -->
                <?php elseif ($q['question_type'] === 'true_false'): ?>
                <div style="display:flex;gap:16px;max-width:360px">
                    <label style="flex:1;padding:12px 16px;border-radius:8px;border:1px solid var(--border);background:#fff;display:flex;align-items:center;gap:10px;cursor:pointer">
                        <input type="radio" name="answers[<?= $qId ?>]" value="True" style="transform:scale(1.2);cursor:pointer">
                        <span style="font-size:14px;font-weight:600;color:var(--text)">True</span>
                    </label>
                    <label style="flex:1;padding:12px 16px;border-radius:8px;border:1px solid var(--border);background:#fff;display:flex;align-items:center;gap:10px;cursor:pointer">
                        <input type="radio" name="answers[<?= $qId ?>]" value="False" style="transform:scale(1.2);cursor:pointer">
                        <span style="font-size:14px;font-weight:600;color:var(--text)">False</span>
                    </label>
                </div>

                <!-- Essay / Open Response -->
                <?php elseif ($q['question_type'] === 'essay'): ?>
                <div>
                    <textarea name="answers[<?= $qId ?>]" class="form-control" rows="5" placeholder="Type your response here..." style="font-size:13.5px;line-height:1.6"></textarea>
                    <small style="color:var(--text3);display:block;margin-top:6px">
                        <i class="fas fa-pencil-alt"></i> Write a complete, detailed answer. Your instructor will grade this question.
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Submit Bar -->
    <div class="card" style="margin-top:24px;border:1px solid rgba(16,185,129,0.3);background:#f0fdf4">
        <div class="card-body" style="padding:18px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
            <div>
                <strong style="font-size:14px;color:#166534">Ready to complete your submission?</strong>
                <p style="font-size:12px;color:#15803d;margin:2px 0 0">
                    Objective questions will be graded automatically. Review your choices before submitting.
                </p>
            </div>
            <div style="display:flex;gap:10px">
                <a href="assessments.php" class="btn btn-secondary btn-sm" onclick="return confirm('Leave this page? Your unsaved answers will be lost.')">Cancel</a>
                <button type="submit" class="btn btn-primary" style="background:#10b981;border-color:#10b981">
                    <i class="fas fa-paper-plane" style="margin-right:6px"></i> Submit Assessment
                </button>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>
<?php endif; ?>

</div></div></div>

<script>
function confirmSubmit() {
    return confirm('Are you sure you want to finalize and submit your assessment? You cannot modify your answers after submission.');
}
</script>
</body></html>
