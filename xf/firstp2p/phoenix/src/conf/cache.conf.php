<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-23 15:22:07
 * @encode UTF-8编码
 */
class P_Conf_Cache {

    const CACHE_HOST = 'host';
    const CACHE_PORT = 'port';
    const CACHE_ENGINE = 0;
    const CACHE_SERVERS = 1;
    const CACHE_PREFIX = 2;
    const DEFAULT_EXPIRE = 60;
    const DEFAULT_GLUE = '_';
    const DEFAULT_INFFIX = 'Cache';
    const DEFAULT_PREFIX = '';
    const ENGINE_MEMCACHE = 'memcache';
    const ENGINE_MEMCACHED = 'memcached';
    const ENGINE_REDIS = 'redis';

}
