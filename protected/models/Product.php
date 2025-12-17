<?php

class Product extends BaseModel
{
    public function tableName() { return 'products'; }

    protected function ulidFields() { return ['productId']; }

    public function rules()
    {
        return [
            ['sku, name, categoryId, unitPrice, costPrice', 'required'],
            ['unitPrice, costPrice', 'numerical'],
            ['sku', 'unique'],
            ['isActive', 'safe'],
        ];
    }

    public function relations()
    {
        return [
            'category' => [self::BELONGS_TO, 'Category', 'categoryId'],
            'warehouseStocks' => [self::HAS_MANY, 'WarehouseStock', 'productId'],
        ];
    }
}
