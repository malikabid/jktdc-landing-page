<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;

class UserController
{
    /**
     * Get all users (Super Admin only)
     */
    public function index(Request $request, Response $response): Response
    {
        $users = User::orderBy('created_at', 'DESC')->get();
        
        $userData = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at
            ];
        });
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'users' => $userData
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Get single user by ID (Super Admin only)
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $userId = $args['id'];
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
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Create new user (Super Admin only)
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate required fields
        $required = ['username', 'email', 'password', 'full_name', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode([
                    'error' => "Field {$field} is required"
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }
        
        // Validate role
        $validRoles = [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_EDITOR];
        if (!in_array($data['role'], $validRoles)) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid role'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if username already exists
        if (User::where('username', $data['username'])->exists()) {
            $response->getBody()->write(json_encode([
                'error' => 'Username already exists'
            ]));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }
        
        // Check if email already exists
        if (User::where('email', $data['email'])->exists()) {
            $response->getBody()->write(json_encode([
                'error' => 'Email already exists'
            ]));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }
        
        // Create user
        $user = new User();
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->password = $data['password']; // Will be hashed by model
        $user->full_name = $data['full_name'];
        $user->role = $data['role'];
        $user->is_active = $data['is_active'] ?? true;
        $user->save();
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'role' => $user->role,
                'is_active' => $user->is_active
            ]
        ]));
        
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Update user (Super Admin only)
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $userId = $args['id'];
        $user = User::find($userId);
        
        if (!$user) {
            $response->getBody()->write(json_encode([
                'error' => 'User not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $data = $request->getParsedBody();
        
        // Update username if provided
        if (!empty($data['username']) && $data['username'] !== $user->username) {
            if (User::where('username', $data['username'])->exists()) {
                $response->getBody()->write(json_encode([
                    'error' => 'Username already exists'
                ]));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }
            $user->username = $data['username'];
        }
        
        // Update email if provided
        if (!empty($data['email']) && $data['email'] !== $user->email) {
            if (User::where('email', $data['email'])->exists()) {
                $response->getBody()->write(json_encode([
                    'error' => 'Email already exists'
                ]));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }
            $user->email = $data['email'];
        }
        
        // Update password if provided
        if (!empty($data['password'])) {
            $user->password = $data['password']; // Will be hashed by model
        }
        
        // Update other fields
        if (!empty($data['full_name'])) {
            $user->full_name = $data['full_name'];
        }
        
        if (!empty($data['role'])) {
            $validRoles = [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_EDITOR];
            if (!in_array($data['role'], $validRoles)) {
                $response->getBody()->write(json_encode([
                    'error' => 'Invalid role'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            $user->role = $data['role'];
        }
        
        if (isset($data['is_active'])) {
            $user->is_active = (bool) $data['is_active'];
        }
        
        $user->save();
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'role' => $user->role,
                'is_active' => $user->is_active
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Delete user (Super Admin only)
     * Note: Super Admin users cannot be deleted, only deactivated
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $userId = $args['id'];
        $currentUserId = $request->getAttribute('user_id');
        
        // Prevent super admin from deleting themselves
        if ($userId == $currentUserId) {
            $response->getBody()->write(json_encode([
                'error' => 'Cannot delete your own account'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $user = User::find($userId);
        
        if (!$user) {
            $response->getBody()->write(json_encode([
                'error' => 'User not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Prevent deletion of super admin users - they can only be deactivated
        if ($user->role === User::ROLE_SUPER_ADMIN) {
            $response->getBody()->write(json_encode([
                'error' => 'Super Admin users cannot be deleted. Please deactivate the account instead.'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        $user->delete();
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
