<?php

class Category extends BaseModel
{
    public function tableName() { return 'categories'; }

    protected function ulidFields() { return ['categoryId']; }

    public function relations()
    {
        return [
            'products' => [self::HAS_MANY, 'Product', 'categoryId'],
            'parentCategory' => [self::BELONGS_TO, 'Category', 'parentCategoryId'],
            'childCategories' => [self::HAS_MANY, 'Category', 'parentCategoryId'],
        ];
    }
}
