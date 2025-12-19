<?php
class WarehouseController extends Controller
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

    /**
     * Check if request is an API request
     */
    protected function isApiRequest()
    {
        return true;
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
    
    /**
     * Get input data from request
     */
    protected function getInputData($isApiRequest = false)
    {
        if ($isApiRequest) {
            // For API: parse JSON body
            $rawBody = Yii::app()->request->getRawBody();
            
            if (!empty($rawBody)) {
                $data = json_decode($rawBody, true);
                if ($data === null) {
                    $data = array();
                }
            } else {
                $data = array();
            }
        } else {
            // For Web: get from form
            $data = Yii::app()->request->getPost('Warehouse', array());
        }
        
        return $data;
    }

    public function actionIndex()
    {
        $warehouses = Warehouse::model()->with('warehouseStocks')->findAll();

        if ($this->isApiRequest()) {
            $data = $this->arToArray($warehouses);
            $this->sendJson(['success' => true, 'data' => $data]);
            return;
        }
        // $this->render('index', ['products' => $products]);
    }


    /**
     * Create a new warehouse
     */
    public function actionCreate()
{
    $model = new Warehouse();

    // Handle POST requests
    if (Yii::app()->request->getIsPostRequest()) {
        // Get raw input
        $rawBody = file_get_contents('php://input');
        
        // DEBUG: Log raw input
        Yii::log("Raw input: " . $rawBody, 'info');
        
        // Try to decode JSON
        $postData = json_decode($rawBody, true);
        
        // If decode failed or returned a string, try decoding again
        if (is_string($postData)) {
            Yii::log("First decode returned string, trying again...", 'info');
            $postData = json_decode($postData, true);
        }
        
        // If still not an array, something is wrong
        if (!is_array($postData)) {
            $this->sendJson(array(
                'success' => false,
                'message' => 'Invalid JSON data',
                'rawBody' => $rawBody,
                'decoded' => $postData,
                'jsonError' => json_last_error_msg()
            ), 400);
            return;
        }
        
        // Direct assignment
        $model->name = isset($postData['name']) ? trim($postData['name']) : null;
        $model->location = isset($postData['location']) ? trim($postData['location']) : null;
        $model->status = isset($postData['status']) ? (int)$postData['status'] : 1;
        
        // DEBUG: Check what we got
        Yii::log("PostData after decode: " . print_r($postData, true), 'info');
        Yii::log("Model name: " . $model->name, 'info');
        
        // Validate and save
        if ($model->save()) {
            $this->sendJson(array(
                'success' => true,
                'message' => 'Warehouse created successfully',
                'data' => $model->getApiData()
            ), 201);
        } else {
            $this->sendJson(array(
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $model->getErrors(),
                'debug' => array(
                    'rawBody' => $rawBody,
                    'postData' => $postData,
                    'modelName' => $model->name,
                    'modelAttrs' => $model->attributes,
                    'jsonDepth' => $this->getJsonDepth($rawBody)
                )
            ), 400);
        }
        return;
    }

    // GET request
    $this->sendJson(array(
        'success' => true,
        'message' => 'Send POST request to create warehouse',
        'example' => array(
            'name' => 'Main Warehouse',
            'location' => '123 Street, City',
            'status' => 1
        )
    ));
}

/**
 * Helper to check JSON nesting depth
 */
protected function getJsonDepth($json)
{
    $depth = 0;
    $temp = $json;
    while (is_string($temp) && ($decoded = json_decode($temp, true)) !== null) {
        $depth++;
        $temp = $decoded;
        if (is_array($temp)) {
            break;
        }
    }
    return $depth;
}
    /**
     * View a single warehouse
     */
    public function actionView($id)
    {
        if (is_numeric($id)) {
            $warehouse = Warehouse::model()->with('warehouseStocks')->findByPk($id);
        } else {
            $warehouse = Warehouse::model()->with('warehouseStocks')->findByAttributes(array('warehouseId' => $id));
        }

        if (!$warehouse) {
            if ($this->isApiRequest()) {
                $this->sendJson(array(
                    'success' => false,
                    'message' => 'Warehouse not found'
                ), 404);
            } else {
                throw new CHttpException(404, 'Warehouse not found');
            }
        }

        if ($this->isApiRequest()) {
            $this->sendJson(array(
                'success' => true,
                'data' => $warehouse->getApiData()
            ));
        } else {
            $this->render('view', array('warehouse' => $warehouse));
        }
    }

    /**
     * Update a warehouse
     */
    public function actionUpdate($id)
    {
        if (is_numeric($id)) {
            $warehouse = Warehouse::model()->findByPk($id);
        } else {
            $warehouse = Warehouse::model()->findByAttributes(array('warehouseId' => $id));
        }

        if (!$warehouse) {
            if ($this->isApiRequest()) {
                $this->sendJson(array(
                    'success' => false,
                    'message' => 'Warehouse not found'
                ), 404);
            } else {
                throw new CHttpException(404, 'Warehouse not found');
            }
        }

        if (Yii::app()->request->getIsPostRequest()) {
            $isApiRequest = $this->isApiRequest();
            $postData = $this->getInputData($isApiRequest);
            
            // Only update allowed attributes
            $allowedAttributes = array('name', 'location', 'status');
            foreach ($allowedAttributes as $attribute) {
                if (isset($postData[$attribute])) {
                    $warehouse->$attribute = $postData[$attribute];
                }
            }
            
            if ($warehouse->save()) {
                if ($isApiRequest) {
                    $this->sendJson(array(
                        'success' => true,
                        'message' => 'Warehouse updated successfully',
                        'data' => $warehouse->getApiData()
                    ));
                } else {
                    Yii::app()->user->setFlash('success', 'Warehouse updated successfully');
                    $this->redirect(array('index'));
                }
            } else {
                if ($isApiRequest) {
                    $this->sendJson(array(
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $warehouse->getErrors()
                    ), 400);
                }
            }
        }

        if ($this->isApiRequest()) {
            // For GET API requests, return current data
            $this->sendJson(array(
                'success' => true,
                'data' => $warehouse->getApiData()
            ));
        } else {
            $this->render('update', array('model' => $warehouse));
        }
    }

    /**
     * Delete a warehouse
     */
    public function actionDelete($id)
    {
        if (is_numeric($id)) {
            $warehouse = Warehouse::model()->findByPk($id);
        } else {
            $warehouse = Warehouse::model()->findByAttributes(array('warehouseId' => $id));
        }

        if (!$warehouse) {
            if ($this->isApiRequest()) {
                $this->sendJson(array(
                    'success' => false,
                    'message' => 'Warehouse not found'
                ), 404);
            } else {
                throw new CHttpException(404, 'Warehouse not found');
            }
        }

        // Check if warehouse has stocks before deleting
        $hasStocks = WarehouseStock::model()->exists('warehouseId = :warehouseId', array(
            ':warehouseId' => $warehouse->warehouseId
        ));

        if ($hasStocks) {
            if ($this->isApiRequest()) {
                $this->sendJson(array(
                    'success' => false,
                    'message' => 'Cannot delete warehouse with existing stock. Remove all stock first.'
                ), 400);
            } else {
                Yii::app()->user->setFlash('error', 'Cannot delete warehouse with existing stock.');
                $this->redirect(array('index'));
                return;
            }
        }

        if ($warehouse->delete()) {
            if ($this->isApiRequest()) {
                $this->sendJson(array(
                    'success' => true,
                    'message' => 'Warehouse deleted successfully'
                ));
            } else {
                Yii::app()->user->setFlash('success', 'Warehouse deleted successfully');
                $this->redirect(array('index'));
            }
        } else {
            if ($this->isApiRequest()) {
                $this->sendJson(array(
                    'success' => false,
                    'message' => 'Failed to delete warehouse'
                ), 500);
            }
        }
    }

    /**
     * Get active warehouses only
     */
    public function actionActive()
    {
        $warehouses = Warehouse::model()->with('warehouseStocks')->findAllByAttributes(
            array('status' => 1)
        );
        
        $data = array();
        foreach ($warehouses as $warehouse) {
            $data[] = $warehouse->getApiData();
        }
        
        $this->sendJson(array(
            'success' => true,
            'data' => $data
        ));
    }

    /**
     * Get warehouse statistics
     */
    public function actionStats()
    {
        $totalWarehouses = Warehouse::model()->count();
        $activeWarehouses = Warehouse::model()->countByAttributes(array('status' => 1));
        
        // Get warehouses with their stock counts
        $warehouses = Warehouse::model()->findAll();
        $warehouseStats = array();
        
        foreach ($warehouses as $warehouse) {
            $stockCount = WarehouseStock::model()->countByAttributes(
                array('warehouseId' => $warehouse->warehouseId)
            );
            
            $warehouseStats[] = array(
                'warehouseId' => $warehouse->warehouseId,
                'name' => $warehouse->name,
                'location' => $warehouse->location,
                'status' => (bool)$warehouse->status,
                'stockCount' => $stockCount
            );
        }
        
        $this->sendJson(array(
            'success' => true,
            'data' => array(
                'totalWarehouses' => $totalWarehouses,
                'activeWarehouses' => $activeWarehouses,
                'warehouses' => $warehouseStats
            )
        ));
    }
}