<?php

namespace core\dao\vip;

use \libs\db\Db;
use core\dao\ProxyModel;

class VipBaseModel extends ProxyModel
{
    public function __construct()
    {
        self::$prefix = 'firstp2p_';
        $this->db = \libs\db\Db::getInstance('vip');
    }

    protected function getSlave()
    {
        return Db::getInstance('vip', 'slave');
    }
}
