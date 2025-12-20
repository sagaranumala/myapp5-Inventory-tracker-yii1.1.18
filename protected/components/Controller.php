<?php
class Controller extends CController
{
    public $layout = '//layouts/column1';
    public $menu = array();
    public $breadcrumbs = array();

    protected function beforeAction($action)
    {
        // Global CORS headers
        $this->setCorsHeaders();

        // Preflight OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            echo json_encode(['success' => true]);
            Yii::app()->end();
        }

        return parent::beforeAction($action);
    }

    protected function setCorsHeaders()
    {
        $allowedOrigins = ['http://localhost:3000', 'http://127.0.0.1:3000'];
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Max-Age: 86400");
        }
    }

    protected function sendJson($data, $statusCode = 200)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($statusCode);
        }
        echo json_encode($data, JSON_PRETTY_PRINT);
        Yii::app()->end();
    }
}
