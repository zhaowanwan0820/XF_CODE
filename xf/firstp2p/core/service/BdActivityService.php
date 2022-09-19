<?php

namespace core\service;
use core\dao\BdActivityOrderModel;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\YiShangAwardEvent;
use core\dao\UserModel;
use libs\utils\Logger;

class BdActivityService extends BaseService {

    /**
     * pushYiShangOrder
     * 购买易赏商品
     * @param mixed $itemId 商品ID,0流量
     * @param mixed $userid 购买用户ID
     * @param int $count 购买数量
     * @access public
     * @return void
     */
    public function pushYiShangOrder($itemId, $userid, $coupon, $count = 1, $unique = true) {
        $config = $GLOBALS['sys_config']['bd_activity']['YiShang'];
        if($config){
            if(isset($config['item'][$itemId])){
                $userinfo = UserModel::instance()->find($userid, 'mobile');
                if(!$userinfo || empty($userinfo['mobile'])){
                    return false;
                }else{
                    $phone = $userinfo['mobile'];
                }
                $model = BdActivityOrderModel::instance();
                $prize_id = $config['item'][$itemId]['prizeId'];
                $relID = \core\service\CouponService::hexToUserId($coupon);
                if($unique === true){
                    if($model->count('user_id=:user_id AND relation_id=:relation_id AND prize_id=:prize_id', array(':user_id'=>$userid, ':relation_id'=>$relID, ':prize_id'=>$prize_id))>0){
                        $log = array('event'=>'user_duplication', 'params'=> array($userid, $prize_id));
                        Logger::wlog(array('BD_ACT'=>$log));
                        return false;
                    }
                }

                $orderSn = $this->makeOrderSn('YS');
                $data = array('user_id'=>$userid, 'order_sn'=>$orderSn, 'relation_id'=>$relID, 'count'=>$count, 'prize_id'=>$prize_id, 'coupon'=>$coupon);
                $ret = $model->insertOrder($data);
                if($ret){
                    $item = $config['item'][$itemId];
                    $url = $config['url_getAward'];
                    $params = array(
                        'phone' => $phone,
                        'orderId' => $item['orderId'],
                        'userId' => $item['userId'],
                        'prizeId' => $item['prizeId'],
                        'prizePriceTypeId' => $item['prizePriceTypeId'],
                        'count' => $count,
                        'customOrderCode' => $orderSn,
                    );
                    if (!in_array($itemId, array('CPA30','Dixintong'), true)) {
                        $params['operation'] = 'recharge';
                    }

                    $sign = md5($orderSn.$item['orderId'].$item['userId'].$item['key']);
                    $params['sign'] = $sign;
                    $event = new YiShangAwardEvent($orderSn, $params);
                    $obj = new GTaskService();
                    if($obj->doBackground($event, 3)){
                        return $orderSn;
                    }else{
                        $log = array('event'=>'gearman_fail','params'=> array($orderSn, $params));
                        Logger::wlog(array('BD_ACT'=>$log));
                        throw new \Exception('任务注册失败');
                    }
                }else{
                    $log = array('event'=>'mysql_insert_error','params'=> $data);
                    Logger::wlog(array('BD_ACT'=>$log));
                    return false;
                }
            }else{
                $log = array('event'=>'config_item_error','params'=> $config);
                Logger::wlog(array('BD_ACT'=>$log));
                return false;
            }
        }else{
            $log = array('event'=>'config_error','params'=> $config);
            Logger::wlog(array('BD_ACT'=>$log));
            return false;
        }
    }

    /**
     * makeOrderSn
     * 生成22位随机数字
     * @param string $pre 随机数前缀
     * @access public
     * @return string
     */
    public function makeOrderSn($pre = 'YS'){
        list($microsec, $sec) = explode(" ",microtime());
        $microsec = (float)$microsec*1000000;
        return $pre.date('YmdHis').str_pad($microsec, 6, "0", STR_PAD_LEFT);
    }

    /**
     * Export csv
     * 导出csv
     * @param string $coupon //邀请码 
     * @param string $prizeId //商品Id
     * @param string $startDate //开始时间
     * @param string $endDate //结束时间
     * @access public 
     * @return string
     */
    public function getOrders($coupon=null, $prizeId=null, $startDate = null, $endDate = null){
        $cond = '';
        $params = array();
        $startDate = $startDate?strtotime($startDate):strtotime(date("Y-m-d"));
        $endDate = $endDate?strtotime($endDate):strtotime(date("Y-m-d"))+86400;
        
        $cond = "create_time >= $startDate AND create_time < $endDate";
        if($coupon){
            $cond .= " AND coupon = ':coupon'";
            $params[':coupon'] = $coupon;
        }
        if($prizeId){
            $cond .= " AND prize_id = ':prizeId'";
            $params[':prizeId'] = intval($prizeId);
        }

        $ret = BdActivityOrderModel::instance()->findAllViaSlave($cond, true, '*', $params);
        //batch get mobile
        if($ret){
            $uids = array();
            foreach($ret as $item){
                $uids[] = $item['user_id'];
            }

            $uids = array_unique($uids);
            $uidsStr = implode(",", $uids);
            $mobiles = UserModel::instance()->getMobileByIds($uidsStr);
       
            foreach($mobiles as $mobile){
                $uids[$mobile['id']] = $mobile['mobile'];
            }

            foreach($ret as &$item){
                $item['mobile'] = $uids[$item['user_id']];
            }

        }


        return $ret;

    }
}
