<?php

/**
 * Async Client 异步任务客户端配置
 */
return [
    'class' => 'itzlib.plugins.swoole.AsyncClient',
    'cluster' => ConfUtil::get('Async-dashboard.cluster-arr', true),
    'alertUser' => ConfUtil::get('Async-dashboard.alertUser-arr', true)
];
