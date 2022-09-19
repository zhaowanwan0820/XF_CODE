<?php

/*
 * @anstract
 * 同步用户抽奖排名信息到redis中
 * @date    2015-11-21
 * @author  王传路<wangchuanlu@ucfgroup.com>
 *
 * @crontab * * * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php user_roulette_rank_sync.php
 */

ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');

use core\service\UserRouletteRankService;

class UserRouletteRankSync {
    private $_service;

    function __construct() {
        $this->_service = new UserRouletteRankService();
    }

    public function run() {
        return $this->_service->syncRedisRanks();
    }
}

$sync = new UserRouletteRankSync();
if ($sync->run()) {
    var_dump("sync success");
} else {
    var_dump("sync failed");
}

