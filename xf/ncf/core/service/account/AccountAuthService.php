<?php
/**
 * 账户授权服务类
 *
 * @author  weiwei12
 * @date 2018-6-22 18:08:29
 */
namespace core\service\account;

Use libs\utils\Logger;
use core\service\BaseService;
use core\service\account\AccountService;
use core\service\supervision\SupervisionService;
use core\service\supervision\SupervisionAccountService;
use core\service\duotou\DtInvestNumService;
use core\service\reserve\UserReservationService;
use core\service\speedloan\SpeedloanService;
use core\service\creditloan\CreditLoanService;
use core\dao\account\AccountAuthModel;
use core\dao\deal\DealModel;
use core\enum\UserAccountEnum;
use core\enum\AccountAuthEnum;

class AccountAuthService extends BaseService {

    /**
     * 授权管理列表
     * 一个账号对应多个账户
     * $params int $userId 账号ID
     * @param boolean $needCheckCancel 是否需要可以解授权
     * @return array
     */
    public static function getAuthList($userId, $needCheckCancel = false) {
        $accountAuthModel = AccountAuthModel::instance();
        //存管账户列表
        $accountList = AccountService::getAccountListByUserId($userId);

        $result = [];
        foreach ($accountList as $account) {
            $accountId = $account['user_id']; // todo 刷表之后修改
            $accountType = $account['account_type'];

            //根据账户用途获取授权类型
            $grantTypeList = self::getGrantTypeByPurpose($accountType);

            //授权状态
            $authList = $accountAuthModel->getAuthListByAccountId($accountId);
            $tempList = [];
            foreach ($grantTypeList as $grantType) {
                $temp = [];
                $temp['grantType'] = $grantType;
                $temp['grantName'] = AccountAuthEnum::$grantTypeName[$grantType];
                $temp['grant'] = array_flip(AccountAuthEnum::$grantTypeMap)[$grantType];
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
                    $checkRet = $needCheckCancel ? self::checkCanCancelAuth($accountId, [$grantType]) : [];
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
            $resultTmp['accountPurpose'] = $accountType;
            $resultTmp['accountPurposeName'] = UserAccountEnum::$accountDesc[UserAccountEnum::PLATFORM_SUPERVISION][$accountType];
            $resultTmp['authList'] = $tempList;
            $result[] = $resultTmp;

        }
        return $result;
    }

    /**
     * 检查账户授权
     * 根据业务检查账户授权
     */
    public static function checkAccountAuth($accountId, $bizType = AccountAuthEnum::BIZ_TYPE_SXY)
    {
        $grantList = [];
        if ($bizType == AccountAuthEnum::BIZ_TYPE_SXY) {
            $grantList[AccountAuthEnum::GRANT_TYPE_INVEST] = 0;
        }
        if ($bizType == AccountAuthEnum::BIZ_TYPE_ZDX) {
            $grantList[AccountAuthEnum::GRANT_TYPE_INVEST] = 0;
            $grantList[AccountAuthEnum::GRANT_TYPE_PAYMENT] = 0;
        }
        if ($bizType == AccountAuthEnum::BIZ_TYPE_BORROW) {
            $grantList[AccountAuthEnum::GRANT_TYPE_REPAY] = 0;
            $grantList[AccountAuthEnum::GRANT_TYPE_PAYMENT] = 0;
        }
        $needGrant = $needGrantArr = $granted = $grantMsg = [];
        try {
            $authInfo = self::checkAuth($accountId, $grantList);
            if (isset($authInfo['code']) && $authInfo['code'] == 1) {
                foreach ($authInfo['data'] as $v) {
                    if ($v['code'] == 1) {
                        $needGrantStr[] = $v['grant'];
                        $needGrantArr[] = $v['grantType'];
                        $grantMsg[] = $v['grantName'].$v['msg'];
                    } else {
                        $granted[] = $v['grantType'];
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::error('checkAuthErr:'.$accountId.','.$e->getMessage());
        }
        $return = [];
        if ($needGrantArr) {
            $return = [
                'needGrantStr' => join(',', $needGrantStr),
                'needGrantArr' => $needGrantArr,
                'grantMsg' => join(',', $grantMsg),
                'grantedArr' => $granted,
                ];
        }
        Logger::info('checkUserAuth:'.$accountId.','.json_encode($return));
        return $return;
    }

    /**
     * 检查授权
     * $params int 账户ID
     * $params array $grantTypeArray 授权类型=>触发金额 (金额单位分)
     * @return array
     */
    public static function checkAuth($accountId, $grantTypeArray = [AccountAuthEnum::GRANT_TYPE_INVEST => 0]) {
        if (!is_array($grantTypeArray)) {
            return ['code' => 2, 'msg' => '参数错误', 'data' => []];
        }

        //获取授权
        $accountAuthModel = AccountAuthModel::instance();
        $authList = $accountAuthModel->getAuthListByAccountId($accountId);
        $checkRet = ['code' => 0, 'msg' => '校验通过', 'data' => []];
        foreach ($grantTypeArray as $grantType => $amount) {
            $grantName = AccountAuthEnum::$grantTypeName[$grantType];
            $grant = array_flip(AccountAuthEnum::$grantTypeMap)[$grantType];
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
    public static function checkCanCancelAuth($accountId, $grantTypeList = [AccountAuthEnum::GRANT_TYPE_INVEST]) {
        if (!is_array($grantTypeList) || $accountId <= 0) {
            return ['code' => 2, 'msg' => ['参数错误']];
        }
        $accountInfo = AccountService::getAccountInfoById($accountId);
        if (empty($accountInfo)) {
            return ['code' => 3, 'msg' => ['账户不存在']];
        }

        $userId = $accountInfo['user_id'];
        $userPurpose = $accountInfo['account_type'];
        if (!in_array($userPurpose, [UserAccountEnum::ACCOUNT_INVESTMENT, UserAccountEnum::ACCOUNT_FINANCE])) {
            return ['code' => 1, 'msg' => ['非投资户、借款户暂不支持取消授权功能']];
        }

        $checkRet = ['code' => 0, 'msg' => ['校验通过']];
        $creditLoanService = new CreditLoanService();
        $dtInvestNumService = new DtInvestNumService();
        $userReservationService = new UserReservationService();
        $dealModel = DealModel::instance();
        $error = [];
        $confirmMsg = []; //取消确认文案
        foreach ($grantTypeList as $grantType) {
            switch ($grantType) {
                //免密投资
                case AccountAuthEnum::GRANT_TYPE_INVEST:
                    $confirmMsg[] = $userPurpose == UserAccountEnum::ACCOUNT_INVESTMENT ? '取消授权后，您将无法使用相关工具' : '';

                    //1、用户有未完成而有效的随心约预约
                    $result = $userReservationService->getEffectReserveCountByUserId($accountId);
                    if ($result !== 0) {
                        $error[] = '随心约有未完成的预约，请先取消预约';
                    }

                    //2、用户有在持的智多鑫资产
                    $result = $dtInvestNumService->getUserOngoingLoanCount($userId);
                    if ($result !== 0) {
                        $error[] = '智多鑫有未转让/退出的资产，请先进行转让/退出';
                    }
                    break;

                //免密还款
                case AccountAuthEnum::GRANT_TYPE_REPAY:
                    $confirmMsg[] = $userPurpose == UserAccountEnum::ACCOUNT_INVESTMENT ? '' : '取消授权后，您将无法正常进行借款';

                    //用户有待放款及未还清的网贷标的
                    $result = $dealModel->getUserInTheLoanCount($userId);
                    if ($result !== 0) {
                        $error[] = '存在待放款及未还清的网贷标的，请先将标的还清';
                    }
                    break;

                //免密缴费
                case AccountAuthEnum::GRANT_TYPE_PAYMENT:
                    $confirmMsg[] = $userPurpose == UserAccountEnum::ACCOUNT_INVESTMENT ? '取消授权后，您将无法使用相关工具' : '取消授权后，您将无法正常进行借款';
                    //1、用户有在持的智多鑫资产
                    $result = $dtInvestNumService->getUserOngoingLoanCount($userId);
                    if ($result !== 0) {
                        $error[] = '智多鑫有未转让/退出的资产，请先进行转让/退出';
                    }
                    //2、用户有待放款及未还清的网贷标的
                    $result = $dealModel->getUserInTheLoanCount($userId);
                    if ($result !== 0) {
                        $error[] = '存在待放款及未还清的网贷标的，请先将标的还清';
                    }

                    //3、用户有未还清的网信速贷借款
                    if (SpeedloanService::userHasLoan($userId)) {
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
    public static function cancelAuth($accountId, $grantTypeList = [AccountAuthEnum::GRANT_TYPE_INVEST]) {
        $result = [
            'code' => 0,
            'msg' => '操作成功',
        ];
        try {
            if (empty($accountId) || empty($grantTypeList)) {
                throw new \Exception('参数错误');
            }

            //存管降级，不能取消
            if (SupervisionService::isServiceDown()) {
                throw new \Exception(SupervisionService::maintainMessage());
            }

            //检查是否可以取消授权
            $checkRet = self::checkCanCancelAuth($accountId, $grantTypeList);
            if ($checkRet['code'] !== 0) {
                throw new \Exception('不可取消授权，请先取消相关业务');
            }

            //删除本地授权
            AccountAuthModel::instance()->deleteAuth($accountId, $grantTypeList);

            // 远程删除授权
            $grantList = self::convertToGrant($grantTypeList);
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
    public static function convertToGrant($grantTypeList = []) {
        $grantList = [];
        foreach ($grantTypeList as $grantType) {
            $map = array_flip(AccountAuthEnum::$grantTypeMap);
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
    public static function getGrantTypeByPurpose($accountPurpose) {
        $grantTypeList = [];
        //投资户最多有 免密投资和免密缴费授权
        //其他账户最多有 免密回款和免密缴费授权
        if ($accountPurpose == UserAccountEnum::ACCOUNT_INVESTMENT) {
            $grantTypeList = [
                AccountAuthEnum::GRANT_TYPE_INVEST,
                AccountAuthEnum::GRANT_TYPE_PAYMENT
            ];
        } else {
            $grantTypeList = [
                AccountAuthEnum::GRANT_TYPE_REPAY,
                AccountAuthEnum::GRANT_TYPE_PAYMENT
            ];
        }
        return $grantTypeList;
    }
}
