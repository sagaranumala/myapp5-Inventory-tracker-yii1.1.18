<?php
class CategoryController extends Controller
{
    public function actionIndex()
    {
        $categories = Category::model()->findAll();
        $this->render('index', ['categories' => $categories]);
    }

    public function actionView($id)
    {
        $category = Category::model()->findByPk($id);
        if (!$category) throw new CHttpException(404, 'Category not found');
        $this->render('view', ['category' => $category]);
    }

    public function actionCreate()
    {
        $model = new Category();
        if (isset($_POST['Category'])) {
            $model->attributes = $_POST['Category'];
            if ($model->save()) $this->redirect(['view', 'id' => $model->id]);
        }
        $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = Category::model()->findByPk($id);
        if (!$model) throw new CHttpException(404);
        if (isset($_POST['Category'])) {
            $model->attributes = $_POST['Category'];
            if ($model->save()) $this->redirect(['view', 'id' => $model->id]);
        }
        $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = Category::model()->findByPk($id);
        if ($model) $model->delete();
        $this->redirect(['index']);
    }
}
