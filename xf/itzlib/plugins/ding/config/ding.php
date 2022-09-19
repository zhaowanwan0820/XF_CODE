<?php

return [

    // 默认发送的机器人

    'default' => [
        // 是否要开启机器人，关闭则不再发送消息
        'enabled' => true,
        // 机器人的access_token
        'token' => ConfUtil::get('DING_TOKEN'),
        // 钉钉请求的超时时间
        'timeout' => 5.0
    ],

    'other' => [
        // 是否要开启机器人，关闭则不再发送消息
        'enabled' => true,
        // 机器人的access_token
        'token' => ConfUtil::get('DING_TOKEN'),
        // 钉钉请求的超时时间
        'timeout' => 5.0
    ]

];