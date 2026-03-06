<?php
// Daftar kata-kata kotor untuk disensor
define('BLOCKED_WORDS', [
    'gay', 'banci', 'anjing', 'monyet', 'goblok', 'tolol', 'asu', 'bajingan', 'puki', 'gendeng', 'kirek', 'ndlogok',
    'bodoh', 'idiot', 'bangsat', 'kontol', 'memek', 'bitch', 'fuck', 'shit',
    'asshole', 'berengsek', 'jancok', 'ngentot',
    'setan', 'iblis', 'murka', 'kesal', 'gila', 'edan', 'bodo', 'dungu','bencong', 'lesbi',
    'pemerkosa', 'pencuri', 'psikopat'
]);

// Function to censor blocked words
function censorContent($content) {
    $censored = $content;
    foreach (BLOCKED_WORDS as $word) {
        // Case-insensitive replacement with asterisks
        $replacement = str_repeat('*', strlen($word));
        $censored = preg_replace('/\b' . preg_quote($word, '/') . '\b/i', $replacement, $censored);
    }
    return $censored;
}

// Function to validate and process file upload
function uploadPostPhoto($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No file uploaded
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed: ' . $file['error']];
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['error' => 'Hanya file gambar yang diizinkan (JPG, PNG, GIF, WebP)'];
    }
    
    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return ['error' => 'Ukuran file terlalu besar (maksimal 5MB)'];
    }
    
    // Generate unique filename
    $upload_dir = __DIR__ . '/uploads/posts/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = time() . '_' . md5($file['name']) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filepath = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['error' => 'Gagal menyimpan file'];
    }
    
    return ['success' => true, 'filename' => $filename];
}
?>
