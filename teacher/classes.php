<?php
require_once '../includes/config.php';
requireRole('teacher');
redirect(BASE_URL . 'teacher/syllabi.php');
