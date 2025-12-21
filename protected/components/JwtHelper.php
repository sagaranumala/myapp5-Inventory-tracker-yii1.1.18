<?php
/**
 * Simple JWT Helper for Yii 1.x
 * Install: composer require firebase/php-jwt
 */

require_once __DIR__ . '/../../vendor/autoload.php'; // Adjust path

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper extends CApplicationComponent
{
    public $secretKey = 'your-default-secret-key-change-me';
    public $algorithm = 'HS256';
    public $expireTime = 86400; // 24 hours

    public function init()
    {
        parent::init();
        if (!class_exists('Firebase\JWT\JWT')) {
            throw new CException('Firebase JWT library not found. Run: composer require firebase/php-jwt');
        }
    }

    /**
     * Generate JWT token
     * Accepts a single array payload
     */
    public function generateToken(array $payload)
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->expireTime;

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Validate and decode JWT token
     * Returns decoded payload or null
     */
    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded->data ?? null; // Return inner 'data' array
        } catch (Exception $e) {
            Yii::log('JWT Error: ' . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Extract token from request (Authorization header or query parameter)
     */
    public function extractToken()
    {
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

        if (isset($_GET['token'])) {
            return trim($_GET['token']);
        }

        return null;
    }

    /**
     * Get current authenticated user from JWT
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
     * Returns the configured expiration time in seconds
     */
    public function getExpiryTime()
    {
        return $this->expireTime;
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
            'data'    => $data
        ];

        echo json_encode($response);
        Yii::app()->end();
    }
}
