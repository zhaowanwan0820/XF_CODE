<?php

/**
 * 多投宝取消投资列表
 * CancelLoadlist.php.
 *
 * @author wangchuanlu@ucfgroup.com
 */

namespace web\controllers\finplan;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\duotou\DtCancelService;

class CancelLoadlist extends BaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
        );
        if (!$this->form->validate()) {
            return app_redirect(url('index'));
        }
    }

    public function invoke()
    {
        $user = $GLOBALS['user_info'];
        if (empty($user)) {
            $this->show_error('未登陆');
        }

        $userId = $user['id'];
        $dtCancelService = new DtCancelService();
        $res = $dtCancelService->getCanCancelDealLoans($userId);
        
        $datas = $res['data'];
        foreach ($datas as &$data) {
            $data['money'] = format_price($data['money']);
            //赎回服务费
            $data['fee'] = format_price($data['fee']);
            //未到账收益
            $data['noRepayInterest'] = format_price($data['noRepayInterest']);
        }
        $this->tpl->assign('list', $datas);
        $this->tpl->assign('code', $res['errCode']);
    }
}
