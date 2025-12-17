<?php
/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'SiteController'.
 */
class LoginForm extends CFormModel
{
    public $email;
    public $password;
    public $rememberMe;

    private $_user;

    /**
     * Declares the validation rules.
     * The rules state that email and password are required,
     * and password needs to be authenticated.
     */
    public function rules()
    {
        return array(
            array('email, password', 'required'),
            array('rememberMe', 'boolean'),
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
     * This is the 'authenticate' validator as declared in rules().
     */
    public function authenticate($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError('password', 'Incorrect email or password.');
            }
        }
    }

    /**
     * Logs in the user using the given email and password in the model.
     * @return boolean whether login is successful
     */
    public function login()
    {
        if ($this->_user === null) {
            $this->_user = User::findByEmail($this->email);
        }
        
        if ($this->_user === null || !$this->_user->validatePassword($this->password)) {
            return false;
        }
        
        // Set session
        $identity = new UserIdentity($this->email, $this->password);
        if ($identity->authenticate()) {
            Yii::app()->user->login($identity, $this->rememberMe ? 3600*24*30 : 0);
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