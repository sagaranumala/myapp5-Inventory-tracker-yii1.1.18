<?php
class AuthController extends Controller
{

    /**
     * Disable CSRF for API actions
     */
   public function beforeAction($action)
{
    Yii::log("Before action called for: {$action->id}", CLogger::LEVEL_INFO, 'auth');

    // API actions that should NOT require CSRF
    $apiActions = ['login', 'signup', 'refresh', 'check', 'forgotPassword', 'resetPassword'];

    Yii::log(
        "CSRF enabled before: " .
        (Yii::app()->request->enableCsrfValidation ? 'YES' : 'NO'),
        CLogger::LEVEL_INFO,
        'auth'
    );

    if (in_array($action->id, $apiActions)) {
        Yii::app()->request->enableCsrfValidation = false;
        Yii::log("CSRF disabled for action: {$action->id}", CLogger::LEVEL_INFO, 'auth');
    }

    Yii::log(
        "CSRF enabled after: " .
        (Yii::app()->request->enableCsrfValidation ? 'YES' : 'NO'),
        CLogger::LEVEL_INFO,
        'auth'
    );

    return parent::beforeAction($action);
}

    /**
     * WEB: GET /auth/login - Display login form
     * API: POST /auth/login - Handle API login
     */
    public function actionLogin()
    {
        $model = new LoginForm();

        // Handle API/JSON login requests
        if ($this->isApiRequest()) {
            $this->handleApiLogin($model);
            return;
        }

        // Handle WEB form login
        if (isset($_POST['LoginForm'])) {
            $model->attributes = $_POST['LoginForm'];
            if ($model->validate() && $model->login()) {
                $this->redirect(array('site/dashboard'));
            }
        }

        // Display the web login form
        $this->render('login', array('model' => $model));
    }

    /**
     * WEB: GET /auth/signup - Display signup form
     * API: POST /auth/signup - Handle API signup
     */
    public function actionSignup()
    {
        $model = new SignupForm();

        // Handle API/JSON signup requests
        if ($this->isApiRequest()) {
            $this->handleApiSignup($model);
            return;
        }

        // Handle WEB form signup
        if (isset($_POST['SignupForm'])) {
            $model->attributes = $_POST['SignupForm'];
            if ($model->validate() && $model->register()) {
                // Send welcome email for web signup
                $user = $model->getUser();
                $this->sendWelcomeEmail($user);
                
                if ($model->autoLogin()) {
                    $this->redirect(array('site/dashboard'));
                } else {
                    Yii::app()->user->setFlash('success', 'Registration successful! Please check your email for confirmation.');
                    $this->redirect(array('auth/login'));
                }
            }
        }

        // Display the web signup form
        $this->render('signup', array('model' => $model));
    }

