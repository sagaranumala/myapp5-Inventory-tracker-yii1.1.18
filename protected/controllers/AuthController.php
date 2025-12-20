<?php
// protected/controllers/AuthController.php

class AuthController extends Controller
{
    /**
     * Before any action: set CORS & disable CSRF for API
     */
    public function beforeAction($action)
    {
        $this->setCorsHeaders();

        // Disable CSRF for API actions
        $apiActions = ['signup','login','refresh','check'];
        if (in_array($action->id, $apiActions)) {
            Yii::app()->request->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Set global CORS headers
     */
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

        // Handle OPTIONS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            echo json_encode(['success'=>true]);
            Yii::app()->end();
        }
    }

    /**
     * Helper to send JSON responses
     */
    protected function sendJson($data, $status=200)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($status);
        }
        echo json_encode($data, JSON_PRETTY_PRINT);
        Yii::app()->end();
    }

    /**
     * POST /auth/signup
     */
    public function actionSignup()
    {
        if (!Yii::app()->request->isPostRequest) {
            return $this->sendJson(['success'=>false,'message'=>'POST required'], 405);
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw,true);

        if (!is_array($data) || empty($data['email']) || empty($data['password']) || empty($data['name'])) {
            return $this->sendJson(['success'=>false,'message'=>'Name, email, and password required'], 400);
        }

        $user = new User();
        $user->name = trim($data['name']);
        $user->email = trim($data['email']);
        $user->password = $data['password']; // hashed automatically in User::beforeSave
        $user->phone = $data['phone'] ?? null;
        $user->role = $data['role'] ?? 'user';
        $user->status = 1;

        if ($user->save()) {
            return $this->sendJson(['success'=>true,'data'=>$user->getApiData()],201);
        }

        return $this->sendJson([
            'success'=>false,
            'message'=>'Failed to create user',
            'errors'=>$user->getErrors()
        ],422);
    }

    /**
     * POST /auth/login
     */
    public function actionLogin()
    {
        if (!Yii::app()->request->isPostRequest) {
            return $this->sendJson(['success'=>false,'message'=>'POST required'], 405);
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw,true);

        if (!is_array($data) || empty($data['email']) || empty($data['password'])) {
            return $this->sendJson(['success'=>false,'message'=>'Email & password required'], 400);
        }

        $email = trim($data['email']);
        $password = $data['password'];

        $user = User::model()->findByAttributes(['email'=>$email]);

        if (!$user || !$user->validatePassword($password)) {
            return $this->sendJson(['success'=>false,'message'=>'Invalid credentials'],401);
        }

        // Generate JWT token
        $jwt = Yii::app()->jwt;
        $token = $jwt->generateToken($user->userId, $user->email, $user->role, $user->name);

        return $this->sendJson([
            'success'=>true,
            'data'=>[
                'token'=>$token,
                'user'=>$user->getApiData()
            ]
        ],200);
    }
}
