<?php
namespace api\controllers\candysnatch;

use core\service\candy\CandySnatchService;
use core\service\candy\CandyAccountService;
use api\controllers\AppBaseAction;
use libs\web\Form;

/**
 * 信宝夺宝-单个商品页面
 */
class SnatchProduct extends AppBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token不能为空'),
            'periodId' => array('filter' => 'required', 'message' => '期号ID不能为空'),
            'sign' => array('filter' =>'string'),
        );
        if (!$this->form->validate()) {
            return $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        $periodId = intval($data['periodId']);
        if (empty($loginUser)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }
        $userId = $loginUser['id'];
        $this->tpl->assign('token', $data['token']);
        $candySnatchService = new CandySnatchService();

        //获得期信息
        $productInfo = $candySnatchService->getPeriodInfo([$periodId]);
        //附加产品信息
        $productInfo = $candySnatchService->attachProductInfo($productInfo);
        //附加用户信息
        $productInfo = $candySnatchService->attachUserInfo($productInfo);
        $periodCodes = $candySnatchService->getUserPeriodCodes($userId, $periodId);
        //商品页面只展示前5条数据，第6条数据用来判断是否显示更多
        $periodOrders = $candySnatchService->getPeriodOrders($periodId, 0,6);
        $calculate = $candySnatchService->getPrizeData($periodId);
        $calculate['remainder'] = $calculate['prize_time_sum'] % $calculate['code_total'];
        $candyAccountService = new CandyAccountService();
        // 夺宝邀新专享商品
        if($productInfo[0]['productInfo']['type'] == $candySnatchService::INVITE_PRODUCT_TYPE){
            $todayAvailableCount = $candySnatchService->getAvailableInviteAmount($userId);
        }else{
            //当日可投
            $todayAvailableCount = $candySnatchService->getUserCodeAvailable($userId);
        }
        //信宝账户
        $accountInfo = $candyAccountService->getAccountInfo($userId);
        //递归对数组计数，此处统计用户某一期所投的所有信宝数
        $periodCodesCount = count($periodCodes, 1) - count($periodCodes);
        //用户每期最多投的比例(某期总共所需的信宝数 * 30%)
        $maxCount = $productInfo[0]['code_total'] * $candySnatchService::SINGLE_PRODUCT_LIMIT;
        //某期商品剩余可投的信宝数
        $remainder = $productInfo[0]['code_total'] - $productInfo[0]['code_used'];
        //最终用户在某期可投入的最大信宝数
        $availableCount = min($maxCount-$periodCodesCount, $remainder, $todayAvailableCount, $accountInfo['amount']);
        //当前时间的小时
        $currentTime = date('H');
        if (intval($currentTime) < intval(substr($candySnatchService::SNATCH_START_TIME,0,2))) {
            $display = '今日';
        }else{
            $display = '明日';
        }
        $inviteAmount = $candySnatchService->getInviteAmount($userId);
        $this->tpl->assign('userId', $userId);
        $this->tpl->assign('productInfo', $productInfo);
        $this->tpl->assign('periodCodes', $periodCodes);
        $this->tpl->assign('periodOrders', $periodOrders);
        $this->tpl->assign('calculate', $calculate);
        $this->tpl->assign('accountInfo', $accountInfo);
        $this->tpl->assign('startTime', $candySnatchService::SNATCH_START_TIME);
        $this->tpl->assign('display', $display);
        $this->tpl->assign('sign', $data['sign']);
        //配置的信宝夺宝一信宝对应的年化额
        $this->tpl->assign('unitAmount', app_conf('CANDY_SNATCH_ANNUALIZED_AMOUNT_CODE_RATE'));
        $this->tpl->assign('periodCodesCount', $periodCodesCount);
        $this->tpl->assign('availableCount', floor($availableCount));
        $this->tpl->assign('maxCount', $maxCount);
        $this->tpl->assign('todayAvailableCount', $todayAvailableCount);
        $this->tpl->assign('remainder', $remainder);
        $this->tpl->assign('presentCount', $inviteAmount);
        $this->tpl->assign('investLimit', app_conf('CANDY_SNATCH_FIRST_INVEST_LIMIT'));
    }
}
