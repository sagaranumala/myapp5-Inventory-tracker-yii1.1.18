<?php
class Product extends BaseModel
{
    /**
     * @return string the associated database table name
     */
    public function tableName() 
    { 
        return 'products'; 
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('sku, name, categoryId, unitPrice, costPrice', 'required'),
            array('unitPrice, costPrice', 'numerical'),
            array('sku', 'unique'),
            array('isActive', 'boolean'),
            array('sku, name', 'length', 'max' => 255),
            array('description', 'safe'),
            array('isActive', 'default', 'value' => 1),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
            'category' => array(self::BELONGS_TO, 'Category', 'categoryId'),
            'warehouseStocks' => array(self::HAS_MANY, 'WarehouseStock', 'productId'),
        );
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'productId' => 'Product ID',
            'sku' => 'SKU',
            'name' => 'Product Name',
            'description' => 'Description',
            'categoryId' => 'Category',
            'unitPrice' => 'Unit Price',
            'costPrice' => 'Cost Price',
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
        return array('productId');
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
     * Get product data for API response
     */
    public function getApiData()
    {
        return array(
            'id' => $this->id,
            'productId' => $this->productId,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'unitPrice' => (float)$this->unitPrice,
            'costPrice' => (float)$this->costPrice,
            'isActive' => (bool)$this->isActive,
            'category' => $this->category ? $this->category->getApiData() : null,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        );
    }
}