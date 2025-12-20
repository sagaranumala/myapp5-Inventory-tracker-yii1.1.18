<?php
class PurchaseController extends Controller
{

     /**
     * Send JSON response
     */
    protected function sendJson($data, $statusCode = 200)
    {
        // Clean any output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        Yii::app()->end();
    }

     /**
     * Debug version to see exact JSON
     */
    // public function actionCreate()
    // {
    //     // Start output buffer
    //     ob_start();
        
    //     try {
    //         // Set headers
    //         header('Content-Type: application/json');
            
    //         // Handle POST request
    //         if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //             // Get raw JSON
    //             $rawJson = file_get_contents('php://input');
                
    //             // Log to error log for debugging
    //             error_log("=== DEBUG PURCHASE CREATE ===");
    //             error_log("Raw input length: " . strlen($rawJson));
    //             error_log("Raw input: " . $rawJson);
    //             error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
                
    //             // Handle double-encoded JSON
    //             $data = $rawJson;
    //             $decodedCount = 0;
                
    //             // Keep decoding until we get an array or can't decode anymore
    //             while (is_string($data) && $decodedCount < 5) {
    //                 $temp = json_decode($data, true);
    //                 if (json_last_error() === JSON_ERROR_NONE && $temp !== null) {
    //                     $data = $temp;
    //                     $decodedCount++;
    //                     error_log("Decoded level $decodedCount, type: " . gettype($data));
    //                 } else {
    //                     break;
    //                 }
    //             }
                
    //             error_log("Final data type: " . gettype($data));
    //             error_log("Final data: " . print_r($data, true));
    //             error_log("JSON last error: " . json_last_error());
    //             error_log("JSON last error msg: " . json_last_error_msg());
    //             error_log("Decoded count: " . $decodedCount);
                
    //             // If data is still a string, try one more time
    //             if (is_string($data)) {
    //                 error_log("Data is still string, trying direct decode...");
    //                 $data = json_decode($data, true);
    //                 error_log("After direct decode, type: " . gettype($data));
    //             }
                
    //             // Check if we have valid array
    //             if (!is_array($data)) {
    //                 error_log("ERROR: Data is not an array. Type: " . gettype($data));
    //                 error_log("Data value: " . $data);
    //                 throw new Exception('Invalid data format. Expected JSON object, got: ' . gettype($data));
    //             }
                
    //             // Debug: Check if fields exist
    //             error_log("Field check:");
    //             error_log("  supplierId exists: " . (isset($data['supplierId']) ? 'YES' : 'NO'));
                
    //             if (isset($data['supplierId'])) {
    //                 error_log("  supplierId value: " . $data['supplierId']);
    //                 error_log("  supplierId type: " . gettype($data['supplierId']));
    //                 error_log("  supplierId empty: " . (empty($data['supplierId']) ? 'YES' : 'NO'));
    //             }
                
    //             // Check ALL keys in the data
    //             error_log("All keys in data: " . implode(', ', array_keys($data)));
                
    //             // Check required fields
    //             if (!isset($data['supplierId']) || empty(trim($data['supplierId']))) {
    //                 error_log("ERROR: supplierId is missing or empty");
    //                 throw new Exception('supplierId is required');
    //             }
                
    //             if (!isset($data['warehouseId']) || empty(trim($data['warehouseId']))) {
    //                 error_log("ERROR: warehouseId is missing or empty");
    //                 throw new Exception('warehouseId is required');
    //             }
                
    //             error_log("SUCCESS: Found supplierId: " . $data['supplierId'] . ", warehouseId: " . $data['warehouseId']);
                
    //             // Create purchase model
    //             $model = new Purchase();
    //             $model->supplierId = trim($data['supplierId']);
    //             $model->warehouseId = trim($data['warehouseId']);
    //             $model->totalAmount = isset($data['totalAmount']) ? floatval($data['totalAmount']) : 5;
    //             $model->status = isset($data['status']) ? strtolower(trim($data['status'])) : 'draft';
    //             $model->createdBy = isset($data['createdBy']) ? trim($data['createdBy']) : null;
                
