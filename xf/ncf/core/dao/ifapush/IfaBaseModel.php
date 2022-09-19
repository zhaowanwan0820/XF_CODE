<?php
namespace core\dao\ifapush;

use core\dao\BaseModel;
use core\enum\IfaEnum;
use libs\utils\DBDes;

class IfaBaseModel extends BaseModel
{
    // 数据库中包含敏感信息字段
    public static $tmFields = array(
        'firstp2p_ifa_user' => ['userIdcard', 'userPhone', 'userBankAccount'],
        'firstp2p_ifa_deal' => ['userIdcard'],
        'firstp2p_ifa_deal_repay' => ['userIdcard'],
        'firstp2p_ifa_deal_load' => ['userIdcard'],
        'firstp2p_ifa_receive' => ['userIdcard'],
        'firstp2p_ifa_transfer' => ['userIdcard'],
        'firstp2p_ifa_user_log' => ['userIdcard'],
        'firstp2p_ifa_user_log_zdx' => ['userIdcard'],
        'firstp2p_ifa_loan' => ['userIdcard'],
        'firstp2p_ifa_lender_log' =>['userIdcard'],
    );

    public function saveData($data)
    {
        foreach ($data as $k => $v) {
            if (in_array($k, ['userIdcard', 'userPhone', 'userBankAccount'])) {
                $v = DBDes::encryptOneValue($v);
            }
            $this->{$k} = $v;
        }
        $this->update_time = time();
        return $this->insert();
    }

    /**
     * 获取批量数据，用于更新batchOrderId
    */
    public function getBatchPushDataForOrderId($seconds, $limit = 10, $orderId = false, $dbTimeColumn = '',$pushType)
    {
        $createTimeLimit = time() - $seconds;
        $param = array(
            ':status' => 0,
            ':time' => $createTimeLimit,
        );
        if ($orderId) {
            $cond = "`order_id` =  {$orderId}";
        } elseif (!empty($dbTimeColumn)) {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $key = $this->tableName().'_'.$dbTimeColumn.'_min';
            if(!$redis){
                return array();
            }
            $oldData = $redis->get($key);
            $firstSql = '';
            if(empty($oldData)){
                $firstSql = "SELECT min({$dbTimeColumn}) AS  minTime FROM " . $this->tableName() . " WHERE `batchOrderId` = '' AND `status` = " . IfaEnum::STATUS_NONE;
            }else{
                $firstSql = "SELECT {$dbTimeColumn} AS  minTime FROM " . $this->tableName() . " WHERE {$dbTimeColumn} >= '{$oldData}' AND `batchOrderId` = '' AND `status` = " . IfaEnum::STATUS_NONE . " ORDER BY {$dbTimeColumn} ASC LIMIT 1";
            }
            $result = $this->findBySqlViaSlave($firstSql);
            if (empty($result) || !isset($result['minTime'])) {
                return array();
            }
            $condStr = $this->getRelationCond($pushType);
            // 将每次最小的日期存在redis中，用于下次查询，有效期24小时。
            $redis->setEx($key,86400,$result['minTime']);
            if (strlen($result['minTime']) <= 10) { // 只有年月日
                $startTime = date("Y-m-d", strtotime($result['minTime']));
                $cond = "{$dbTimeColumn} = '{$startTime}' AND `status` = 0  AND create_time < {$createTimeLimit} AND `batchOrderId` = '' {$condStr}  ORDER BY {$dbTimeColumn} ASC LIMIT {$limit} ";
            } else {
                $minTime = strtotime($result['minTime']);
                $startTime = date("Y-m-d 00:00:00", $minTime);
                $endTime = date("Y-m-d 23:59:59", $minTime);
                $cond = "{$dbTimeColumn} >= '{$startTime}' AND {$dbTimeColumn} <= '{$endTime}' AND `status` = 0  AND create_time < {$createTimeLimit}  AND `batchOrderId` = '' {$condStr} ORDER BY {$dbTimeColumn} ASC LIMIT {$limit} ";
            }

        } else {
            $cond = "`status` = ':status' AND create_time < ':time' order by id asc limit {$limit}";
        }

        $results = $this->findAll($cond, false, '*', $param);

        return $this->decryptTmData($results);
    }

    public function getRelationCond($pushType=''){
        $types = array('deal_status', 'deal_repay','deal_load','user_log','user_log_zdx');
        $cond = '';
        if(!empty($pushType) && in_array($pushType,$types)){
            if(in_array($pushType,array('user_log','user_log_zdx'))){
                $cond.=" AND (sourceProductCode=-1 or sourceProductCode in (select sourceProductCode from firstp2p_ifa_deal where batchOrderId <> ''))";
            }else{
                $cond.= " AND sourceProductCode in (select sourceProductCode from firstp2p_ifa_deal where batchOrderId <> '') ";
            }
        }
        return $cond;
    }

    /**
     * 获取某个batchOrderId的批量数据，用于上传协会
     */
    public function getBatchPushData(){
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $key = $this->tableName().'_batchOrderId_min';
        if(!$redis){
            return array();
        }
        $oldData = $redis->get($key);
        $extraSql = '';
        if(!empty($oldData)){
            $extraSql = " `batchOrderId` >= '{$oldData}' AND ";
        }
        $batchOrderIdSql = "SELECT `batchOrderId` FROM " . $this->tableName() . " WHERE " . $extraSql . " `batchOrderId` <> '' AND `status` IN ("  . implode(',',[IfaEnum::STATUS_NONE,IfaEnum::STATUS_CALLBACK_FAIL])  . ") ORDER BY `batchOrderId` ASC LIMIT 1" ;
        $batchOrderIdResult = $this->findBySqlViaSlave($batchOrderIdSql);
        if(empty($batchOrderIdResult) || !isset($batchOrderIdResult['batchOrderId'])){
            return array();
        }
        // 将每次最小的日期存在redis中，用于下次查询，有效期24小时。
        $redis->setEx($key,86400,$batchOrderIdResult['batchOrderId']);
        $cond = " `batchOrderId` = '".$batchOrderIdResult['batchOrderId']."'";
        $results = $this->findAll($cond, false, '*');
        return $this->decryptTmData($results);
    }

