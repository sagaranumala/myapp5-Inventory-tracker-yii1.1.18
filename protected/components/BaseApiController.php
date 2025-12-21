<?php
class BaseApiController extends CController
{
    public $enableCsrfValidation = false; // disable CSRF for API

    protected function sendJson($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data, JSON_PRETTY_PRINT);
        Yii::app()->end();
    }

     protected function sendResponse($success, $message, $data = null, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ]);

        Yii::app()->end();
    }
    /**
     * Get current authenticated user from JWT
     */
    protected function getCurrentUser()
    {
        $jwt = Yii::app()->jwt; // register JwtHelper as 'jwt' component
        return $jwt->getCurrentUser();
    }
}
