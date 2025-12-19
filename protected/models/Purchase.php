<?php

/**
 * This is the model class for table "purchases".
 *
 * @property integer $id
 * @property string $purchaseId
 * @property string $supplierId
 * @property string $warehouseId
 * @property double $totalAmount
 * @property string $status
 * @property string $createdBy
 * @property string $createdAt
 * 
 * @property Supplier $supplier
 * @property Warehouse $warehouse
 * @property User $creator
 * @property PurchaseItem[] $items
 */
class Purchase extends BaseModel
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_ORDERED = 'ordered';
    const STATUS_PARTIAL = 'partial';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_CLOSED = 'closed';

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'purchases';
    }

    /**
     * Specify ULID fields
     */
    protected function ulidFields()
    {
        return array('purchaseId');
    }

    /**
     * @return array validation rules for model attributes.
     */
    /**
 * @return array validation rules for model attributes.
 */
public function rules()
{
    return array(
        // Required fields with DIFFERENT messages
        array('supplierId', 'required', 'message' => 'Supplier ID is required.'),
        array('warehouseId', 'required', 'message' => 'Warehouse ID is required.'),
        
        // Numeric validation
        array('totalAmount', 'numerical', 'message' => 'Total Amount must be a number.'),
        
        // Length validation
        array('purchaseId, supplierId, warehouseId, createdBy', 'length', 'max'=>26),
        array('status', 'length', 'max'=>50),
        
        // Safe fields
        array('createdAt', 'safe'),
        
        // Auto-generate purchaseId on insert
        array('purchaseId', 'default', 'value' => function() {
            return $this->generateUlid();
        }, 'on' => 'insert'),
        
        // Make existing fields safe for create/update
        array('supplierId, warehouseId, totalAmount, status, createdBy', 'safe', 'on' => 'create, update'),
        
        // Search scenario
        array('id, purchaseId, supplierId, warehouseId, totalAmount, status, createdBy, createdAt', 'safe', 'on'=>'search'),
    );
}

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
            'supplier' => array(self::BELONGS_TO, 'Supplier', 'supplierId'),
            'warehouse' => array(self::BELONGS_TO, 'Warehouse', 'warehouseId'),
            'creator' => array(self::BELONGS_TO, 'User', 'createdBy'),
            'items' => array(self::HAS_MANY, 'PurchaseItem', 'purchaseId'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'purchaseId' => 'Purchase ID',
            'supplierId' => 'Supplier ID',  // Changed from 'Supplier'
            'warehouseId' => 'Warehouse ID', // Changed from 'Warehouse'
            'totalAmount' => 'Total Amount',
            'status' => 'Status',
            'createdBy' => 'Created By',
            'createdAt' => 'Created At',
            // REMOVE: 'notes', 'expectedDelivery', 'updatedAt' - they don't exist in DB
        );
    }

    /**
     * Behaviors - Remove CTimestampBehavior since updatedAt doesn't exist
     */
    public function behaviors()
    {
        return array(
            // Remove CTimestampBehavior since updatedAt column doesn't exist
        );
    }

    /**
     * Before save - auto-generate purchaseId if not set
     */
    protected function beforeSave()
    {
        if (parent::beforeSave()) {
            if ($this->isNewRecord && empty($this->purchaseId)) {
                $this->purchaseId = $this->generateUlid();
            }
            
            return true;
        }
        return false;
    }

    /**
     * After save - calculate total amount from items
     */
    protected function afterSave()
    {
        parent::afterSave();
        
        // Calculate total amount from items if we have items
        if ($this->items && count($this->items) > 0) {
            $total = 0;
            foreach ($this->items as $item) {
                // Check if item has unitCost or unitPrice field
                $unitPrice = property_exists($item, 'unitCost') ? $item->unitCost : 
                            (property_exists($item, 'unitPrice') ? $item->unitPrice : 0);
                $total += $item->quantity * $unitPrice;
            }
            
            // Only update if total is different
            if ($this->totalAmount != $total) {
                Yii::app()->db->createCommand()
                    ->update('purchases', 
                        array('totalAmount' => $total),
                        'id = :id',
                        array(':id' => $this->id)
                    );
                
                // Refresh this object
                $this->refresh();
            }
        }
    }

    /**
     * Get status options
     */
    public static function getStatusOptions()
    {
        return array(
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_ORDERED => 'Ordered',
            self::STATUS_PARTIAL => 'Partially Received',
            self::STATUS_RECEIVED => 'Fully Received',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_CLOSED => 'Closed',
        );
    }

    /**
     * Get status label for this purchase
     */
    public function getStatusLabel()
    {
        $options = self::getStatusOptions();
        return isset($options[$this->status]) ? $options[$this->status] : $this->status;
    }

    /**
     * Get total items count
     */
    public function getItemsCount()
    {
        return count($this->items);
    }

    /**
     * Get total quantity
     */
    public function getTotalQuantity()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->quantity;
        }
        return $total;
    }

    /**
     * Get API data for response
     */
    public function getApiData()
    {
        $itemsData = array();
        if ($this->items) {
            foreach ($this->items as $item) {
                $itemsData[] = array(
                    'id' => $item->id,
                    'productId' => $item->productId,
                    'quantity' => $item->quantity,
                    'unitPrice' => property_exists($item, 'unitCost') ? $item->unitCost : 
                                  (property_exists($item, 'unitPrice') ? $item->unitPrice : 0),
                    'totalPrice' => $item->quantity * (property_exists($item, 'unitCost') ? $item->unitCost : 
                                     (property_exists($item, 'unitPrice') ? $item->unitPrice : 0))
                );
            }
        }
        
        return array(
            'id' => $this->id,
            'purchaseId' => $this->purchaseId,
            'supplierId' => $this->supplierId,
            'warehouseId' => $this->warehouseId,
            'totalAmount' => (float)$this->totalAmount,
            'status' => $this->status,
            'createdBy' => $this->createdBy,
            'createdAt' => $this->createdAt,
            'items' => $itemsData,
            'supplier' => $this->supplier ? $this->supplier->attributes : null,
            'warehouse' => $this->warehouse ? $this->warehouse->attributes : null
        );
    }

    /**
     * Returns the static model of the specified AR class.
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}