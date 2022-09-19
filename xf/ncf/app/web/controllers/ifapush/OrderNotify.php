<?php
/**
 * 协会上报 批量推送的回调接口
 *
 */
namespace web\controllers\ifapush;

use core\enum\IfaEnum;
use web\controllers\ifapush\NotifyAction;
use core\service\ifapush\PushToIfa;
use core\dao\ifapush\IfaBaseModel;
use libs\utils\Logger;
use libs\utils\Alarm;

class OrderNotify extends NotifyAction
{
    const IS_H5 = false;

    /**
     * 处理回调
     */
    public function process($requestData)
    {
        // 参数列表
        $params = array();
        $params['orderId'] = isset($requestData['orderId']) ? addslashes($requestData['orderId']) : '';
        $params['type'] = isset($requestData['type']) ? addslashes($requestData['type']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        // 资金记录 因为协会端无法区分智多新资金记录和资金记录，所以通过orderId来进行区分（orderId包含zdx就为智多新资金记录）
        if(($params['type'] == 'user_log') && preg_match("/zdx$/",$params['orderId'])){
           $params['type'] = 'user_log_zdx';
        }
        $ifa = new PushToIfa($params['type']);
        if(!in_array($params['status'], [IfaEnum::STATUS_SUCC,IfaEnum::STATUS_CALLBACK_FAIL])) {
            $this->errno = '7';
            $this->error = 'status参数出错';
            return;
        }
        if(empty($ifa->handle) || !($ifa->handle instanceof IfaBaseModel)){
            $this->errno = '7';
            $this->error = 'type参数出错';
            return;
        }
        $nums = $ifa->handle->getNum($params['orderId']);
        if(empty($nums['total'])){
            $this->errno = '8';
            $this->error = '该批次不存在';
            $this->json_data = '';
            return;
        }
        if(empty($nums['un_success'])){
            if($params['status'] == IfaEnum::STATUS_SUCC){
                $this->errno = '0';
                $this->error = '成功';
                $this->json_data = 'ok';
                return;
            }else{
                $this->errno = '9';
                $this->error = '该批次已经成功，但是本次请求参数中status为失败';
                return;
            }
        }

        $result = $ifa->handle->batchUpdateStatus($params['orderId'],$params['status']);
        if(!$result){
            $this->errno = '1';
            $this->error = '更新失败';
            $this->json_data = '';
            return;
        }
        if($params['status'] == IfaEnum::STATUS_CALLBACK_FAIL){
            Alarm::push('ifa_push_data_fail', '协会上报数据回调结果为失败', '参数：'. json_encode($params));
            // 回调失败状态的订单，需要重试，所以需要把redis的值设置成最小值，否则无法重试。
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $key = $ifa->handle->tableName().'_batchOrderId_min';
            $oldData = $redis->get($key);
            if(!empty($oldData) && (strcmp($params['orderId'],$oldData)) < 0){
                // 将每次最小的订单号存在redis中，用于下次重试，有效期24小时。
                $redis->setEx($key,86400,$params['orderId']);
            }
        }

        $this->errno = '0';
        $this->error = '成功';
        $this->json_data = '';
        return ;
    }
}
