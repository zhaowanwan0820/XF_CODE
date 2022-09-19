<?php
/**
 * Created by PhpStorm.
 * User: qianyi
 * Date: 2018/11/12
 * Time: 15:13
 */

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\duotou\DuotouService;

class InterestReport extends DuotouBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'pageSize' => array(
                'filter' => 'int',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    /*累计到账、持有资产、XX月总收益*/
    public function invoke()
    {
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL'); //获取oauth用户信息失败
            return false;
        }
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;

        $data = $this->form->data;

        //计算始末月份
        $startMonth = strtotime(date('2018-11'));
        $endMonth = strtotime("+6month", $startMonth);
        $currentMonth = strtotime(date('Y-m'));

        if ($endMonth <= $currentMonth) {
            $startMonth = strtotime("-6month", $currentMonth);
            $endMonth = $currentMonth;
        }
        $vars = array('userId' => $userId, 'startMonth' => $startMonth, 'endMonth' => $endMonth);
        $response = \SiteApp::init()->dataCache->call(new DuotouService() , 'call', array(array('\NCFGroup\Duotou\Services\UserStats', "getUserInterestReport", $vars)), 180);

        if (!$response) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }

        $this->json_data = $response['data'];
    }
}
