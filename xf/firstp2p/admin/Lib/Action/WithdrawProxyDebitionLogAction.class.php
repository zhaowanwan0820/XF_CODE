<?php

/**
 * Class WithdrawProxyAction
 *
 */
use core\dao\WithdrawProxyModel;
use core\dao\WithdrawProxyCheckModel;
use core\dao\WithdrawProxyDebitionModel;
use core\service\WithdrawProxyService;
use core\service\WithdrawProxyDebitionService;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\StandardApi as Api;
use NCFGroup\Common\Library\services\UcfpayGateway;


class WithdrawProxyDebitionLogAction extends CommonAction
{
    public function index()
    {
        $map = $this->_getDebitionMap();
        $this->assign("default_map", $map);
        parent::index();
    }

    private function _getDebitionMap()
    {
        $map = [];
        // 出让方用户id
        if(!empty($_REQUEST['transferor_user_id']))
        {
            $map['transferor_user_id'] = intval($_REQUEST['transferor_user_id']);
        }
        // 受让方账户
        if (!empty($_REQUEST['transferee_account']))
        {
            $map['transferee_account'] = addslashes(trim($_REQUEST['transferee_account']));
        }

        // 创建时间
        $applyTimeStart = $applyTimeEnd = 0;
        if (!empty($_REQUEST['apply_time_start']))
        {
            $applyTimeStart = strtotime($_REQUEST['apply_time_start']);
            $map['create_time'] = array('egt', $apply_time);
        }
        if (!empty($_REQUEST['apply_time_end']))
        {
            $applyTimeEnd = strtotime($_REQUEST['apply_time_end']);
            $map['create_time'] = array('between', sprintf('%s,%s', $applyTimeStart, $applyTimeEnd));
        }

        return $map;
    }
}
