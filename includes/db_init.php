<?php
/**
 * BlendEd LMS - Database Initialization & Schema Auto-Sync
 * Automatically creates all tables and synchronizes modern schema
 * whenever deployed to a fresh or migrated MySQL database.
 */

function ensureDatabaseSchemaReady($conn) {
    static $initialized = false;
    if ($initialized) return;
    $initialized = true;

    // Check if main table 'users' exists
    $chk = $conn->query("SHOW TABLES LIKE 'users'");
    $isFresh = (!$chk || $chk->num_rows === 0);

    if ($isFresh) {
        // 1. Load initial dump from databasecode.sql or if0_42325974_learnlms.sql
        $sqlFiles = [
            dirname(__DIR__) . '/databasecode.sql',
            dirname(__DIR__) . '/if0_42325974_learnlms.sql'
        ];

        foreach ($sqlFiles as $sqlPath) {
            if (file_exists($sqlPath)) {
                $sqlContent = file_get_contents($sqlPath);
                if (!empty($sqlContent)) {
                    $conn->multi_query($sqlContent);
                    do {
                        if ($res = $conn->store_result()) {
                            $res->free();
                        }
                    } while ($conn->more_results() && $conn->next_result());
                    break;
                }
            }
        }
    }

    // 2. Ensure modern tables exist
    $tables = [
        "CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `description` varchar(255) NOT NULL,
            `category` varchar(50) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_activity_user` (`user_id`),
            KEY `idx_activity_category` (`category`),
            KEY `idx_activity_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `system_settings` (
            `id` int(11) NOT NULL,
            `system_name` varchar(100) NOT NULL DEFAULT 'BlendEd LMS',
            `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
            `allow_registration` tinyint(1) NOT NULL DEFAULT 1,
            `academic_year` varchar(20) NOT NULL DEFAULT '2025-2026',
            `semester` varchar(20) NOT NULL DEFAULT '1st',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `assessment_questions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `assessment_id` int(11) NOT NULL,
            `question_text` text NOT NULL,
            `question_type` varchar(50) NOT NULL DEFAULT 'multiple_choice',
            `points` decimal(8,2) NOT NULL DEFAULT 1.00,
            `options` longtext DEFAULT NULL,
            `correct_answer` text DEFAULT NULL,
            `explanation` text DEFAULT NULL,
            `sort_order` int(11) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_aq_ass` (`assessment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `submission_answers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `submission_id` int(11) NOT NULL,
            `question_id` int(11) NOT NULL,
            `student_answer` text DEFAULT NULL,
            `is_correct` tinyint(1) DEFAULT NULL,
            `points_awarded` decimal(8,2) NOT NULL DEFAULT 0.00,
            `feedback` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_sa_sub` (`submission_id`),
            KEY `idx_sa_q` (`question_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `topic_progress` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `student_id` int(11) NOT NULL,
            `syllabus_topic_id` int(11) NOT NULL,
            `status` varchar(50) NOT NULL DEFAULT 'not_started',
            `read_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
            `completed_at` datetime DEFAULT NULL,
            `last_read_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_student_topic` (`student_id`,`syllabus_topic_id`),
            KEY `idx_tp_student` (`student_id`),
            KEY `idx_tp_topic` (`syllabus_topic_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($tables as $tSql) {
        $conn->query($tSql);
    }

    // Ensure default system_settings row exists
    $conn->query("INSERT IGNORE INTO `system_settings` (`id`, `system_name`, `maintenance_mode`) VALUES (1, 'BlendEd LMS', 0)");

    // 3. Ensure required modern columns exist
    ensureDbColumn($conn, 'syllabus_topics', 'is_completed', 'tinyint(1) NOT NULL DEFAULT 0');
    ensureDbColumn($conn, 'syllabus_topics', 'completion_notes', 'text DEFAULT NULL');
    ensureDbColumn($conn, 'syllabus_topics', 'deletion_requested', 'tinyint(1) NOT NULL DEFAULT 0');
    ensureDbColumn($conn, 'syllabus_topics', 'deletion_reason', 'text DEFAULT NULL');
    ensureDbColumn($conn, 'syllabus_topics', 'ilo_code', 'varchar(50) DEFAULT NULL');
    ensureDbColumn($conn, 'syllabus_topics', 'blooms_level', 'varchar(50) DEFAULT NULL');
    ensureDbColumn($conn, 'syllabus_topics', 'activity_title', 'varchar(255) DEFAULT NULL');

    ensureDbColumn($conn, 'learning_materials', 'syllabus_topic_id', 'int(11) DEFAULT NULL');
    ensureDbColumn($conn, 'learning_materials', 'content', 'longtext DEFAULT NULL');
    ensureDbColumn($conn, 'learning_materials', 'estimated_read_time', 'int(11) NOT NULL DEFAULT 5');
    ensureDbColumn($conn, 'learning_materials', 'delivery_mode', "varchar(50) NOT NULL DEFAULT 'both'");

    ensureDbColumn($conn, 'assessments', 'topic_id', 'int(11) DEFAULT NULL');
    ensureDbColumn($conn, 'assessments', 'delivery_mode', "varchar(50) NOT NULL DEFAULT 'online'");
    ensureDbColumn($conn, 'assessments', 'shuffle_questions', 'tinyint(1) NOT NULL DEFAULT 1');
    ensureDbColumn($conn, 'assessments', 'is_closed', 'tinyint(1) NOT NULL DEFAULT 0');
    ensureDbColumn($conn, 'assessments', 'submission_type', "varchar(50) NOT NULL DEFAULT 'quiz_builder'");

    ensureDbColumn($conn, 'submissions', 'is_auto_graded', 'tinyint(1) NOT NULL DEFAULT 0');

    // 4. If syllabus topics are empty, run the seed data
    $checkTopics = $conn->query("SELECT COUNT(*) as c FROM syllabus_topics");
    $count = ($checkTopics && $r = $checkTopics->fetch_assoc()) ? (int)$r['c'] : 0;
    if ($count === 0 && file_exists(dirname(__DIR__) . '/seed_data.php')) {
        try {
            // Buffer output from seed_data.php so it doesn't pollute HTML responses
            ob_start();
            include_once dirname(__DIR__) . '/seed_data.php';
            ob_end_clean();
        } catch (\Throwable $e) {
            // Silently continue
        }
    }
}

function ensureDbColumn($conn, $table, $column, $definition) {
    // Check if table exists first
    $tableChk = $conn->query("SHOW TABLES LIKE '$table'");
    if (!$tableChk || $tableChk->num_rows === 0) return;

    $chk = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($chk && $chk->num_rows === 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}
