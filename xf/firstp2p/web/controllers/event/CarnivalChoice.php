<?php

/**
 * 嘉年华奖品选择
 *
 * @author yutao
 * @date 2014-10-10
 */

namespace web\controllers\event;

use web\controllers\BaseAction;

class CarnivalChoice extends BaseAction {

    public function init() {
        \es_session::set('before_login', '/event/carnivalChoice');
        return $this->check_login();
    }

    public function invoke() {
        //win user
        if ($GLOBALS['user_info']['id'] > 0) {
            //get user info
            $userInfo = $this->rpc->local("ActivityCarnivalService\getUserInfo", array($GLOBALS['user_info']['id']));
            if (empty($userInfo)) {
                $msg = "抱歉，您无权浏览此页面";
                $this->show_error($msg, "", 0, 0, url("/"), 3);
                return;
            }
            if ($userInfo->expire_time < get_gmtime()) {
                $msg = "抱歉，选奖期限已过";
                $this->show_error($msg, "", 0, 0, url("/"), 3);
                return;
            }
            if ($userInfo->is_commit == 1) {
                $gift_type = "virtual";
                if (strpos($userInfo->gift_practical, $userInfo->gift_choose) !== false) {
                    $gift_type = "practical";
                }
                $this->tpl->assign('gift_choose', $userInfo->gift_choose);
                $this->tpl->assign('recipient_name', $userInfo->recipient_name);
                $this->tpl->assign('mobile', $userInfo->mobile);
                $this->tpl->assign('province', $userInfo->province);
                $this->tpl->assign('city', $userInfo->city);
                $this->tpl->assign('country', $userInfo->country);
                $this->tpl->assign('address', $userInfo->address);
                $this->tpl->assign('gift_type', $gift_type);
            } else {
                $giftPracticalItemArray = array();
                if ($userInfo->gift_practical != '') {
                    $giftPracticalItems = explode("/", $userInfo->gift_practical);
                    foreach ($giftPracticalItems as $key => $value) {
                        $giftPracticalItemArray[$key]['type'] = 1;
                        $giftPracticalItemArray[$key]['gift'] = $value;
                    }
                }
                $giftVirtualItemArray = array();
                if ($userInfo->gift_virtual != '') {
                    $giftVirtualItems = explode("/", $userInfo->gift_virtual);
                    foreach ($giftVirtualItems as $key => $value) {
                        $giftVirtualItemArray[$key]['type'] = 0;
                        $giftVirtualItemArray[$key]['gift'] = $value;
                    }
                }

                $gift = array_merge($giftPracticalItemArray, $giftVirtualItemArray);
                $this->tpl->assign('gift', $gift);
            }

            $this->tpl->assign('is_commit', $userInfo->is_commit);
            $this->tpl->assign('user_id', $GLOBALS['user_info']['id']);
            return;
        }
        $msg = "抱歉，您无权浏览此页面";
        $this->show_error($msg, "", 0, 0, url("/"), 3);
        return;
    }

}
