<?php

use core\dao\UserThirdBalanceModel;
use core\dao\UserModel;
use core\service\SupervisionAccountService;
use core\service\UserThirdBalanceService;
use core\service\ncfph\AccountService as PhAccountService;
class UserThirdBalanceAction extends CommonAction {

    public function __construct() {
        parent::__construct();
    }

    public function index()
    {
        $userId = intval($_REQUEST['userId']);
        $user = UserModel::instance()->find($userId);
        $phAccountService = new PhAccountService();
        $phResult = $phAccountService->getInfoByUserIdAndType($userId, $user['user_purpose']);
        $thirdBalance = ['supervision' => [
            'supervisionMoney' => isset($phResult) ? $phResult['totalMoney'] : 0,
            'supervisionBalance' => isset($phResult) ? $phResult['money'] : 0,
            'supervisionLockMoney' => isset($phResult) ? $phResult['lockMoney'] : 0,
        ]];

        $summary = $phAccountService->getSummary($userId);
        $this->assign('balance', $thirdBalance);
        $this->assign('realBalance', $this->getRealBalance($userId));
        $this->assign('typeDesc', UserThirdBalanceModel::$balanceTypeDesc);
        $this->assign('balanceEnum', UserThirdBalanceModel::$balanceEnum);
        $this->assign('summary', $summary);
        $this->display();
    }

    public function syncBalance()
    {
        $userId = intval($_REQUEST['userId']);
        $balanceType = trim($_REQUEST['type']);
        $redirectUrl = '/m.php?m=UserThirdBalance&userId='. $userId;
        if (!array_key_exists($balanceType, UserThirdBalanceModel::$balanceEnum)) {
            $this->error('错误的余额类型', '', $redirectUrl);
        }

        $function = 'sync' . ucfirst($balanceType).'Balance';
        if (!$this->$function($userId)) {
            $this->error('余额同步失败', '', $redirectUrl);
        }
        $this->success('同步成功', '', $redirectUrl);
    }

    private function getRealBalance($userId)
    {
        foreach (UserThirdBalanceModel::$balanceEnum as $key => $item) {
            $function = 'get'.ucfirst($key).'RealBalance';
            $realBalance[$key] = $this->$function($userId);
        }
        return $realBalance;
    }

    private function getSupervisionRealBalance($userId)
    {
        $data = [
            'supervisionBalance' => '',
            'supervisionLockMoney' => ''
        ];
        $supervisionService = new SupervisionAccountService();
        $supervisionBalance = $supervisionService->balanceSearch($userId);
        if ($supervisionBalance['status'] != SupervisionAccountService::RESPONSE_SUCCESS) {
            return $data;
        }

        $data['supervisionBalance'] = bcdiv($supervisionBalance['data']['availableBalance'], 100, 2);
        $data['supervisionLockMoney'] = bcdiv($supervisionBalance['data']['freezeBalance'], 100, 2);
        return $data;
    }

    private function syncSupervisionBalance($userId) {
        $user = UserModel::instance()->find($userId);
        $phAccountService = new PhAccountService();
        return $phAccountService->syncAccountMoney($userId, $user['user_purpose']);
    }
}