    /**
     * Handle API login
     */
    private function handleApiLogin($model)
    {
        if (!Yii::app()->request->isPostRequest) {
            $this->sendJson([
                'success' => false,
                'message' => 'POST request required'
            ], 405);
        }

        // Debug: Log request
        Yii::log('API Login Request Started', CLogger::LEVEL_INFO, 'api.login');
        
        $data = $this->getJsonInput();
        
        // Debug: Log received data
        Yii::log('Received JSON data: ' . print_r($data, true), CLogger::LEVEL_INFO, 'api.login');

        if (empty($data['email']) || empty($data['password'])) {
            Yii::log('Missing email or password', CLogger::LEVEL_WARNING, 'api.login');
            $this->sendJson([
                'success' => false,
                'message' => 'Email and password required'
            ], 400);
        }

        $model->email = $data['email'];
        $model->password = $data['password'];
        
        // Debug: Log what we're trying to validate
        Yii::log("Attempting login for email: {$data['email']}", CLogger::LEVEL_INFO, 'api.login');

        if ($model->validate()) {
            Yii::log('Model validation passed', CLogger::LEVEL_INFO, 'api.login');
            
            if ($model->login()) {
                Yii::log('Login successful', CLogger::LEVEL_INFO, 'api.login');
                
                try {
                    // Generate JWT token using JwtHelper component
                    $jwt = Yii::app()->jwt;
                    $user = $model->getUser();
                    
                    $token = $jwt->generateToken(
                        $user->userId,
                        $user->email,
                        $user->role,
                        $user->name
                    );
                    
                    $this->sendJson([
                        'success' => true,
                        'message' => 'Login successful',
                        'data' => [
                            'token' => $token,
                            'user'  => $user->getApiData(),
                        ]
                    ]);
                    
                } catch (Exception $e) {
                    Yii::log('Error generating token or user data: ' . $e->getMessage(), CLogger::LEVEL_ERROR, 'api.login');
                    $this->sendJson([
                        'success' => false,
                        'message' => 'Login process error',
                        'debug' => YII_DEBUG ? $e->getMessage() : null
                    ], 500);
                }
            } else {
                Yii::log('Login failed - invalid credentials', CLogger::LEVEL_WARNING, 'api.login');
            }
        } else {
            // Debug: Log validation errors
            Yii::log('Model validation failed: ' . print_r($model->getErrors(), true), CLogger::LEVEL_WARNING, 'api.login');
        }

        $this->sendJson([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    /**
     * Handle API signup with welcome email
     */
    private function handleApiSignup($model)
    {
        if (!Yii::app()->request->isPostRequest) {
            $this->sendJson([
                'success' => false,
                'message' => 'POST request required'
            ], 405);
        }

        $data = $this->getJsonInput();
        $model->attributes = $data;

        if ($model->validate() && $model->register()) {
            $user = $model->getUser();
            
            // Send welcome email
            $emailSent = $this->sendWelcomeEmail($user);
            
            // Generate JWT token
            $jwt = Yii::app()->jwt;
            $token = $jwt->generateToken(
                $user->userId,
                $user->email,
                $user->role,
                $user->name
            );
            
            // Prepare response
            $response = [
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'token' => $token,
                    'user'  => $user->getApiData(),
                ]
            ];
            
            // Add email status to response
            if ($emailSent) {
                $response['message'] = 'Registration successful. Welcome email sent!';
                $response['data']['email_sent'] = true;
            } else {
                $response['message'] = 'Registration successful but welcome email failed to send.';
                $response['data']['email_sent'] = false;
                $response['data']['email_error'] = YII_DEBUG ? 'Email sending failed' : null;
            }

            $this->sendJson($response);
        }

        $this->sendJson([
            'success' => false,
            'message' => 'Registration failed',
            'errors' => $model->getErrors()
        ], 400);
    }

    /**
     * Send welcome email to new user
     */
    private function sendWelcomeEmail($user)
    {
        try {
            // Check if email component is configured
            if (!Yii::app()->hasComponent('email')) {
                Yii::log('Email component not configured', 'warning', 'application.auth');
                return false;
            }
            
            // Generate login link
            $loginLink = Yii::app()->createAbsoluteUrl('auth/login');
            
            // Send welcome email using EmailComponent
            $success = Yii::app()->email->sendWelcomeEmail($user, $loginLink);
            
            if ($success) {
                Yii::log("Welcome email sent to: {$user->email}", 'info', 'application.auth');
                return true;
            } else {
                Yii::log("Failed to send welcome email to: {$user->email}", 'warning', 'application.auth');
                return false;
            }
            
        } catch (Exception $e) {
            Yii::log("Welcome email error for {$user->email}: " . $e->getMessage(), 'error', 'application.auth');
            return false;
        }
    }

    /**
     * Logout action (works for both web and API)
     */
    public function actionLogout()
    {
        Yii::app()->user->logout();
        
        if ($this->isApiRequest()) {
            $this->sendJson([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } else {
            $this->redirect(Yii::app()->homeUrl);
        }
    }

    /**
     * Get user profile (protected API endpoint)
     */
    public function actionProfile()
    {
        if (Yii::app()->user->isGuest) {
            $this->sendJson([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = Yii::app()->user->getModel();
        if (!$user) {
            $this->sendJson([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        $this->sendJson([
            'success' => true,
            'data' => $user->getApiData()
        ]);
    }

    /**
     * Refresh token (API only)
     */
    public function actionRefresh()
    {
        if (!$this->isApiRequest()) {
            $this->redirect(Yii::app()->homeUrl);
            return;
        }

        $user = Yii::app()->user->getModel();
        if ($user) {
            $jwt = Yii::app()->jwt;
            $newToken = $jwt->generateToken(
                $user->userId,
                $user->email,
                $user->role,
                $user->name
            );
            
            $this->sendJson([
                'success' => true,
                'data' => [
                    'token' => $newToken,
                    'user'  => $user->getApiData(),
                ]
            ]);
        } else {
            $this->sendJson([
                'success' => false,
                'message' => 'Cannot refresh token'
            ], 401);
        }
    }

    /**
     * Check if user is authenticated (API endpoint)
     */
    public function actionCheck()
    {
        if ($this->isApiRequest()) {
            $userData = null;
            if (!Yii::app()->user->isGuest) {
                $user = Yii::app()->user->getModel();
                $userData = $user ? $user->getApiData() : null;
            }
            
            $this->sendJson([
                'success' => true,
                'authenticated' => !Yii::app()->user->isGuest,
                'user' => $userData
            ]);
        } else {
            $this->redirect(Yii::app()->homeUrl);
        }
    }

    /**
     * Forgot password action (send reset email)
     */
    public function actionForgotPassword()
    {
        if ($this->isApiRequest()) {
            $this->handleApiForgotPassword();
            return;
        }

        $model = new ForgotPasswordForm();
        
        if (isset($_POST['ForgotPasswordForm'])) {
            $model->attributes = $_POST['ForgotPasswordForm'];
            if ($model->validate()) {
                if ($this->sendPasswordResetEmail($model->email)) {
                    Yii::app()->user->setFlash('success', 'Password reset link sent to your email.');
                    $this->refresh();
                } else {
                    Yii::app()->user->setFlash('error', 'Failed to send reset email. Please try again.');
                }
            }
        }

        $this->render('forgotPassword', array('model' => $model));
    }

    /**
     * Handle API forgot password
     */
    private function handleApiForgotPassword()
    {
        if (!Yii::app()->request->isPostRequest) {
            $this->sendJson([
                'success' => false,
                'message' => 'POST request required'
            ], 405);
        }

        $data = $this->getJsonInput();
        
        if (empty($data['email'])) {
            $this->sendJson([
                'success' => false,
                'message' => 'Email is required'
            ], 400);
        }

        $email = $data['email'];
        $user = User::model()->findByEmail($email);
        
        if ($user) {
            // Generate reset token
            $user->reset_token = bin2hex(random_bytes(32));
            $user->reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            if ($user->save()) {
                $resetLink = Yii::app()->createAbsoluteUrl('auth/resetPassword', [
                    'token' => $user->reset_token
                ]);
                
                // Send password reset email
                $emailSent = false;
                if (Yii::app()->hasComponent('email')) {
                    $emailSent = Yii::app()->email->sendPasswordReset($user, $resetLink);
                }
                
                if ($emailSent) {
                    $this->sendJson([
                        'success' => true,
                        'message' => 'Password reset link sent to your email'
                    ]);
                } else {
                    $this->sendJson([
                        'success' => false,
                        'message' => 'Failed to send reset email'
                    ], 500);
                }
            } else {
                $this->sendJson([
                    'success' => false,
                    'message' => 'Failed to generate reset token'
                ], 500);
            }
        } else {
            // For security, don't reveal if email exists or not
            $this->sendJson([
                'success' => true,
                'message' => 'If an account exists with this email, a reset link will be sent'
            ]);
        }
    }

    /**
     * Reset password action
     */
    public function actionResetPassword($token = null)
    {
        if (empty($token)) {
            throw new CHttpException(400, 'Reset token is required');
        }

        $user = User::model()->findByAttributes([
            'reset_token' => $token
        ]);

        if (!$user) {
            throw new CHttpException(400, 'Invalid reset token');
        }

        // Check if token is expired
        if ($user->reset_token_expires && strtotime($user->reset_token_expires) < time()) {
            throw new CHttpException(400, 'Reset token has expired');
        }

        $model = new ResetPasswordForm();
        
        if ($this->isApiRequest()) {
            $this->handleApiResetPassword($user, $model);
            return;
        }

        if (isset($_POST['ResetPasswordForm'])) {
            $model->attributes = $_POST['ResetPasswordForm'];
            if ($model->validate()) {
                $user->password = $user->hashPassword($model->new_password);
                $user->reset_token = null;
                $user->reset_token_expires = null;
                
                if ($user->save()) {
                    Yii::app()->user->setFlash('success', 'Password reset successfully. Please login with your new password.');
                    $this->redirect(array('auth/login'));
                }
            }
        }

        $this->render('resetPassword', array(
            'model' => $model,
            'token' => $token
        ));
    }

    /**
     * Handle API reset password
     */
    private function handleApiResetPassword($user, $model)
    {
        if (!Yii::app()->request->isPostRequest) {
            $this->sendJson([
                'success' => false,
                'message' => 'POST request required'
            ], 405);
        }

        $data = $this->getJsonInput();
        $model->attributes = $data;

        if ($model->validate()) {
            $user->password = $user->hashPassword($model->new_password);
            $user->reset_token = null;
            $user->reset_token_expires = null;
            
            if ($user->save()) {
                $this->sendJson([
                    'success' => true,
                    'message' => 'Password reset successfully'
                ]);
            } else {
                $this->sendJson([
                    'success' => false,
                    'message' => 'Failed to save new password'
                ], 500);
            }
        } else {
            $this->sendJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $model->getErrors()
            ], 400);
        }
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email)
    {
        $user = User::model()->findByEmail($email);
        if (!$user) {
            return false;
        }

        // Generate reset token
        $user->reset_token = bin2hex(random_bytes(32));
        $user->reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        if (!$user->save()) {
            return false;
        }

        $resetLink = Yii::app()->createAbsoluteUrl('auth/resetPassword', [
            'token' => $user->reset_token
        ]);
        
        // Send email using EmailComponent
        if (Yii::app()->hasComponent('email')) {
            return Yii::app()->email->sendPasswordReset($user, $resetLink);
        }
        
        return false;
    }

    /**
     * Verify email (email verification endpoint)
     */
    public function actionVerifyEmail($token = null)
    {
        if (empty($token)) {
            if ($this->isApiRequest()) {
                $this->sendJson([
                    'success' => false,
                    'message' => 'Verification token required'
                ], 400);
            } else {
                throw new CHttpException(400, 'Verification token required');
            }
        }

        $user = User::model()->findByAttributes(['email_verification_token' => $token]);
        
        if (!$user) {
            if ($this->isApiRequest()) {
                $this->sendJson([
                    'success' => false,
                    'message' => 'Invalid verification token'
                ], 400);
            } else {
                throw new CHttpException(400, 'Invalid verification token');
            }
        }

        // Mark email as verified
        $user->email_verified = 1;
        $user->email_verification_token = null;
        $user->email_verified_at = date('Y-m-d H:i:s');
        
        if ($user->save()) {
            if ($this->isApiRequest()) {
                $this->sendJson([
                    'success' => true,
                    'message' => 'Email verified successfully'
                ]);
            } else {
                Yii::app()->user->setFlash('success', 'Email verified successfully! You can now login.');
                $this->redirect(array('auth/login'));
            }
        } else {
            if ($this->isApiRequest()) {
                $this->sendJson([
                    'success' => false,
                    'message' => 'Failed to verify email'
                ], 500);
            } else {
                Yii::app()->user->setFlash('error', 'Failed to verify email. Please try again.');
                $this->redirect(array('auth/login'));
            }
        }
    }

    /**
     * Get JSON input from request
     */
    private function getJsonInput()
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        // Fallback to $_POST if JSON decoding fails
        return $data ?: $_POST;
    }

    /**
     * Override to indicate which actions require authentication
     */
    protected function requireAuthForApi($action)
    {
        // Actions that require authentication
        $protectedActions = ['profile', 'refresh', 'logout'];
        return in_array($action->id, $protectedActions);
    }
}