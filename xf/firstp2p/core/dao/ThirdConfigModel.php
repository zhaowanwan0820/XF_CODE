<?php
/**
 * 第三方网贷平台配置Model
 * @author longbo
 */

namespace core\dao;
use libs\db\MysqlDb;

class ThirdConfigModel extends BaseModel
{

    public function __construct()
    {
        parent::__construct();
        $this->db = MysqlDb::getInstance('firstp2p_thirdparty');
    }


    public function getAll()
    {
        return $this->findAll('length(`key`) > 1 order by sort', true);
    }

    public function getOne($key = '')
    {
        if ($key) {
            $condition = '`key` = ":key"';
            $params = [":key" => $key];
            $res = $this->findBy($condition, '*', $params);
            if (!empty($res)) {
                return $res->getRow();
            }
        }
        return [];
    }




}
