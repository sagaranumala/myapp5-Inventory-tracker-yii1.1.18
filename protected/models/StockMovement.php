<?php
class StockMovement extends BaseModel
{
    public function tableName() { return 'stockMovements'; }
    protected function ulidFields() { return ['movementId']; }

    public function relations()
    {
        return [
            'product' => [self::BELONGS_TO, 'Product', 'productId'],
            'warehouse' => [self::BELONGS_TO, 'Warehouse', 'warehouseId'],
            'user' => [self::BELONGS_TO, 'User', 'createdBy'],
        ];
    }
}
