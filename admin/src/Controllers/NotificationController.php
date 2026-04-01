<?php

namespace App\Controllers;

use App\Models\Notification;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotificationController
{
    /**
     * List all notifications with filtering
     */
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $query = Notification::orderBy('publish_date', 'desc');

        // Filter by priority
        if (!empty($params['priority'])) {
            $query->where('priority', $params['priority']);
        }

        // Filter by category
        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }

        // Filter by active status
        if (isset($params['is_active'])) {
            $query->where('is_active', (bool)$params['is_active']);
        }

        // Search
        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('notification_no', 'like', "%{$search}%");
            });
        }

        $notifications = $query->get();

        $response->getBody()->write(json_encode([
            'notifications' => $notifications->map(fn($n) => $n->toPublicArray())->toArray(),
            'total' => $notifications->count(),
            'priorities' => Notification::PRIORITIES,
            'categories' => Notification::CATEGORIES,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get single notification details
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $notification = Notification::find($args['id']);

        if (!$notification) {
            $response->getBody()->write(json_encode(['error' => 'Notification not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['notification' => $notification->toPublicArray()]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create a new notification
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Validation
        if (empty($data['title']) || empty($data['publish_date'])) {
            $response->getBody()->write(json_encode([
                'error' => 'title and publish_date are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validate dates
        $publishDate = $data['publish_date'];
        $expiryDate = $data['expiry_date'] ?? null;
        if ($expiryDate && strtotime($publishDate) > strtotime($expiryDate)) {
            $response->getBody()->write(json_encode([
                'error' => 'Publish date cannot be after expiry date.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $notification = Notification::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'notification_no' => $data['notification_no'] ?? null,
                'icon' => $data['icon'] ?? '📄',
                'show_arrow' => isset($data['show_arrow']) ? (bool)$data['show_arrow'] : true,
                'priority' => $data['priority'] ?? 'medium',
                'publish_date' => $publishDate,
                'expiry_date' => $expiryDate,
                'category' => $data['category'] ?? 'General',
                'file_url' => $data['file_url'] ?? null,
                'file_name' => $data['file_name'] ?? null,
                'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
                'created_by' => $request->getAttribute('user')->id ?? null,
            ]);

            $response->getBody()->write(json_encode(['notification' => $notification->toPublicArray()]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Update a notification
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $notification = Notification::find($args['id']);

        if (!$notification) {
            $response->getBody()->write(json_encode(['error' => 'Notification not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $data = $request->getParsedBody();

        // Validate dates
        $publishDate = $data['publish_date'] ?? $notification->publish_date;
        $expiryDate = isset($data['expiry_date']) ? $data['expiry_date'] : $notification->expiry_date;
        if ($expiryDate && strtotime($publishDate) > strtotime($expiryDate)) {
            $response->getBody()->write(json_encode([
                'error' => 'Publish date cannot be after expiry date.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $notification->update([
                'title' => $data['title'] ?? $notification->title,
                'description' => $data['description'] ?? $notification->description,
                'notification_no' => isset($data['notification_no']) ? $data['notification_no'] : $notification->notification_no,
                'icon' => $data['icon'] ?? $notification->icon,
                'show_arrow' => isset($data['show_arrow']) ? (bool)$data['show_arrow'] : $notification->show_arrow,
                'priority' => $data['priority'] ?? $notification->priority,
                'publish_date' => $publishDate,
                'expiry_date' => $expiryDate,
                'category' => $data['category'] ?? $notification->category,
                'file_url' => isset($data['file_url']) ? $data['file_url'] : $notification->file_url,
                'file_name' => isset($data['file_name']) ? $data['file_name'] : $notification->file_name,
                'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : $notification->is_active,
                'updated_by' => $request->getAttribute('user')->id ?? null,
            ]);

            $response->getBody()->write(json_encode(['notification' => $notification->toPublicArray()]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $notification = Notification::find($args['id']);

        if (!$notification) {
            $response->getBody()->write(json_encode(['error' => 'Notification not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $notification->delete();

        $response->getBody()->write(json_encode(['message' => 'Notification deleted successfully']));
        return $response->withStatus(204)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get notifications for public API
     */
    public function publicIndex(Request $request, Response $response): Response
    {
        $notifications = Notification::active()
            ->published()
            ->notExpired()
            ->orderBy('publish_date', 'desc')
            ->limit(20)
            ->get();

        $response->getBody()->write(json_encode([
            'notifications' => $notifications->map(fn($n) => $n->toPublicArray())->toArray(),
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}