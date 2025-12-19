<?php

/**
 * This is the model class for table "purchaseItems".
 *
 * @property integer $id
 * @property string $purchaseItemId
 * @property string $purchaseId
 * @property string $productId
 * @property integer $quantity
 * @property double $unitCost
 * @property string $createdAt
 * @property string $updatedAt
 * 
 * @property Purchase $purchase
 * @property Product $product
 */
class PurchaseItem extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'purchaseItems';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('purchaseId, productId, quantity, unitCost', 'required'),
            array('quantity', 'numerical', 'integerOnly'=>true, 'min'=>1),
            array('unitCost', 'numerical', 'min'=>0),
            array('purchaseItemId, purchaseId, productId', 'length', 'max'=>26),
            array('createdAt, updatedAt', 'safe'),
            // The following rule is used by search().
            array('id, purchaseItemId, purchaseId, productId, quantity, unitCost, createdAt, updatedAt', 'safe', 'on'=>'search'),
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
            'id' => 'ID',
            'purchaseItemId' => 'Item ID',
            'purchaseId' => 'Purchase',
            'productId' => 'Product',
            'quantity' => 'Quantity',
            'unitCost' => 'Unit Cost',
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
                $this->purchaseItemId = $this->generateId('PITM_');
            }
            
            return true;
        }
        return false;
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
     * Calculate total cost
     */
    public function getTotalCost()
    {
        return $this->quantity * $this->unitCost;
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
        $criteria->compare('purchaseItemId',$this->purchaseItemId,true);
        $criteria->compare('purchaseId',$this->purchaseId,true);
        $criteria->compare('productId',$this->productId,true);
        $criteria->compare('quantity',$this->quantity);
        $criteria->compare('unitCost',$this->unitCost);
        $criteria->compare('createdAt',$this->createdAt,true);
        $criteria->compare('updatedAt',$this->updatedAt,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return PurchaseItem the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}