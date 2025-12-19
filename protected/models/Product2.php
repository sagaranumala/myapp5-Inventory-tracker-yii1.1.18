<?php

/**
 * This is the model class for table "products".
 *
 * The followings are the available columns in table 'products':
 * @property string $id
 * @property string $productId
 * @property string $sku
 * @property string $name
 * @property string $categoryId
 * @property string $unitPrice
 * @property string $costPrice
 * @property integer $reorderLevel
 * @property string $expiryDate
 * @property integer $isActive
 * @property string $createdAt
 * @property string $updatedAt
 */
class Product extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'products';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('productId, sku, name, categoryId, unitPrice, costPrice', 'required'),
			array('reorderLevel, isActive', 'numerical', 'integerOnly'=>true),
			array('productId, categoryId', 'length', 'max'=>26),
			array('sku', 'length', 'max'=>100),
			array('name', 'length', 'max'=>255),
			array('unitPrice, costPrice', 'length', 'max'=>10),
			array('expiryDate, createdAt, updatedAt', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, productId, sku, name, categoryId, unitPrice, costPrice, reorderLevel, expiryDate, isActive, createdAt, updatedAt', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'productId' => 'Product',
			'sku' => 'Sku',
			'name' => 'Name',
			'categoryId' => 'Category',
			'unitPrice' => 'Unit Price',
			'costPrice' => 'Cost Price',
			'reorderLevel' => 'Reorder Level',
			'expiryDate' => 'Expiry Date',
			'isActive' => 'Is Active',
			'createdAt' => 'Created At',
			'updatedAt' => 'Updated At',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);
		$criteria->compare('productId',$this->productId,true);
		$criteria->compare('sku',$this->sku,true);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('categoryId',$this->categoryId,true);
		$criteria->compare('unitPrice',$this->unitPrice,true);
		$criteria->compare('costPrice',$this->costPrice,true);
		$criteria->compare('reorderLevel',$this->reorderLevel);
		$criteria->compare('expiryDate',$this->expiryDate,true);
		$criteria->compare('isActive',$this->isActive);
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
	 * @return Product the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
