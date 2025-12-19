<!-- <?php
class PurchaseController extends Controller
{
    protected function arToArray($ar)
    {
        if (is_array($ar)) {
            $data = [];
            foreach ($ar as $item) {
                $data[] = $this->arToArray($item);
            }
            return $data;
        }
        $attributes = $ar->attributes;
        foreach ($ar->relations() as $name => $relation) {
            if ($ar->$name !== null) {
                $attributes[$name] = $this->arToArray($ar->$name);
            }
        }
        return $attributes;
    }
    public function actionIndex()
    {
        // $purchases = Purchase::model()->with('supplier', 'warehouse', 'items')->findAll();
        // if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$purchases]);
        // else $this->render('index',['purchases'=>$purchases]);

         $purchases = Purchase::model()->with('supplier', 'warehouse', 'items')->findAll();

        if ($this->isApiRequest()) {
            $data = $this->arToArray($purchases);
            $this->sendJson(['success' => true, 'data' => $data]);
            return;
        }
    }

    public function actionView($id)
    {
        $purchase = Purchase::model()->with('supplier', 'warehouse', 'items')->findByPk($id);
        if(!$purchase) throw new CHttpException(404,'Purchase not found');
        if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$purchase]);
        else $this->render('view',['purchase'=>$purchase]);
    }

    public function actionCreate()
    {
        $model = new Purchase();
        if(isset($_POST['Purchase'])){
            $model->attributes=$_POST['Purchase'];

            $transaction = Yii::app()->db->beginTransaction();
            try {
                if($model->save()){
                    // Save items
                    if(isset($_POST['PurchaseItem'])){
                        foreach($_POST['PurchaseItem'] as $itemData){
                            $item = new PurchaseItem();
                            $item->attributes = $itemData;
                            $item->purchaseId = $model->id;
                            if(!$item->save()) throw new Exception('Failed to save item');
                            
                            // Update stock
                            $stock = WarehouseStock::model()->findByAttributes([
                                'warehouseId'=>$model->warehouseId,
                                'productId'=>$item->productId
                            ]);
                            if(!$stock){
                                $stock = new WarehouseStock();
                                $stock->warehouseId = $model->warehouseId;
                                $stock->productId = $item->productId;
                                $stock->quantity = 0;
                            }
                            $stock->quantity += $item->quantity;
                            if(!$stock->save()) throw new Exception('Failed to update stock');
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
        $suppliers = Supplier::model()->findAll();
        $warehouses = Warehouse::model()->findAll();
        $this->render('create',['model'=>$model,'suppliers'=>$suppliers,'warehouses'=>$warehouses]);
    }

    public function actionUpdate($id)
    {
        $model = Purchase::model()->findByPk($id);
        if(!$model) throw new CHttpException(404);
        if(isset($_POST['Purchase'])){
            $model->attributes=$_POST['Purchase'];
            if($model->save()){
                if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$model]);
                else $this->redirect(['view','id'=>$model->id]);
            }
        }
        $suppliers = Supplier::model()->findAll();
        $warehouses = Warehouse::model()->findAll();
        $this->render('update',['model'=>$model,'suppliers'=>$suppliers,'warehouses'=>$warehouses]);
    }

    public function actionDelete($id)
    {
        $model = Purchase::model()->findByPk($id);
        if($model) $model->delete();
        if($this->isApiRequest()) $this->sendJson(['success'=>true]);
        else $this->redirect(['index']);
    }
} 