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
            'supplierId' => 'Supplier ID',
            'warehouseId' => 'Warehouse ID',
            'totalAmount' => 'Total Amount',
            'status' => 'Status',
            'createdBy' => 'Created By',
            'createdAt' => 'Created At',
        );
    }

    /**
     * Behaviors
     */
    public function behaviors()
    {
        return array();
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
     * Get supplier name by supplierId
     */
    /**
 * Get supplier name by supplierId
 */
public function getSupplierName()
{
    if (!$this->supplierId) {
        return null;
    }
    
    if ($this->supplier) {
        return isset($this->supplier->name) ? $this->supplier->name : null;
    }
    
    // If relation not loaded, query directly
    $supplier = Supplier::model()->findByPk($this->supplierId);
    if ($supplier) {
        return isset($supplier->name) ? $supplier->name : null;
    }
    
    return null;
}

/**
 * Get warehouse name by warehouseId
 */
public function getWarehouseName()
{
    if (!$this->warehouseId) {
        return null;
    }
    
    if ($this->warehouse) {
        return isset($this->warehouse->name) ? $this->warehouse->name : null;
    }
    
    // If relation not loaded, query directly
    $warehouse = Warehouse::model()->findByPk($this->warehouseId);
    if ($warehouse) {
        return isset($warehouse->name) ? $warehouse->name : null;
    }
    
    return null;
}
    /**
     * Named scope to include supplier and warehouse names
     */
    public function withNames()
    {
        $this->with(array(
            'supplier' => array('select' => 'supplierId, name, supplierName'),
            'warehouse' => array('select' => 'warehouseId, name, warehouseName')
        ));
        return $this;
    }

    /**
     * Get API data for response
     */
    /**
 * Get API data for response
 */
public function getApiData()
{
    $itemsData = array();
    if ($this->items) {
        foreach ($this->items as $item) {
            // Check for unitCost or unitPrice field
            $unitPrice = 0;
            if (property_exists($item, 'unitCost')) {
                $unitPrice = $item->unitCost;
            } elseif (property_exists($item, 'unitPrice')) {
                $unitPrice = $item->unitPrice;
            } elseif (isset($item->unitCost)) {
                $unitPrice = $item->unitCost;
            } elseif (isset($item->unitPrice)) {
                $unitPrice = $item->unitPrice;
            }
            
            $itemsData[] = array(
                'id' => $item->id,
                'purchaseItemId' => isset($item->purchaseItemId) ? $item->purchaseItemId : null,
                'productId' => $item->productId,
                'quantity' => (int)$item->quantity,
                'unitPrice' => (float)$unitPrice,
                'totalPrice' => (float)($item->quantity * $unitPrice)
            );
        }
    }
    
    // Get supplier data
    $supplierName = null;
    $supplierData = null;
    if ($this->supplier) {
        $supplierName = $this->supplier->name;
        $supplierData = array(
            'supplierId' => $this->supplier->supplierId,
            'name' => $this->supplier->name,
            'email' => isset($this->supplier->email) ? $this->supplier->email : null,
            'phone' => isset($this->supplier->phone) ? $this->supplier->phone : null,
            'address' => isset($this->supplier->address) ? $this->supplier->address : null
        );
    } else {
        // Fallback to getSupplierName()
        $supplierName = $this->getSupplierName();
    }
    
    // Get warehouse data
    $warehouseName = null;
    $warehouseData = null;
    if ($this->warehouse) {
        $warehouseName = $this->warehouse->name;
        $warehouseData = array(
            'warehouseId' => $this->warehouse->warehouseId,
            'name' => $this->warehouse->name,
            'location' => isset($this->warehouse->location) ? $this->warehouse->location : null,
            'address' => isset($this->warehouse->address) ? $this->warehouse->address : null
        );
    } else {
        // Fallback to getWarehouseName()
        $warehouseName = $this->getWarehouseName();
    }
    
    // Get creator data
    $creatorData = null;
    if ($this->creator) {
        $creatorData = array('userId' => $this->creator->userId);
        
        // Check for common user fields
        if (isset($this->creator->name)) {
            $creatorData['name'] = $this->creator->name;
        }
        if (isset($this->creator->email)) {
            $creatorData['email'] = $this->creator->email;
        }
        if (isset($this->creator->username)) {
            $creatorData['username'] = $this->creator->username;
        }
        if (isset($this->creator->fullName)) {
            $creatorData['fullName'] = $this->creator->fullName;
        }
    } elseif ($this->createdBy) {
        // Try to load creator if not loaded
        $creator = User::model()->findByPk($this->createdBy);
        if ($creator) {
            $creatorData = array('userId' => $creator->userId);
            if (isset($creator->name)) $creatorData['name'] = $creator->name;
            if (isset($creator->email)) $creatorData['email'] = $creator->email;
        }
    }
    
    return array(
        'id' => (int)$this->id,
        'purchaseId' => $this->purchaseId,
        'supplierId' => $this->supplierId,
        'warehouseId' => $this->warehouseId,
        'supplierName' => $supplierName,
        'warehouseName' => $warehouseName,
        'totalAmount' => (float)$this->totalAmount,
        'status' => $this->status,
        'statusLabel' => $this->getStatusLabel(),
        'createdBy' => $this->createdBy,
        'createdAt' => $this->createdAt,
        'itemsCount' => $this->getItemsCount(),
        'totalQuantity' => $this->getTotalQuantity(),
        'items' => $itemsData,
        'supplier' => $supplierData,
        'warehouse' => $warehouseData,
        'creator' => $creatorData
    );
}

    /**
     * Search method for CGridView
     */
    public function search()
    {
        $criteria = new CDbCriteria;
        $criteria->with = array('supplier', 'warehouse');

        $criteria->compare('id', $this->id);
        $criteria->compare('purchaseId', $this->purchaseId, true);
        $criteria->compare('supplierId', $this->supplierId, true);
        $criteria->compare('warehouseId', $this->warehouseId, true);
        $criteria->compare('totalAmount', $this->totalAmount);
        $criteria->compare('status', $this->status, true);
        $criteria->compare('createdBy', $this->createdBy, true);
        $criteria->compare('createdAt', $this->createdAt, true);

        // Search by supplier name
        if (!empty($this->supplierId)) {
            $criteria->addSearchCondition('supplier.name', $this->supplierId, true, 'OR');
            $criteria->addSearchCondition('supplier.supplierName', $this->supplierId, true, 'OR');
        }

        // Search by warehouse name
        if (!empty($this->warehouseId)) {
            $criteria->addSearchCondition('warehouse.name', $this->warehouseId, true, 'OR');
            $criteria->addSearchCondition('warehouse.warehouseName', $this->warehouseId, true, 'OR');
        }

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'sort' => array(
                'defaultOrder' => 'createdAt DESC',
                'attributes' => array(
                    'supplierName' => array(
                        'asc' => 'supplier.name ASC, supplier.supplierName ASC',
                        'desc' => 'supplier.name DESC, supplier.supplierName DESC',
                    ),
                    'warehouseName' => array(
                        'asc' => 'warehouse.name ASC, warehouse.warehouseName ASC',
                        'desc' => 'warehouse.name DESC, warehouse.warehouseName DESC',
                    ),
                    '*',
                ),
            ),
            'pagination' => array(
                'pageSize' => 20,
            ),
        ));
    }

    /**
     * Get purchase by purchaseId
     * @param string $purchaseId
     * @return Purchase|null
     */
    public static function findByPurchaseId($purchaseId)
    {
        return self::model()->findByAttributes(array('purchaseId' => $purchaseId));
    }

    /**
     * Get purchases by supplierId
     * @param string $supplierId
     * @return Purchase[]
     */
    public static function findBySupplierId($supplierId)
    {
        return self::model()->findAllByAttributes(
            array('supplierId' => $supplierId),
            array('order' => 'createdAt DESC')
        );
    }

    /**
     * Get purchases by warehouseId
     * @param string $warehouseId
     * @return Purchase[]
     */
    public static function findByWarehouseId($warehouseId)
    {
        return self::model()->findAllByAttributes(
            array('warehouseId' => $warehouseId),
            array('order' => 'createdAt DESC')
        );
    }

    /**
     * Update purchase status
     * @param string $status
     * @return boolean
     */
    public function updateStatus($status)
    {
        $validStatuses = array_keys(self::getStatusOptions());
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        $this->status = $status;
        return $this->save();
    }

    /**
     * Calculate total amount from items
     * @return float
     */
    public function calculateTotalAmount()
    {
        $total = 0;
        if ($this->items) {
            foreach ($this->items as $item) {
                $unitPrice = property_exists($item, 'unitCost') ? $item->unitCost : 
                            (property_exists($item, 'unitPrice') ? $item->unitPrice : 0);
                $total += $item->quantity * $unitPrice;
            }
        }
        return $total;
    }

    /**
     * Returns the static model of the specified AR class.
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}