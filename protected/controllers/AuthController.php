<?php

class AuthController extends BaseApiController
{
    /**
     * Disable CSRF for API actions
     */
    public function beforeAction($action)
    {
        $apiActions = [
            'signup', 'login', 'refresh', 'check', 'profile',
            'logout', 'forgotPassword', 'resetPassword', 'updatePassword'
        ];
        if (in_array($action->id, $apiActions)) {
            Yii::app()->request->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * POST /auth/login
     * Params: email, password
     */
    public function actionLogin()
    {
        if (!Yii::app()->request->isPostRequest) {
            return $this->sendJson(['success' => false, 'message' => 'POST required'], 405);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data) || empty($data['email']) || empty($data['password'])) {
            return $this->sendJson(['success' => false, 'message' => 'Email & password required'], 400);
        }

        $email = trim($data['email']);
        $password = $data['password'];

        $user = User::model()->findByAttributes(['email' => $email]);
        if (!$user || !$user->validatePassword($password)) {
            return $this->sendJson(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        // Generate JWT token
        $jwt = Yii::app()->jwt;
        $token = $jwt->generateToken([
            'iss' => 'your-app',
            'data' => [
                'userId' => $user->userId,
                'email'  => $user->email,
                'role'   => $user->role,
                'name'   => $user->name,
            ]
        ]);

        return $this->sendJson([
            'success' => true,
            'data' => [
                'token' => $token,
                'user'  => $this->getSafeUserData($user)
            ]
        ], 200);
    }

    /**
     * POST /auth/signup
     */
    public function actionSignup()
    {
        if (!Yii::app()->request->isPostRequest) {
            Yii::app()->jwt->sendResponse(false, 'POST required', null, 405);
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

        if (strlen($data['password']) < 6) {
            Yii::app()->jwt->sendResponse(false, 'Password must be at least 6 characters', null, 400);
        }

        if (User::model()->findByAttributes(['email' => trim($data['email'])])) {
            Yii::app()->jwt->sendResponse(false, 'Email already registered', null, 409);
        }

        $user = new User();
        $user->name = trim($data['name']);
        $user->email = strtolower(trim($data['email']));
        $user->password = $data['password']; // assumes hashing in setter
        $user->role = 'user';
        $user->status = 1;

        if (!$user->save()) {
            Yii::app()->jwt->sendResponse(false, 'Registration failed', $user->getErrors(), 422);
        }

        $jwt = Yii::app()->jwt;
        $token = $jwt->generateToken([
            'iss' => 'your-app',
            'data' => [
                'userId' => $user->userId,
                'email'  => $user->email,
                'role'   => $user->role,
                'name'   => $user->name,
            ]
        ]);

        Yii::app()->jwt->sendResponse(true, 'Registration successful', [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Yii::app()->jwt->getExpiryTime(),
            'user' => $this->getSafeUserData($user)
        ], 201);
    }

    /**
     * GET /auth/profile
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
     * POST /auth/logout
     */
    public function actionLogout()
    {
        Yii::app()->jwt->sendResponse(true, 'Logged out successfully');
    }

    /**
     * POST /auth/updatePassword
     */
    public function actionUpdatePassword()
    {
        if (!Yii::app()->request->isPostRequest) {
            $this->sendResponse(false, 'POST required', null, 405);
        }

        $currentUser = Yii::app()->jwt->getCurrentUser();
        if (!$currentUser) {
            $this->sendResponse(false, 'Authentication required', null, 401);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendResponse(false, 'Invalid JSON format', null, 400);
        }

        $currentPassword = trim($data['currentPassword'] ?? '');
        $newPassword = trim($data['newPassword'] ?? '');

        if (!$currentPassword || !$newPassword) {
            $this->sendResponse(false, 'currentPassword and newPassword are required', null, 400);
        }

        if (strlen($newPassword) < 6) {
            $this->sendResponse(false, 'New password must be at least 6 characters', null, 400);
        }

       $user = User::model()->findByAttributes(['userId' => $currentUser['userId']]);
        if (!$user) {
            $this->sendResponse(false, 'User not found', ['email' => $currentUser['email']], 404);
        }

        $userInfo = [
            'userId' => $user->userId,
            'name' => $user->name,
            'email' => $user->email
        ];

        if (!$user->validatePassword($currentPassword)) {
            $this->sendResponse(false, 'Current password is incorrect', ['user' => $userInfo], 403);
        }

        if ($user->validatePassword($newPassword)) {
            $this->sendResponse(false, 'New password must be different', ['user' => $userInfo], 400);
        }

        $user->password = $newPassword;
        if (!$user->save()) {
            $this->sendResponse(false, 'Failed to update password', [
                'user' => $userInfo,
                'errors' => $user->getErrors()
            ], 422);
        }

        $this->sendResponse(true, 'Password updated successfully', [
            'user' => array_merge($userInfo, ['updated_at' => date('Y-m-d H:i:s')])
        ], 200);
    }

    /**
     * POST /auth/forgotPassword
     */
    /**
 * POST /auth/forgotPassword
 * Params: email
 */
public function actionForgotPassword()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $email = strtolower(trim($data['email'] ?? ''));

    if (!$email) {
        Yii::app()->jwt->sendResponse(false, 'Email is required', null, 400);
    }

    $user = User::model()->findByAttributes(['email' => $email]);
    if (!$user) {
        Yii::app()->jwt->sendResponse(false, 'Email not found', null, 404);
    }

    // Generate JWT reset token (expires in 1 hour)
    $resetToken = Yii::app()->jwt->generateToken([
        'iss'  => 'your-app',
        'exp'  => time() + 3600, // 1 hour expiry
        'data' => [
            'userId' => $user->userId,
            'email'  => $user->email
        ]
    ]);

    // TODO: Send email with reset link containing $resetToken
    // Example: https://yourdomain.com/reset-password?token=$resetToken

    Yii::app()->jwt->sendResponse(true, 'Password reset email sent', [
        'reset_token' => $resetToken
    ]);
}

    /**
     * POST /auth/resetPassword
     */
    /**
 * POST /auth/resetPassword
 * Params: token, new_password
 */
public function actionResetPassword()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $token       = $data['token'] ?? '';
    $newPassword = trim($data['new_password'] ?? '');

    if (!$token || !$newPassword) {
        Yii::app()->jwt->sendResponse(false, 'Token and new password are required', null, 400);
    }

    // Decode token
    $decoded = Yii::app()->jwt->validateToken($token);
    if (!$decoded) {
        Yii::app()->jwt->sendResponse(false, 'Invalid or expired token', null, 400);
    }

    // Validate new password length
    if (strlen($newPassword) < 6) {
        Yii::app()->jwt->sendResponse(false, 'New password must be at least 6 characters', null, 400);
    }

    // Fetch user from decoded token
    $user = User::model()->findByPk($decoded['userId'] ?? '');
    if (!$user) {
        Yii::app()->jwt->sendResponse(false, 'User not found', null, 404);
    }

    // Update password
    $user->password = $newPassword; // assuming hashing in setter
    if (!$user->save()) {
        Yii::app()->jwt->sendResponse(false, 'Failed to reset password', $user->getErrors(), 422);
    }

    Yii::app()->jwt->sendResponse(true, 'Password reset successfully', [
        'user' => [
            'userId' => $user->userId,
            'email'  => $user->email,
            'name'   => $user->name
        ]
    ]);
}


    /**
     * Helper: get safe user data
     */
    private function getSafeUserData($user)
    {
        return [
            'userId' => $user->userId,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role
        ];
    }

    /**
     * Helper: log login (optional)
     */
    private function logLogin($user)
    {
        // Implement login logging if needed
    }

    /**
     * Helper: send JSON response (for internal calls)
     */
    protected function sendJson($response, $httpCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($httpCode);
        echo json_encode($response);
        Yii::app()->end();
    }
}
