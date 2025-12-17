<?php
// Load environment variables (Docker)
$env = getenv();

defined('APP_ENV') or define('APP_ENV', $env['APP_ENV'] ?? 'dev');
defined('DB_HOST') or define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
defined('DB_NAME') or define('DB_NAME', $env['DB_NAME'] ?? '');
defined('DB_USER') or define('DB_USER', $env['DB_USER'] ?? '');
defined('DB_PASS') or define('DB_PASS', $env['DB_PASS'] ?? '');
defined('SMTP_EMAIL') or define('SMTP_EMAIL', $env['SMTP_EMAIL'] ?? '');
defined('SMTP_PASSWORD') or define('SMTP_PASSWORD', $env['SMTP_PASSWORD'] ?? '');

return array(

    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'My Web Application',

    'sourceLanguage' => 'en',
    'language' => 'en',

    // ---------------------------------------------------
    // IMPORTS
    // ---------------------------------------------------
    'import' => array(
        'application.models.*',
        'application.components.*',
        'application.controllers.*',
    ),

    // ---------------------------------------------------
    // MODULES
    // ---------------------------------------------------
    'modules' => array(
        'gii' => array(
            'class' => 'system.gii.GiiModule',
            'password' => 'gii123',
            'ipFilters' => array('*'),
        ),
    ),

    // ---------------------------------------------------
    // COMPONENTS
    // ---------------------------------------------------
    
    'components' => array(

        // User
        'user' => array(
            'allowAutoLogin' => true,
        ),

        // JWT
        'jwt' => array(
            'class' => 'JwtHelper',
            'secretKey' => getenv('JWT_SECRET') ?: 'your-super-secret-jwt-key-2024',
            'expireTime' => 86400,
        ),

        // Email
        'email' => array(
            'class' => 'application.components.EmailComponent',
            'smtpHost' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
            'smtpUsername' => SMTP_EMAIL,
            'smtpPassword' => SMTP_PASSWORD,
            'fromEmail' => 'noreply@yourdomain.com',
            'fromName' => 'Digital systems',
        ),

        // URL Manager
        'urlManager' => array(
            'urlFormat' => 'get',
            'showScriptName' => true,
            'rules' => array(

                // Blog
                'blogs' => 'blog/index',
                'blog/<id:\d+>' => 'blog/view',
                'blog/create' => 'blog/create',
                'blog/update/<id:\d+>' => 'blog/update',
                'blog/delete/<id:\d+>' => 'blog/delete',
                'my-blogs' => 'blog/myBlogs',

                // Aliases
                'article/<id:\d+>' => 'blog/view',
                'posts' => 'blog/index',
                'write' => 'blog/create',

                // Pages
                'calculator' => 'site/simpleCalc',
                'calc' => 'site/simpleCalc',
                'test-db' => 'site/testDb',

                // API
                'api/users' => 'api/getUsers',
                'api/users/<id:\d+>' => 'api/getUser',
                'api/users/email/<email:.*>' => 'api/getUserByEmail',
                'api/users/userId/<userId:.*>' => 'api/getUserByUserId',
                'api/users/create' => 'api/createUser',
                'api/users/update/<id:\d+>' => 'api/updateUser',
                'api/users/delete/<id:\d+>' => 'api/deleteUser',

                // Default (MUST BE LAST)
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ),
        ),

        // Translations - FIXED
        'messages' => array(
            'class' => 'CPhpMessageSource',
            'basePath' => dirname(__FILE__) . '/../messages',  // FIXED PATH
            'forceTranslation' => true,
            // Optional: For debugging, disable cache
            'cachingDuration' => 0,
            // Optional: Enable extended caching
            // 'cacheID' => 'cache',
        ),


        // Request
        'request' => array(
            'enableCookieValidation' => true,
            'enableCsrfValidation' => true,
        ),

        // Database
        'db' => require(dirname(__FILE__) . '/database.php'),

        // Error handler
        'errorHandler' => array(
            'errorAction' => 'site/error',
        ),

        // Logs
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                ),
            ),
        ),
    ),

    // ---------------------------------------------------
    // PARAMS (ONLY ONE PLACE)
    // ---------------------------------------------------
    'params' => array(
        'adminEmail' => 'webmaster@example.com',
        'salt' => 'your-secret-salt-string',
        'jwtSecret' => 'your-super-secret-jwt-key-32-chars-minimum!',

        // ✅ Language config
        'languages' => array(
            'en' => 'English',
            'ar' => 'العربية',
        ),
        'defaultLanguage' => 'en',  // Add this for consistency
    ),

    // ---------------------------------------------------
    // CORS
    // ---------------------------------------------------
    'onBeginRequest' => function () {

        $allowedOrigins = array('http://localhost:3000');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header("Access-Control-Allow-Credentials: true");
        } else {
            header("Access-Control-Allow-Origin: *");
        }

        header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 86400");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            Yii::app()->end();
        }
    },
);
