<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use App\Services\JwtService;
use Psr\Container\ContainerInterface;

class AuthController
{
    private JwtService $jwtService;
    
    public function __construct(ContainerInterface $container)
    {
        $this->jwtService = $container->get('jwt');
    }
    
    /**
     * Handle user login
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $response->getBody()->write(json_encode([
                'error' => 'Username and password are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Find user by username
        $user = User::where('username', $username)->first();
        
        if (!$user || !$user->verifyPassword($password)) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid credentials'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        if (!$user->is_active) {
            $response->getBody()->write(json_encode([
                'error' => 'Account is inactive'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        // Update last login timestamp
        $user->updateLastLogin();
        
        // Generate JWT token
        $token = $this->jwtService->generateToken([
            'user_id' => $user->id,
            'role' => $user->role
        ]);
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'role' => $user->role
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Get current authenticated user info
     */
    public function me(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        
        $user = User::find($userId);
        
        if (!$user) {
            $response->getBody()->write(json_encode([
                'error' => 'User not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Handle user logout (client-side token deletion)
     */
    public function logout(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
