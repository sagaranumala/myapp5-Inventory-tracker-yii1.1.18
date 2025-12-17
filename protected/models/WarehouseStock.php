<?php
class WarehouseStock extends BaseModel
{
    public function tableName() { return 'warehouseStock'; }

    protected function ulidFields() { return ['stockId']; }

    public function relations()
    {
        return [
            'product' => [self::BELONGS_TO, 'Product', 'productId'],
            'warehouse' => [self::BELONGS_TO, 'Warehouse', 'warehouseId'],
        ];
    }
}
