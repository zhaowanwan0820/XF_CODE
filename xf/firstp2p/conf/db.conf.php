<?php

return array(
    'DB_HOST'=>'m1-p2p.wxlc.org',
    'DB_NAME'=>'firstp2p',
    'DB_USER'=>'wxlc_pro',
    'DB_PWD'=>'lzBbkfLiwEQZU8WXWww7',
    'DB_PORT'=>'3322',
    'DB_PREFIX'=>'firstp2p_',

    //firstp2p主业务库
    'firstp2p_db' => array(
        'master' => array(
            'name' => 'firstp2p',
            'host' => 'rm-2zev331edt40lf44woo.mysql.rds.aliyuncs.com',
            'port' => '3306',
            'user' => 'superadmin_xf',
            'password' => 'CY7x2Z8EXu%@!z6M',
            'prefix' => 'firstp2p_',
        ),
        'slave' => array(
            'name' => 'firstp2p',
            'host' => 'rm-2zev331edt40lf44woo.mysql.rds.aliyuncs.com',
            'port' => '3306',
            'user' => 'superadmin_xf',
            'password' => 'CY7x2Z8EXu%@!z6M',
            'prefix' => 'firstp2p_',
        ),
        'vipslave' => array(
            'name' => 'firstp2p',
            'host' => 'rm-2zev331edt40lf44woo.mysql.rds.aliyuncs.com',
            'port' => '3306',
            'user' => 'superadmin_xf',
            'password' => 'CY7x2Z8EXu%@!z6M',
            'prefix' => 'firstp2p_',
        ),
        'adminslave' => array(
            'name' => 'firstp2p',
            'host' => 'rm-2zev331edt40lf44woo.mysql.rds.aliyuncs.com',
            'port' => '3306',
            'user' => 'superadmin_xf',
            'password' => 'CY7x2Z8EXu%@!z6M',
            'prefix' => 'firstp2p_',
        ),
    ),
    //p2p删除备份库
    'firstp2p_deleted_db' => array(
        'master' => array(
            'name' => 'firstp2p_deleted',
            'host' => 'archiver.dbs.wxlc.org',
            'port' => '3306',
            'user' => 'archiver_u',
            'password' => 'sNlwkXoVAW5KnHbi',
            'prefix' => '',
        ),
        'slave' => array(
            'name' => 'firstp2p_deleted',
            'host' => 'r-history.wxlc.org',
            'port' => '3306',
            'user' => 'archiver_u',
            'password' => 'sNlwkXoVAW5KnHbi',
            'prefix' => '',
        ),
    ),
    //p2p迁移备份库
    'firstp2p_moved_db' => array(
        'master' => array(
            'name' => 'firstp2p_moved',
            'host' => 'archiver.dbs.wxlc.org',
            'port' => '3306',
            'user' => 'archiver_u',
            'password' => 'sNlwkXoVAW5KnHbi',
            'prefix' => '',
        ),
        'slave' => array(
            'name' => 'firstp2p_moved',
            'host' => 'r-history.wxlc.org',
            'port' => '3306',
            'user' => 'archiver_u',
            'password' => 'sNlwkXoVAW5KnHbi',
            'prefix' => '',
        ),
    ),
    //资管相关
    'firstp2p_payment_db' => array(
        'master' => array(
            'name' => 'firstp2p_payment',
            'host' => 'w-payment.dbs.wxlc.org',
            'port' => '3307',
            'user' => 'pay_pro',
            'password' => '8FtAADr40wF981AC',
            'prefix' => '',
        ),
        'slave' => array(
            'name' => 'firstp2p_payment',
            'host' => 'r-payment.dbs.wxlc.org',
            'port' => '3307',
            'user' => 'pay_pro',
            'password' => '8FtAADr40wF981AC',
            'prefix' => '',
        ),
    ),
    //合同
    'contract_db' => array(
        'master' => array(
            'name' => 'contract_service',
            'host' => 'w-contract.dbs.wxlc.org',
            'port' => '3307',
            'user' => 'cs_pro',
            'password' => '7AbF46CFB1fD489',
            'prefix' => '',
        ),
    ),
    //站内信
    'msg_box_db' => array(
        'master' => array(
            'name' => 'firstp2p_msg_box',
            'host' => 'w-msgbox.dbs.wxlc.org',
            'port' => '3306',
            'user' => 'p2p_msgbox_pro',
            'password' => '8VEXqmZ4aEPkg3uo',
            'prefix' => '',
        ),
    ),
    // UserProfile
    'profile_db' => array(
        'master' => array(
            'name' => 'firstp2p_profile',
            'host' => 'w-userprofile.dbs.wxlc.org',
            'port' => '3308',
            'user' => 'profile_pro',
            'password' => '8AAD0a40eF981dC',
            'prefix' => '',
        ),
    ),
    //itil
    'itil_db' => array(
        'master' => array(
            'name' => 'firstp2p_itil',
            'host' => 'w-itil.dbs.wxlc.org',
            'port' => '3306',
            'user' => 'firstp2p_itil_u',
            'password' => 'LhsmxCDyi1Rf96W1iu0C',
            'prefix' => '',
        ),
    ),
    //marketing
    'marketing_db'  => array(
        'master' => array(
            'name'    =>  'marketing',
            'host'  =>  'w-marketing.dbs.wxlc.org',
            'user'  =>  'mark_pro',
            'password'  =>  '0C1EAD9Es4D5c3AB',
            'port'  =>  '3308',
            'prefix' => '',
        ),
        'slave' => array(
            'name'  =>  'marketing',
            'host'  =>  'r-marketing.dbs.wxlc.org',
            'user'  =>  'mark_pro',
            'password'  =>  '0C1EAD9Es4D5c3AB',
            'port'  =>  '3308',
            'prefix'  => '',
        ),
    ),
    //vip
    'vip_db' => array(
        'master' => array(
            'name' => 'firstp2p_vip',
            'host' => 'w-p2p-vip.dbs.wxlc.org',
            'port' => '3307',
            'user' => 'p2p_vip_pro',
            'password' => 'WeKGUZwe3gTMaKeg',
            'prefix' => 'firstp2p_',
        ),
        'slave' => array(
            'name' => 'firstp2p_vip',
            'host' => 'r-p2p-vip.dbs.wxlc.org',
            'port' => '3307',
            'user' => 'p2p_vip_r',
            'password' => 'fIn5yeGY2PZazhHk',
            'prefix' => 'firstp2p_',
        ),
    ),
    //candy
    'candy_db' => array(
        'master' => array(
            'name' => 'firstp2p_candy',
            'host' => 'w-candy.dbs.wxlc.org',
            'port' => '3307',
            'user' => 'candy_pro',
            'password' => 'FA6DpE5E6sA959C',
            'prefix' => '',
        ),
    ),

    'firstp2p_thirdparty_db' => array(
        'master' => array(
            'name' => 'firstp2p_thirdparty',
            'host' => 'w-p2pthirdparty.mysql.ncfrds.com',
            'port' => '3307',
            'user' => 'thirdparty_pro',
            'password' => '56BD9B1e3C74FD9',
            'prefix' => 'firstp2p_',
            ),
        ),


);
