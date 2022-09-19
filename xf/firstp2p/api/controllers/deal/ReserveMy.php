<?php
/**
 * 短期标预约-我的预约页面
 *
 * @date 2016-11-16
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\service\UserReservationService;
use core\dao\UserReservationModel;

class ReserveMy extends ReserveBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }

        $userInfo = $this->getUserBaseInfo();
        $appLoginUrl = $this->getAppScheme('native', array('name'=>'login'));
        if (empty($userInfo))
        {
            // 登录token失效，跳到App登录页
            header('Location:' . $appLoginUrl);
            return false;
        }

        $data = $this->form->data;
        $productType = UserReservationModel::PRODUCT_TYPE_EXCLUSIVE;
        $dealTypeList = $this->rpc->local("UserReservationService\getDealTypeListByProduct", array($productType, $userInfo['id']));
        if (empty($dealTypeList)) {
            $this->setErr('ERR_MANUAL_REASON', '服务暂不可用，请稍后再试！');
            return false;
        }

        $siteId = \libs\utils\Site::getId();
        // 用户身份标识
        $userClientKey = $data['userClientKey'];
        $list = array('list'=>array(), 'count'=>0);
        // 获取用户所有的预约列表
        $userReservationService = new UserReservationService();
        $userAllReserveList = $userReservationService->getUserReserveListByPage($userInfo['id'], -1, 1, 1, $dealTypeList);
        $list['count'] = count($userAllReserveList);

        $this->tpl->assign('product_type', $productType);

        // 临时Token
        $this->tpl->assign('asgn', md5(uniqid()));
        $this->tpl->assign('returnLoginUrl', $appLoginUrl);
        $this->tpl->assign('userClientKey', $userClientKey);
        // 获取用户Token
        $this->tpl->assign('token', (!empty($this->_userRedisInfo['token']) ? $this->_userRedisInfo['token'] : ''));
        $this->tpl->assign('reserve_list', $list);
        $this->tpl->assign('is_firstp2p', $this->is_firstp2p);
        $this->tpl->assign('site_id', $siteId);
        return true;
    }
}
