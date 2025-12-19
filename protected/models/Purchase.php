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
 * @property string $notes
 * @property string $expectedDelivery
 * @property string $createdBy
 * @property string $createdAt
 * @property string $updatedAt
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
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('supplierId, warehouseId', 'required'),
            array('totalAmount', 'numerical'),
            array('purchaseId, supplierId, warehouseId, createdBy', 'length', 'max'=>26),
            array('status', 'length', 'max'=>50),
            array('notes', 'safe'),
            array('expectedDelivery, createdAt, updatedAt', 'safe'),
            // The following rule is used by search().
            array('id, purchaseId, supplierId, warehouseId, totalAmount, status, notes, expectedDelivery, createdBy, createdAt, updatedAt', 'safe', 'on'=>'search'),
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
            'supplierId' => 'Supplier',
            'warehouseId' => 'Warehouse',
            'totalAmount' => 'Total Amount',
            'status' => 'Status',
            'notes' => 'Notes',
            'expectedDelivery' => 'Expected Delivery',
            'createdBy' => 'Created By',
            'createdAt' => 'Created At',
            'updatedAt' => 'Updated At',
        );
    }

    /**
     * Behaviors
     */
    public function behaviors()
    {
        return array(
            'CTimestampBehavior' => array(
                'class' => 'zii.behaviors.CTimestampBehavior',
                'createAttribute' => 'createdAt',
                'updateAttribute' => 'updatedAt',
                'setUpdateOnCreate' => true,
            ),
        );
    }

    /**
     * Before save - Yii 1.x doesn't take parameters
     */
    protected function beforeSave()
    {
        if (parent::beforeSave()) {
            if ($this->isNewRecord) {
                $this->purchaseId = $this->generateId('PUR_');
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
                $total += $item->quantity * $item->unitCost;
            }
            
            // Update total amount directly in database
            Yii::app()->db->createCommand()
                ->update('purchases', 
                    array('totalAmount' => $total),
                    'purchaseId = :purchaseId',
                    array(':purchaseId' => $this->purchaseId)
                );
            
            // Refresh this object
            $this->refresh();
        }
    }

    /**
     * Generate ID
     */
    private function generateId($prefix = '')
    {
        $microtime = str_replace('.', '', microtime(true));
        $random = bin2hex(random_bytes(5));
        $id = $prefix . substr($microtime, -10) . $random;
        return substr($id, 0, 26);
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
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        $criteria=new CDbCriteria;

        $criteria->compare('id',$this->id);
        $criteria->compare('purchaseId',$this->purchaseId,true);
        $criteria->compare('supplierId',$this->supplierId,true);
        $criteria->compare('warehouseId',$this->warehouseId,true);
        $criteria->compare('totalAmount',$this->totalAmount);
        $criteria->compare('status',$this->status,true);
        $criteria->compare('notes',$this->notes,true);
        $criteria->compare('expectedDelivery',$this->expectedDelivery,true);
        $criteria->compare('createdBy',$this->createdBy,true);
        $criteria->compare('createdAt',$this->createdAt,true);
        $criteria->compare('updatedAt',$this->updatedAt,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
            'sort'=>array(
                'defaultOrder'=>'createdAt DESC',
            ),
            'pagination'=>array(
                'pageSize'=>20,
            ),
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Purchase the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}