<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private string $secret;
    private string $algorithm;
    private int $expiration;
    
    public function __construct(array $config)
    {
        $this->secret = $config['secret'];
        $this->algorithm = $config['algorithm'];
        $this->expiration = $config['expiration'];
    }
    
    /**
     * Generate JWT token for user
     */
    public function generateToken(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expiration;
        
        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expire
        ]);
        
        return JWT::encode($tokenPayload, $this->secret, $this->algorithm);
    }
    
    /**
     * Decode and verify JWT token
     */
    public function verifyToken(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, $this->algorithm));
    }
    
    /**
     * Extract user ID from token
     */
    public function getUserIdFromToken(string $token): ?int
    {
        try {
            $decoded = $this->verifyToken($token);
            return $decoded->user_id ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
