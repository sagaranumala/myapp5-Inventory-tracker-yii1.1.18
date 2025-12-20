<?php
/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data.
 */
class LoginForm extends CFormModel
{
    public $email;
    public $password;
    public $rememberMe;

    private $_user;

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return array(
            array('email, password', 'required'),
            array('password', 'authenticate'),
        );
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return array(
            'email' => 'Email',
            'password' => 'Password',
            'rememberMe' => 'Remember me',
        );
    }

    /**
     * Authenticates the password.
     */
    public function authenticate($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            
            Yii::log("authenticate called for: {$this->email}", CLogger::LEVEL_INFO, 'auth.debug');
            Yii::log("User found: " . ($user ? 'YES' : 'NO'), CLogger::LEVEL_INFO, 'auth.debug');
            
            if (!$user) {
                $this->addError('password', 'Incorrect email or password.');
                Yii::log("User not found: {$this->email}", CLogger::LEVEL_WARNING, 'auth');
            } else if (!$user->validatePassword($this->password)) {
                $this->addError('password', 'Incorrect email or password.');
                Yii::log("Password validation failed for: {$this->email}", CLogger::LEVEL_WARNING, 'auth');
            } else {
                Yii::log("Password validation SUCCESS for: {$this->email}", CLogger::LEVEL_INFO, 'auth.debug');
            }
        }
    }

    /**
     * Logs in the user using the given email and password in the model.
     */
    public function login()
    {
        Yii::log("Login attempt for: {$this->email}", CLogger::LEVEL_INFO, 'auth.debug');
        
        if (!$this->validate()) {
            Yii::log("Login validation failed", CLogger::LEVEL_WARNING, 'auth');
            return false;
        }
        
        $user = $this->getUser();
        if (!$user) {
            Yii::log("User not found: {$this->email}", CLogger::LEVEL_WARNING, 'auth');
            return false;
        }

        Yii::log("Login successful for: {$user->email}", CLogger::LEVEL_INFO, 'auth');
        
        // Create and use UserIdentity for session login
        $identity = new UserIdentity($this->email, $this->password);
        $identity->authenticate();
        
        if ($identity->errorCode === UserIdentity::ERROR_NONE) {
            $duration = $this->rememberMe ? 3600 * 24 * 30 : 0; // 30 days
            Yii::app()->user->login($identity, $duration);
            return true;
        }
        
        return false;
    }

    /**
     * Get user
     */
    public function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByEmail($this->email);
        }
        return $this->_user;
    }

    /**
     * Generate JWT token for API login
     */
    public function generateJwtToken()
    {
        $user = $this->getUser();
        if (!$user) {
            return null;
        }

        /** @var JwtHelper $jwt */
        $jwt = Yii::app()->jwt;
        return $jwt->generateToken(
            $user->userId,
            $user->email,
            $user->role,
            $user->name
        );
    }
}