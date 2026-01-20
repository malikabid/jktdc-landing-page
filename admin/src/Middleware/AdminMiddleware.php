<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Models\User;

class AdminMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $userId = $request->getAttribute('user_id');
        
        if (!$userId) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        $user = User::find($userId);
        
        if (!$user || !$user->isAdmin()) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'error' => 'Forbidden - Admin access required'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        return $handler->handle($request);
    }
}
