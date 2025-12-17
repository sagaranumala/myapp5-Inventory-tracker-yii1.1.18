<?php
class SupplierController extends Controller
{
    public function actionIndex()
    {
        $suppliers = Supplier::model()->findAll();
        if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$suppliers]);
        else $this->render('index',['suppliers'=>$suppliers]);
    }

    public function actionCreate()
    {
        $model = new Supplier();
        if(isset($_POST['Supplier'])){
            $model->attributes=$_POST['Supplier'];
            if($model->save()){
                if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$model]);
                else $this->redirect(['index']);
            }
        }
        $this->render('create',['model'=>$model]);
    }

    public function actionUpdate($id)
    {
        $model = Supplier::model()->findByPk($id);
        if(!$model) throw new CHttpException(404);
        if(isset($_POST['Supplier'])){
            $model->attributes=$_POST['Supplier'];
            if($model->save()){
                if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$model]);
                else $this->redirect(['index']);
            }
        }
        $this->render('update',['model'=>$model]);
    }

    public function actionDelete($id)
    {
        $model = Supplier::model()->findByPk($id);
        if($model) $model->delete();
        if($this->isApiRequest()) $this->sendJson(['success'=>true]);
        else $this->redirect(['index']);
    }
}
