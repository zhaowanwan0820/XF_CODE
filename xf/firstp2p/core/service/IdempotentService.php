<?php
/**
 * 幂等保障服务
 */

namespace core\service;
use core\dao\IdempotentModel;

class IdempotentService extends BaseService {

    /**
     * 生成唯一token
     * @return string
     */
    public static function genToken() {
       $day = date('ymdHis');
       $rand = substr(microtime(),2,5) . mt_rand(100,999);
       return $day . $rand;
   }

    /**
     * 获取token信息
     * @param $token
     * @return array
     */
    public static function getTokenInfo($token) {
        $res =IdempotentModel::instance()->findBy("token='$token'");
        $tokenInfo =  $res ? $res->_row : array();
        if(!empty($tokenInfo)) {
            $tokenInfo['data'] = json_decode($tokenInfo['data'], true);
        }
        return $tokenInfo;
    }

    /**
     * 检查token是否存在
     * @param $token
     * @return mixed
     */
    public static function hasExists($token) {
        $tokenInfo = self::getTokenInfo($token);
        return (!empty($tokenInfo) && !empty($tokenInfo['token'])) ? true : false;
    }

    /**
     * 保存token
     * @param $token
     * @param $data
     * @param $status
     * @return bool
     */
    public static function saveToken($token,$data,$source,$mark=0,$status = 0) {
        if(!in_array($source,IdempotentModel::$allowSource)) {
            throw new \Exception("idempotentModel source not exists");
        }
        $now  = time();
        $m = new IdempotentModel();
        $m->token = $token;
        $m->data = json_encode($data);
        $m->source = $source;
        $m->mark = $mark;
        $m->status = $status;
        $m->create_time = $now;
        $m->update_time = $now;
        $res = $m->save();
        return $res;
    }

    /**
     * 修改token状态
     * @param $token
     * @param $status
     * @return bool
     */
    public static function updateStatusByToken($token,$status) {
        $status = intval($status);
        $m = new IdempotentModel();
        $res = $m->updateBy(array('status'=>$status),"token='".$token."'");
        return $res;
    }

    public static function updateStatusFromOriStatus($token,$newStatus,$oriStatus){
        $m = new IdempotentModel();
        $sql = "UPDATE `firstp2p_idempotent` SET `status` = $newStatus WHERE token= '{$token}' AND `status` = $oriStatus";
        $affectedRows = $m->updateRows($sql);
        return $affectedRows;
    }

    public static function getDtUnFinishBidByTime($endTime) {
        $m = new IdempotentModel();
        $res = $m->getDtUnFinishBidByTime($endTime);
        return $res;
    }

    public static function updateByToken($token, $data) {
        $status = intval($status);
        $m = new IdempotentModel();
        $res = $m->updateBy($data, "token='".$token."'");
        return $res;
    }
}
