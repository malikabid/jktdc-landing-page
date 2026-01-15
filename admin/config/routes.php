<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    
    // Health check endpoint
    $app->get('/health', function (Request $request, Response $response) {
        $data = [
            'status' => 'ok',
            'timestamp' => time(),
            'service' => 'DOTK Admin API',
            'version' => '1.0.0',
        ];
        
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    // API base endpoint
    $app->get('/api', function (Request $request, Response $response) {
        $data = [
            'message' => 'DOTK Admin API',
            'version' => '1.0.0',
            'endpoints' => [
                'health' => '/health',
                'auth' => '/api/auth/*',
                'notifications' => '/api/notifications/*',
                'events' => '/api/events/*',
                'tenders' => '/api/tenders/*',
                'officials' => '/api/officials/*',
            ]
        ];
        
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    // Root - Welcome/Login page
    $app->get('/', function (Request $request, Response $response) {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOTK Admin Panel - Welcome</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 32px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .status {
            background: #10b981;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 600;
        }
        .api-link {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s;
        }
        .api-link:hover {
            background: #764ba2;
        }
        .info {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏔️ DOTK Admin Panel</h1>
        <p>Welcome to the Directorate of Tourism Kashmir Admin Panel</p>
        
        <div class="status">
            ✓ System Online and Running
        </div>
        
        <p>The admin API is successfully configured and ready to use.</p>
        
        <a href="/admin/api" class="api-link">View API Documentation</a>
        
        <div class="info">
            <p>Login functionality coming soon...</p>
            <p>Version 1.0.0</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
    
    // Auth routes will be added here
    // $app->group('/api/auth', function ($group) { ... });
    
    // Protected routes will be added here
    // $app->group('/api', function ($group) { ... })->add(AuthMiddleware::class);
};