    /**
     * 获取某个batchOrderId的批量数据，用于上传协会
     * 通过batchOrderId的最后一位来分隔batchOrderId
     */
    public function getBatchPushDataMod($mod){
        $batchOrderIdSql = "SELECT `batchOrderId` FROM " . $this->tableName() . " WHERE RIGHT(`batchOrderId`,1) = '{$mod}' AND `batchOrderId` <> '' AND `status` IN ("  . implode(',',[IfaEnum::STATUS_NONE,IfaEnum::STATUS_CALLBACK_FAIL])  . ") LIMIT 1 " ;
        $batchOrderIdResult = $this->findBySqlViaSlave($batchOrderIdSql);
        if(empty($batchOrderIdResult) || !isset($batchOrderIdResult['batchOrderId'])){
            return array();
        }
        $cond = " `batchOrderId` = '".$batchOrderIdResult['batchOrderId']."'";
        $results = $this->findAll($cond, false, '*');
        return $this->decryptTmData($results);
    }

    /**
     * 将托米字段解密
     * 将脱敏字段还原成可读字段
     * @param  $results mysql查出的结果
     * @return array $results
     */
    public function decryptTmData($results){
        $tmFields = isset(self::$tmFields[$this->tableName()]) ? self::$tmFields[$this->tableName()] : array();
        if (!empty($tmFields)) {
            foreach ($results as $k => $oneResult) {
                foreach($tmFields as $tmField){
                    $oneResult->{$tmField} = DBDes::decryptOneValue($oneResult->{$tmField});
                }
                $results[$k] = $oneResult;
            }
        }
        return $results;
    }

    /**
     * 批量更新数据
     * @param $id  数字或者数组（存储着id值）
     * @status 状态
     * @batchOrderId 批量订单号，使用批量推送时生成的订单号
    */
    public function updateStatus($id, $status, $batchOrderId='')
    {
        $status = intval($status);
        if ($status == 1) {
            $data = array('status' => $status, 'update_time' => time(), 'succ_time' => time());
        } else {
            $data = array('status' => $status, 'update_time' => time());
        }
        if(!empty($batchOrderId)){
            $data['batchOrderId'] = $batchOrderId;
        }
        if (is_array($id)) {
            $cond = '`id` IN  (' . implode(',', $id) . ')';
        } elseif (is_numeric($id)) {
            $cond = '`id`= ' . $id;
        } else {
            return false;
        }
        return $this->updateBy($data, $cond);
    }

    /**
     * 批量更新数据
     * @param $id  数字或者数组（存储着id值）
     * @batchOrderId 批量订单号，使用批量推送时生成的订单号
     */
    public function updateBatchOrderId($id, $batchOrderId){
        if(empty($id) || empty($batchOrderId)){
            return false;
        }
        $data['batchOrderId'] = $batchOrderId;
        if (is_array($id)) {
            $cond = '`id` IN  (' . implode(',', $id) . ')';
        } elseif (is_numeric($id)) {
            $cond = '`id`= ' . $id;
        } else {
            return false;
        }
        return $this->updateBy($data, $cond);
    }

    /**
     * 根据批量订单号获取各种状态下数量
    */
    public function getNum($batchOrderId){
        if(empty($batchOrderId)){
            return 0;
        }
        $sql = "SELECT `status`,count(*) AS num FROM " . $this->tableName() ." WHERE batchOrderId = '{$batchOrderId}' GROUP BY `status`;";
        $result = $this->findAllBySqlViaSlave($sql);
        $data = array('total'=> 0, 'un_success'=>0,'un_fail'=>0);
        foreach($result as $item){
            switch($item['status']){
                case IfaEnum::STATUS_NONE :
                    $data[IfaEnum::STATUS_NONE]  = $item['num'];
                    $data['un_success'] += $item['num'];
                    $data['un_fail'] += $item['num'];
                    break;
                case IfaEnum::STATUS_SUCC :
                    $data[IfaEnum::STATUS_SUCC]  = $item['num'];
                    $data['un_fail'] += $item['num'];
                    break;
                case IfaEnum::STATUS_FAIL :
                    $data[IfaEnum::STATUS_FAIL]  = $item['num'];
                    $data['un_success'] += $item['num'];
                    break;
                case IfaEnum::STATUS_DOING :
                    $data[IfaEnum::STATUS_DOING]  = $item['num'];
                    $data['un_success'] += $item['num'];
                    $data['un_fail'] += $item['num'];
                    break;
                case IfaEnum::STATUS_CALLBACK_FAIL :
                    $data[IfaEnum::STATUS_CALLBACK_FAIL]  = $item['num'];
                    $data['un_success'] += $item['num'];
                    break;
            }
            $data['total'] += $item['num'];
        }
        return $data;
    }

    /**
     * 根据批量订单号 将该批次订单更新为某种状态
     */
    public function batchUpdateStatus($batchOrderId,$status)
    {
        $status = intval($status);
        $batchOrderId = trim($batchOrderId);
        $time = time();
        if(empty($batchOrderId)){
            return false;
        }
        $sql = " UPDATE `" . $this->tableName() ."` SET `status` = '{$status}', `succ_time` = '{$time}' WHERE `batchOrderId` = '{$batchOrderId}';";
        return $this->updateRows($sql);
    }
}