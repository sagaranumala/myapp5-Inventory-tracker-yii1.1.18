<?php
class Warehouse extends BaseModel
{
    public function tableName() { return 'warehouses'; }

    protected function ulidFields() { return ['warehouseId']; }

    public function relations()
    {
        return [
            'stocks' => [self::HAS_MANY, 'WarehouseStock', 'warehouseId'],
        ];
    }
}
