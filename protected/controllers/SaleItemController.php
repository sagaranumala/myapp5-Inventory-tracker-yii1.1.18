<?php
class SaleItemController extends Controller
{
    public function actionIndex($saleId)
    {
        $items = SaleItem::model()->with('product')->findAllByAttributes(['saleId'=>$saleId]);
        if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$items]);
        else $this->render('index',['items'=>$items]);
    }

    public function actionDelete($id)
    {
        $item = SaleItem::model()->findByPk($id);
        if($item){
            // Restore stock
            $stock = WarehouseStock::model()->findByAttributes([
                'warehouseId'=>$item->sale->warehouseId,
                'productId'=>$item->productId
            ]);
            if($stock){
                $stock->quantity += $item->quantity;
                $stock->save();
            }
            $item->delete();
        }
        if($this->isApiRequest()) $this->sendJson(['success'=>true]);
        else $this->redirect(['index','saleId'=>$item->saleId]);
    }
}
