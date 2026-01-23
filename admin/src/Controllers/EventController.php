<?php

namespace App\Controllers;

use App\Models\Event;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EventController
{
    /**
     * List all events with filtering
     */
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        
        $query = Event::orderBy('startDate', 'desc');
        
        // Filter by category
        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        
        // Search
        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }
        
        $events = $query->get();
        
        $response->getBody()->write(json_encode([
            'events' => $events->map(fn($e) => $e->toPublicArray())->toArray(),
            'total' => $events->count(),
            'categories' => Event::CATEGORIES,
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Get single event details
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $event = Event::find($args['id']);
        
        if (!$event) {
            $response->getBody()->write(json_encode(['error' => 'Event not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode(['event' => $event->toPublicArray()]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Create a new event
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validation
        if (empty($data['title']) || empty($data['startDate'])) {
            $response->getBody()->write(json_encode([
                'error' => 'title and startDate are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        try {
            $event = Event::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'startDate' => $data['startDate'],
                'endDate' => $data['endDate'] ?? null,
                'location' => $data['location'] ?? null,
                'category' => $data['category'] ?? null,
                'videoUrl' => $data['videoUrl'] ?? null,
                'thumbnail' => $data['thumbnail'] ?? null,
                'file_path' => $data['file_path'] ?? null,
                'cta_text' => $data['cta_text'] ?? null,
                'cta_link' => $data['cta_link'] ?? null,
                'showOnHomepage' => (bool)($data['showOnHomepage'] ?? false),
                'created_by' => $request->getAttribute('user')->id ?? null,
            ]);
            
            $response->getBody()->write(json_encode(['event' => $event->toPublicArray()]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
    
    /**
     * Update an event
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $event = Event::find($args['id']);
        
        if (!$event) {
            $response->getBody()->write(json_encode(['error' => 'Event not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $data = $request->getParsedBody();
        
        try {
            $event->update([
                'title' => $data['title'] ?? $event->title,
                'description' => $data['description'] ?? $event->description,
                'startDate' => $data['startDate'] ?? $event->startDate,
                'endDate' => $data['endDate'] ?? $event->endDate,
                'location' => $data['location'] ?? $event->location,
                'category' => $data['category'] ?? $event->category,
                'videoUrl' => $data['videoUrl'] ?? $event->videoUrl,
                'thumbnail' => $data['thumbnail'] ?? $event->thumbnail,
                'file_path' => isset($data['file_path']) ? $data['file_path'] : $event->file_path,
                'cta_text' => isset($data['cta_text']) ? $data['cta_text'] : $event->cta_text,
                'cta_link' => isset($data['cta_link']) ? $data['cta_link'] : $event->cta_link,
                'showOnHomepage' => isset($data['showOnHomepage']) ? (bool)$data['showOnHomepage'] : $event->showOnHomepage,
                'updated_by' => $request->getAttribute('user')->id ?? null,
            ]);
            
            $response->getBody()->write(json_encode(['event' => $event->toPublicArray()]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
    
    /**
     * Delete an event
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $event = Event::find($args['id']);
        
        if (!$event) {
            $response->getBody()->write(json_encode(['error' => 'Event not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $event->delete();
        
        $response->getBody()->write(json_encode(['message' => 'Event deleted successfully']));
        return $response->withStatus(204)->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Get events for homepage
     */
    public function homepage(Request $request, Response $response): Response
    {
        $events = Event::homepage()
            ->orderBy('startDate', 'asc')
            ->limit(10)
            ->get();
        
        $response->getBody()->write(json_encode([
            'events' => $events->map(fn($e) => $e->toPublicArray())->toArray(),
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Upload event file
     */
    public function uploadFile(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();
        if (!isset($uploadedFiles['file'])) {
            $response->getBody()->write(json_encode(['error' => 'No file uploaded']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $file = $uploadedFiles['file'];
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'File upload error']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validate file size (50MB)
        if ($file->getSize() > 50 * 1024 * 1024) {
            $response->getBody()->write(json_encode(['error' => 'File size must be less than 50MB']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validate file type
        $allowedMimeTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'video/mp4', 'video/avi', 'video/quicktime'
        ];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'mp4', 'avi', 'mov'];
        $clientMediaType = $file->getClientMediaType();
        $filename = $file->getClientFilename();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($clientMediaType, $allowedMimeTypes) || !in_array($extension, $allowedExtensions)) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid file type. Allowed: Images (JPG, PNG, GIF), Documents (PDF, DOC, DOCX), Videos (MP4, AVI, MOV)'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            // Use base path from settings/env (like TenderController)
            $container = $GLOBALS['app']->getContainer();
            $basePath = $container->get('settings')['app']['base_path'] ?? '';
            $uploadDir = rtrim($basePath, '/') . '/pub/events/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $uniqueFilename = 'event_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;
            $destination = $uploadDir . $uniqueFilename;

            die($destination);
            // Move uploaded file
            $file->moveTo($destination);

            // Return web-accessible path
            $webPath = '/pub/events/' . $uniqueFilename;

            $response->getBody()->write(json_encode([
                'file_path' => $webPath,
                'filename' => $uniqueFilename,
                'message' => 'File uploaded successfully'
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'File upload failed: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}

