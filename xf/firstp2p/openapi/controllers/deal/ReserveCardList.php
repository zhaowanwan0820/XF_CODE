<?php
/**
 * @wap站随心约-获取预约卡片列表
 * @date:2017-05-17
 * @author:liuzhenpeng
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\ReserveBaseAction;
use core\dao\ReservationConfModel;

class ReserveCardList extends ReserveBaseAction
{
    public static $rateText = array(
            0 => '年化借款利率',
            1 => '预期年化',
            2 => '预期年化',
            3 => '预期年化',
        );

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = $this->sys_param_rules;

        if(!$this->form->validate()){
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if(!$this->isOpenReserve()){
            $this->setErr('ERR_RESERVE_CLOSE');
            return false;
        }

        if(!$userInfo = $this->getUserByAccessToken()){
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        $list = array();
        $list = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ReservationCardService\getReserveCardList', array(200000)), 10, false, true);
        if(!empty($list)){
            $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
            $url = $http . $_SERVER['HTTP_HOST'];
            $data = $list['list'];
            foreach ($data as $key=>$val){
                $data[$key]['amount'] = (empty($val['amount']) && !strpos($val['amount'], '元')) ? '0.00元' : $val['amount'];
                $data[$key]['reserCommitUrl']    = sprintf("/reserveIndex/details?investLine=%s&investUnit=%s", $val['investLine'], $val['unitType']);
                $data[$key]['reserveDetailsUrl'] = sprintf("/reserveIndex/details?investLine=%s&investUnit=%s", $val['investLine'], $val['unitType']);
                $data[$key]['rateText'] = self::$rateText[$val['dealType']] ?: self::$rateText[2];
            }

            $tmp = $data;
            unset($data);
            $list = $tmp;
        }

        $resData['list'] = $list;

        $this->json_data = $resData;
    }
}
