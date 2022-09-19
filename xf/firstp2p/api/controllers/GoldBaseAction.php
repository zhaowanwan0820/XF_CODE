<?php

/**
 * GoldBaseAction class file.
 *
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * */

namespace api\controllers;

use libs\utils\Logger;

class GoldBaseAction extends AppBaseAction
{

    const GOLD_CURRENT_TYPE = 1; //优金宝类型
    const GOLD_TYPE = 2; //优长金类型

    public static $loantype_info = array(
                                    5 => '已购黄金及收益克重到期一次性交付',
                                    6 => '已购黄金到期交付，收益克重按季度交付'
                        );
    public static $white_class_list = array(
        'api\controllers\gold\GoldCurrentDetail',
        'api\controllers\gold\GoldPrice',
        'api\controllers\gold\Index',
        'api\controllers\gold\InvestList',
        'api\controllers\gold\LoadAmount',
        'api\controllers\gold\P2pIndex',
        'api\controllers\gold\UserAuthorize',
    );

    public function init()
    {
        parent::init();
        $this->isOpenSwitch();

    }

    public function _before_invoke() {
        $class_name =get_class($this);
        if( !in_array($class_name,self::$white_class_list) ){
            $this->isSaleWhitch();
        }
        return parent::_before_invoke();
    }

    /**
     *
     */
    protected function isOpenSwitch()
    {
        //判断开关 0是关闭 不让用户看到黄金入口
        //$goldStatus = $this->rpc->local('GoldService\isGoldOpen');
        if ((int)app_conf('GOLD_SWITCH') === 0 ) {
            $this->setErr('ERR_SYSTEM');
            $this->return_error();
        }
    }

    protected function isSaleWhitch(){
        $userInfo = $this->getUserByToken(false);
        $result=$this->rpc->local('GoldService\isWhite', array($userInfo['id']));
        if(!$result){
            $this->setErr('ERR_SYSTEM');
            $this->return_error();
        }
    }

    /**
     * 判断当前时间是否为每天的交易时间段
     * @param $time
     * @return bool
     */
    public function check_trade_time() {
        $time = time();
        if (empty($time)) {
            return false;
        }
        $time_conf = app_conf('GOLD_TRADE_TIME');
        if (empty($time_conf)) {
            //默认时间为9:30:00-25:30:00
            $startTime = strtotime('9:30:00');
            $endTime = strtotime('23:50:00');
        } else {
            $timeConf = explode(',',$time_conf);
            $startTime = strtotime($timeConf['0']);
            $endTime = strtotime($timeConf['1']);
        }
        if ($time >= $startTime && $time <= $endTime) {
            return true;
        }
        return false;
    }
    public function return_error() {
        parent::_after_invoke();
        return false;
    }
}
