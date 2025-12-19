<?php
class Category extends BaseModel
{
    /**
     * @return string the associated database table name
     */
    public function tableName() 
    { 
        return 'categories'; 
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('name', 'required'),
            array('name', 'length', 'max' => 255),
            array('description', 'safe'),
            array('isActive', 'boolean'),
            array('isActive', 'default', 'value' => 1),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
{
    return array(
        'products' => array(self::HAS_MANY, 'Product', 'categoryId'),

        // Parent category
        'parentCategory' => array(self::BELONGS_TO, 'Category', 'parentCategoryId'),

        // Subcategories (children)
        'subcategories' => array(self::HAS_MANY, 'Category', 'parentCategoryId'),
    );
}


    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'categoryId' => 'Category ID',
            'name' => 'Category Name',
            'description' => 'Description',
            'isActive' => 'Active',
            'createdAt' => 'Created At',
            'updatedAt' => 'Updated At',
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
        return array('categoryId');
    }

    /**
     * Before save - set timestamps
     */
    protected function beforeSave()
    {
        if (parent::beforeSave()) {
            if ($this->isNewRecord) {
                $this->createdAt = date('Y-m-d H:i:s');
            }
            $this->updatedAt = date('Y-m-d H:i:s');
            return true;
        }
        return false;
    }

    /**
     * Get category data for API response
     */
    public function getApiData()
    {
        return array(
            'id' => $this->id,
            'categoryId' => $this->categoryId,
            'name' => $this->name,
            'description' => $this->description,
            'isActive' => (bool)$this->isActive,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        );
    }
}