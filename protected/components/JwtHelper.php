<?php
/**
 * Simple JWT Helper for Yii 1.x
 * Install: composer require firebase/php-jwt
 */

// Load Composer autoload
require_once __DIR__ . '/../../vendor/autoload.php'; // adjust path if needed

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtHelper extends CApplicationComponent
{
    public $secretKey = 'your-default-secret-key-change-me';
    public $algorithm = 'HS256';
    public $expireTime = 86400; // 24 hours

    public function init()
    {
        parent::init();
        
        // Check if JWT class exists after autoload
        if (!class_exists('Firebase\JWT\JWT')) {
            throw new CException('Firebase JWT library not found. Run: composer require firebase/php-jwt');
        }
    }

    /**
     * Generate JWT token
     */
    public function generateToken($userId, $email, $role, $name)
    {
        $payload = [
            'iss' => 'your-app',
            'iat' => time(),
            'exp' => time() + $this->expireTime,
            'data' => [
                'userId' => $userId,
                'email' => $email,
                'role' => $role,
                'name' => $name
            ]
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Validate JWT token
     */
    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded->data;
        } catch (Exception $e) {
            Yii::log('JWT Error: ' . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Extract token from request
     */
    public function extractToken()
    {
        // Check Authorization header
        $authHeader = null;
        
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $name => $value) {
                if (strtolower($name) === 'authorization') {
                    $authHeader = $value;
                    break;
                }
            }
        }
        
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }
        
        // Check query parameter
        if (isset($_GET['token'])) {
            return trim($_GET['token']);
        }
        
        return null;
    }

    /**
     * Get current user from token
     */
    public function getCurrentUser()
    {
        $token = $this->extractToken();
        if (!$token) {
            return null;
        }
        return $this->validateToken($token);
    }

    /**
     * Send JSON response
     */
    public function sendResponse($success, $message, $data = null, $httpCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($httpCode);
        
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
        
        echo json_encode($response);
        Yii::app()->end();
    }
}