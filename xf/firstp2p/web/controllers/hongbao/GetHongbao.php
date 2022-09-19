<?php
/**
 * GetHongbao.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\hongbao\HongbaoBase;
use libs\weixin\Weixin;
use core\service\BonusService;

class GetHongbao extends HongbaoBase {

    public function init() {
        if (parent::init() === false) {
            return false;
        }
    }

    public function invoke() {
        $this->tpl->assign('bonusDetail', $this->bonusDetail);
        $this->tpl->assign('mobile', $this->mobile);
        $this->tpl->assign('date', date("m-d H:i", time()));
        $this->tpl->assign("host", APP_HOST);
        $bonusService = new BonusService();
        // $totalMoney = $bonusService->getUserSumMoney(array('mobile' => $this->mobile, 'status' => 1));
        $totalMoney = $bonusService->getUsableBonusForGroup($this->mobile, $this->bonusDetail);
        $this->tpl->assign('totalMoney', $totalMoney);
        $this->tpl->assign('userInfo', $this->wxInfo['user_info']);

        if ($this->bonusDetail['status'] == 3) {
            $this->tpl->assign('only_new_user', '领取失败，本活动仅适用未注册用户');
            $this->template = "web/views/hongbao/qianghongbao.html";
        } elseif ($this->bonusDetail['status'] == 2) {
            $this->tpl->assign('bonusUserList', $this->bonusUserList);
            $this->template = "web/views/hongbao/yilinghongbao.html";
        } elseif ($this->bonusDetail['status'] == 5) {
            $bonus = $this->bonusDetail['bonus'];
            $this->show_error("本活动与“{$bonus['name']}”只能领取一个", '', 0 , 1);
            return false;
        } else {
            //if (!empty($this->bonusUserList)) {//如果有这个判断，第一个抢到的用户列表无法展示
                //$this->bonusUserList[$this->openid] = array(
                //    'money' => $this->bonusDetail['money'],
                //    'created_at' => date("m-d H:i:s", $this->bonusDetail['created_at']),
                //    'mobile_view' => substr_replace($this->bonusDetail['mobile'], '****', 3, 4),
                //    'wxInfo' => $this->wxInfo['user_info']
                //)
            $tmpDetail = array(
                $this->openid => array(
                    'money' => $this->bonusDetail['money'],
                    'created_at' => date("m-d H:i:s", $this->bonusDetail['created_at']),
                    'mobile_view' => substr_replace($this->bonusDetail['mobile'], '****', 3, 4),
                    'wxInfo' => $this->wxInfo['user_info']
                )
            );
            $this->bonusUserList = $tmpDetail + $this->bonusUserList;
            $this->tpl->assign('bonusUserList', $this->bonusUserList);
            //}
            $this->template = "web/views/hongbao/qiangdaohongbao.html";
        }
    }
}
