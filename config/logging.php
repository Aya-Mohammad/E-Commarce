<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'performance' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/performance.log'),
            'level'  => 'info',
            'days'   => 7,
        ],
        
        // ========== ORDER LOGS ==========
        'order_race' => [
            'driver' => 'single',
            'path'   => storage_path('logs/order/RaceCondition.log'),
            'level'  => 'debug',
        ],
        'order_race_aop' => [
            'driver' => 'single',
            'path'   => storage_path('logs/order/RaceConditionAOP.log'),
            'level'  => 'debug',
        ],
        'order_duplicate' => [
            'driver' => 'single',
            'path'   => storage_path('logs/order/DuplicateCheckout.log'),
            'level'  => 'debug',
        ],
        'order_duplicate_aop' => [
            'driver' => 'single',
            'path'   => storage_path('logs/order/DuplicateCheckoutAOP.log'),
            'level'  => 'debug',
        ],
        'order_stress' => [
            'driver' => 'single',
            'path'   => storage_path('logs/order/Stress.log'),
            'level'  => 'debug',
        ],
        'order_stress_aop' => [
            'driver' => 'single',
            'path'   => storage_path('logs/order/StressAOP.log'),
            'level'  => 'debug',
        ],

        // ========== AUTH LOGS ==========
        'auth_login' => [
            'driver' => 'single',
            'path'   => storage_path('logs/auth/Login.log'),
            'level'  => 'debug',
        ],
        'auth_login_aop' => [
            'driver' => 'single',
            'path'   => storage_path('logs/auth/LoginAOP.log'),
            'level'  => 'debug',
        ],

        // ========== SEARCH LOGS ==========
        'search' => [
            'driver' => 'single',
            'path'   => storage_path('logs/search/Search.log'),
            'level'  => 'debug',
        ],
        'search_aop' => [
            'driver' => 'single',
            'path'   => storage_path('logs/search/SearchAOP.log'),
            'level'  => 'debug',
        ],

        // أضيفي هذه في logging.php

        // ========== CART LOGS ==========
        'cart_race' => [
            'driver' => 'single',
            'path'   => storage_path('logs/cart/RaceCondition.log'),
            'level'  => 'debug',
        ],
        'cart_race_aop' => [
            'driver' => 'single',
            'path'   => storage_path('logs/cart/RaceConditionAOP.log'),
            'level'  => 'debug',
        ],
        'cart_stress' => [
            'driver' => 'single',
            'path'   => storage_path('logs/cart/Stress.log'),
            'level'  => 'debug',
        ],
        'cart_stress_aop' => [
            'driver' => 'single',
            'path'   => storage_path('logs/cart/StressAOP.log'),
            'level'  => 'debug',
        ],

        // ========== PRODUCT LOGS ==========
        'product_show' => [
            'driver' => 'single',
            'path'   => storage_path('logs/product/ShowProduct.log'),
            'level'  => 'debug',
        ],
        'product_show_aop' => [
            'driver' => 'single',
            'path'   => storage_path('logs/product/ShowProductAOP.log'),
            'level'  => 'debug',
        ],


        // ========== COMBINED 100-USER TEST ==========
        'combined' => [
            'driver' => 'single',
            'path'   => storage_path('logs/combined/Combined.log'),
            'level'  => 'debug',
        ],
        'combined_aop' => [
            'driver' => 'single',
            'path'   => storage_path('logs/combined/CombinedAOP.log'),
            'level'  => 'debug',
        ],

        'combined_search' => [
            'driver' => 'single',
            'path' => storage_path('logs/combined/search.log'),
        ],

        'combined_search_aop' => [
            'driver' => 'single',
            'path' => storage_path('logs/combined/search_aop.log'),
        ],

        'combined_product_show' => [
            'driver' => 'single',
            'path' => storage_path('logs/combined/product_show.log'),
        ],

        'combined_product_show_aop' => [
            'driver' => 'single',
            'path' => storage_path('logs/combined/product_show_aop.log'),
        ],

        'combined_cart' => [
            'driver' => 'single',
            'path' => storage_path('logs/combined/cart.log'),
        ],

        'combined_cart_aop' => [
            'driver' => 'single',
            'path' => storage_path('logs/combined/cart_aop.log'),
        ],

        'combined_auth_login' => [
            'driver' => 'single',
            'path' => storage_path('logs/combined/auth_login.log'),
            'level' => 'debug',
        ],

        'combined_auth_login_aop' => [
            'driver' => 'single',
            'path' => storage_path('logs/combined/auth_login_aop.log'),
            'level' => 'debug',
        ],

        'combined_order' => [
            'driver' => 'single',
            'path' => storage_path('logs/combined/order.log'),
        ],

        'combined_order_aop' => [
            'driver' => 'single',
            'path' => storage_path('logs/combined/order_aop.log'),
        ],

    ],

];
