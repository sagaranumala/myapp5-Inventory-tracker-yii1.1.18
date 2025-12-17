<?php
/**
 * SignupForm for user registration with JWT
 */
class SignupForm extends CFormModel
{
    public $name;
    public $email;
    public $password;
    public $password_repeat;
    public $phone;
    public $role = 'user';
    public $agree_terms = false;
    
    private $_user = false;

    public function rules()
    {
        return array(
            // Required fields
            array('name, email, password, password_repeat, agree_terms', 'required', 
                  'message' => '{attribute} cannot be blank.'),
            
            // Safe fields
            array('phone, role', 'safe'),
            
            // Email validation
            array('email', 'email', 'message' => 'Please enter a valid email address.'),
            
            // Check if email already exists
            array('email', 'unique', 'className' => 'User', 'attributeName' => 'email',
                  'message' => 'This email is already registered.'),
            
            // Name validation
            array('name', 'length', 'min' => 2, 'max' => 255,
                  'tooShort' => 'Name must be at least 2 characters.',
                  'tooLong' => 'Name cannot exceed 255 characters.'),
            
            // Password validation
            array('password', 'length', 'min' => 6, 
                  'tooShort' => 'Password must be at least 6 characters.'),
            
            // Password confirmation
            array('password_repeat', 'validatePasswordMatch'),
            
            // Terms agreement
            array('agree_terms', 'boolean'),
            array('agree_terms', 'compare', 'compareValue' => true,
                  'message' => 'You must agree to the terms and conditions.'),
            
            // Phone validation
            array('phone', 'length', 'max' => 20,
                  'tooLong' => 'Phone number cannot exceed 20 characters.'),
        );
    }
    
    public function attributeLabels()
    {
        return array(
            'name' => 'Full Name',
            'email' => 'Email Address',
            'password' => 'Password',
            'password_repeat' => 'Confirm Password',
            'phone' => 'Phone Number',
            'role' => 'Account Type',
            'agree_terms' => 'I agree to the terms and conditions',
        );
    }
    
    /**
     * Custom validation for password confirmation
     */
    public function validatePasswordMatch($attribute, $params)
    {
        if ($this->password !== $this->password_repeat) {
            $this->addError('password_repeat', 'Passwords do not match.');
            $this->addError('password', ''); // Clear any previous error on password field
        }
    }
    
    /**
     * Register new user
     */
    public function register()
    {
        if (!$this->validate()) {
            return false;
        }
        
        $user = new User();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->password = $user->hashPassword($this->password);
        $user->phone = $this->phone;
        $user->role = $this->role;
        $user->userId = 'usr_' . time() . '_' . rand(1000, 9999);
        
        if ($user->save()) {
            $this->_user = $user;
            return true;
        } else {
            $this->addErrors($user->getErrors());
            return false;
        }
    }
    
    /**
     * Get user after registration
     */
    public function getUser()
    {
        return $this->_user;
    }
    
    /**
     * Generate JWT token for new user
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
    
    /**
     * Auto login after registration
     */
    /**
 * Auto login after registration
 */
public function autoLogin()
{
    $user = $this->getUser();
    if (!$user) {
        return false;
    }
    
    // Create a custom identity that bypasses password check for newly registered users
    $identity = new CUserIdentity($user->email, '');
    
    // Manually authenticate (since we just created the user)
    $identity->setState('id', $user->id);
    $identity->setState('name', $user->name);
    $identity->setState('email', $user->email);
    $identity->setState('role', $user->role);
    $identity->setState('userId', $user->userId);
    
    // Set error code to NONE to indicate success
    $identity->errorCode = CUserIdentity::ERROR_NONE;
    
    // Log the user in
    $duration = 3600*24*30; // 30 days
    return Yii::app()->user->login($identity, $duration);
}
}