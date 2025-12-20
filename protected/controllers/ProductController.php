<?php
class ProductController extends Controller
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
	
    public function beforeAction($action)
    {
        // Disable CSRF for API endpoints
        // if (Yii::app()->request->isAjaxRequest || Yii::app()->request->getRequestType() === 'POST') {
        //     Yii::app()->request->enableCsrfValidation = false;
        // }
		 Yii::app()->request->enableCsrfValidation = false;
        return parent::beforeAction($action);
    
    }
    // Utility function to convert AR objects to arrays, including relations
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
        $products = Product::model()->with('category')->findAll();

        if ($this->isApiRequest()) {
            $data = $this->arToArray($products);
            $this->sendJson(['success' => true, 'data' => $data]);
            return;
        }
        // $this->render('index', ['products' => $products]);
    }

    // VIEW PRODUCT
    // public function actionView($id)
    // {
    //     $product = Product::model()->with('category')->findByPk($id);
    //     if (!$product) {
    //         if ($this->isApiRequest()) {
    //             $this->sendJson(['success' => false, 'message' => 'Product not found'], 404);
    //             return;
    //         }
    //         throw new CHttpException(404);
    //     }

    //     if ($this->isApiRequest()) {
    //         $data = $this->arToArray($product);
    //         $this->sendJson(['success' => true, 'data' => $data]);
    //         return;
    //     }
    //     $this->render('view', ['product' => $product]);
    // }

    // public function actionCreate()
    //     {
    //         $model = new Product();

    //         if (Yii::app()->request->isPostRequest) {
    //             $json = file_get_contents('php://input');
    //             $data = json_decode($json, true);

    //             if (!$data) {
    //                 $this->sendJson(['success' => false, 'message' => 'Invalid JSON'], 400);
    //                 return;
    //             }

    //             // Direct assignment
    //             $model->sku         = $data['sku'] ?? null;
    //             $model->name        = $data['name'] ?? null;
    //             $model->categoryId  = $data['categoryId'] ?? null;
    //             $model->unitPrice   = $data['unitPrice'] ?? null;
    //             $model->costPrice   = $data['costPrice'] ?? null;
    //             $model->reorderLevel= $data['reorderLevel'] ?? null;
    //             $model->expiryDate  = $data['expiryDate'] ?? null;
    //             $model->isActive    = $data['isActive'] ?? 1;

    //             if ($model->save()) {
    //                 $this->sendJson(['success' => true, 'data' => [
    //                     'sku' => $model->sku,
    //                     'name' => $model->name,
    //                     'categoryId' => $model->categoryId,
    //                     'unitPrice' => $model->unitPrice,
    //                     'costPrice' => $model->costPrice,
    //                     'reorderLevel' => $model->reorderLevel,
    //                     'expiryDate' => $model->expiryDate,
    //                     'isActive' => $model->isActive
    //                 ]]);
    //             } else {
    //                 $this->sendJson(['success' => false, 'errors' => $model->getErrors()], 400);
    //             }
    //         } else {
    //             $this->sendJson(['success' => false, 'message' => 'Invalid request, POST required'], 400);
    //         }
    //     }

    public function actionCreate()
{
    $model = new Product();

    // Decode incoming JSON into an array
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        Yii::app()->end();
    }

    // Directly assign JSON keys to model attributes
    $model->attributes = $data;

    // Save record
    if ($model->save()) {
        echo json_encode([
            'success' => true,
            'data' => $model->attributes  // just return raw attributes
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'errors' => $model->getErrors()
        ]);
    }

    Yii::app()->end();
}




public function actionUpdate($productId)
{
    // Find product by ULID
    $product = Product::model()->findByAttributes(['productId' => $productId]);

    if (!$product) {
        error_log("UPDATE ERROR: Product not found for productId={$productId}");
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Product not found'
        ]);
        Yii::app()->end();
    }

    // Read raw JSON input
    $raw = file_get_contents('php://input');
    error_log("RAW INPUT: " . $raw); // Log input in Docker

    $input = json_decode($raw, true);

    if (!$input || !is_array($input)) {
        error_log("UPDATE ERROR: Invalid JSON input");
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Invalid input data',
            'raw' => $raw // Optional: for Postman debugging
        ]);
        Yii::app()->end();
    }

    // Assign allowed fields
    $fields = ['sku', 'name', 'categoryId', 'unitPrice', 'costPrice', 'description', 'isActive', 'reorderLevel', 'expiryDate'];
    foreach ($fields as $field) {
        if (isset($input[$field])) {
            $product->$field = $input[$field];
        }
    }

    error_log("PRODUCT DATA BEFORE SAVE: " . print_r($product->attributes, true));

    // Attempt to save
    if ($product->save()) {
        error_log("PRODUCT UPDATED SUCCESSFULLY: productId={$productId}");
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $product->getApiData()
        ]);
    } else {
        error_log("PRODUCT UPDATE FAILED: " . print_r($product->getErrors(), true));
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'errors' => $product->getErrors(),
            'debug_attributes' => $product->attributes
        ]);
    }

    Yii::app()->end();
}


    /**
     * Example: Get product by ID
     * GET request
     */
    public function actionView($productId)
    {
        $product = Product::model()->findByAttributes(['productId' => $productId]);

        if (!$product) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            Yii::app()->end();
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $product->getApiData()]);
        Yii::app()->end();
    }

    // DELETE PRODUCT
    public function actionDelete($id)
    {
        $model = Product::model()->findByPk($id);
        if ($model) $model->delete();

        if ($this->isApiRequest()) {
            $this->sendJson(['success' => true]);
            return;
        }
        $this->redirect(['index']);
    }
}