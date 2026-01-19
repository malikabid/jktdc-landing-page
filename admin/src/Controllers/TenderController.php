<?php

namespace App\Controllers;

use App\Models\Tender;
use App\Models\TenderDocument;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

class TenderController
{
    /**
     * List all tenders (Admin API)
     */
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        
        $query = Tender::with('documents')->orderBy('created_at', 'desc');
        
        // Filter by status
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
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
                  ->orWhere('tender_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        $tenders = $query->get();
        
        $response->getBody()->write(json_encode([
            'tenders' => $tenders->map(function($tender) {
                return [
                    'id' => $tender->id,
                    'title' => $tender->title,
                    'tender_number' => $tender->tender_number,
                    'publish_date' => $tender->publish_date->format('Y-m-d'),
                    'closing_date' => $tender->closing_date->format('Y-m-d'),
                    'extended_date' => $tender->extended_date?->format('Y-m-d'),
                    'estimated_value' => $tender->estimated_value,
                    'formatted_value' => $tender->getFormattedValue(),
                    'category' => $tender->category,
                    'status' => $tender->status,
                    'department' => $tender->department,
                    'documents_count' => $tender->documents->count(),
                    'created_at' => $tender->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray(),
            'total' => $tenders->count(),
            'categories' => Tender::CATEGORIES,
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Get single tender details (Admin API)
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $tender = Tender::with('documents', 'creator', 'updater')->find($args['id']);
        
        if (!$tender) {
            $response->getBody()->write(json_encode(['error' => 'Tender not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode([
            'tender' => [
                'id' => $tender->id,
                'title' => $tender->title,
                'description' => $tender->description,
                'tender_number' => $tender->tender_number,
                'reference_number' => $tender->reference_number,
                'publish_date' => $tender->publish_date->format('Y-m-d'),
                'closing_date' => $tender->closing_date->format('Y-m-d'),
                'extended_date' => $tender->extended_date?->format('Y-m-d'),
                'estimated_value' => $tender->estimated_value,
                'formatted_value' => $tender->getFormattedValue(),
                'category' => $tender->category,
                'status' => $tender->status,
                'department' => $tender->department,
                'contact_person' => $tender->contact_person,
                'contact_email' => $tender->contact_email,
                'contact_phone' => $tender->contact_phone,
                'documents' => $tender->documents->map(fn($doc) => [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'file_path' => $doc->file_path,
                    'file_type' => $doc->file_type,
                    'file_size' => $doc->file_size,
                    'formatted_size' => $doc->getFormattedSize(),
                ])->toArray(),
                'created_by' => $tender->creator?->full_name,
                'updated_by' => $tender->updater?->full_name,
                'created_at' => $tender->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $tender->updated_at->format('Y-m-d H:i:s'),
            ],
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Create new tender
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');
        
        // Validate required fields
        $required = ['title', 'tender_number', 'publish_date', 'closing_date', 'category'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode([
                    'error' => "Field '{$field}' is required"
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }
        
        // Check for duplicate tender number
        if (Tender::where('tender_number', $data['tender_number'])->exists()) {
            $response->getBody()->write(json_encode([
                'error' => 'Tender number already exists'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Parse estimated value (convert from rupees to paise)
        $estimatedValue = null;
        if (!empty($data['estimated_value'])) {
            // Remove currency symbols and commas
            $value = preg_replace('/[₹,\s]/', '', $data['estimated_value']);
            if (is_numeric($value)) {
                $estimatedValue = (int)($value * 100); // Convert to paise
            }
        }
        
        $tender = Tender::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'tender_number' => $data['tender_number'],
            'reference_number' => $data['reference_number'] ?? null,
            'publish_date' => $data['publish_date'],
            'closing_date' => $data['closing_date'],
            'extended_date' => $data['extended_date'] ?? null,
            'estimated_value' => $estimatedValue,
            'category' => $data['category'],
            'status' => $data['status'] ?? Tender::STATUS_DRAFT,
            'department' => $data['department'] ?? 'Directorate of Tourism Kashmir',
            'contact_person' => $data['contact_person'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Tender created successfully',
            'tender' => ['id' => $tender->id],
        ]));
        
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Update tender
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $tender = Tender::find($args['id']);
        
        if (!$tender) {
            $response->getBody()->write(json_encode(['error' => 'Tender not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');
        
        // Check for duplicate tender number (excluding current)
        if (!empty($data['tender_number']) && $data['tender_number'] !== $tender->tender_number) {
            if (Tender::where('tender_number', $data['tender_number'])->where('id', '!=', $tender->id)->exists()) {
                $response->getBody()->write(json_encode([
                    'error' => 'Tender number already exists'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }
        
        // Parse estimated value if provided
        if (isset($data['estimated_value'])) {
            $value = preg_replace('/[₹,\s]/', '', $data['estimated_value']);
            $data['estimated_value'] = is_numeric($value) ? (int)($value * 100) : null;
        }
        
        // Update fields
        $fillable = [
            'title', 'description', 'tender_number', 'reference_number',
            'publish_date', 'closing_date', 'extended_date', 'estimated_value',
            'category', 'status', 'department',
            'contact_person', 'contact_email', 'contact_phone'
        ];
        
        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $tender->$field = $data[$field] ?: null;
            }
        }
        
        $tender->updated_by = $userId;
        $tender->save();
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Tender updated successfully',
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Delete tender
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $tender = Tender::find($args['id']);
        
        if (!$tender) {
            $response->getBody()->write(json_encode(['error' => 'Tender not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Delete associated document files
        foreach ($tender->documents as $doc) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $doc->file_path;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Documents will be cascade deleted due to foreign key
        $tender->delete();
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Tender deleted successfully',
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Upload document to tender
     */
    public function uploadDocument(Request $request, Response $response, array $args): Response
    {
        $tender = Tender::find($args['id']);
        
        if (!$tender) {
            $response->getBody()->write(json_encode(['error' => 'Tender not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $uploadedFiles = $request->getUploadedFiles();
        $data = $request->getParsedBody();
        
        if (empty($uploadedFiles['document'])) {
            $response->getBody()->write(json_encode(['error' => 'No file uploaded']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $uploadedFiles['document'];
        
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'File upload failed']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Validate file type
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $mimeType = $uploadedFile->getClientMediaType();
        
        if (!in_array($mimeType, $allowedTypes)) {
            $response->getBody()->write(json_encode(['error' => 'Only PDF and Word documents are allowed']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Generate filename
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $filename = sprintf(
            'tender_%d_%s.%s',
            $tender->id,
            date('YmdHis'),
            $extension
        );
        
        // Upload directory - use absolute path to main site's pub folder
        $uploadDir = '/var/www/html/pub/tenders/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadedFile->moveTo($uploadDir . $filename);
        
        // Create document record
        // Accept both 'label' (from frontend) and 'name' for backwards compatibility
        $documentName = $data['label'] ?? $data['name'] ?? $uploadedFile->getClientFilename();
        
        $document = TenderDocument::create([
            'tender_id' => $tender->id,
            'name' => $documentName,
            'file_path' => '/pub/tenders/' . $filename,
            'file_type' => $extension,
            'file_size' => $uploadedFile->getSize(),
            'sort_order' => $tender->documents()->count(),
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
     * Delete document from tender
     */
    public function deleteDocument(Request $request, Response $response, array $args): Response
    {
        $document = TenderDocument::where('tender_id', $args['id'])
            ->where('id', $args['docId'])
            ->first();
        
        if (!$document) {
            $response->getBody()->write(json_encode(['error' => 'Document not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Delete file
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $document->file_path;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $document->delete();
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Document deleted successfully',
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Get tender statistics
     */
    public function stats(Request $request, Response $response): Response
    {
        $stats = [
            'total' => Tender::count(),
            'active' => Tender::active()->count(),
            'draft' => Tender::where('status', Tender::STATUS_DRAFT)->count(),
            'closed' => Tender::where('status', Tender::STATUS_CLOSED)->count(),
            'cancelled' => Tender::where('status', Tender::STATUS_CANCELLED)->count(),
        ];
        
        $response->getBody()->write(json_encode($stats));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Public API - Get active tenders for frontend
     */
    public function publicIndex(Request $request, Response $response): Response
    {
        $tenders = Tender::with('documents')
            ->published()
            ->orderBy('publish_date', 'desc')
            ->get();
        
        $response->getBody()->write(json_encode(
            $tenders->map(fn($tender) => $tender->toPublicArray())->toArray()
        ));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
