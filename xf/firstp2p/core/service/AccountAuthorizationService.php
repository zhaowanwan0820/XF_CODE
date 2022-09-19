<?php
namespace core\service;

use core\dao\AccountAuthorizationModel;
use core\dao\EnterpriseModel;
use core\dao\UserModel;
use core\service\UserReservationService;
use core\service\UserService;
use core\service\CreditLoanService;
use core\service\speedLoan\LoanService;
use core\service\SupervisionAccountService;
use core\service\ncfph\ReserveService as PhReserveService;
use core\service\ncfph\AccountService as PhAccountService;
use libs\utils\Logger;
use libs\payment\supervision\Supervision;

use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

/**
 * 账户授权服务
 */
class AccountAuthorizationService {
    /**
     * 授权管理列表
     * 一个账号对应多个账户
     * $params int $userId 账号ID
     * @param boolean $needCheckCancel 是否需要可以解授权
     * @return array
     */
    public function getAuthList($userId, $needCheckCancel = false) {
        $accountIdArray = [$userId]; //@todo 通过账号获取账户

        $result = [];
        foreach ($accountIdArray as $accountId) {

            //@todo 获取账户用途
            $accountInfo = UserModel::instance()->find($accountId);
            $grantTypeList = $this->getGrantTypeByPurpose($accountInfo['user_purpose']);

            //授权状态
            $accountAuthModel = AccountAuthorizationModel::instance();
            $authList = $accountAuthModel->getAuthListByAccountId($accountId);
            $tempList = [];
            foreach ($grantTypeList as $grantType) {
                $temp = [];
                $temp['grantType'] = $grantType;
                $temp['grantName'] = AccountAuthorizationModel::$grantTypeName[$grantType];
                $temp['grant'] = array_flip(AccountAuthorizationModel::$grantTypeMap)[$grantType];
                $temp['isOpen'] = 0; //开通状态
                $temp['isExpire'] = 0; //是否过期
                $temp['grantAmount'] = '';
                $temp['grantAmountFormat'] = '';
                $temp['grantTime'] = '';
                $temp['grantTimeFormat'] = '';
                $msg = [];
                $confirmMsg = [];//取消确认文案
                if (isset($authList[$grantType])) {
                    $temp['isOpen'] = 1; //开通状态
                    $auth = $authList[$grantType];
                    $temp['isExpire'] = $auth['grant_time'] != 0 && time() > $auth['grant_time'] ? 1 : 0;
                    $temp['grantAmount'] = $auth['grant_amount'];
                    $temp['grantAmountFormat'] = $auth['grant_amount'] > 0 ? bcdiv($auth['grant_amount'], 1000000) . '万' : '无限制';
                    $temp['grantTime'] = $auth['grant_time'];
                    $temp['grantTimeFormat'] = $auth['grant_time'] > 0 ? date('Y年m月d日', $auth['grant_time']) : '无限制';
                    $checkRet = $needCheckCancel ? $this->checkCanCancelAuth($accountId, [$grantType]) : [];
                    if (isset($checkRet['code']) && $checkRet['code'] == 1) {
                        $msg = $checkRet['msg'];
                    }
                    $confirmMsg = isset($checkRet['confirmMsg']) ? $checkRet['confirmMsg'] : [];
                }
                $temp['msg'] = $msg;
                $temp['confirmMsg'] = $confirmMsg;
                $tempList[] = $temp;
            }

            $resultTmp = [];
            $resultTmp['accountId'] = $accountId;
            $resultTmp['accountPurpose'] = $accountInfo['user_purpose'];
            $resultTmp['accountPurposeName'] = UserAccountEnum::$accountDesc[UserAccountEnum::PLATFORM_SUPERVISION][$accountInfo['user_purpose']];
            $resultTmp['authList'] = $tempList;
            $result[] = $resultTmp;

        }
        return $result;
    }

