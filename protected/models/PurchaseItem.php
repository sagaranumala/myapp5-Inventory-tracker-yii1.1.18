<?php
class PurchaseItem extends BaseModel
{
    public function tableName() { return 'purchaseItems'; }
    protected function ulidFields() { return ['purchaseItemId']; }

    public function relations()
    {
        return [
            'purchase' => [self::BELONGS_TO, 'Purchase', 'purchaseId'],
            'product' => [self::BELONGS_TO, 'Product', 'productId'],
        ];
    }
}