    //             error_log("Model attributes before save: " . print_r($model->attributes, true));
                
    //             // Save purchase
    //             if ($model->save()) {
    //                 error_log("Purchase saved successfully! ID: " . $model->id . ", purchaseId: " . $model->purchaseId);
                    
    //                 // Return success
    //                 ob_end_clean();
    //                 echo json_encode([
    //                     'success' => true,
    //                     'message' => 'Purchase created successfully',
    //                     'data' => [
    //                         'id' => $model->id,
    //                         'purchaseId' => $model->purchaseId,
    //                         'supplierId' => $model->supplierId,
    //                         'warehouseId' => $model->warehouseId,
    //                         'status' => $model->status,
    //                         'createdAt' => $model->createdAt
    //                     ]
    //                 ]);
    //             } else {
    //                 $errors = $model->getErrors();
    //                 error_log("Save failed with errors: " . print_r($errors, true));
    //                 throw new Exception('Failed to save purchase');
    //             }
                
    //         } else {
    //             // GET request - show example
    //             ob_end_clean();
    //             echo json_encode([
    //                 'success' => true,
    //                 'message' => 'Send POST request with JSON',
    //                 'example' => [
    //                     'supplierId' => 'SUP0000000000000000000001',
    //                     'warehouseId' => 'WH0000000000000000000001',
    //                     'status' => 'pending'
    //                 ]
    //             ]);
    //         }
            
    //     } catch (Exception $e) {
    //         ob_end_clean();
    //         http_response_code(400);
    //         echo json_encode([
    //             'success' => false,
    //             'message' => 'Failed to create purchase',
    //             'error' => $e->getMessage()
    //         ]);
    //     }
        
    //     exit;
    // }

    public function actionView($id)
    {
        $purchase = Purchase::model()->with('supplier', 'warehouse', 'items')->findByPk($id);
        if (!$purchase) {
            $this->sendJson(['success' => false, 'message' => 'Purchase not found'], 404);
        }
        $this->sendJson(['success' => true, 'data' => $purchase->getApiData()]);
    }

    public function actionUpdate($id)
    {
        $model = Purchase::model()->findByPk($id);
        if (!$model) {
            $this->sendJson(['success' => false, 'message' => 'Purchase not found'], 404);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if ($data) {
                $model->attributes = $data;
                if ($model->save()) {
                    $this->sendJson(['success' => true, 'data' => $model->getApiData()]);
                } else {
                    $this->sendJson(['success' => false, 'message' => 'Update failed'], 400);
                }
            }
        } else {
            $this->sendJson(['success' => true, 'data' => $model->getApiData()]);
        }
    }