    /**
     * 检查授权
     * $params int $accountId 账户ID
     * $params array $grantTypeArray 授权类型=>触发金额 (金额单位分)
     * @return array
     */
    public function checkAuth($accountId, $grantTypeArray = [AccountAuthorizationModel::GRANT_TYPE_INVEST => 0]) {
        if (!is_array($grantTypeArray)) {
            return ['code' => 2, 'msg' => '参数错误', 'data' => []];
        }

        //获取授权
        $accountAuthModel = AccountAuthorizationModel::instance();
        $authList = $accountAuthModel->getAuthListByAccountId($accountId);
        $checkRet = ['code' => 0, 'msg' => '校验通过', 'data' => []];
        foreach ($grantTypeArray as $grantType => $amount) {
            $grantName = AccountAuthorizationModel::$grantTypeName[$grantType];
            $grant = array_flip(AccountAuthorizationModel::$grantTypeMap)[$grantType];
            try {
                //检查是否开通
                if (!isset($authList[$grantType])) {
                    throw new \Exception('未开通', 1);
                }

                //检查授权过期
                $auth = $authList[$grantType];
                if ($auth['grant_time'] != 0 && time() > $auth['grant_time']) {
                    throw new \Exception('已过期', 2);
                }

                //检查单笔上限
                if ($auth['grant_amount'] != 0 && $amount > $auth['grant_amount']) {
                    throw new \Exception('超过单笔上限', 3);
                }

                $checkRet['data'][] = ['code' => 0, 'msg' => '校验通过', 'grantType' => $grantType, 'grantName' => $grantName, 'grant' => $grant];
            } catch (\Exception $e) {
                $checkRet['code'] = 1;
                $checkRet['msg'] = '校验失败';
                $checkRet['data'][] = ['code' => $e->getCode(), 'msg' => $e->getMessage(), 'grantType' => $grantType, 'grantName' => $grantName, 'grant' => $grant];
            }
        }
        return $checkRet;
    }

    /**
     * 是否能取消授权
     * $params int $accountId 账户ID
     */
    public function checkCanCancelAuth($accountId, $grantTypeList = [AccountAuthorizationModel::GRANT_TYPE_INVEST]) {
        if (!is_array($grantTypeList) || $accountId <= 0) {
            return ['code' => 2, 'msg' => ['参数错误']];
        }

        //@todo
        $userId = $accountId; //转换为账号ID
        $accountInfo = UserModel::instance()->find($accountId);
        if (!in_array($accountInfo['user_purpose'], [UserAccountEnum::ACCOUNT_INVESTMENT, UserAccountEnum::ACCOUNT_FINANCE])) {
            return ['code' => 1, 'msg' => ['非投资户、借款户暂不支持取消授权功能']];
        }

        $checkRet = ['code' => 0, 'msg' => ['校验通过']];
        $phReserveService = new PhReserveService();
        $phAccountService = new PhAccountService();
        $userService = new UserService();
        $creditLoanService = new CreditLoanService();
        $loanService = new LoanService();
        $error = [];
        $confirmMsg = []; //取消确认文案
        foreach ($grantTypeList as $grantType) {
            switch ($grantType) {
                //免密投资
                case AccountAuthorizationModel::GRANT_TYPE_INVEST:
                    $confirmMsg[] = $accountInfo['user_purpose'] == UserAccountEnum::ACCOUNT_INVESTMENT ? '取消授权后，您将无法使用相关工具' : '';

                    //1、用户有未完成而有效的随心约预约
                    $result = $phReserveService->getEffectReserveCountByUserId($userId);
                    if ($result !== 0) {
                        $error[] = '随心约有未完成的预约，请先取消预约';
                    }

                    //2、用户有在持的智多新资产
                    $result = $userService->getUserDuotouInTheLoanCount($userId);
                    if ($result !== 0) {
                        $error[] = '智多新有未转让/退出的资产，请先进行转让/退出';
                    }
                    break;

                //免密还款
                case AccountAuthorizationModel::GRANT_TYPE_REPAY:
                    $confirmMsg[] = $accountInfo['user_purpose'] == UserAccountEnum::ACCOUNT_INVESTMENT ? '' : '取消授权后，您将无法正常进行借款';

                    //用户有待放款及未还清的网贷标的
                    $result = $phAccountService->getUserInTheLoanCount($userId);
                    if ($result !== 0) {
                        $error[] = '存在待放款及未还清的网贷标的，请先将标的还清';
                    }
                    break;

                //免密缴费
                case AccountAuthorizationModel::GRANT_TYPE_PAYMENT:
                    $confirmMsg[] = $accountInfo['user_purpose'] == UserAccountEnum::ACCOUNT_INVESTMENT ? '取消授权后，您将无法使用相关工具' : '取消授权后，您将无法正常进行借款';
                    //1、用户有在持的智多新资产
                    $result = $userService->getUserDuotouInTheLoanCount($userId);
                    if ($result !== 0) {
                        $error[] = '智多新有未转让/退出的资产，请先进行转让/退出';
                    }
                    //2、用户有待放款及未还清的网贷标的
                    $result = $phAccountService->getUserInTheLoanCount($userId);
                    if ($result !== 0) {
                        $error[] = '存在待放款及未还清的网贷标的，请先将标的还清';
                    }

                    //3、用户有未还清的网信速贷借款
                    if ($loanService->userHasLoan($userId)) {
                        $error[] = '网信速贷有未还清的借款，请先将网信速贷还清';
                    }
                    //4、用户有未还清的银信通借款
                    if ($creditLoanService->isShowCreditEntrance($userId)) {
                        $error[] = '银信通有未还清的借款，请先将银信通还清';
                    }
                    break;

                default:
                    $error[] = '未知授权类型';
            }
        }
        if ($error) {
            $checkRet['code'] = 1;
            $checkRet['msg'] = array_unique($error);
        }
        $checkRet['confirmMsg'] = $confirmMsg;
        return $checkRet;
    }


