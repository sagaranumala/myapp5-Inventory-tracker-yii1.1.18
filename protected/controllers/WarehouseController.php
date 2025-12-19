<?php
class WarehouseController extends Controller
{
    /**
     * List all warehouses
     */
    public function actionIndex()
    {
        $warehouses = Warehouse::model()->with('warehouseStocks')->findAll();
        
        if ($this->isApiRequest()) {
            $data = array();
            foreach ($warehouses as $warehouse) {
                $data[] = $warehouse->getApiData();
            }
            $this->sendJson(array(
                'success' => true,
                'data' => $data
            ));
        } else {
            $this->render('index', array('warehouses' => $warehouses));
        }
    }

    /**
     * Create a new warehouse
     */
    public function actionCreate()
    {
        $model = new Warehouse();

        if (isset($_POST['Warehouse'])) {
            $model->attributes = $_POST['Warehouse'];
            
            if ($model->save()) {
                if ($this->isApiRequest()) {
                    $this->sendJson(array(
                        'success' => true,
                        'message' => 'Warehouse created successfully',
                        'data' => $model->getApiData()
                    ), 201);
                } else {
                    Yii::app()->user->setFlash('success', 'Warehouse created successfully');
                    $this->redirect(array('index'));
                }
            } else {
                if ($this->isApiRequest()) {
                    $this->sendJson(array(
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $model->getErrors()
                    ), 400);
                }
            }
        }

        if ($this->isApiRequest()) {
            // For GET API requests, return form structure
            $this->sendJson(array(
                'success' => true,
                'message' => 'Send POST request to create warehouse',
                'example' => array(
                    'name' => 'Main Warehouse',
                    'location' => '123 Street, City',
                    'status' => 1
                )
            ));
        } else {
            $this->render('create', array('model' => $model));
        }
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

        if (isset($_POST['Warehouse'])) {
            $warehouse->attributes = $_POST['Warehouse'];
            
            if ($warehouse->save()) {
                if ($this->isApiRequest()) {
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
                if ($this->isApiRequest()) {
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