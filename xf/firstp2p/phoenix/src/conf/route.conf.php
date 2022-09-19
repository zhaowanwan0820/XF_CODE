<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-12 12:47:28
 * @encode UTF-8编码
 */
class P_Conf_Route {

    const ROUTE_STATIC = '_route_static';
    const ROUTE_SIMPLE = '_route_simple';
    const ROUTE_SUPERVAR = '_route_supervar';
    const ROUTE_MAP = '_route_map';
    const ROUTE_REWRITE = '_route_rewrite';
    const ROUTE_REGEX = '_route_regex';

    public static $valid_route = array(
        self::ROUTE_STATIC,
        self::ROUTE_SIMPLE,
        self::ROUTE_SUPERVAR,
        self::ROUTE_MAP,
        self::ROUTE_REWRITE,
        self::ROUTE_REGEX,
    );

}
