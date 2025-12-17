<?php
class StockMovementController extends Controller
{
    public function actionIndex()
    {
        $movements = StockMovement::model()->with('product','warehouse','user')->findAll();
        if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$movements]);
        else $this->render('index',['movements'=>$movements]);
    }

    public function actionCreate()
    {
        $model = new StockMovement();
        if(isset($_POST['StockMovement'])){
            $model->attributes = $_POST['StockMovement'];
            $model->userId = Yii::app()->user->id;
            $transaction = Yii::app()->db->beginTransaction();
            try {
                if($model->save()){
                    // Update stock
                    $stock = WarehouseStock::model()->findByAttributes([
                        'warehouseId'=>$model->warehouseId,
                        'productId'=>$model->productId
                    ]);
                    if(!$stock){
                        $stock = new WarehouseStock();
                        $stock->warehouseId = $model->warehouseId;
                        $stock->productId = $model->productId;
                        $stock->quantity = 0;
                    }
                    $stock->quantity += $model->quantityChange;
                    $stock->save();
                    $transaction->commit();
                    if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$model]);
                    else $this->redirect(['index']);
                }
            } catch(Exception $e){
                $transaction->rollback();
                if($this->isApiRequest()) $this->sendJson(['success'=>false,'message'=>$e->getMessage()]);
                else throw $e;
            }
        }
        $products = Product::model()->findAll();
        $warehouses = Warehouse::model()->findAll();
        $this->render('create',['model'=>$model,'products'=>$products,'warehouses'=>$warehouses]);
    }
}
