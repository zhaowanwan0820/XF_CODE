<?php

/**
 * yiic 加载文件。
 *
 * 该文件内容将在 Async Server WorkerStart 时被载入。
 * 自动读取运行对应 APP 下的 bin/yiic.php 文件, 初始化 runtime 环境。
 *
 * @author liuhaiyang (liuhaiyang@xxx.com)
 */

define('DS', DIRECTORY_SEPARATOR);
/* 预定义 APP_DIR 目录, trunk/itouzi | trunk/dashboard */
define('APP_DIR', dirname(dirname(dirname(ASYNC_DIR))));

$yiicFile = dirname(ASYNC_DIR) . DS . 'yiic.php';
$yiicCodes = file($yiicFile);

$yiicCodes = array_filter($yiicCodes, function ($code) {
    /* 将「载入 framework/yiic.php 」的代码删除, 防止 ConsoleApplication 被自动运行 (run)。*/
    if (strpos($code, 'framework/yiic.php') !== false
        || strpos($code, '"APP_DIR"') !== false
        || strpos($code, '<?php') !== false) {
        return false;
    } else {
        return true;
    }
});

/* 执行应用目录下的 yiic code */
eval(implode(' ', $yiicCodes));

// 以下为框架内的 thirdlib/yii/framework/yiic.php 代码
/****************** START modify from framework/yiic.php ****************************/
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

require_once(WWW_DIR . '/thirdlib/yii/framework/yii.php');

$app = Yii::createConsoleApplication($config);
$app->commandRunner->addCommands(YII_PATH . '/cli/commands');

$env = @getenv('YII_CONSOLE_COMMANDS');
if (!empty($env))
    $app->commandRunner->addCommands($env);
/***************** END modify from framework/yiic.php *****/
