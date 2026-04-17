<?php
/**
 * Eventura - QR Code Generator
 * Uses phpqrcode library
 */

/**
 * Generate unique QR code data for a registration
 */
function generateQRCode($registration_id, $event_id, $user_id) {
    // Create unique, encoded data string
    $data = [
        'r' => $registration_id,  // registration_id
        'e' => $event_id,         // event_id
        'u' => $user_id,          // user_id
        't' => time(),            // timestamp
        'h' => hash('sha256', $registration_id . $event_id . $user_id . 'eventura_secret_key')
    ];
    
    // Encode to base64 for compactness
    $qr_data = base64_encode(json_encode($data));
    
    // Generate QR image if library available
    generateQRImage($qr_data, $registration_id);
    
    return $qr_data;
}

/**
 * Generate QR Code Image
 */
function generateQRImage($qr_data, $registration_id) {
    $qr_dir = QR_PATH;
    if (!file_exists($qr_dir)) {
        mkdir($qr_dir, 0755, true);
    }
    
    $filename = 'qr_' . $registration_id . '.png';
    $filepath = $qr_dir . $filename;
    
    // Check if phpqrcode library exists
    $qr_lib = __DIR__ . '/../lib/phpqrcode/qrlib.php';
    
    if (file_exists($qr_lib)) {
        require_once $qr_lib;
        QRcode::png($qr_data, $filepath, QR_ECLEVEL_H, 10, 2);
    } else {
        // Fallback: generate using Google Chart API or create placeholder
        // For now, we'll store the data and render dynamically
        file_put_contents($qr_dir . 'qr_' . $registration_id . '.txt', $qr_data);
    }
    
    return QR_URL . $filename;
}

/**
 * Decode QR data
 */
function decodeQRData($qr_data) {
    try {
        $decoded = base64_decode($qr_data);
        $data = json_decode($decoded, true);
        
        if (!$data || !isset($data['r']) || !isset($data['e']) || !isset($data['u']) || !isset($data['h'])) {
            return false;
        }
        
        // Verify hash
        $expected_hash = hash('sha256', $data['r'] . $data['e'] . $data['u'] . 'eventura_secret_key');
        if (!hash_equals($expected_hash, $data['h'])) {
            return false;
        }
        
        return $data;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get QR Code display (HTML)
 */
function getQRDisplay($qr_data, $size = 200) {
    // Use QRServer API for dynamic generation
    $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($qr_data);
    return '<img src="' . $qr_url . '" alt="QR Code" style="width: ' . $size . 'px; height: ' . $size . 'px;">';
}
