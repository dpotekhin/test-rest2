<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-api',
    'name' => 'My Yii2 API',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'api\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'basePath' => '@app/modules/v1',
            'class' => 'app\modules\v1\Module'
        ]
    ],
    'components' => [
        'user' => [
            'identityClass' => 'common\models\User',
            'enableSession' => !false,
            'enableAutoLogin' => !false,
            'loginUrl' => null,
        ],
        'request' => [
            'class' => '\yii\web\Request',
            'enableCookieValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                '<_m:[\w-]+>/<_c:[\w-]+>' => '<_m>/<_c>/index',                         // v1/user
                '<_m:[\w-]+>/<_c:[\w-]+>/<id:\d+>' => '<_m>/<_c>/index',                // v1/user/1
                '<_m:[\w-]+>/<_c:[\w-]+>/<_a:[\w-]+>' => '<_m>/<_c>/<_a>',              // v1/user/login
                '<_m:[\w-]+>/<_c:[\w-]+>/<id:\d+>/<_a:[\w-]+>' => '<_m>/<_c>/<_a>',     // v1/user/1/delete
            ],
        ],
    ],
    'params' => $params,
];