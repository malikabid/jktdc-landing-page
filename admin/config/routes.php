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
    
    // PHP Info endpoint
    $app->get('/info', function (Request $request, Response $response) {
        $view = $this->get('view');
        
        ob_start();
        phpinfo();
        $phpinfo = ob_get_clean();
        
        return $view->render($response, 'info.html.twig', [
            'phpinfo_output' => $phpinfo
        ]);
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
        $view = $this->get('view');
        
        return $view->render($response, 'welcome.html.twig', [
            'title' => 'Welcome',
            'heading' => 'DOTK Admin Panel',
            'description' => 'Welcome to the Directorate of Tourism Kashmir Admin Panel',
            'status' => 'System Online and Running',
            'message' => 'The admin API is successfully configured and ready to use.',
            'footer_message' => 'Login functionality coming soon...',
            'version' => '1.0.1'
        ]);
    });
    
    // Auth routes will be added here
    // $app->group('/api/auth', function ($group) { ... });
    
    // Protected routes will be added here
    // $app->group('/api', function ($group) { ... })->add(AuthMiddleware::class);
};
