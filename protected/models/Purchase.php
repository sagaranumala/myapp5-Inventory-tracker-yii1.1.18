<?php
class Purchase extends BaseModel
{
    public function tableName() { return 'purchases'; }
    protected function ulidFields() { return ['purchaseId']; }

    public function relations()
    {
        return [
            'items' => [self::HAS_MANY, 'PurchaseItem', 'purchaseId'],
            'supplier' => [self::BELONGS_TO, 'Supplier', 'supplierId'],
            'warehouse' => [self::BELONGS_TO, 'Warehouse', 'warehouseId'],
            'creator' => [self::BELONGS_TO, 'User', 'createdBy'],
        ];
    }
}