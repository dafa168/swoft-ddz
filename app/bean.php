<?php

use App\Common\DbSelector;
use App\Process\MonitorProcess;
use Swoft\Db\Pool;
use Swoft\Http\Server\HttpServer;
use Swoft\Task\Swoole\SyncTaskListener;
use Swoft\Task\Swoole\TaskListener;
use Swoft\Task\Swoole\FinishListener;
use Swoft\Rpc\Client\Client as ServiceClient;
use Swoft\Rpc\Client\Pool as ServicePool;
use Swoft\Rpc\Server\ServiceServer;
use Swoft\Http\Server\Swoole\RequestListener;
use Swoft\WebSocket\Server\WebSocketServer;
use Swoft\Server\SwooleEvent;
use Swoft\Db\Database;
use Swoft\Redis\RedisDb;

return [
    'config'   => [
        'path' => __DIR__ . '/../config',
//        'env' => 'dev',
    ],
    'httpServer'       => [
        'class'    => HttpServer::class,
        'port'     => 18306,
        'listener' => [
            'rpc' => bean('rpcServer')
        ],
        'process' => [
//            'monitor' => bean(MonitorProcess::class)
        ],
        'on'       => [
            SwooleEvent::TASK   => bean(SyncTaskListener::class),  // Enable sync task
            SwooleEvent::TASK   => bean(TaskListener::class),  // Enable task must task and finish event
            SwooleEvent::FINISH => bean(FinishListener::class)
        ],
        /* @see HttpServer::$setting */
        'setting'  => [
            'task_worker_num'       => 12,
            'task_enable_coroutine' => true
        ]
    ],
    'httpDispatcher'   => [
        // Add global http middleware
        'middlewares' => [
            // Allow use @View tag
//            \Swoft\View\Middleware\ViewMiddleware::class,
            \App\Http\Middleware\SomeMiddleware::class,
//            \Swoft\Smarty\Middleware\SmartyMiddleware::class
        ],
    ],
    'db'               => [
        'class'    => Database::class,
        'dsn'      => 'mysql:dbname=accounts_mj;host=192.168.22.34:3307',
        'username' => 'web',
        'password' => '111www',
        'charset'   => 'utf8',
    ],
    'db.pool'         => [
        'class'    => Pool::class,
        'database' => bean('db'),
        'minActive'   => 10,
        'maxActive'   => 20,
        'maxWait'     => 0,
        'maxWaitTime' => 0,
        'maxIdleTime' => 60,
    ],
    'migrationManager' => [
        'migrationPath' => '@app/Migration',
    ],
    'redis'            => [
        'class'    => RedisDb::class,
        'host'     => '192.168.1.155',
        'port'     => 6379,
        'database' => 0,
        'option' => [
            'prefix' => ''
        ]
    ],
    'redis.pool'     => [
        'class'   => \Swoft\Redis\Pool::class,
        'redisDb' => \bean('redis'),
        'minActive'   => 10,
        'maxActive'   => 20,
        'maxWait'     => 0,
        'maxWaitTime' => 0,
        'maxIdleTime' => 60,
    ],
    'user'             => [
        'class'   => ServiceClient::class,
        'host'    => '127.0.0.1',
        'port'    => 18307,
        'setting' => [
            'timeout'         => 0.5,
            'connect_timeout' => 1.0,
            'write_timeout'   => 10.0,
            'read_timeout'    => 0.5,
        ],
        'packet'  => bean('rpcClientPacket')
    ],
    'user.pool'        => [
        'class'  => ServicePool::class,
        'client' => bean('user')
    ],
    'rpcServer'        => [
        'class' => ServiceServer::class,
    ],
    'wsServer'         => [
        'class'   => WebSocketServer::class,
        'port'    => 18308,
        'on'      => [
            // 开启处理http请求支持
            SwooleEvent::REQUEST => bean(RequestListener::class),
            // 启用任务必须添加 task, finish 事件处理
            SwooleEvent::TASK   => bean(TaskListener::class),
            SwooleEvent::FINISH => bean(FinishListener::class)
        ],
        'listener' => [
            // 引入 tcpServer
            'tcp' => \bean('tcpServer')
        ],
        'debug'   => env('SWOFT_DEBUG', 0),
        /* @see WebSocketServer::$setting */
        'setting' => [
            'log_file' => alias('@runtime/swoole.log'),
            'document_root' => dirname(__DIR__) . '/public/',
            'enable_static_handler' => true,
            'worker_num'            => 2,
            // 任务需要配置 task worker
            'task_worker_num'       => 4,
            'task_enable_coroutine' => true,
            'max_request'           => 10000,
            'package_max_length'    => 20480
        ],
    ],
    'tcpServer'         => [
        'port'  => 18309,
        'debug' => 1,
        'on'      => [
            SwooleEvent::RECEIVE => bean(App\Common\TcpReceiveListener::class)
        ],
        'setting' => [
            'log_file' => alias('@runtime/swoole.log'),
            'worker_num'            => 2,
            // 任务需要配置 task worker
            'task_worker_num'       => 4,
            'task_enable_coroutine' => true,
            'max_request'           => 10000,
            'package_max_length'    => 20480
        ],
    ],
    'cliRouter'         => [
        // 'disabledGroups' => ['demo', 'test'],
    ],
    'processPool' => [
        'class' => \Swoft\Process\ProcessPool::class,
        'workerNum' => 3
    ],
    'lineFormatter'      => [
        'format'     => '%datetime% [%level_name%] [%channel%] [%event%] [tid:%tid%] [cid:%cid%] [traceid:%traceid%] [spanid:%spanid%] [parentid:%parentid%] %messages%',
        'dateFormat' => 'Y-m-d H:i:s',
    ],
    'noticeHandler'      => [
        'class'     => \Swoft\Log\Handler\FileHandler::class,
        'logFile'   => '@runtime/logs/notice.log',
        'formatter' => \bean('lineFormatter'),
        'levels'    => 'notice,info,debug,trace',
    ],
    'applicationHandler' => [
        'class'     => \Swoft\Log\Handler\FileHandler::class,
        'logFile'   => '@runtime/logs/error.log',
        'formatter' => \bean('lineFormatter'),
        'levels'    => 'error,warning',
    ],
    'logger'             => [
        'flushRequest' => false,
        'enable'       => true,
        'handlers'     => [
            'application' => \bean('applicationHandler'),
            'notice'      => \bean('noticeHandler'),
        ],
    ]
];
