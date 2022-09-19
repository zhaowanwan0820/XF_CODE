<?php

/**
 * @abstract openapi  掌众查询预约标的信息
 * @author 于涛 <yutao@ucfgroup.com>
 * @date 2017-01-11
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Alarm;
use libs\utils\Logger;

/**
 * 获取预约信息
 *
 * @package openapi\controllers\asm
 */
class ReservationInfoQuery extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array();
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        //判断调用方有效性
        if (empty($this->clientConf['reservation'])) {
            $this->setErr("ERR_SYSTEM_CLIENTID", '调用查询失败');
            return false;
        }
        $reservConf = $this->clientConf['reservation'];
        $type = $reservConf['type'];
        $deadline = $reservConf['deadline'];
        $data = [];
        foreach ($deadline as $v) {
            $ret = \SiteApp::init()->dataCache->call($this->rpc, 'local',
                    ['UserReservationService\getEffectReserveAmountByTypeTag', [$type, $v['length'], $v['unit']], 'reserve'], 600);

            $key = $v['length'].($v['unit'] == 1 ? '天' : '月');
            $data[$key] = ['amount' => $ret];
        }
        $this->json_data = $data;
        return;
    }

}
