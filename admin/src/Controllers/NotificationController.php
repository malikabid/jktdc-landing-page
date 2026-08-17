<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Models\NotificationDocument;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

class NotificationController
{
    /**
     * List all notifications (Admin API)
     */
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $query = Notification::with('documents')->orderBy('created_at', 'desc');

        // Filter by status
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        // Filter by priority
        if (!empty($params['priority'])) {
            $query->where('priority', $params['priority']);
        }

        // Filter by category
        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }

        // Search
        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('notification_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $notifications = $query->get();

        $response->getBody()->write(json_encode([
            'notifications' => $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'notification_number' => $notification->notification_number,
                    'priority' => $notification->priority,
                    'publish_date' => $notification->publish_date->format('Y-m-d'),
                    'expiry_date' => $notification->expiry_date?->format('Y-m-d'),
                    'category' => $notification->category,
                    'status' => $notification->status,
                    'is_live' => $notification->isLive(),
                    'is_expired' => $notification->isExpired(),
                    'documents_count' => $notification->documents->count(),
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray(),
            'total' => $notifications->count(),
            'categories' => Notification::CATEGORIES,
            'priorities' => Notification::PRIORITIES,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get single notification details (Admin API)
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $notification = Notification::with('documents', 'creator', 'updater')->find($args['id']);

        if (!$notification) {
            $response->getBody()->write(json_encode(['error' => 'Notification not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'notification' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'notification_number' => $notification->notification_number,
                'description' => $notification->description,
                'icon' => $notification->icon,
                'show_arrow' => (bool)$notification->show_arrow,
                'priority' => $notification->priority,
                'publish_date' => $notification->publish_date->format('Y-m-d'),
                'expiry_date' => $notification->expiry_date?->format('Y-m-d'),
                'category' => $notification->category,
                'status' => $notification->status,
                'is_live' => $notification->isLive(),
                'is_expired' => $notification->isExpired(),
                'documents' => $notification->documents->map(fn($doc) => [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'file_path' => $doc->file_path,
                    'file_type' => $doc->file_type,
                    'file_size' => $doc->file_size,
                    'formatted_size' => $doc->getFormattedSize(),
                ])->toArray(),
                'created_by' => $notification->creator?->full_name,
                'updated_by' => $notification->updater?->full_name,
                'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $notification->updated_at->format('Y-m-d H:i:s'),
            ],
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create new notification
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        // Validate required fields
        $required = ['title', 'description', 'publish_date', 'category'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode([
                    'error' => "Field '{$field}' is required"
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        // Validate priority
        $priority = $data['priority'] ?? Notification::PRIORITY_MEDIUM;
        if (!in_array($priority, Notification::PRIORITIES, true)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid priority']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Expiry must not precede publication
        if (!empty($data['expiry_date']) && $data['expiry_date'] < $data['publish_date']) {
            $response->getBody()->write(json_encode([
                'error' => 'Expiry date cannot be before the publish date'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $notification = Notification::create([
            'title' => $data['title'],
            'notification_number' => ($data['notification_number'] ?? null) ?: null,
            'description' => $data['description'],
            'icon' => ($data['icon'] ?? null) ?: '📄',
            'show_arrow' => $this->toBool($data['show_arrow'] ?? true),
            'priority' => $priority,
            'publish_date' => $data['publish_date'],
            'expiry_date' => ($data['expiry_date'] ?? null) ?: null,
            'category' => $data['category'],
            'status' => $data['status'] ?? Notification::STATUS_DRAFT,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Notification created successfully',
            'notification' => ['id' => $notification->id],
        ]));

        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update notification
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $notification = Notification::find($args['id']);

        if (!$notification) {
            $response->getBody()->write(json_encode(['error' => 'Notification not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        // Validate priority if provided
        if (!empty($data['priority']) && !in_array($data['priority'], Notification::PRIORITIES, true)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid priority']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Expiry must not precede publication
        $publishDate = $data['publish_date'] ?? $notification->publish_date->format('Y-m-d');
        if (!empty($data['expiry_date']) && $data['expiry_date'] < $publishDate) {
            $response->getBody()->write(json_encode([
                'error' => 'Expiry date cannot be before the publish date'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Fields that keep an empty value as NULL
        $nullable = ['notification_number', 'expiry_date'];

        $fillable = [
            'title', 'notification_number', 'description', 'icon',
            'priority', 'publish_date', 'expiry_date', 'category', 'status'
        ];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $notification->$field = in_array($field, $nullable, true)
                    ? ($data[$field] ?: null)
                    : $data[$field];
            }
        }

        if (array_key_exists('show_arrow', $data)) {
            $notification->show_arrow = $this->toBool($data['show_arrow']);
        }

        $notification->updated_by = $userId;
        $notification->save();

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Notification updated successfully',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $notification = Notification::find($args['id']);

        if (!$notification) {
            $response->getBody()->write(json_encode(['error' => 'Notification not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Delete associated document files
        foreach ($notification->documents as $doc) {
            $this->deleteFile($doc->file_path);
        }

        // Documents will be cascade deleted due to foreign key
        $notification->delete();

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Notification deleted successfully',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Upload document to notification
     */
    public function uploadDocument(Request $request, Response $response, array $args): Response
    {
        $notification = Notification::find($args['id']);
        if (!$notification) {
            $response->getBody()->write(json_encode(['error' => 'Notification not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $uploadedFiles = $request->getUploadedFiles();
        $data = $request->getParsedBody();

        // A request larger than post_max_size is discarded by PHP before it
        // reaches us: no files, no body. Without this check that surfaces as a
        // misleading "No file uploaded".
        $postMax = $this->iniBytes('post_max_size');
        $contentLength = (int)$request->getHeaderLine('Content-Length');
        if (empty($uploadedFiles) && $postMax > 0 && $contentLength > $postMax) {
            $response->getBody()->write(json_encode([
                'error' => sprintf(
                    'Upload too large: the request is %s but this server accepts at most %s per upload.',
                    $this->formatBytes($contentLength),
                    $this->formatBytes($postMax)
                )
            ]));
            return $response->withStatus(413)->withHeader('Content-Type', 'application/json');
        }

        if (empty($uploadedFiles['document'])) {
            $response->getBody()->write(json_encode(['error' => 'No file was attached to the request.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $uploadedFiles['document'];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $error = $uploadedFile->getError();
            $response->getBody()->write(json_encode([
                'error' => $this->describeUploadError($error),
                'upload_error_code' => $error,
            ]));
            $status = in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 413 : 400;
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }

        // Validate the extension against a server-side allow-list. The client
        // media type is attacker-controlled and must never decide what we write
        // to disk - anything ending in .php under the docroot is executed.
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $extension = strtolower(pathinfo($uploadedFile->getClientFilename() ?? '', PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            $response->getBody()->write(json_encode(['error' => 'Only PDF and Word documents are allowed']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Generate filename. The random suffix matters: two uploads in the same
        // second would otherwise collide, and a rejected upload would overwrite
        // then delete an existing valid document.
        $filename = sprintf(
            'notification_%d_%s_%s.%s',
            $notification->id,
            date('YmdHis'),
            bin2hex(random_bytes(4)),
            $extension
        );

        // Use base path from settings/env
        $container = $GLOBALS['app']->getContainer();
        $basePath = $container->get('settings')['app']['base_path'] ?? '';
        $uploadDir = rtrim($basePath, '/') . '/pub/notifications/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uploadedFile->moveTo($uploadDir . $filename);

        // Confirm the real content type now that the file is on disk, and bin it
        // if the bytes disagree with the extension we accepted
        if (!$this->hasAllowedContent($uploadDir . $filename, $extension)) {
            unlink($uploadDir . $filename);
            $response->getBody()->write(json_encode([
                'error' => 'File content does not match a PDF or Word document'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Create document record. Use ?: not ?? - a blank label is submitted as an
        // empty string, which ?? would happily accept and store as a nameless doc.
        $documentName = trim((string)($data['label'] ?? ''))
            ?: trim((string)($data['name'] ?? ''))
            ?: $uploadedFile->getClientFilename();

        $document = NotificationDocument::create([
            'notification_id' => $notification->id,
            'name' => $documentName,
            'file_path' => '/pub/notifications/' . $filename,
            'file_type' => $extension,
            'file_size' => $uploadedFile->getSize(),
            'sort_order' => $notification->documents()->count(),
        ]);

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'document' => [
                'id' => $document->id,
                'name' => $document->name,
                'file_path' => $document->file_path,
                'file_type' => $document->file_type,
                'formatted_size' => $document->getFormattedSize(),
            ],
        ]));

        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Delete document from notification
     */
    public function deleteDocument(Request $request, Response $response, array $args): Response
    {
        $document = NotificationDocument::where('notification_id', $args['id'])
            ->where('id', $args['docId'])
            ->first();

        if (!$document) {
            $response->getBody()->write(json_encode(['error' => 'Document not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $this->deleteFile($document->file_path);
        $document->delete();

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Document deleted successfully',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get notification statistics
     */
    public function stats(Request $request, Response $response): Response
    {
        $stats = [
            'total' => Notification::count(),
            'published' => Notification::where('status', Notification::STATUS_PUBLISHED)->count(),
            'draft' => Notification::where('status', Notification::STATUS_DRAFT)->count(),
            'archived' => Notification::where('status', Notification::STATUS_ARCHIVED)->count(),
            'live' => Notification::published()->current()->count(),
        ];

        $response->getBody()->write(json_encode($stats));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Public API - Get live notifications for frontend
     */
    public function publicIndex(Request $request, Response $response): Response
    {
        $notifications = Notification::with('documents')
            ->published()
            ->current()
            ->byPriority()
            ->get();

        $response->getBody()->write(json_encode(
            $notifications->map(fn($notification) => $notification->toPublicArray())->toArray()
        ));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Normalise the various truthy forms a form/JSON body can send
     */
    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string)$value), ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Turn a PHP UPLOAD_ERR_* code into something a person can act on
     */
    private function describeUploadError(int $error): string
    {
        $limit = $this->formatBytes($this->iniBytes('upload_max_filesize'));

        return match ($error) {
            UPLOAD_ERR_INI_SIZE => "File is too large. This server accepts files up to {$limit}.",
            UPLOAD_ERR_FORM_SIZE => 'File is larger than the limit set by the upload form.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No file was selected.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server is missing its temporary upload folder. Contact the administrator.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the file to disk. Contact the administrator.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension blocked the upload. Contact the administrator.',
            default => "File upload failed (error code {$error}).",
        };
    }

    /**
     * Resolve a php.ini shorthand size (e.g. "32M") to bytes
     */
    private function iniBytes(string $key): int
    {
        $value = trim((string)ini_get($key));
        if ($value === '') {
            return 0;
        }

        $unit = strtolower($value[strlen($value) - 1]);
        $number = (int)$value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int)$value,
        };
    }

    /**
     * Human-readable byte size
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $i === 0 ? 0 : 1) . ' ' . $units[$i];
    }

    /**
     * Verify a stored file's real content type matches the extension we accepted
     */
    private function hasAllowedContent(string $path, string $extension): bool
    {
        if (!function_exists('finfo_open')) {
            // Without fileinfo we cannot verify; the extension allow-list already
            // guarantees the file is not executable, so let it through
            return true;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected = finfo_file($finfo, $path);
        finfo_close($finfo);

        $allowed = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage'],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
            ],
        ];

        return in_array($detected, $allowed[$extension] ?? [], true);
    }

    /**
     * Remove an uploaded file from disk, resolving against the configured base path.
     * Paths are written server-side, but the containment check keeps it that way.
     */
    private function deleteFile(string $filePath): void
    {
        $container = $GLOBALS['app']->getContainer();
        $basePath = $container->get('settings')['app']['base_path'] ?? '';

        $roots = array_filter([
            $basePath ? rtrim($basePath, '/') : null,
            !empty($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') : null,
        ]);

        foreach ($roots as $root) {
            $uploadRoot = realpath($root . '/pub/notifications');
            $candidate = realpath($root . $filePath);

            if (!$uploadRoot || !$candidate || !is_file($candidate)) {
                continue;
            }

            // Never delete outside the notifications upload directory
            if (!str_starts_with($candidate, $uploadRoot . DIRECTORY_SEPARATOR)) {
                continue;
            }

            unlink($candidate);
            return;
        }
    }
}
