<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-25 10:47:28
 * @encode UTF-8编码
 */
class P_Conf_Rpc {

    const DEFAULT_INFFIX = 'rpc';
    const METHOD_LOCAL = 'local';
    const METHOD_REMOTE = 'remote';

    public static $rpc_method = self::METHOD_LOCAL;
    public static $rpc_remote = array(
        'service_root' => 'http://services.api.zhongchou.cn/rpc.php?c=rpc',
        'timeout' => 5,
    );

}
