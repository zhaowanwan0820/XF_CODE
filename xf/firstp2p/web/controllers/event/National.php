<?php

/**
 * iphone6 活动页面
 *
 * @author yutao
 * @date 2014-10-10
 */

namespace web\controllers\event;

use web\controllers\BaseAction;

class National extends BaseAction {

    public function init() {
        
    }

    public function invoke() {

        //$lotteryList = $this->rpc->local('ActivityIphoneService\getLottery');
        $lotteryList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ActivityIphoneService\getLottery', array()), 60);
        $lottery = array_slice($lotteryList, 0, 2);

        //$userWinList = $this->rpc->local('ActivityIphoneService\getIphoneUserWin');
        $userWinList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ActivityIphoneService\getIphoneUserWin', array()), 60);
        $key = count($userWinList) - 1;
        $lastDate = date('m月d日', strtotime("2014-" . $userWinList[$key]['date']));
        $lastCount = $userWinList[$key]['deal_count'];
        $queryString = "timestamp=" . $userWinList[$key]['stat_time'];

        $this->tpl->assign('queryString', $queryString);
        $this->tpl->assign('lottery', $lottery);
        $this->tpl->assign('lastDate', $lastDate);
        $this->tpl->assign('lastCount', $lastCount);
        $this->tpl->assign('userWinList', $userWinList);
    }

}
