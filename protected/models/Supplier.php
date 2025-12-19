<?php
class Supplier extends BaseModel
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'suppliers';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('name, supplierId', 'required'),
            array('supplierId', 'unique'),
            array('name, email, phone', 'length', 'max' => 255),
            array('phone', 'length', 'max' => 50),
            array('address', 'safe'),
            array('status', 'boolean'),
            array('status', 'default', 'value' => 1),
            array('email', 'email'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
            'purchases' => array(self::HAS_MANY, 'Purchase', 'supplierId'),
        );
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'supplierId' => 'Supplier ID',
            'name' => 'Supplier Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'status' => 'Status',
            'createdAt' => 'Created At',
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
     * Specify ULID fields
     */
    protected function ulidFields()
    {
        return array('supplierId');
    }

    /**
     * Get supplier data for API response
     */
    public function getApiData()
    {
        return array(
            'id' => $this->id,
            'supplierId' => $this->supplierId,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => (bool)$this->status,
            'createdAt' => $this->createdAt,
        );
    }
}