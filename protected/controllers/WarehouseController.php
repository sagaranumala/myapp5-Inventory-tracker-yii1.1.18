<?php
class WarehouseController extends Controller
{
    public function actionIndex()
    {
        $warehouses = Warehouse::model()->findAll();
        $this->render('index', ['warehouses' => $warehouses]);
    }

    public function actionCreate()
    {
        $model = new Warehouse();
        if (isset($_POST['Warehouse'])) {
            $model->attributes = $_POST['Warehouse'];
            if ($model->save()) $this->redirect(['index']);
        }
        $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = Warehouse::model()->findByPk($id);
        if (!$model) throw new CHttpException(404);
        if (isset($_POST['Warehouse'])) {
            $model->attributes = $_POST['Warehouse'];
            if ($model->save()) $this->redirect(['index']);
        }
        $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = Warehouse::model()->findByPk($id);
        if ($model) $model->delete();
        $this->redirect(['index']);
    }
}