  public function actionDelete()
{
    // Method 1: Check URL parameter first (most likely how it's being sent)
    $purchaseId = isset($_GET['purchaseId']) ? $_GET['purchaseId'] : null;
    
    // Method 2: Check POST data
    if (!$purchaseId && isset($_POST['purchaseId'])) {
        $purchaseId = $_POST['purchaseId'];
    }
    
    // Method 3: Check raw JSON body
    if (!$purchaseId) {
        $rawBody = file_get_contents('php://input');
        if (!empty($rawBody)) {
            $data = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['purchaseId'])) {
                $purchaseId = $data['purchaseId'];
            }
        }
    }
    
    // Debug output
    error_log("DEBUG - Purchase ID received: " . ($purchaseId ?: 'NULL'));
    error_log("DEBUG - GET params: " . print_r($_GET, true));
    error_log("DEBUG - POST params: " . print_r($_POST, true));
    
    if (!$purchaseId) {
        $this->sendJson([
            'success' => false, 
            'message' => 'Purchase ID is required',
            'debug_info' => [
                'get_params' => $_GET,
                'post_params' => $_POST,
                'raw_input' => file_get_contents('php://input'),
                'request_method' => $_SERVER['REQUEST_METHOD']
            ]
        ], 400);
        return;
    }
    
    // Clean the ID (remove any whitespace)
    $purchaseId = trim($purchaseId);
    
    $transaction = Yii::app()->db->beginTransaction();
    
    try {
        // Try to find the purchase
        $model = Purchase::model()->findByPk($purchaseId);
        
        if (!$model) {
            // Try alternative lookup - maybe by purchaseId field (not primary key)
            $model = Purchase::model()->find('purchaseId = :pid', array(':pid' => $purchaseId));
            
            if (!$model) {
                $this->sendJson([
                    'success' => false, 
                    'message' => "Purchase not found with ID: " . htmlspecialchars($purchaseId),
                    'search_id' => $purchaseId
                ], 404);
                return;
            }
        }
        
        // Found it! Now delete items first
        $itemsDeleted = PurchaseItem::model()->deleteAll(
            'purchaseId = :pid',
            array(':pid' => $model->id) // Use the actual ID from the model
        );
        
        // Delete the purchase
        if ($model->delete()) {
            $transaction->commit();
            $this->sendJson([
                'success' => true, 
                'message' => 'Purchase deleted successfully',
                'deleted_id' => $model->id,
                'items_deleted' => $itemsDeleted
            ]);
        } else {
            $transaction->rollback();
            $this->sendJson(['success' => false, 'message' => 'Failed to delete purchase'], 500);
        }
        
    } catch (Exception $e) {
        if (isset($transaction)) {
            $transaction->rollback();
        }
        error_log("Delete error: " . $e->getMessage());
        $this->sendJson(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()], 500);
    }
}



