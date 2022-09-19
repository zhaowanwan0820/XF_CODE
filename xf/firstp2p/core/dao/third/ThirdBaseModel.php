<?php
namespace core\dao\third;

use core\dao\BaseModel;

class ThirdBaseModel extends BaseModel
{
    /**
     * firstp2p_thirdparty.
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_thirdparty');
        parent::__construct();
    }
}
