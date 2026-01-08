<?php
/**
 * Image Processing Helper
 * Handles image upload, validation, resize and crop with 12MB limit
 */

class ImageProcessor {
    
    const MAX_FILE_SIZE = 12582912; // 12MB in bytes
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const UPLOAD_DIR = 'uploads/images/';
    
    /**
     * Validate uploaded image
     */
    public static function validate($file) {
        $errors = [];
        
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = 'Image must be under 12MB';
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            $errors[] = 'Only JPG, PNG, GIF, and WebP images are allowed';
        }
        
        // Check if it's a valid image
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            $errors[] = 'Invalid image file';
        }
        
        return $errors;
    }
    
    /**
     * Upload and process image
     */
    public static function upload($file, $cropData = null) {
        // Validate
        $errors = self::validate($file);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Create upload directory if not exists
        if (!file_exists(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_', true) . '.' . $extension;
        $filepath = self::UPLOAD_DIR . $filename;
        
        // Load source image
        $source = self::loadImage($file['tmp_name']);
        if (!$source) {
            return ['success' => false, 'errors' => ['Failed to load image']];
        }
        
        // Get original dimensions
        list($origWidth, $origHeight) = getimagesize($file['tmp_name']);
        
        // Apply crop if provided
        if ($cropData && isset($cropData['x'], $cropData['y'], $cropData['width'], $cropData['height'])) {
            $cropped = self::cropImage($source, $cropData['x'], $cropData['y'], $cropData['width'], $cropData['height']);
            imagedestroy($source);
            $source = $cropped;
        }
        
        // Save image
        $saved = self::saveImage($source, $filepath);
        imagedestroy($source);
        
        if (!$saved) {
            return ['success' => false, 'errors' => ['Failed to save image']];
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => '/' . $filepath,
            'original_width' => $origWidth,
            'original_height' => $origHeight
        ];
    }
    
    /**
     * Load image from file
     */
    private static function loadImage($filepath) {
        $imageInfo = getimagesize($filepath);
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filepath);
            case 'image/png':
                return imagecreatefrompng($filepath);
            case 'image/gif':
                return imagecreatefromgif($filepath);
            case 'image/webp':
                return imagecreatefromwebp($filepath);
            default:
                return false;
        }
    }
    
    /**
     * Crop image
     */
    private static function cropImage($source, $x, $y, $width, $height) {
        $cropped = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG/GIF
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        
        imagecopy($cropped, $source, 0, 0, $x, $y, $width, $height);
        
        return $cropped;
    }
    
    /**
     * Resize image maintaining aspect ratio
     */
    public static function resize($source, $maxWidth, $maxHeight) {
        $origWidth = imagesx($source);
        $origHeight = imagesy($source);
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
        $newWidth = round($origWidth * $ratio);
        $newHeight = round($origHeight * $ratio);
        
        // Create resized image
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        
        return $resized;
    }
    
    /**
     * Save image to file
     */
    private static function saveImage($image, $filepath) {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $filepath, 90);
            case 'png':
                return imagepng($image, $filepath, 9);
            case 'gif':
                return imagegif($image, $filepath);
            case 'webp':
                return imagewebp($image, $filepath, 90);
            default:
                return false;
        }
    }
    
    /**
     * Delete image file
     */
    public static function delete($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}
?>