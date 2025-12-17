<?php
class PurchaseItemController extends Controller
{
    public function actionIndex($purchaseId)
    {
        $items = PurchaseItem::model()->with('product')->findAllByAttributes(['purchaseId'=>$purchaseId]);
        if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$items]);
        else $this->render('index',['items'=>$items]);
    }

    public function actionDelete($id)
    {
        $item = PurchaseItem::model()->findByPk($id);
        if($item){
            // Reduce stock
            $stock = WarehouseStock::model()->findByAttributes([
                'warehouseId'=>$item->purchase->warehouseId,
                'productId'=>$item->productId
            ]);
            if($stock){
                $stock->quantity -= $item->quantity;
                $stock->save();
            }
            $item->delete();
        }
        if($this->isApiRequest()) $this->sendJson(['success'=>true]);
        else $this->redirect(['index','purchaseId'=>$item->purchaseId]);
    }
}
