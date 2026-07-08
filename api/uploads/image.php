<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

require __DIR__ . '/../../vendor/autoload.php';

use Cloudinary\Cloudinary;

// Image-upload endpoint: uploads a logged-in user's image to Cloudinary and returns its URL.

// Send one JSON response and stop the upload request.
function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

// Enforce the HTTP method used for uploads.
function requireMethod(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        respond(405, [
            'success' => false,
            'message' => 'Method not allowed',
        ]);
    }
}

// Require a logged-in user before allowing uploads.
function requireLogin(): string
{
    if (!isset($_SESSION['user_id'])) {
        respond(401, [
            'success' => false,
            'message' => 'Please log in first',
        ]);
    }

    return (string) $_SESSION['user_id'];
}

// Select the Cloudinary folder based on the feature that is uploading the image.
function uploadFolder(string $type): string
{
    return match ($type) {
        'avatar' => 'event-management/avatars',
        'event' => 'event-management/events',
        'post' => 'event-management/posts',
        default => respond(422, [
            'success' => false,
            'message' => 'Upload type must be post, event, or avatar',
        ]),
    };
}

requireMethod('POST');
$userId = requireLogin();

$type = trim((string) ($_GET['type'] ?? 'post'));
$folder = uploadFolder($type);
$file = $_FILES['image'] ?? null;

// The frontend must send the file in the "image" multipart field.
if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    respond(422, [
        'success' => false,
        'message' => 'Please choose an image to upload',
    ]);
}

if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    respond(422, [
        'success' => false,
        'message' => 'Image upload failed',
    ]);
}

$tmpName = (string) ($file['tmp_name'] ?? '');
$size = (int) ($file['size'] ?? 0);
$maxSize = 5 * 1024 * 1024;

// Validate that PHP actually received an uploaded file and not a forged path.
if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    respond(422, [
        'success' => false,
        'message' => 'Invalid uploaded image',
    ]);
}

// Keep uploads small enough for the project and Cloudinary free-tier usage.
if ($size <= 0 || $size > $maxSize) {
    respond(422, [
        'success' => false,
        'message' => 'Image must be 5MB or smaller',
    ]);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpName) ?: '';
$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
];

// Check MIME type server-side because browser accept attributes can be bypassed.
if (!in_array($mimeType, $allowedMimeTypes, true)) {
    respond(422, [
        'success' => false,
        'message' => 'Only JPG, PNG, WEBP, or GIF images are allowed',
    ]);
}

$config = require __DIR__ . '/../../connect_db/config.php';
$cloudinaryConfig = $config['cloudinary'] ?? [];

// Fail early if local config.php does not contain the required Cloudinary credentials.
foreach (['cloud_name', 'api_key', 'api_secret'] as $field) {
    if (trim((string) ($cloudinaryConfig[$field] ?? '')) === '') {
        respond(500, [
            'success' => false,
            'message' => 'Cloudinary is not configured',
        ]);
    }
}

try {
    // Upload the local temporary file and let Cloudinary return a permanent HTTPS URL.
    $cloudinary = new Cloudinary([
        'cloud' => $cloudinaryConfig,
    ]);

    $result = $cloudinary->uploadApi()->upload($tmpName, [
        'folder' => $folder,
        'public_id' => $userId . '_' . bin2hex(random_bytes(8)),
        'resource_type' => 'image',
        'overwrite' => false,
    ]);
} catch (Throwable $error) {
    respond(500, [
        'success' => false,
        'message' => 'Cloudinary upload failed: ' . $error->getMessage(),
    ]);
}

$imageUrl = (string) ($result['secure_url'] ?? '');

// The frontend stores this URL in MongoDB through the normal create/update APIs.
if ($imageUrl === '') {
    respond(500, [
        'success' => false,
        'message' => 'Cloudinary did not return an image URL',
    ]);
}

respond(201, [
    'success' => true,
    'image_url' => $imageUrl,
    'public_id' => (string) ($result['public_id'] ?? ''),
]);
