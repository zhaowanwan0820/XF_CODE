<?php
/**
 * 标的详情页
 * @author zhaohui zhaohui3@ucfgroup
 * @data 2017.5.22
 */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class GoldCurrentDetail extends GoldBaseAction {

    const IS_H5 = true;
    public function init() {
        parent::init();
    }

    public function invoke() {
        //获取优金宝详情
        $goldCurrentDetail = $this->rpc->local('GoldService\getInfo', array());
        if (floorfix($goldCurrentDetail['goldPrice'],2) == 0) {
            $goldCurrentDetail['goldPrice'] = '- -';
        }
        //获取购买记录
        $dealCurrentLog = $this->rpc->local('GoldService\getDealCurrentLog', array());
        $minSize=$this->rpc->local('GoldService\getMinSize',array());
        $goldCurrentDetail['minSize']=number_format($minSize);
        $goldCurrentDetail['loadAmount'] = '--';
        //每人每日最大变现黄金克重
        $maxGoldCurrentConf = app_conf('GOLD_MAX_WITHDRAW_PER_DAY');
        $maxGoldCurrent = $maxGoldCurrentConf === '' ? 1000 : $maxGoldCurrentConf;
        $this->tpl->assign('withdrawLimit',floorfix($maxGoldCurrent,3));
        $this->tpl->assign('goldCurrentDetail', $goldCurrentDetail);
        $this->tpl->assign('dealCurrentLog', $dealCurrentLog);

    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }

}
