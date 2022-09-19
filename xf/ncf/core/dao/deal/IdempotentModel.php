<?php
namespace core\dao\deal;
use core\enum\duotou\CommonEnum;
use core\dao\BaseModel;

class IdempotentModel extends BaseModel {
    const STATUS_WAIT = 0; // 待处理
    const STATUS_FAIL = 1; // 处理失败
    const STATUS_SUCCESS = 2; // 处理成功

    const SOURCE_DUOTOU         = 'duotou';
    const SOURCE_DTBREDEEM      = 'dtbredeem';
    const SOURCE_DTBP2PREPAY    = 'dtbp2prepay';
    const SOURCE_DTBIDPTP       = 'dtbidptp';
    const SOURCE_DTBIDPTPREDEEM = 'dtbidptpredeem';

    const SOURCE_DTDEPOSITORY_REDEEM = 'duotou_depository_redeem';
    const SOURCE_DTDEPOSITORY_REPAY = 'duotou_depository_repay';


    public static $allowSource = array(
        self::SOURCE_DUOTOU,
        self::SOURCE_DTBREDEEM,
        self::SOURCE_DTBP2PREPAY,
        self::SOURCE_DTBIDPTP,
        self::SOURCE_DTBIDPTPREDEEM,
        self::SOURCE_DTDEPOSITORY_REDEEM,
        self::SOURCE_DTDEPOSITORY_REPAY,
    );

    /**
     * 取得未完成的多投投标
     * @param $endTime
     * @return array
     */
    public function getDtUnFinishBidByTime($endTime) {
        $sql = "SELECT id,token,data,create_time FROM `firstp2p_idempotent` WHERE source='duotou' AND mark=".CommonEnum::TOKEN_MARK_BID." AND `status` = 0 AND create_time <= $endTime ORDER  BY id ASC ";
        $res = $this->findAllBySqlViaSlave($sql,true);
        return $res;
    }

    public function getUnFinishCntBySource($source){
        $sql = "SELECT count(*) as cnt FROM `firstp2p_idempotent` WHERE source='".$source."' AND `status` != ".self::STATUS_SUCCESS;
        return $this->countBySql($sql);
    }


    public function getUnFinishTaskBySourceAndTime($source,$time = 0){
        $time = $time? $time:time();
        $sql = "SELECT source,token,data FROM `firstp2p_idempotent` WHERE source='".$source."' AND create_time <= '".$time ."'  AND `status` != ".self::STATUS_SUCCESS;
        return $this->findAllBySqlViaSlave($sql,true);
    }
}