<?php
/**
 * 随心约-入口页接口
 *
 * @date 2018-06-04
 * @author weiwei12@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\dao\UserReservationModel;
use libs\utils\Logger;

class ReserveEntry extends ReserveBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );

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
        if (empty($userInfo))
        {
            // 登录token失效，跳到App登录页
            header('Location:' . $this->getAppScheme('native', array('name'=>'login')));
            return false;
        }
        $userId = $userInfo['id'];

        $data = $this->form->data;
        $token = $data['token'];
        $siteId = \libs\utils\Site::getId();
        $userClientKey = parent::genUserClientKey($data['token'], $userId);

        // 记录日志
        Logger::info(implode(' | ', array(__CLASS__, 'ReserveEntry', APP, $userId, $token)));

        //入口数据
        $entryData = $this->rpc->local('UserReservationService\getReserveEntryData', array($userId));
        $p2pEntry = $entryData['p2pEntry'];
        $exclusiveEntry = $entryData['exclusiveEntry'];

        //入口数据
        $entryData = [];
        if ($exclusiveEntry) {
            $entryData[] = [
                'product_type' => UserReservationModel::PRODUCT_TYPE_EXCLUSIVE,
                'product_name' => UserReservationModel::$productNameMap[UserReservationModel::PRODUCT_TYPE_EXCLUSIVE],
                'pre_name' => '我的',
            ];
        }
        if ($p2pEntry) {
            $entryData[] = [
                'product_type' => UserReservationModel::PRODUCT_TYPE_P2P,
                'product_name' => UserReservationModel::$productNameMap[UserReservationModel::PRODUCT_TYPE_P2P],
                'pre_name' => '我的',
            ];
        }
        $this->tpl->assign('token', $token);
        $this->tpl->assign('userClientKey', $userClientKey);
        $this->tpl->assign('entry_data', $entryData);
        return true;
    }
}
