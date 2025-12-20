<?php
class AuthController extends BaseApiController
{
    /**
     * Disable CSRF for API actions
     */
    public function beforeAction($action)
    {
        $apiActions = ['signup','login','refresh','check','profile','logout'];
        if (in_array($action->id, $apiActions)) {
            Yii::app()->request->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * User login
     */
    public function actionLogin()
    {
        if (!Yii::app()->request->isPostRequest) {
            Yii::app()->jwt->sendResponse(false, 'Method not allowed. Use POST', null, 405);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::app()->jwt->sendResponse(false, 'Invalid JSON format', null, 400);
        }

        if (empty($data['email']) || empty($data['password'])) {
            Yii::app()->jwt->sendResponse(false, 'Email and password are required', null, 400);
        }

        $user = User::model()->findByAttributes(['email' => trim($data['email'])]);
        if (!$user || !$user->validatePassword($data['password'])) {
            Yii::app()->jwt->sendResponse(false, 'Invalid email or password', null, 401);
        }

        if ($user->status != 1) {
            Yii::app()->jwt->sendResponse(false, 'Account is not active', null, 403);
        }

        $token = Yii::app()->jwt->generateToken(
            $user->userId,
            $user->email,
            $user->role,
            $user->name
        );

        $this->logLogin($user);

        Yii::app()->jwt->sendResponse(true, 'Login successful', [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Yii::app()->jwt->getExpiryTime(),
            'user' => $this->getSafeUserData($user)
        ], 200);
    }

    /**
     * User registration
     */
    public function actionSignup()
    {
        if (!Yii::app()->request->isPostRequest) {
            Yii::app()->jwt->sendResponse(false, 'Method not allowed. Use POST', null, 405);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::app()->jwt->sendResponse(false, 'Invalid JSON format', null, 400);
        }

        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            Yii::app()->jwt->sendResponse(false, 'Name, email, and password are required', null, 400);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Yii::app()->jwt->sendResponse(false, 'Invalid email format', null, 400);
        }

        if (strlen($data['password']) < 8) {
            Yii::app()->jwt->sendResponse(false, 'Password must be at least 8 characters', null, 400);
        }

        if (User::model()->findByAttributes(['email' => trim($data['email'])])) {
            Yii::app()->jwt->sendResponse(false, 'Email already registered', null, 409);
        }

        $user = new User();
        $user->name = trim($data['name']);
        $user->email = strtolower(trim($data['email']));
        $user->password = $data['password']; // assume hashing in setter
        $user->role = 'user';
        $user->status = 1;
        // $user->created_at = date('Y-m-d H:i:s');

        if (!$user->save()) {
            Yii::app()->jwt->sendResponse(false, 'Registration failed', $user->getErrors(), 422);
        }

        $token = Yii::app()->jwt->generateToken(
            $user->userId,
            $user->email,
            $user->role,
            $user->name
        );

        Yii::app()->jwt->sendResponse(true, 'Registration successful', [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Yii::app()->jwt->getExpiryTime(),
            'user' => $this->getSafeUserData($user)
        ], 201);
    }

    /**
     * Profile
     */
    public function actionProfile()
    {
        $currentUser = Yii::app()->jwt->getCurrentUser();
        if (!$currentUser) {
            Yii::app()->jwt->sendResponse(false, 'Unauthorized', null, 401);
        }
        Yii::app()->jwt->sendResponse(true, 'Profile fetched', $currentUser);
    }

    /**
     * Logout
     */
    public function actionLogout()
    {
        Yii::app()->jwt->sendResponse(true, 'Logged out successfully');
    }

    private function getSafeUserData($user)
    {
        return [
            'id' => $user->userId,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            // 'created_at' => $user->created_at
        ];
    }

    private function logLogin($user)
    {
        // Implement login logging if needed
    }
}
