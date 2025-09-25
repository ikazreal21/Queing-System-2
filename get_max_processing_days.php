<?php
include('db.php');

$input = json_decode(file_get_contents('php://input'), true);
$docs = $input['documents'] ?? '';
$docsArray = array_map('trim', explode(',', $docs));

$maxDays = 0;
foreach ($docsArray as $doc) {
    $stmt = $pdo->prepare("SELECT processing_days FROM documents WHERE name = :name");
    $stmt->execute([':name' => $doc]);
    $d = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($d && intval($d['processing_days']) > $maxDays) {
        $maxDays = intval($d['processing_days']);
    }
}

echo json_encode(['success' => true, 'maxDays' => $maxDays]);
