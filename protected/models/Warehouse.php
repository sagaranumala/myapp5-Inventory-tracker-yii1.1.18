<?php
class Warehouse extends BaseModel
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'warehouses';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('name, warehouseId', 'required'),
            array('warehouseId', 'unique'),
            array('name, location', 'length', 'max' => 255),
            array('status', 'boolean'),
            array('status', 'default', 'value' => 1),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
            'warehouseStocks' => array(self::HAS_MANY, 'WarehouseStock', 'warehouseId'),
        );
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'warehouseId' => 'Warehouse ID',
            'name' => 'Warehouse Name',
            'location' => 'Location',
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
        return array('warehouseId');
    }

    /**
     * Get warehouse data for API response
     */
    public function getApiData()
    {
        return array(
            'id' => $this->id,
            'warehouseId' => $this->warehouseId,
            'name' => $this->name,
            'location' => $this->location,
            'status' => (bool)$this->status,
            'createdAt' => $this->createdAt,
            'warehouseStocks' => $this->getWarehouseStocksData(),
        );
    }

    /**
     * Get warehouse stocks data
     */
    protected function getWarehouseStocksData()
    {
        $stocks = array();
        if ($this->warehouseStocks) {
            foreach ($this->warehouseStocks as $stock) {
                $stocks[] = $stock->getApiData();
            }
        }
        return $stocks;
    }

    /**
     * Get active warehouses
     */
    public static function getActiveWarehouses()
    {
        return self::model()->findAllByAttributes(array('status' => 1));
    }
}