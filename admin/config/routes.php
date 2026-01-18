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
            'version' => '1.0.2',
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
            'version' => '1.0.2',
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
    
    // Root - Login page
    $app->get('/', function (Request $request, Response $response) {
        $view = $this->get('view');
        
        return $view->render($response, 'auth/login.html.twig', [
            'title' => 'Login - DOTK Admin',
            'version' => '1.0.2'
        ]);
    });
    
    // Dashboard
    $app->get('/dashboard', function (Request $request, Response $response) {
        $view = $this->get('view');
        return $view->render($response, 'dashboard.html.twig');
    });
    
    // Users management page (Super Admin only)
    $app->get('/users', function (Request $request, Response $response) {
        $view = $this->get('view');
        return $view->render($response, 'users/index.html.twig');
    });
    
    // Tenders management page
    $app->get('/tenders', function (Request $request, Response $response) {
        $view = $this->get('view');
        return $view->render($response, 'tenders/index.html.twig');
    });
    
    // Auth routes (public)
    $app->group('/api/auth', function ($group) {
        $group->post('/login', 'App\Controllers\AuthController:login');
        $group->post('/logout', 'App\Controllers\AuthController:logout');
        $group->get('/me', 'App\Controllers\AuthController:me')->add('App\Middleware\AuthMiddleware');
    });
    
    // User management routes (Super Admin only)
    $app->group('/api/users', function ($group) {
        $group->get('', 'App\Controllers\UserController:index');
        $group->get('/{id}', 'App\Controllers\UserController:show');
        $group->post('', 'App\Controllers\UserController:store');
        $group->put('/{id}', 'App\Controllers\UserController:update');
        $group->delete('/{id}', 'App\Controllers\UserController:destroy');
    })->add('App\Middleware\SuperAdminMiddleware')->add('App\Middleware\AuthMiddleware');
    
    // Tender management routes (Admin only)
    $app->group('/api/tenders', function ($group) {
        $group->get('', 'App\Controllers\TenderController:index');
        $group->get('/stats', 'App\Controllers\TenderController:stats');
        $group->get('/{id}', 'App\Controllers\TenderController:show');
        $group->post('', 'App\Controllers\TenderController:store');
        $group->put('/{id}', 'App\Controllers\TenderController:update');
        $group->delete('/{id}', 'App\Controllers\TenderController:destroy');
        $group->post('/{id}/documents', 'App\Controllers\TenderController:uploadDocument');
        $group->delete('/{id}/documents/{docId}', 'App\Controllers\TenderController:deleteDocument');
    })->add('App\Middleware\AdminMiddleware')->add('App\Middleware\AuthMiddleware');
    
    // Public API for tenders (no auth required)
    $app->get('/api/public/tenders', 'App\Controllers\TenderController:publicIndex');
};
