<?php

namespace core\dao\third;

class ThirdDealModel extends ThirdBaseModel
{
    public static $DEAL_STATUS = array(
        'waiting' => 0, //等待材料
        'progressing' => 1, //进行中
        'full' => 2, //满标
        'repaying' => 3, //还款中
        'failed' => 4, //流标
        'repaid' => 5, //已还清
    );

    public function getPlatformInfo()
    {
        $sql = 'select DISTINCT client_id,client_name from '.$this->tableName();
        return $this->findAllBySql($sql, true);
    }

    public function getInfoByDealIdAndClientId($dealId,$clientId){
        $sql = "select id from %s where `deal_id`= '%s' AND `client_id` = '%s'";
        $sql = sprintf($sql,$this->tableName(),$dealId,$clientId);
        return $this->db->getRow($sql);
    }
}
