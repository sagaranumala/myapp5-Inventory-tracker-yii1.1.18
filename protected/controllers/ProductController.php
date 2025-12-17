<?php
class ProductController extends Controller
{
    public function actionIndex()
    {
        $products = Product::model()->with('category')->findAll();
        $this->render('index', ['products' => $products]);
    }

    public function actionView($id)
    {
        $product = Product::model()->findByPk($id);
        if (!$product) throw new CHttpException(404);
        $this->render('view', ['product' => $product]);
    }

    public function actionCreate()
    {
        $model = new Product();
        if (isset($_POST['Product'])) {
            $model->attributes = $_POST['Product'];
            if ($model->save()) $this->redirect(['view', 'id' => $model->id]);
        }
        $categories = Category::model()->findAll();
        $this->render('create', ['model' => $model, 'categories' => $categories]);
    }

    public function actionUpdate($id)
    {
        $model = Product::model()->findByPk($id);
        if (!$model) throw new CHttpException(404);
        if (isset($_POST['Product'])) {
            $model->attributes = $_POST['Product'];
            if ($model->save()) $this->redirect(['view', 'id' => $model->id]);
        }
        $categories = Category::model()->findAll();
        $this->render('update', ['model' => $model, 'categories' => $categories]);
    }

    public function actionDelete($id)
    {
        $model = Product::model()->findByPk($id);
        if ($model) $model->delete();
        $this->redirect(['index']);
    }
}
