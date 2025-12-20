<?php
class SupplierController extends Controller
{

    protected function isApiRequest()
    {
        return Yii::app()->request->isAjaxRequest || 
               Yii::app()->request->getParam('format') === 'json' ||
               strpos(Yii::app()->request->getRequestUri(), '/api/') !== false;
    }

    /**
     * Send JSON response
     */
    protected function sendJson($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        Yii::app()->end();
    }
    
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
        // $suppliers = Supplier::model()->findAll();
        // if($this->isApiRequest()) $this->sendJson(['success'=>true,'data'=>$suppliers]);
        // else $this->render('index',['suppliers'=>$suppliers]);

         $suppliers = Supplier::model()->findAll();

        if ($this->isApiRequest()) {
            $data = $this->arToArray($suppliers);
            $this->sendJson(['success' => true, 'data' => $data]);
            return;
        }
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
        // $this->render('create',['model'=>$model]);
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
        // $this->render('update',['model'=>$model]);
    }

    public function actionDelete($id)
    {
        $model = Supplier::model()->findByPk($id);
        if($model) $model->delete();
        if($this->isApiRequest()) $this->sendJson(['success'=>true]);
        else $this->redirect(['index']);
    }
}
