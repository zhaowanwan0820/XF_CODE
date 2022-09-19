<?php

return array(
    'DB_HOST'=>'w-ncfph.mysql.ncfrds.com',
    'DB_NAME'=>'ncfph',
    'DB_USER'=>'ncfph_pro',
    'DB_PWD'=>'9718DFw3165pCF1',
    'DB_PORT'=>'3306',
    'DB_PREFIX'=>'firstp2p_',

    //firstp2p主业务库
    'firstp2p_db' => array(
        'master' => array(
            'name' => 'ncfph',
            'host' => 'rm-2zev331edt40lf44woo.mysql.rds.aliyuncs.com',
            'port' => '3306',
            'user' => 'superadmin_xf',
            'password' => 'CY7x2Z8EXu%@!z6M',
            'prefix' => 'firstp2p_',
        ),
        'slave' => array(
            'name' => 'ncfph',
            'host' => 'rm-2zev331edt40lf44woo.mysql.rds.aliyuncs.com',
            'port' => '3306',
            'user' => 'superadmin_xf',
            'password' => 'CY7x2Z8EXu%@!z6M',
            'prefix' => 'firstp2p_',
        ),
        'vipslave' => array(
            'name' => 'ncfph',
            'host' => 'rm-2zev331edt40lf44woo.mysql.rds.aliyuncs.com',
            'port' => '3306',
            'user' => 'superadmin_xf',
            'password' => 'CY7x2Z8EXu%@!z6M',
            'prefix' => 'firstp2p_',
        ),
        'adminslave' => array(
            'name' => 'ncfph',
            'host' => 'rm-2zev331edt40lf44woo.mysql.rds.aliyuncs.com',
            'port' => '3306',
            'user' => 'superadmin_xf',
            'password' => 'CY7x2Z8EXu%@!z6M',
            'prefix' => 'firstp2p_',
        ),
    ),
    //原firstp2p数据库，迁移数据使用
    'ncfwx_db' => array(
        'master' => array(
            'name' => 'firstp2p',
            'host' => 'w-p2p.wxlc.org',
            'port' => '3322',
            'user' => 'wxlc_pro',
            'password' => 'lzBbkfLiwEQZU8WXWww7',
            'prefix' => 'firstp2p_',
        ),
        'slave' => array(
            'name' => 'firstp2p',
            'host' => 'r1-p2p.wxlc.org',
            'port' => '3333',
            'user' => 'wxlc_pro_r',
            'password' => 'Y7pFCXuM3xF1pxnVLzXk',
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
            'name' => 'ncfph_moved',
            'host' => 'w-ncfphmoved.mysql.ncfrds.com',
            'port' => '3307',
            'user' => 'phmoved_pro',
            'password' => 'FC201wB628m3B24',
            'prefix' => 'firstp2p_',
        ),
        'slave' => array(
            'name' => 'ncfph_moved',
            'host' => 'r-ncfphmoved.mysql.ncfrds.com',
            'port' => '3307',
            'user' => 'phmoved_pro',
            'password' => 'FC201wB628m3B24',
            'prefix' => 'firstp2p_',
        ),
    ),
    //p2p历史库
    'firstp2p_history_db' => array(
        'master' => array(
            'name' => 'ncfph_history',
            'host' => 'w-ncfphhistory.mysql.ncfrds.com',
            'port' => '3306',
            'user' => 'phhistory_pro',
            'password' => 'B931A21e5F4A1s9',
            'prefix' => '',
        ),
        'slave' => array(
            'name' => 'ncfph_history',
            'host' => 'r-ncfphhistory.mysql.ncfrds.com',
            'port' => '3306',
            'user' => 'phhistory_pro_r',
            'password' => '290t886281l195D',
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
            'host' => 'rm-2zel0e2czpan13o19co.mysql.rds.aliyuncs.com',
            'port' => '3306',
            'user' => 'superadmin_wx',
            'password' => 'CY7x2Z8EXu%@!z6M',
            'prefix' => '',
        ),
        'slave' => array(
            'name' => 'contract_service',
            'host' => 'rm-2zel0e2czpan13o19co.mysql.rds.aliyuncs.com',
            'port' => '3306',
            'user' => 'superadmin_wx',
            'password' => 'CY7x2Z8EXu%@!z6M',
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

);
