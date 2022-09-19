<?php
/**
 * Created by PhpStorm.
 * User: qianyi
 * Date: 2018/11/22
 * Time: 11:49
 */

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\duotou\DuotouService;

class InterestReportPage extends DuotouBaseAction
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

    public function invoke()
    {
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL'); //获取oauth用户信息失败
            return false;
        }
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;

        $data = $this->form->data;
        $data['pageSize'] = isset($data['pageSize'])?intval($data['pageSize']):1;
        //计算始末月份
        $pageNum = ($data['pageSize'] - 1) * 10;
        $start = strtotime(date('2018-11'));
        $currentMonth = strtotime(date('Y-m'));

        $endMonth = strtotime("-" . $pageNum . "month", $currentMonth);
        $startMonth = strtotime("-10month", $endMonth);


        if ($startMonth < $start) {
            $startMonth =$start;
        }
        $vars = array('userId' => $userId, 'startMonth' => $startMonth, 'endMonth' => $endMonth);
        $response = \SiteApp::init()->dataCache->call(new DuotouService() , 'call', array(array('\NCFGroup\Duotou\Services\UserStats', "getUserInterestReportPage", $vars)), 180);
        if (!$response) {
            $this->setErr("ERR_SYSTEM_CALL_CUSTOMER");
            return false;
        }
        $this->json_data = $response['data'];
    }

}
