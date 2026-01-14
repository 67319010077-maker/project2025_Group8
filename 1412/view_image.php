<?php
require_once 'db.php';

if (isset($_GET['table']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $table = $_GET['table'];
    $id = (int) $_GET['id'];
    $column = isset($_GET['col']) ? $_GET['col'] : 'image';

    // Whitelist tables and columns for security
    $allowedTables = ['products', 'orders', 'admin'];
    $allowedColumns = ['image', 'payment_slip', 'avatar'];

    if (in_array($table, $allowedTables) && in_array($column, $allowedColumns)) {
        $stmt = $pdo->prepare("SELECT $column FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // IMPORTANT: Use logic to fetch data

        if ($row && !empty($row[$column])) {
            // Detect MIME type (simple check, or default to jpeg)
            // Ideally we should store mime type in DB, but for now we can try to detect or default.
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($row[$column]);

            header("Content-Type: " . $mimeType);
            echo $row[$column];
            exit;
        }
    }
}

// Fallback image or 404
header("HTTP/1.0 404 Not Found");
// Optional: Output a placeholder
// readfile('placeholder.png'); 
?>