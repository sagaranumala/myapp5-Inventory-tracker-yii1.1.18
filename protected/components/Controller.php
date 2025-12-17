<?php
class Controller extends CController
{
    public $layout = '//layouts/column1';
    public $menu = array();
    public $breadcrumbs = array();

    public function init()
    {
        parent::init();
        // Only set language for non-API requests
        if (!$this->isApiRequest()) {
            $this->setLanguage();
        }
    }

    protected function setLanguage()
    {
        $languages = array_keys(Yii::app()->params['languages'] ?? []);

        // 1. GET parameter - check both 'lang' and 'language'
        $lang = Yii::app()->request->getParam('lang');
        if (!$lang) {
            $lang = Yii::app()->request->getParam('language');
        }

        // 2. Session
        if (!$lang && Yii::app()->user->hasState('language')) {
            $lang = Yii::app()->user->getState('language');
        }

        // 3. Cookie
        if (!$lang && isset($_COOKIE['language'])) {
            $lang = $_COOKIE['language'];
        }

        // 4. Browser detect
        if (!$lang) {
            $lang = $this->detectBrowserLanguage();
        }

        // Validate and set
        if ($lang && in_array($lang, $languages)) {
            Yii::app()->language = $lang;
            Yii::app()->user->setState('language', $lang);
            
            // Use Yii's cookie management to avoid header issues
            $cookie = new CHttpCookie('language', $lang);
            $cookie->expire = time() + 86400*365;
            $cookie->path = '/';
            Yii::app()->request->cookies['language'] = $cookie;
        } else {
            // Default language
            Yii::app()->language = Yii::app()->params['defaultLanguage'] ?? 'en';
        }
    }

    protected function detectBrowserLanguage()
    {
        $languages = array_keys(Yii::app()->params['languages'] ?? []);
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if (in_array($browserLang, $languages)) {
                return $browserLang;
            }
        }
        return Yii::app()->params['defaultLanguage'] ?? 'en';
    }

    public function beforeAction($action)
    {
        // Detect API request
        if ($this->isApiRequest()) {
            // Disable redirect for API requests
            Yii::app()->user->loginUrl = null;
            // Send CORS headers
            $this->setCorsHeaders();
            
            // If the controller defines protected API actions, check guest
            if (method_exists($this, 'requireAuthForApi') && $this->requireAuthForApi($action)) {
                if (Yii::app()->user->isGuest) {
                    $this->sendJson([
                        'success' => false,
                        'message' => 'Unauthorized'
                    ], 401);
                }
            }
        } else {
            // Set language for non-API requests (fallback if init didn't work)
            $this->setLanguage();
        }

        return parent::beforeAction($action);
    }
	
    protected function isApiRequest()
    {
        $path = Yii::app()->request->getPathInfo();
        return strpos($path, 'api/') === 0
            || Yii::app()->request->isAjaxRequest
            || isset($_SERVER['HTTP_AUTHORIZATION'])
            || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    }

    protected function setCorsHeaders()
    {
        $allowedOrigins = ['http://localhost:3000'];

        if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Max-Age: 86400");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            Yii::app()->end();
        }
    }

    protected function sendJson($data, $statusCode = 200)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($statusCode);
        }
        echo json_encode($data, JSON_PRETTY_PRINT);
        Yii::app()->end();
    }
}
