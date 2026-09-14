<?php
/**
 * syllabus_import.php
 *
 * All-in-one page for shared hosting (e.g. InfinityFree) where there's
 * no command-line access. Upload a syllabus CSV through the browser,
 * it gets converted to JSON and saved, then previewed below.
 *
 * Just upload this file to your htdocs (or a subfolder) via the
 * InfinityFree File Manager or FTP, then visit it in your browser.
 */

$dataDir    = __DIR__ . '/data';
$jsonFile   = $dataDir . '/syllabus.json';
$message    = null;
$error      = null;

// Make sure the data folder exists and is writable
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function importSyllabusFromCsv(string $filePath): array
{
    $rows = [];
    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        throw new RuntimeException("Could not open uploaded file.");
    }

    $header = fgetcsv($handle);
    if ($header === false) {
        fclose($handle);
        throw new RuntimeException("CSV file appears to be empty.");
    }
    $header = array_map('trim', $header);

    while (($line = fgetcsv($handle)) !== false) {
        if (count($line) === 1 && trim($line[0]) === '') {
            continue;
        }
        $line = array_pad($line, count($header), '');
        $row = array_combine($header, array_slice($line, 0, count($header)));

        $rows[] = [
            'week'       => $row['week']       ?? null,
            'date'       => $row['date']       ?? null,
            'topic'      => $row['topic']      ?? null,
            'readings'   => $row['readings']   ?? null,
            'assignment' => $row['assignment'] ?? null,
        ];
    }

    fclose($handle);
    return $rows;
}

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload failed (error code {$file['error']}).";
    } else {
        try {
            $entries = importSyllabusFromCsv($file['tmp_name']);

            $output = [
                'source'      => $file['name'],
                'imported_at' => date('c'),
                'entry_count' => count($entries),
                'entries'     => $entries,
            ];

            $json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (file_put_contents($jsonFile, $json) === false) {
                $error = "Could not write JSON file. Check that the 'data' folder is writable (chmod 755 or 775).";
            } else {
                $message = "Imported " . count($entries) . " entries successfully.";
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

// Load existing JSON for preview, if present
$entries = [];
$meta = [];
if (file_exists($jsonFile)) {
    $data = json_decode(file_get_contents($jsonFile), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $entries = $data['entries'] ?? [];
        $meta = [
            'source'      => $data['source'] ?? '—',
            'imported_at' => $data['imported_at'] ?? '—',
            'entry_count' => $data['entry_count'] ?? count($entries),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Syllabus Import</title>
<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
        color: #222;
        background: #fafafa;
    }
    h1 { margin-bottom: 4px; }
    .meta { color: #666; font-size: 0.9em; margin-bottom: 24px; }
    form {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }
    input[type="file"] { margin-right: 10px; }
    button {
        background: #2a4d9b;
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
    }
    button:hover { background: #1e3a7a; }
    table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    th, td {
        text-align: left;
        padding: 10px 12px;
        border-bottom: 1px solid #e5e5e5;
        vertical-align: top;
    }
    th {
        background: #f0f0f0;
        font-weight: 600;
        font-size: 0.85em;
        text-transform: uppercase;
    }
    tr:hover { background: #f7f9fc; }
    .success { padding: 12px; background: #eafbea; border: 1px solid #b7e4b7; border-radius: 6px; color: #276027; margin-bottom: 20px; }
    .error { padding: 12px; background: #fff3f3; border: 1px solid #f3c9c9; border-radius: 6px; color: #a33; margin-bottom: 20px; }
    .empty { padding: 20px; background: #fff; border-radius: 6px; color: #888; }
    .week-badge {
        display: inline-block;
        background: #e8eefc;
        color: #2a4d9b;
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 0.85em;
    }
</style>
</head>
<body>

<h1>Syllabus Import</h1>
<div class="meta">Upload a CSV to convert it into a JSON syllabus and preview it below.</div>

<?php if ($message): ?>
    <div class="success"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="error"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit">Import CSV</button>
</form>

<?php if (!empty($entries)): ?>
    <div class="meta">
        Source: <?= h($meta['source']) ?> &middot;
        Imported: <?= h($meta['imported_at']) ?> &middot;
        <?= (int)$meta['entry_count'] ?> entries
    </div>
    <table>
        <thead>
            <tr>
                <th>Week</th><th>Date</th><th>Topic</th><th>Readings</th><th>Assignment</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><span class="week-badge"><?= h($entry['week'] ?? '') ?></span></td>
                    <td><?= h($entry['date'] ?? '') ?></td>
                    <td><?= h($entry['topic'] ?? '') ?></td>
                    <td><?= h($entry['readings'] ?? '') ?></td>
                    <td><?= h($entry['assignment'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="empty">No syllabus imported yet. Upload a CSV above to get started.</div>
<?php endif; ?>

</body>
</html>