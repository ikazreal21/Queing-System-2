<?php
// Cloudinary Helper Functions
require_once "vendor/autoload.php";
require_once "cloudinary_config.php";

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;

class CloudinaryHelper {
    
    public function __construct() {
        global $cloudinary_config;
        Configuration::instance($cloudinary_config);
    }
    
    /**
     * Upload file to Cloudinary
     * @param string $filePath Path to the file to upload
     * @param string $fileName Original filename
     * @param string $folder Cloudinary folder
     * @return array|false Upload result or false on failure
     */
    public function uploadFile($filePath, $fileName, $folder = 'queue_system/attachments') {
        try {
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $uniqueFileName = time() . "_" . uniqid() . "_" . pathinfo($fileName, PATHINFO_FILENAME);
            
            $uploadOptions = [
                'public_id' => $folder . '/' . $uniqueFileName,
                'resource_type' => ($fileType === 'pdf') ? 'raw' : 'image',
                'folder' => $folder,
                'use_filename' => false,
                'unique_filename' => true,
                'overwrite' => false
            ];
            
            // Add transformation for images (optional - resize large images)
            if (in_array($fileType, ['jpg', 'jpeg', 'png'])) {
                $uploadOptions['transformation'] = [
                    'quality' => 'auto:good',
                    'fetch_format' => 'auto'
                ];
            }
            
            $result = (new UploadApi())->upload($filePath, $uploadOptions);
            
            return [
                'success' => true,
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
                'original_name' => $fileName,
                'file_type' => $fileType,
                'file_size' => $result['bytes'] ?? 0
            ];
            
        } catch (Exception $e) {
            error_log("Cloudinary upload error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete file from Cloudinary
     * @param string $publicId Public ID of the file to delete
     * @param string $resourceType Type of resource (image, raw, video)
     * @return bool Success status
     */
    public function deleteFile($publicId, $resourceType = 'image') {
        try {
            $result = (new UploadApi())->destroy($publicId, ['resource_type' => $resourceType]);
            return $result['result'] === 'ok';
        } catch (Exception $e) {
            error_log("Cloudinary delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get optimized URL for an image
     * @param string $publicId Public ID of the image
     * @param array $transformations Array of transformations
     * @return string Optimized URL
     */
    public function getOptimizedUrl($publicId, $transformations = []) {
        try {
            // Use Cloudinary's URL generation
            $config = Configuration::instance();
            $baseUrl = "https://res.cloudinary.com/" . $config->cloud->cloudName . "/";
            
            if (!empty($transformations)) {
                $transformStr = $this->buildTransformationString($transformations);
                return $baseUrl . "image/upload/" . $transformStr . "/" . $publicId;
            }
            
            return $baseUrl . "image/upload/" . $publicId;
        } catch (Exception $e) {
            error_log("Cloudinary URL generation error: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Build transformation string from array
     * @param array $transformations
     * @return string
     */
    private function buildTransformationString($transformations) {
        $parts = [];
        foreach ($transformations as $key => $value) {
            $parts[] = $key . '_' . $value;
        }
        return implode(',', $parts);
    }
}
?>
