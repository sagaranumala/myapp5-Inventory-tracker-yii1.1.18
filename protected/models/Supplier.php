<?php
class Supplier extends BaseModel
{
    public function tableName() { return 'suppliers'; }
    protected function ulidFields() { return ['supplierId']; }

    public function relations()
    {
        return [
            'purchases' => [self::HAS_MANY, 'Purchase', 'supplierId'],
        ];
    }
}