    /**
     * 取消授权
     * @param  int $accountId
     * @param  array $grantTypeList
     * @return array
     */
    public function cancelAuth($accountId, $grantTypeList = [AccountAuthorizationModel::GRANT_TYPE_INVEST]) {
        $result = [
            'code' => 0,
            'msg' => '操作成功',
        ];
        try {
            if (empty($accountId) || empty($grantTypeList)) {
                throw new \Exception('参数错误');
            }

            //存管降级，不能取消
            if (Supervision::isServiceDown()) {
                throw new \Exception(Supervision::maintainMessage());
            }

            //检查是否可以取消授权
            $checkRet = $this->checkCanCancelAuth($accountId, $grantTypeList);
            if ($checkRet['code'] !== 0) {
                throw new \Exception('不可取消授权，请先取消相关业务');
            }

            //删除本地授权
            AccountAuthorizationModel::instance()->deleteAuth($accountId, $grantTypeList);

            // TODO 远程删除授权
            $grantList = $this->convertToGrant($grantTypeList);
            $supervisionAccountService = new SupervisionAccountService();
            $supervisionAccountService->memberAuthorizationCancel($accountId, $grantList);

        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
            $result['code'] = 1;
        }
        return $result;
    }

    /**
     * 转换枚举值为授权名称
     * @params array $grantTypeList
     */
    public function convertToGrant($grantTypeList = []) {
        $grantList = [];
        foreach ($grantTypeList as $grantType) {
            $map = array_flip(AccountAuthorizationModel::$grantTypeMap);
            if (!isset($map[$grantType])) {
                throw new \Exception('未知的授权类型');
            }
            $grantList[] = $map[$grantType];
        }
        return $grantList;
    }

    /**
     * 根据账户用途获取授权类型
     */
    public function getGrantTypeByPurpose($accountPurpose) {
        $grantTypeList = [];
        //投资户最多有 免密投资和免密缴费授权
        //其他账户最多有 免密回款和免密缴费授权
        if ($accountPurpose == EnterpriseModel::COMPANY_PURPOSE_INVESTMENT) {
            $grantTypeList = [
                AccountAuthorizationModel::GRANT_TYPE_INVEST,
                AccountAuthorizationModel::GRANT_TYPE_PAYMENT
            ];
        } else {
            $grantTypeList = [
                AccountAuthorizationModel::GRANT_TYPE_REPAY,
                AccountAuthorizationModel::GRANT_TYPE_PAYMENT
            ];
        }
        return $grantTypeList;
    }
}
