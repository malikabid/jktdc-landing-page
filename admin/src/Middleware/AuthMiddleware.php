<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Services\JwtService;
use App\Models\User;
use Psr\Container\ContainerInterface;

class AuthMiddleware
{
    private JwtService $jwt;
    
    public function __construct(ContainerInterface $container)
    {
        $this->jwt = $container->get('jwt');
    }
    
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        // If Authorization header is missing, try to get it from server params (Apache workaround)
        if (empty($authHeader)) {
            $serverParams = $request->getServerParams();
            if (isset($serverParams['HTTP_AUTHORIZATION'])) {
                $authHeader = $serverParams['HTTP_AUTHORIZATION'];
            } elseif (isset($serverParams['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authHeader = $serverParams['REDIRECT_HTTP_AUTHORIZATION'];
            }
        }
        
        if (empty($authHeader)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'error' => 'Authorization header missing'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // Extract token from "Bearer {token}"
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'error' => 'Invalid authorization format'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        try {
            $decoded = $this->jwt->verifyToken($token);
            
            // Add user ID to request attributes
            $request = $request->withAttribute('user_id', $decoded->user_id);
            $request = $request->withAttribute('user_role', $decoded->role);
            
            return $handler->handle($request);
            
        } catch (\Exception $e) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'error' => 'Invalid or expired token',
                'message' => $e->getMessage()
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}
