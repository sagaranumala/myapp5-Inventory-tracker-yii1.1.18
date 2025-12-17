<?php
class SaleController extends Controller
{
    public function actionIndex()
    {
        $sales = Sale::model()->with('warehouse','items')->findAll();
        if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$sales]);
        else $this->render('index',['sales'=>$sales]);
    }

    public function actionView($id)
    {
        $sale = Sale::model()->with('warehouse','items')->findByPk($id);
        if(!$sale) throw new CHttpException(404);
        if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$sale]);
        else $this->render('view',['sale'=>$sale]);
    }

    public function actionCreate()
    {
        $model = new Sale();
        if(isset($_POST['Sale'])){
            $model->attributes=$_POST['Sale'];
            $transaction = Yii::app()->db->beginTransaction();
            try {
                if($model->save()){
                    // Save items
                    if(isset($_POST['SaleItem'])){
                        foreach($_POST['SaleItem'] as $itemData){
                            $item = new SaleItem();
                            $item->attributes = $itemData;
                            $item->saleId = $model->id;
                            if(!$item->save()) throw new Exception('Failed to save sale item');
                            
                            // Reduce stock
                            $stock = WarehouseStock::model()->findByAttributes([
                                'warehouseId'=>$model->warehouseId,
                                'productId'=>$item->productId
                            ]);
                            if(!$stock || $stock->quantity < $item->quantity) throw new Exception('Insufficient stock');
                            $stock->quantity -= $item->quantity;
                            $stock->save();
                        }
                    }
                    $transaction->commit();
                    if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$model]);
                    else $this->redirect(['view','id'=>$model->id]);
                }
            } catch(Exception $e){
                $transaction->rollback();
                if($this->isApiRequest()) $this->sendJson(['success'=>false,'message'=>$e->getMessage()]);
                else throw $e;
            }
        }
        $warehouses = Warehouse::model()->findAll();
        $this->render('create',['model'=>$model,'warehouses'=>$warehouses]);
    }

    public function actionDelete($id)
    {
        $model = Sale::model()->findByPk($id);
        if($model) $model->delete();
        if($this->isApiRequest()) $this->sendJson(['success'=>true]);
        else $this->redirect(['index']);
    }
}
