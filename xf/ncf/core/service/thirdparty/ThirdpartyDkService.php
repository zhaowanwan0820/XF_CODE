<?php
/**
 * ThirdpartyDkService.php
 *
 * @date 2018-01-24
 * @author 王鉴通 <wangjiantong@ucfgroup.com>
 */

namespace core\service\thirdparty;

use core\service\BaseService;
use core\dao\thirdparty\ThirdpartyDkModel;


class ThirdpartyDkService extends BaseService {

    /**
     * 获取第三方订单对应的order
     * @param $outOrderId;
     * @return array();
     */

    public static function getThirdPartyByOutOrderId($outOrderId,$clientId){
        $tableName = ThirdpartyDkModel::instance()->tableName();
        $sql = "SELECT * FROM `".$tableName."` WHERE outer_order_id = '{$outOrderId}' AND client_id = '{$clientId}';";
        //因功夫贷业务需求，现在暂时从主库查询订单，2018年3月28日
        $res = ThirdpartyDkModel::instance()->findBySql($sql, array(),false);
        return !empty($res) ? $res->getRow() : array();
    }

    /**
     * 获取第三方订单对应的order
     * @param $orderId;
     * @return array();
     */
    public static function getThirdPartyByOrderId($orderId){
        $tableName = ThirdpartyDkModel::instance()->tableName();
        $sql = "SELECT * FROM `".$tableName."` WHERE order_id = '{$orderId}'";
        $res = ThirdpartyDkModel::instance()->findBySql($sql, array(),false);
        return !empty($res) ? $res->getRow() : array();
    }

    /**
     * 获取第三方订单对应状态的order
     * @param $dealId;
     * @param $repayId;
     * @param $status;
     * @return array();
     */
    public static function getThirdPartyOrderByStatus($dealId,$repayId,$status){
        $tableName = ThirdpartyDkModel::instance()->tableName();
        $sql = "SELECT * FROM `".$tableName."` WHERE deal_id = {$dealId} AND repay_id = {$repayId} AND status IN ({$status})";
        $res = ThirdpartyDkModel::instance()->findBySql($sql, array(),true);
        return !empty($res) ? $res->getRow() : array();
    }
}
