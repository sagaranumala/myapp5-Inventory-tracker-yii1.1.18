<?php
/**
 * User model - Simplified version
 */
class User extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'users';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            // Required fields
            array('name, email, password, userId', 'required'),
            
            // Length limits
            array('name, email, password, phone, role, userId', 'length', 'max' => 255),
            
            // Unique constraints
            array('email, userId', 'unique'),
            
            // Email format
            array('email', 'email'),
            
            // Default values
            array('role', 'default', 'value' => 'user'),
            array('createdAt', 'default', 'value' => date('Y-m-d H:i:s'), 'on' => 'insert'),
        );
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'phone' => 'Phone',
            'role' => 'Role',
            'createdAt' => 'Created At',
            'userId' => 'User ID',
        );
    }

    /**
     * Returns the static model
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Finds user by email
     */
    public static function findByEmail($email)
    {
        return self::model()->findByAttributes(array('email' => $email));
    }

    /**
     * Finds user by userId
     */
    public static function findByUserId($userId)
    {
        return self::model()->findByAttributes(array('userId' => $userId));
    }

    /**
     * Validates password
     */
    public function validatePassword($password)
    {
        return CPasswordHelper::verifyPassword($password, $this->password);
    }

    /**
     * Generates password hash
     */
    public function hashPassword($password)
    {
        return CPasswordHelper::hashPassword($password);
    }

    /**
     * Before save - hash password and generate userId
     */
    protected function beforeSave()
    {
        if (parent::beforeSave()) {
            // Hash password if it's new or changed
            if ($this->isNewRecord || $this->password !== $this->getOldAttribute('password')) {
                $this->password = $this->hashPassword($this->password);
            }
            
            // Generate userId for new records
            if ($this->isNewRecord && empty($this->userId)) {
                $this->userId = 'usr_' . time() . '_' . rand(1000, 9999);
            }
            
            return true;
        }
        return false;
    }

    /**
     * Get user data for API response
     */
    public function getApiData()
    {
        return array(
            'id' => $this->id,
            'userId' => $this->userId,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
            'createdAt' => $this->createdAt
        );
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}