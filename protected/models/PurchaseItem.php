<?php

/**
 * This is the model class for table "purchaseItems".
 *
 * @property string $purchaseItemId
 * @property string $purchaseId
 * @property string $productId
 * @property integer $quantity
 * @property double $unitCost
 */
class PurchaseItem extends BaseModel
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'purchaseItems';
    }

    /**
     * Specify ULID fields
     */
    protected function ulidFields()
    {
        return array('purchaseItemId');
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            // Required fields
            array('purchaseId, productId, quantity, unitCost', 'required'),
            
            // Numeric validation
            array('quantity', 'numerical', 'integerOnly' => true, 'min' => 1, 'message' => 'Quantity must be an integer greater than 0'),
            array('unitCost', 'numerical', 'min' => 0, 'message' => 'Unit cost must be a positive number'),
            
            // Length validation
            array('purchaseItemId, purchaseId, productId', 'length', 'max' => 26),
            
            // Auto-generate purchaseItemId on insert
            array('purchaseItemId', 'default', 'value' => function() {
                return $this->generateUlid();
            }, 'on' => 'insert'),
            
            // Safe fields for create/update
            array('purchaseId, productId, quantity, unitCost', 'safe', 'on' => 'create, update'),
            
            // Search scenario
            array('purchaseItemId, purchaseId, productId, quantity, unitCost', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
            'purchase' => array(self::BELONGS_TO, 'Purchase', 'purchaseId'),
            'product' => array(self::BELONGS_TO, 'Product', 'productId'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'purchaseItemId' => 'Purchase Item ID',
            'purchaseId' => 'Purchase ID',
            'productId' => 'Product ID',
            'quantity' => 'Quantity',
            'unitCost' => 'Unit Cost',
        );
    }

    /**
     * Before save - auto-generate purchaseItemId if not set
     */
    protected function beforeSave()
    {
        if (parent::beforeSave()) {
            if ($this->isNewRecord && empty($this->purchaseItemId)) {
                $this->purchaseItemId = $this->generateUlid();
            }
            
            return true;
        }
        return false;
    }

    /**
     * Calculate total cost for this item
     */
    public function getTotalCost()
    {
        return $this->quantity * $this->unitCost;
    }

    /**
     * Returns the static model of the specified AR class.
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}