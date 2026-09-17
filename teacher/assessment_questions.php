<?php
require_once '../includes/config.php';
requireRole('teacher');
$tid = $_SESSION['user_id'];
$assId = (int)($_GET['id'] ?? 0);

// Verify assessment belongs to this teacher
$stmt = $conn->prepare("
    SELECT a.*, s.course_id, c.course_code, c.course_name,
           (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as q_count,
           (SELECT COUNT(*) FROM submissions WHERE assessment_id = a.id) as sub_count
    FROM assessments a 
    JOIN syllabi s ON a.syllabus_id = s.id 
    JOIN courses c ON s.course_id = c.id 
    WHERE a.id = ? AND a.teacher_id = ?
");
$stmt->bind_param('ii', $assId, $tid);
$stmt->execute();
$assessment = $stmt->get_result()->fetch_assoc();

if (!$assessment) {
    setFlash('error', 'Assessment not found or you do not have permission to manage it.');
    redirect(BASE_URL . 'teacher/assessments.php');
}

$pageTitle = 'Manage Questions: ' . $assessment['title'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token expired. Please try again.');
        redirect(BASE_URL . 'teacher/assessment_questions.php?id=' . $assId);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_question') {
        $qText = sanitize($_POST['question_text'] ?? '');
        $qType = sanitize($_POST['question_type'] ?? 'multiple_choice');
        $points = (float)($_POST['points'] ?? 1);
        if ($points <= 0) $points = 1.0;
        $explanation = sanitize($_POST['explanation'] ?? '');

        if (empty($qText)) {
            setFlash('error', 'Question prompt cannot be empty.');
            redirect(BASE_URL . 'teacher/assessment_questions.php?id=' . $assId);
        }

        $options = null;
        $correctAnswer = null;

        if ($qType === 'multiple_choice') {
            $rawOpts = $_POST['mc_options'] ?? [];
            $cleanOpts = [];
            foreach ($rawOpts as $opt) {
                $trimmed = trim($opt);
                if ($trimmed !== '') {
                    $cleanOpts[] = $trimmed;
                }
            }
            if (count($cleanOpts) < 2) {
                setFlash('error', 'Multiple choice questions require at least 2 choices.');
                redirect(BASE_URL . 'teacher/assessment_questions.php?id=' . $assId);
            }
            $options = json_encode(array_values($cleanOpts));
            $correctIdx = (int)($_POST['mc_correct'] ?? 0);
            if (!isset($cleanOpts[$correctIdx])) {
                $correctIdx = 0;
            }
            $correctAnswer = (string)$correctIdx;

        } elseif ($qType === 'true_false') {
            $options = json_encode(['True', 'False']);
            $correctAnswer = ($_POST['tf_correct'] ?? 'True') === 'False' ? 'False' : 'True';

        } elseif ($qType === 'essay') {
            $options = null;
            $correctAnswer = null;
        }

        // Determine next sort order
        $maxOrder = $conn->query("SELECT MAX(sort_order) m FROM assessment_questions WHERE assessment_id = $assId")->fetch_assoc()['m'] ?? 0;
        $nextOrder = (int)$maxOrder + 1;

        $stmtInsert = $conn->prepare("
            INSERT INTO assessment_questions (assessment_id, question_text, question_type, points, options, correct_answer, explanation, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->bind_param('issdsssi', $assId, $qText, $qType, $points, $options, $correctAnswer, $explanation, $nextOrder);

        if ($stmtInsert->execute()) {
            // Recalculate max_score
            $sum = $conn->query("SELECT SUM(points) s FROM assessment_questions WHERE assessment_id = $assId")->fetch_assoc()['s'] ?? 0;
            $conn->query("UPDATE assessments SET max_score = $sum WHERE id = $assId");
            setFlash('success', 'Question added successfully.');
        } else {
            setFlash('error', 'Failed to add question: ' . $stmtInsert->error);
        }

    } elseif ($action === 'delete_question') {
        $qId = (int)($_POST['question_id'] ?? 0);
        $conn->query("DELETE FROM assessment_questions WHERE id = $qId AND assessment_id = $assId");
        
        // Recalculate max_score
        $sum = $conn->query("SELECT COALESCE(SUM(points), 0) s FROM assessment_questions WHERE assessment_id = $assId")->fetch_assoc()['s'] ?? 0;
        $conn->query("UPDATE assessments SET max_score = $sum WHERE id = $assId");
        setFlash('success', 'Question deleted.');

    } elseif ($action === 'toggle_shuffle') {
        $newVal = (int)($assessment['shuffle_questions'] ? 0 : 1);
        $conn->query("UPDATE assessments SET shuffle_questions = $newVal WHERE id = $assId");
        setFlash('success', 'Question scrambling ' . ($newVal ? 'enabled' : 'disabled') . '.');
    }

    redirect(BASE_URL . 'teacher/assessment_questions.php?id=' . $assId);
}

// Fetch all questions for this assessment
$questionsQuery = $conn->prepare("SELECT * FROM assessment_questions WHERE assessment_id = ? ORDER BY sort_order ASC, id ASC");
$questionsQuery->bind_param('i', $assId);
$questionsQuery->execute();
$questions = $questionsQuery->get_result()->fetch_all(MYSQLI_ASSOC);

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
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<!-- Breadcrumb -->
<div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text3);margin-bottom:16px">
    <a href="assessments.php" style="color:var(--primary);text-decoration:none">
        <i class="fas fa-arrow-left" style="margin-right:4px"></i> Assessments
    </a>
    <span>/</span>
    <span style="color:var(--text)"><?= htmlspecialchars($assessment['course_code']) ?></span>
    <span>/</span>
    <span style="color:var(--text);font-weight:600"><?= htmlspecialchars($assessment['title']) ?></span>
</div>

<!-- Header Card -->
<div class="card" style="margin-bottom:24px;border:1px solid rgba(59,130,246,0.25);box-shadow:0 4px 14px rgba(0,0,0,0.03)">
    <div class="card-body" style="padding:20px 24px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="badge badge-blue" style="font-weight:700"><?= htmlspecialchars($assessment['course_code']) ?></span>
                    <span class="badge badge-purple" style="text-transform:uppercase;font-size:11px"><?= $assessment['type'] ?></span>
                    <?php if ($assessment['shuffle_questions']): ?>
                        <span class="badge badge-green" style="font-size:11px"><i class="fas fa-random"></i> Scrambled for Students</span>
                    <?php endif; ?>
                </div>
                <h2 style="font-size:20px;font-weight:800;color:var(--text);margin:0 0 6px"><?= htmlspecialchars($assessment['title']) ?></h2>
                <p style="font-size:13px;color:var(--text3);margin:0">
                    <?= htmlspecialchars($assessment['course_name']) ?>
                    <?php if (!empty($assessment['description'])): ?>
                        &bull; <?= htmlspecialchars($assessment['description']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <a href="assessment_print.php?id=<?= $assId ?>" target="_blank" class="btn btn-secondary btn-sm" title="Print this assessment (Student Paper or Answer Key)">
                    <i class="fas fa-print" style="margin-right:4px"></i> Print Assessment
                </a>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_shuffle">
                    <button type="submit" class="btn <?= $assessment['shuffle_questions'] ? 'btn-success' : 'btn-secondary' ?> btn-sm" title="Toggle question scrambling order for students">
                        <i class="fas fa-random"></i> Scramble Questions: <?= $assessment['shuffle_questions'] ? 'ON' : 'OFF' ?>
                    </button>
                </form>
                <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addQuestionModal')">
                    <i class="fas fa-plus" style="margin-right:4px"></i> Add Question
                </button>
            </div>
        </div>

        <!-- Metric Badges Row -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:12px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
            <div style="background:#f8fafc;padding:10px 14px;border-radius:8px;border:1px solid var(--border)">
                <div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase">Total Questions</div>
                <div style="font-size:18px;font-weight:800;color:var(--text);margin-top:2px"><?= count($questions) ?></div>
            </div>
            <div style="background:#f8fafc;padding:10px 14px;border-radius:8px;border:1px solid var(--border)">
                <div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase">Total Max Score</div>
                <div style="font-size:18px;font-weight:800;color:var(--primary);margin-top:2px"><?= number_format($totalPoints, 2) ?> pts</div>
            </div>
            <div style="background:#f8fafc;padding:10px 14px;border-radius:8px;border:1px solid var(--border)">
                <div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase">Multiple Choice</div>
                <div style="font-size:18px;font-weight:800;color:#2563eb;margin-top:2px"><?= $mcqCount ?></div>
            </div>
            <div style="background:#f8fafc;padding:10px 14px;border-radius:8px;border:1px solid var(--border)">
                <div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase">True / False</div>
                <div style="font-size:18px;font-weight:800;color:#7c3aed;margin-top:2px"><?= $tfCount ?></div>
            </div>
            <div style="background:#f8fafc;padding:10px 14px;border-radius:8px;border:1px solid var(--border)">
                <div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase">Essay Prompts</div>
                <div style="font-size:18px;font-weight:800;color:#d97706;margin-top:2px"><?= $essayCount ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Questions List -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <h3 style="font-size:16px;font-weight:700">Question Pool (<?= count($questions) ?> items)</h3>
    <span style="font-size:12px;color:var(--text3)">
        <i class="fas fa-info-circle"></i> Objective questions auto-grade automatically. Essays are scored in Gradebook.
    </span>
</div>

<?php if (empty($questions)): ?>
<div class="card" style="padding:48px 24px;text-align:center;color:var(--text3)">
    <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:#eff6ff;color:var(--primary);font-size:28px;margin-bottom:16px">
        <i class="fas fa-question-circle"></i>
    </div>
    <h3 style="font-size:16px;font-weight:700;color:var(--text);margin-bottom:6px">No Questions Created Yet</h3>
    <p style="font-size:13px;max-width:480px;margin:0 auto 16px">
        Build your assessment questionnaire by adding Multiple Choice, True or False, or Essay questions.
    </p>
    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addQuestionModal')">
        <i class="fas fa-plus"></i> Add First Question
    </button>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px">
    <?php foreach ($questions as $idx => $q): 
        $num = $idx + 1;
        $typeBadges = [
            'multiple_choice' => ['name' => 'Multiple Choice', 'color' => '#2563eb', 'bg' => '#eff6ff', 'icon' => 'fa-check-double'],
            'true_false'      => ['name' => 'True or False',   'color' => '#7c3aed', 'bg' => '#f5f3ff', 'icon' => 'fa-toggle-on'],
            'essay'           => ['name' => 'Essay Prompt',    'color' => '#d97706', 'bg' => '#fffbeb', 'icon' => 'fa-paragraph']
        ];
        $tb = $typeBadges[$q['question_type']] ?? $typeBadges['multiple_choice'];
    ?>
    <div class="card" style="border:1px solid var(--border);box-shadow:0 2px 6px rgba(0,0,0,0.02)">
        <div class="card-body" style="padding:18px 20px">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:var(--text);color:#fff;font-size:12px;font-weight:700">
                        <?= $num ?>
                    </span>
                    <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:12px;background:<?= $tb['bg'] ?>;color:<?= $tb['color'] ?>;display:inline-flex;align-items:center;gap:4px">
                        <i class="fas <?= $tb['icon'] ?>"></i> <?= $tb['name'] ?>
                    </span>
                    <span class="badge" style="background:#2563eb;color:#fff;font-size:11.5px;font-weight:700;padding:3px 9px;border-radius:6px">
                        <i class="fas fa-star" style="font-size:10px"></i> Declared: <?= (float)$q['points'] ?> Point<?= $q['points'] != 1 ? 's' : '' ?><?= $q['question_type'] === 'essay' ? ' for this Essay' : '' ?>
                    </span>
                    <?php if ($q['question_type'] !== 'essay'): ?>
                        <span style="font-size:11px;color:#059669;font-weight:600"><i class="fas fa-robot"></i> Instant Auto-Grading</span>
                    <?php else: ?>
                        <span style="font-size:11px;color:#d97706;font-weight:600"><i class="fas fa-user-edit"></i> Manual Teacher Grading</span>
                    <?php endif; ?>
                </div>
                <form method="POST" onsubmit="return confirm('Delete this question?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete_question">
                    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" style="padding:4px 8px;font-size:11px" title="Delete question">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>

            <!-- Question Text -->
            <div style="font-size:14px;font-weight:600;color:var(--text);line-height:1.5;margin-bottom:14px">
                <?= nl2br(htmlspecialchars($q['question_text'])) ?>
            </div>

            <!-- Options Display -->
            <?php if ($q['question_type'] === 'multiple_choice'): 
                $opts = json_decode($q['options'], true) ?: [];
                $rawCorrect = (string)($q['correct_answer'] ?? '');
                $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                $optIdx = 0;
            ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:8px">
                <?php foreach ($opts as $oKey => $optText): 
                    $letter = (is_string($oKey) && preg_match('/^[A-Z]$/i', $oKey)) ? strtoupper($oKey) : ($letters[$optIdx] ?? chr(65 + $optIdx));
                    $isCorrect = (
                        strcasecmp((string)$oKey, $rawCorrect) === 0 ||
                        strcasecmp($letter, $rawCorrect) === 0 ||
                        (is_numeric($rawCorrect) && (int)$rawCorrect === $optIdx)
                    );
                    $optIdx++;
                ?>
                <div style="padding:10px 14px;border-radius:6px;border:1px solid <?= $isCorrect ? '#10b981' : 'var(--border)' ?>;background:<?= $isCorrect ? '#ecfdf5' : '#fff' ?>;display:flex;align-items:center;justify-content:space-between;gap:8px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <strong style="color:<?= $isCorrect ? '#065f46' : 'var(--text3)' ?>"><?= $letter ?>.</strong>
                        <span style="font-size:13px;color:<?= $isCorrect ? '#065f46;font-weight:600' : 'var(--text)' ?>"><?= htmlspecialchars($optText) ?></span>
                    </div>
                    <?php if ($isCorrect): ?>
                        <span style="font-size:11px;font-weight:700;color:#059669;display:inline-flex;align-items:center;gap:4px">
                            <i class="fas fa-check-circle"></i> Correct
                        </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php elseif ($q['question_type'] === 'true_false'): 
                $correctVal = $q['correct_answer'] ?? 'True';
            ?>
            <div style="display:flex;gap:12px;max-width:380px">
                <div style="flex:1;padding:10px 14px;border-radius:6px;border:1px solid <?= $correctVal === 'True' ? '#10b981' : 'var(--border)' ?>;background:<?= $correctVal === 'True' ? '#ecfdf5' : '#fff' ?>;display:flex;align-items:center;justify-content:space-between">
                    <span style="font-size:13px;font-weight:<?= $correctVal === 'True' ? '700;color:#065f46' : '500;color:var(--text)' ?>">True</span>
                    <?php if ($correctVal === 'True'): ?>
                        <span style="font-size:11px;font-weight:700;color:#059669"><i class="fas fa-check-circle"></i> Correct</span>
                    <?php endif; ?>
                </div>
                <div style="flex:1;padding:10px 14px;border-radius:6px;border:1px solid <?= $correctVal === 'False' ? '#10b981' : 'var(--border)' ?>;background:<?= $correctVal === 'False' ? '#ecfdf5' : '#fff' ?>;display:flex;align-items:center;justify-content:space-between">
                    <span style="font-size:13px;font-weight:<?= $correctVal === 'False' ? '700;color:#065f46' : '500;color:var(--text)' ?>">False</span>
                    <?php if ($correctVal === 'False'): ?>
                        <span style="font-size:11px;font-weight:700;color:#059669"><i class="fas fa-check-circle"></i> Correct</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php elseif ($q['question_type'] === 'essay'): ?>
            <div style="background:#fffbeb;border:1px solid #fef3c7;border-radius:6px;padding:10px 14px;font-size:12px;color:#92400e">
                <strong><i class="fas fa-clipboard-check"></i> Scoring Guidance / Rubric:</strong>
                <div style="margin-top:4px;color:#78350f"><?= !empty($q['explanation']) ? nl2br(htmlspecialchars($q['explanation'])) : 'Evaluate student depth of explanation, methodology, and relevance.' ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add Question Modal -->
<div class="modal-overlay" id="addQuestionModal">
<div class="modal" style="max-width:680px">
    <div class="modal-header">
        <span class="modal-title"><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:6px"></i> Add New Question</span>
        <button class="modal-close" onclick="closeModal('addQuestionModal')">&times;</button>
    </div>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_question">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Question Type</label>
                    <select name="question_type" id="modalQType" class="form-control" onchange="switchQuestionType(this.value)">
                        <option value="multiple_choice" selected>Multiple Choice (Auto-Graded)</option>
                        <option value="true_false">True or False (Auto-Graded)</option>
                        <option value="essay">Essay / Open Response (Teacher Graded)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="modalPointsLabel"><strong>Points *</strong> <span style="font-weight:400;color:var(--text3)">(e.g. 1, 2, 1.25)</span></label>
                    <input type="number" name="points" id="modalPoints" class="form-control" value="1" min="0.01" max="1000" step="any" required>
                    <small id="modalPointsHelp" class="text-muted" style="display:block;margin-top:3px">Enter the points for this question (e.g. 1, 2, 1.25, 5, etc.).</small>
                </div>
            </div>

            <div class="form-group">
                <label>Question Prompt / Problem Statement *</label>
                <textarea name="question_text" class="form-control" rows="3" placeholder="Enter your question clearly..." required></textarea>
            </div>

            <!-- Multiple Choice Container -->
            <div id="mcContainer" style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:16px">
                <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:10px;display:flex;align-items:center;justify-content:space-between">
                    <span><i class="fas fa-list-ol"></i> Multiple Choice Options</span>
                    <span style="color:var(--text3);font-weight:400">Select the radio button for the correct answer</span>
                </div>

                <div style="display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <input type="radio" name="mc_correct" value="0" checked style="transform:scale(1.2);cursor:pointer">
                        <span style="font-weight:700;width:20px">A.</span>
                        <input type="text" name="mc_options[]" class="form-control" placeholder="Option A text..." required>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px">
                        <input type="radio" name="mc_correct" value="1" style="transform:scale(1.2);cursor:pointer">
                        <span style="font-weight:700;width:20px">B.</span>
                        <input type="text" name="mc_options[]" class="form-control" placeholder="Option B text..." required>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px">
                        <input type="radio" name="mc_correct" value="2" style="transform:scale(1.2);cursor:pointer">
                        <span style="font-weight:700;width:20px">C.</span>
                        <input type="text" name="mc_options[]" class="form-control" placeholder="Option C text (optional)...">
                    </div>
                    <div style="display:flex;align-items:center;gap:10px">
                        <input type="radio" name="mc_correct" value="3" style="transform:scale(1.2);cursor:pointer">
                        <span style="font-weight:700;width:20px">D.</span>
                        <input type="text" name="mc_options[]" class="form-control" placeholder="Option D text (optional)...">
                    </div>
                </div>
            </div>

            <!-- True or False Container -->
            <div id="tfContainer" style="display:none;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;padding:16px;margin-bottom:16px">
                <div style="font-size:12px;font-weight:700;color:#5b21b6;margin-bottom:10px">
                    <i class="fas fa-toggle-on"></i> Select the Correct Answer:
                </div>
                <div style="display:flex;gap:24px">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;font-size:14px">
                        <input type="radio" name="tf_correct" value="True" checked style="transform:scale(1.2)"> True
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;font-size:14px">
                        <input type="radio" name="tf_correct" value="False" style="transform:scale(1.2)"> False
                    </label>
                </div>
            </div>

            <!-- Essay Container -->
            <div id="essayContainer" style="display:none;background:#fffbeb;border:1px solid #fef3c7;border-radius:8px;padding:16px;margin-bottom:16px">
                <div style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:6px">
                    <i class="fas fa-paragraph"></i> Essay Rubric & Evaluation Guide (Optional)
                </div>
                <p style="font-size:11px;color:#78350f;margin-bottom:8px">
                    Students will write their answer in a text response box. You will evaluate and assign marks in the Gradebook.
                </p>
                <textarea name="explanation" class="form-control" rows="2" placeholder="Key points students should address..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('addQuestionModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Question</button>
        </div>
    </form>
</div>
</div>

</div></div></div>

<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}

function switchQuestionType(type) {
    var mc = document.getElementById('mcContainer');
    var tf = document.getElementById('tfContainer');
    var es = document.getElementById('essayContainer');
    var pts = document.getElementById('modalPoints');
    var ptsLabel = document.getElementById('modalPointsLabel');
    var ptsHelp = document.getElementById('modalPointsHelp');

    mc.style.display = (type === 'multiple_choice') ? 'block' : 'none';
    tf.style.display = (type === 'true_false') ? 'block' : 'none';
    es.style.display = (type === 'essay') ? 'block' : 'none';

    if (type === 'essay') {
        ptsLabel.innerHTML = '<strong>Points for Essay *</strong> <span style="color:#d97706">(e.g. 5)</span>';
        ptsHelp.textContent = 'Enter the point value for this essay (e.g. 5, 10, or custom points).';
        var currentVal = parseFloat(pts.value);
        if (isNaN(currentVal) || currentVal === 1) {
            pts.value = '5';
        }
    } else {
        ptsLabel.innerHTML = '<strong>Points *</strong> <span style="font-weight:400;color:var(--text3)">(e.g. 1, 2, 1.25)</span>';
        ptsHelp.textContent = 'Enter the point value for this question (e.g. 1, 2, 1.25, etc.).';
        var currentVal = parseFloat(pts.value);
        if (currentVal === 5) {
            pts.value = '1';
        }
    }

    // Toggle required on MC inputs
    var mcInputs = mc.querySelectorAll('input[type="text"]');
    if (type === 'multiple_choice') {
        mcInputs[0].required = true;
        mcInputs[1].required = true;
    } else {
        mcInputs[0].required = false;
        mcInputs[1].required = false;
    }
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
