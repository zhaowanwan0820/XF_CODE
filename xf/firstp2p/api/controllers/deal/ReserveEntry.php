<?php
/**
 * 随心约-入口页
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

    const IS_H5 = true;

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
        // 记录日志
        Logger::info(implode(' | ', array(__CLASS__, 'ReserveEntry', APP, $userId, $token)));

        $p2pIndexUrl = sprintf('%s/deal/reserveIndex?token=%s', app_conf('NCFPH_WAP_HOST'), $token);
        $exclusiveIndexUrl = sprintf('%s/deal/reserveIndex?token=%s', get_http() . get_host(), $token);

        //入口数据
        $entryData = $this->rpc->local('UserReservationService\getReserveEntryData', array($userId));
        $p2pEntry = $entryData['p2pEntry'];
        $exclusiveEntry = $entryData['exclusiveEntry'];

        //没有入口报错
        if (!$p2pEntry && !$exclusiveEntry) {
            $this->setErr('ERR_MANUAL_REASON', '系统配置错误，请稍后重试');
            return false;
        }

        //只有一个入口则跳转
        if ($p2pEntry + $exclusiveEntry === 1) {
            if ($p2pEntry) {
                header('Location: ' . $p2pIndexUrl); //直接跳转到随心约-网贷
                return false;
            }
            if ($exclusiveIndexUrl) {
                header('Location: ' . $exclusiveIndexUrl); //直接跳转到随心约-尊享
                return false;
            }
        }

        //显示双入口
        $entryData = [
           [
                'product_type' => UserReservationModel::PRODUCT_TYPE_EXCLUSIVE,
                'product_name' => UserReservationModel::$productNameMap[UserReservationModel::PRODUCT_TYPE_EXCLUSIVE],
                'index_url' => $exclusiveIndexUrl,
            ],
            [
                'product_type' => UserReservationModel::PRODUCT_TYPE_P2P,
                'product_name' => UserReservationModel::$productNameMap[UserReservationModel::PRODUCT_TYPE_P2P],
                'index_url' => $p2pIndexUrl,
            ],
        ];

        $this->tpl->assign('token', $token);
        $this->tpl->assign('entry_data', $entryData);
        return true;
    }
}
