<?php
class CategoryController extends Controller
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

    // LIST PRODUCTS
    public function actionIndex()
    {
        $categories = Category::model()->with('parentCategory')->findAll();
    
        if ($this->isApiRequest()) {
            $data = $this->arToArray($categories);
            $this->sendJson(['success' => true, 'data' => $data]);
            return;
        }
        // $this->render('index', ['products' => $products]);
    }

    // public function actionIndex()
    // {
    //     $categories = Category::model()->findAll();
    //     if ($this->isApiRequest()) {
    //         $this->sendJson(['success' => true, 'data' => $categories]);
    //         return;
    //     }
    //     $this->render('index', ['categories' => $categories]);
    // }

    public function actionView($id)
    {
        $category = Category::model()->findByPk($id);
        if (!$category) {
            if ($this->isApiRequest()) {
                $this->sendJson(['success' => false, 'message' => 'Category not found'], 404);
                return;
            }
            throw new CHttpException(404, 'Category not found');
        }
        if ($this->isApiRequest()) {
            $this->sendJson(['success' => true, 'data' => $category]);
            return;
        }
        $this->render('view', ['category' => $category]);
    }

    public function actionCreate()
    {
        $model = new Category();
        if (isset($_POST['Category'])) {
            $model->attributes = $_POST['Category'];
            if ($model->save()) {
                if ($this->isApiRequest()) {
                    $this->sendJson(['success' => true, 'data' => $model]);
                    return;
                }
                $this->redirect(['view', 'id' => $model->id]);
            } else if ($this->isApiRequest()) {
                $this->sendJson(['success' => false, 'errors' => $model->getErrors()], 400);
                return;
            }
        }
        if ($this->isApiRequest()) {
            $this->sendJson(['success' => true]);
            return;
        }
        $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = Category::model()->findByPk($id);
        if (!$model) {
            if ($this->isApiRequest()) {
                $this->sendJson(['success' => false, 'message' => 'Category not found'], 404);
                return;
            }
            throw new CHttpException(404);
        }
        if (isset($_POST['Category'])) {
            $model->attributes = $_POST['Category'];
            if ($model->save()) {
                if ($this->isApiRequest()) {
                    $this->sendJson(['success' => true, 'data' => $model]);
                    return;
                }
                $this->redirect(['view', 'id' => $model->id]);
            } else if ($this->isApiRequest()) {
                $this->sendJson(['success' => false, 'errors' => $model->getErrors()], 400);
                return;
            }
        }
        if ($this->isApiRequest()) {
            $this->sendJson(['success' => true, 'data' => $model]);
            return;
        }
        $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = Category::model()->findByPk($id);
        if ($model) $model->delete();
        if ($this->isApiRequest()) {
            $this->sendJson(['success' => true]);
            return;
        }
        $this->redirect(['index']);
    }
}