public function actionCreate()
{
    // Start output buffer
    ob_start();
    
    try {
        // Set headers
        header('Content-Type: application/json');
        
        // Handle POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get raw JSON
            $rawJson = file_get_contents('php://input');
            
            // Log to error log for debugging
            error_log("=== DEBUG PURCHASE CREATE ===");
            error_log("Raw input length: " . strlen($rawJson));
            error_log("Raw input: " . $rawJson);
            error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
            
            // Handle double-encoded JSON
            $data = $rawJson;
            $decodedCount = 0;
            
            // Keep decoding until we get an array or can't decode anymore
            while (is_string($data) && $decodedCount < 5) {
                $temp = json_decode($data, true);
                if (json_last_error() === JSON_ERROR_NONE && $temp !== null) {
                    $data = $temp;
                    $decodedCount++;
                    error_log("Decoded level $decodedCount, type: " . gettype($data));
                } else {
                    break;
                }
            }
            
            error_log("Final data type: " . gettype($data));
            error_log("Final data: " . print_r($data, true));
            
            // If data is still a string, try one more time
            if (is_string($data)) {
                error_log("Data is still string, trying direct decode...");
                $data = json_decode($data, true);
                error_log("After direct decode, type: " . gettype($data));
            }
            
            // Check if we have valid array
            if (!is_array($data)) {
                error_log("ERROR: Data is not an array. Type: " . gettype($data));
                error_log("Data value: " . $data);
                throw new Exception('Invalid data format. Expected JSON object, got: ' . gettype($data));
            }
            
            // Debug: Check if fields exist
            error_log("Field check:");
            error_log("  supplierId exists: " . (isset($data['supplierId']) ? 'YES' : 'NO'));
            error_log("  items exists: " . (isset($data['items']) ? 'YES' : 'NO'));
            
            if (isset($data['supplierId'])) {
                error_log("  supplierId value: " . $data['supplierId']);
            }
            
            if (isset($data['items'])) {
                error_log("  items count: " . (is_array($data['items']) ? count($data['items']) : 'NOT ARRAY'));
                error_log("  items: " . print_r($data['items'], true));
            }
            
            // Check required fields
            if (!isset($data['supplierId']) || empty(trim($data['supplierId']))) {
                error_log("ERROR: supplierId is missing or empty");
                throw new Exception('supplierId is required');
            }
            
            if (!isset($data['warehouseId']) || empty(trim($data['warehouseId']))) {
                error_log("ERROR: warehouseId is missing or empty");
                throw new Exception('warehouseId is required');
            }
            
            if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                error_log("ERROR: items array is missing or empty");
                throw new Exception('At least one purchase item is required');
            }
            
            // Validate items
            $totalAmount = 0;
            foreach ($data['items'] as $index => $item) {
                if (!isset($item['productId']) || empty(trim($item['productId']))) {
                    throw new Exception('productId is required for item at index ' . $index);
                }
                if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                    throw new Exception('quantity must be greater than 0 for item at index ' . $index);
                }
                if (!isset($item['unitCost']) || $item['unitCost'] < 0) {
                    throw new Exception('unitCost is required for item at index ' . $index);
                }
                
                // Calculate item total
                $quantity = floatval($item['quantity']);
                $unitCost = floatval($item['unitCost']);
                $itemTotal = $quantity * $unitCost;
                $totalAmount += $itemTotal;
                
                error_log("Item $index - productId: {$item['productId']}, qty: $quantity, unitCost: $unitCost, total: $itemTotal");
            }
            
            error_log("SUCCESS: Found supplierId: " . $data['supplierId'] . ", warehouseId: " . $data['warehouseId']);
            error_log("Total amount calculated: " . $totalAmount);
            
            // Begin transaction
            $transaction = Yii::app()->db->beginTransaction();
            
            try {
                // Create purchase model
                $purchase = new Purchase();
                $purchase->supplierId = trim($data['supplierId']);
                $purchase->warehouseId = trim($data['warehouseId']);
                $purchase->totalAmount = $totalAmount;
                
                // Set status (default to draft if not provided)
                if (isset($data['status']) && !empty(trim($data['status']))) {
                    $purchase->status = strtolower(trim($data['status']));
                } else {
                    $purchase->status = Purchase::STATUS_DRAFT;
                }
                
                // Set createdBy if provided
                if (isset($data['createdBy']) && !empty(trim($data['createdBy']))) {
                    $purchase->createdBy = trim($data['createdBy']);
                }
                
                error_log("Purchase model attributes before save: " . print_r($purchase->attributes, true));
                
                // Save purchase
                if (!$purchase->save()) {
                    $errors = $purchase->getErrors();
                    error_log("Purchase save failed with errors: " . print_r($errors, true));
                    throw new Exception('Failed to save purchase: ' . implode(', ', array_map('reset', $errors)));
                }
                
                error_log("Purchase saved successfully! ID: " . $purchase->id . ", purchaseId: " . $purchase->purchaseId);
                
                // Save purchase items
                $savedItems = [];
                foreach ($data['items'] as $index => $itemData) {
                    $purchaseItem = new PurchaseItem();
                    $purchaseItem->purchaseId = $purchase->purchaseId;
                    $purchaseItem->productId = trim($itemData['productId']);
                    $purchaseItem->quantity = intval($itemData['quantity']);
                    $purchaseItem->unitCost = floatval($itemData['unitCost']);
                    
                    error_log("Saving purchase item $index: " . print_r($purchaseItem->attributes, true));
                    
                    if (!$purchaseItem->save()) {
                        $errors = $purchaseItem->getErrors();
                        error_log("PurchaseItem save failed with errors: " . print_r($errors, true));
                        throw new Exception('Failed to save purchase item for product: ' . $itemData['productId']);
                    }
                    
                    $savedItems[] = [
                        'purchaseItemId' => $purchaseItem->purchaseItemId,
                        'purchaseId' => $purchaseItem->purchaseId,
                        'productId' => $purchaseItem->productId,
                        'quantity' => $purchaseItem->quantity,
                        'unitCost' => $purchaseItem->unitCost,
                        'totalCost' => $purchaseItem->getTotalCost()
                    ];
                    
                    error_log("Purchase item saved successfully: " . $purchaseItem->purchaseItemId);
                }
                
                // Commit transaction
                $transaction->commit();
                
                error_log("Transaction committed successfully. Items saved: " . count($savedItems));
                
                // Get the complete purchase data
                $purchaseData = $purchase->getApiData();
                
                // Return success
                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Purchase created successfully',
                    'data' => $purchaseData
                ]);
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $transaction->rollback();
                error_log("Transaction rolled back due to error: " . $e->getMessage());
                throw $e;
            }
            
        } else {
            // GET request - show example
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Send POST request with JSON',
                'example' => [
                    'supplierId' => 'SUP0000000000000000000001',
                    'warehouseId' => 'WH0000000000000000000001',
                    'status' => 'draft',
                    'createdBy' => 'USER000000000000000000001',
                    'items' => [
                        [
                            'productId' => 'PROD00000000000000000001',
                            'quantity' => 10,
                            'unitCost' => 25.50
                        ],
                        [
                            'productId' => 'PROD00000000000000000002',
                            'quantity' => 5,
                            'unitCost' => 100.00
                        ]
                    ]
                ]
            ]);
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create purchase',
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}




    public function actionIndex()
    {

        // Get purchaseId from query parameter if provided
    $purchaseId = Yii::app()->request->getParam('purchaseId');
    
    // If purchaseId is provided, return single purchase
    if ($purchaseId) {
        return $this->getSinglePurchase($purchaseId);
    }
    

        $connection = Yii::app()->db;
        
        try {
            // Get purchases with manual SQL joins - REMOVED w.address
            $command = $connection->createCommand("
                SELECT 
                    p.*,
                    s.name as supplier_name,
                    s.email as supplier_email,
                    s.phone as supplier_phone,
                    s.address as supplier_address,
                    w.name as warehouse_name,
                    w.location as warehouse_location,
                    u.name as creator_name,
                    u.email as creator_email
                FROM purchases p
                LEFT JOIN suppliers s ON p.supplierId = s.supplierId
                LEFT JOIN warehouses w ON p.warehouseId = w.warehouseId
                LEFT JOIN users u ON p.createdBy = u.userId
                ORDER BY p.createdAt DESC
            ");
            
            $purchases = $command->queryAll();
            
            $data = [];
            foreach ($purchases as $p) {
                // Get items for this purchase
                $itemsCommand = $connection->createCommand("
                    SELECT * FROM purchaseitems 
                    WHERE purchaseId = :purchaseId
                ");
                $itemsCommand->bindParam(':purchaseId', $p['purchaseId']);
                $items = $itemsCommand->queryAll();
                
                $itemsData = [];
                foreach ($items as $item) {
                    // Check for unitCost or unitPrice field
                    $unitPrice = 0;
                    if (isset($item['unitCost'])) {
                        $unitPrice = $item['unitCost'];
                    } elseif (isset($item['unitPrice'])) {
                        $unitPrice = $item['unitPrice'];
                    } elseif (isset($item['cost'])) {
                        $unitPrice = $item['cost'];
                    } elseif (isset($item['price'])) {
                        $unitPrice = $item['price'];
                    }
                    
                    $itemsData[] = [
                        'id' => $item['id'],
                        'purchaseItemId' => isset($item['purchaseItemId']) ? $item['purchaseItemId'] : null,
                        'productId' => $item['productId'],
                        'quantity' => (int)$item['quantity'],
                        'unitPrice' => (float)$unitPrice,
                        'totalPrice' => (float)($item['quantity'] * $unitPrice)
                    ];
                }
                
                // Handle creator data
                $creatorData = null;
                if ($p['createdBy']) {
                    // Try to find the user
                    $userCommand = $connection->createCommand("
                        SELECT * FROM users 
                        WHERE userId = :userId 
                        LIMIT 1
                    ");
                    $userCommand->bindParam(':userId', $p['createdBy']);
                    $creator = $userCommand->queryRow();
                    
                    if ($creator) {
                        $creatorData = [
                            'userId' => $creator['userId'],
                            'name' => $creator['name'],
                            'email' => $creator['email']
                        ];
                    } else {
                        // If not found, get any user for reference
                        $userCommand = $connection->createCommand("SELECT * FROM users LIMIT 1");
                        $anyUser = $userCommand->queryRow();
                        if ($anyUser) {
                            $creatorData = [
                                'userId' => $anyUser['userId'],
                                'name' => $anyUser['name'] . ' (REF)',
                                'email' => $anyUser['email']
                            ];
                        }
                    }
                }
                
                $data[] = [
                    'id' => (int)$p['id'],
                    'purchaseId' => $p['purchaseId'],
                    'supplierId' => $p['supplierId'],
                    'warehouseId' => $p['warehouseId'],
                    'supplierName' => $p['supplier_name'],
                    'warehouseName' => $p['warehouse_name'],
                    'totalAmount' => (float)$p['totalAmount'],
                    'status' => $p['status'],
                    'statusLabel' => $this->getStatusLabelStatic($p['status']),
                    'notes' => $p['notes'],
                    'expectedDelivery' => $p['expectedDelivery'],
                    'createdBy' => $p['createdBy'],
                    'createdAt' => $p['createdAt'],
                    'updatedAt' => $p['updatedAt'],
                    'itemsCount' => count($items),
                    'totalQuantity' => array_sum(array_column($items, 'quantity')),
                    'items' => $itemsData,
                    'supplier' => $p['supplier_name'] ? [
                        'supplierId' => $p['supplierId'],
                        'name' => $p['supplier_name'],
                        'email' => $p['supplier_email'],
                        'phone' => $p['supplier_phone'],
                        'address' => $p['supplier_address']
                    ] : null,
                    'warehouse' => $p['warehouse_name'] ? [
                        'warehouseId' => $p['warehouseId'],
                        'name' => $p['warehouse_name'],
                        'location' => $p['warehouse_location']
                        // Removed address as it doesn't exist in warehouses table
                    ] : null,
                    'creator' => $creatorData
                ];
            }
            
            $this->sendJson(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            error_log("Purchase index error: " . $e->getMessage());
            $this->sendJson([
                'success' => false, 
                'message' => 'Database error',
                'error' => $e->getMessage()
            ]);
        }
    }

        /**
         * Helper method to get status label
         */
        private function getStatusLabelStatic($status)
        {
            $statusLabels = [
                'draft' => 'Draft',
                'pending' => 'Pending Approval', 
                'ordered' => 'Ordered',
                'partial' => 'Partially Received',
                'received' => 'Fully Received',
                'cancelled' => 'Cancelled',
                'closed' => 'Closed',
                'completed' => 'Completed'
            ];
            
            return isset($statusLabels[$status]) ? $statusLabels[$status] : $status;
        }


        public function actionGetPurchase($purchaseId)
{
    $connection = Yii::app()->db;
    
    try {
        // Get purchase with manual SQL joins - REMOVED w.address
        $command = $connection->createCommand("
            SELECT 
                p.*,
                s.name as supplier_name,
                s.email as supplier_email,
                s.phone as supplier_phone,
                s.address as supplier_address,
                w.name as warehouse_name,
                w.location as warehouse_location,
                u.name as creator_name,
                u.email as creator_email
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplierId = s.supplierId
            LEFT JOIN warehouses w ON p.warehouseId = w.warehouseId
            LEFT JOIN users u ON p.createdBy = u.userId
            WHERE p.purchaseId = :purchaseId
        ");
        $command->bindParam(':purchaseId', $purchaseId);
        $purchase = $command->queryRow();
        
        if (!$purchase) {
            $this->sendJson(['success' => false, 'message' => 'Purchase not found']);
            return;
        }
        
        // Get items for this purchase
        $itemsCommand = $connection->createCommand("
            SELECT * FROM purchaseitems 
            WHERE purchaseId = :purchaseId
        ");
        $itemsCommand->bindParam(':purchaseId', $purchaseId);
        $items = $itemsCommand->queryAll();
        
        $itemsData = [];
        foreach ($items as $item) {
            // Check for unitCost or unitPrice field
            $unitPrice = 0;
            if (isset($item['unitCost'])) {
                $unitPrice = $item['unitCost'];
            } elseif (isset($item['unitPrice'])) {
                $unitPrice = $item['unitPrice'];
            } elseif (isset($item['cost'])) {
                $unitPrice = $item['cost'];
            } elseif (isset($item['price'])) {
                $unitPrice = $item['price'];
            }
            
            $itemsData[] = [
                'id' => $item['id'],
                'purchaseItemId' => isset($item['purchaseItemId']) ? $item['purchaseItemId'] : null,
                'productId' => $item['productId'],
                'quantity' => (int)$item['quantity'],
                'unitPrice' => (float)$unitPrice,
                'totalPrice' => (float)($item['quantity'] * $unitPrice)
            ];
        }
        
        // Handle creator data
        $creatorData = null;
        if ($purchase['createdBy']) {
            // Try to find the user
            $userCommand = $connection->createCommand("
                SELECT * FROM users 
                WHERE userId = :userId 
                LIMIT 1
            ");
            $userCommand->bindParam(':userId', $purchase['createdBy']);
            $creator = $userCommand->queryRow();
            
            if ($creator) {
                $creatorData = [
                    'userId' => $creator['userId'],
                    'name' => $creator['name'],
                    'email' => $creator['email']
                ];
            } else {
                // If not found, get any user for reference
                $userCommand = $connection->createCommand("SELECT * FROM users LIMIT 1");
                $anyUser = $userCommand->queryRow();
                if ($anyUser) {
                    $creatorData = [
                        'userId' => $anyUser['userId'],
                        'name' => $anyUser['name'] . ' (REF)',
                        'email' => $anyUser['email']
                    ];
                }
            }
        }
        
        $data = [
            'id' => (int)$purchase['id'],
            'purchaseId' => $purchase['purchaseId'],
            'supplierId' => $purchase['supplierId'],
            'warehouseId' => $purchase['warehouseId'],
            'supplierName' => $purchase['supplier_name'],
            'warehouseName' => $purchase['warehouse_name'],
            'totalAmount' => (float)$purchase['totalAmount'],
            'status' => $purchase['status'],
            'statusLabel' => $this->getStatusLabelStatic($purchase['status']),
            'notes' => $purchase['notes'],
            'expectedDelivery' => $purchase['expectedDelivery'],
            'createdBy' => $purchase['createdBy'],
            'createdAt' => $purchase['createdAt'],
            'updatedAt' => $purchase['updatedAt'],
            'itemsCount' => count($items),
            'totalQuantity' => array_sum(array_column($items, 'quantity')),
            'items' => $itemsData,
            'supplier' => $purchase['supplier_name'] ? [
                'supplierId' => $purchase['supplierId'],
                'name' => $purchase['supplier_name'],
                'email' => $purchase['supplier_email'],
                'phone' => $purchase['supplier_phone'],
                'address' => $purchase['supplier_address']
            ] : null,
            'warehouse' => $purchase['warehouse_name'] ? [
                'warehouseId' => $purchase['warehouseId'],
                'name' => $purchase['warehouse_name'],
                'location' => $purchase['warehouse_location']
                // No address column in warehouses table
            ] : null,
            'creator' => $creatorData
        ];
        
        $this->sendJson(['success' => true, 'data' => $data]);
        
    } catch (Exception $e) {
        error_log("Get purchase error: " . $e->getMessage());
        $this->sendJson([
            'success' => false, 
            'message' => 'Database error',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Helper method to get single purchase
 */
private function getSinglePurchase($purchaseId)
{
    $connection = Yii::app()->db;
    
    try {
        // Get purchase with manual SQL joins
        $command = $connection->createCommand("
            SELECT 
                p.*,
                s.name as supplier_name,
                s.email as supplier_email,
                s.phone as supplier_phone,
                s.address as supplier_address,
                w.name as warehouse_name,
                w.location as warehouse_location,
                u.name as creator_name,
                u.email as creator_email
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplierId = s.supplierId
            LEFT JOIN warehouses w ON p.warehouseId = w.warehouseId
            LEFT JOIN users u ON p.createdBy = u.userId
            WHERE p.purchaseId = :purchaseId
        ");
        $command->bindParam(':purchaseId', $purchaseId);
        $purchase = $command->queryRow();
        
        if (!$purchase) {
            $this->sendJson(['success' => false, 'message' => 'Purchase not found123']);
            return;
        }
        
        // Get items for this purchase
        $itemsCommand = $connection->createCommand("
            SELECT * FROM purchaseitems 
            WHERE purchaseId = :purchaseId
        ");
        $itemsCommand->bindParam(':purchaseId', $purchaseId);
        $items = $itemsCommand->queryAll();
        
        $itemsData = [];
        foreach ($items as $item) {
            // Check for unitCost or unitPrice field
            $unitPrice = 0;
            if (isset($item['unitCost'])) {
                $unitPrice = $item['unitCost'];
            } elseif (isset($item['unitPrice'])) {
                $unitPrice = $item['unitPrice'];
            } elseif (isset($item['cost'])) {
                $unitPrice = $item['cost'];
            } elseif (isset($item['price'])) {
                $unitPrice = $item['price'];
            }
            
            $itemsData[] = [
                'id' => $item['id'],
                'purchaseItemId' => isset($item['purchaseItemId']) ? $item['purchaseItemId'] : null,
                'productId' => $item['productId'],
                'quantity' => (int)$item['quantity'],
                'unitPrice' => (float)$unitPrice,
                'totalPrice' => (float)($item['quantity'] * $unitPrice)
            ];
        }
        
        // Handle creator data
        $creatorData = null;
        if ($purchase['createdBy']) {
            // Try to find the user
            $userCommand = $connection->createCommand("
                SELECT * FROM users 
                WHERE userId = :userId 
                LIMIT 1
            ");
            $userCommand->bindParam(':userId', $purchase['createdBy']);
            $creator = $userCommand->queryRow();
            
            if ($creator) {
                $creatorData = [
                    'userId' => $creator['userId'],
                    'name' => $creator['name'],
                    'email' => $creator['email']
                ];
            } else {
                // If not found, get any user for reference
                $userCommand = $connection->createCommand("SELECT * FROM users LIMIT 1");
                $anyUser = $userCommand->queryRow();
                if ($anyUser) {
                    $creatorData = [
                        'userId' => $anyUser['userId'],
                        'name' => $anyUser['name'] . ' (REF)',
                        'email' => $anyUser['email']
                    ];
                }
            }
        }
        
        $data = [
            'id' => (int)$purchase['id'],
            'purchaseId' => $purchase['purchaseId'],
            'supplierId' => $purchase['supplierId'],
            'warehouseId' => $purchase['warehouseId'],
            'supplierName' => $purchase['supplier_name'],
            'warehouseName' => $purchase['warehouse_name'],
            'totalAmount' => (float)$purchase['totalAmount'],
            'status' => $purchase['status'],
            'statusLabel' => $this->getStatusLabelStatic($purchase['status']),
            'notes' => $purchase['notes'],
            'expectedDelivery' => $purchase['expectedDelivery'],
            'createdBy' => $purchase['createdBy'],
            'createdAt' => $purchase['createdAt'],
            'updatedAt' => $purchase['updatedAt'],
            'itemsCount' => count($items),
            'totalQuantity' => array_sum(array_column($items, 'quantity')),
            'items' => $itemsData,
            'supplier' => $purchase['supplier_name'] ? [
                'supplierId' => $purchase['supplierId'],
                'name' => $purchase['supplier_name'],
                'email' => $purchase['supplier_email'],
                'phone' => $purchase['supplier_phone'],
                'address' => $purchase['supplier_address']
            ] : null,
            'warehouse' => $purchase['warehouse_name'] ? [
                'warehouseId' => $purchase['warehouseId'],
                'name' => $purchase['warehouse_name'],
                'location' => $purchase['warehouse_location']
            ] : null,
            'creator' => $creatorData
        ];
        
        $this->sendJson(['success' => true, 'data' => $data]);
        
    } catch (Exception $e) {
        error_log("Get single purchase error: " . $e->getMessage());
        $this->sendJson([
            'success' => false, 
            'message' => 'Database error',
            'error' => $e->getMessage()
        ]);
    }
}



        }