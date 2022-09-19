<?php
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use \Assert\Assertion as Assert;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use core\service\UserService;
use core\service\UserBankcardService;
use core\service\user\WebBO;
use core\service\PaymentService;
use core\service\BankService;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\SupervisionAccountService;
use core\service\PaymentUserAccountService;
use core\service\SupervisionWithdrawService;
use core\service\SupervisionService;
use core\service\ncfph\SupervisionService as PhSupervisionService;
use core\service\ncfph\AccountService as PhAccountService;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use core\service\P2pDealGrantService;
use NCFGroup\Common\Library\Idworker;
use core\dao\EnterpriseModel;
use core\service\AccountService;

/**
 * 第三方资产端用户服务
 * @author longbo
 */
class PtpThirdUserService extends ServiceBase
{

    /**
     * 根据第三方的idno,mobile等检查用户
     * @param SimpleRequestBase $request
     * @return ResponseBase $response
     */
    public function checkUserInfoByThird(SimpleRequestBase $request)
    {
        do {
            $params = $request->getParamArray();
            $logInfo = $params;
            $logInfo['idno'] = idnoFormat($params['idno']);
            $logInfo['bankInfo'] = bankidFormat($params['bankInfo']);
            Logger::info('GetUserByThirdStart.ThirdUser:'.json_encode($logInfo));
            if (empty($params['idno']) || empty($params['mobile'])) {
                $code = -99;
                $msg = '参数错误';
                break;
            }
            $userService = new UserService();
            if (!($userObj = $userService->getUserByIdno($params['idno']))) {
                $userObj = $userService->getByMobile($params['mobile']);
            }
            if (empty($userObj)) {
                $code = 0;
                break;
            }

            $userInfo = $userObj->getRow();
            $logInfo = $userInfo;
            $logInfo['idno'] = idnoFormat($userInfo['idno']);
            Logger::info('GetUserByThird.WxUser:'.json_encode($logInfo));

            if ($userInfo['is_effect'] == 0) {
                $code = -1;
                $msg = '该用户在网信理财是黑名单用户';
                break;
            }

            if (empty($userInfo['idno']) || $userInfo['idcardpassed'] == 0) {
                if ($params['isThirdUser']) {
                    $code = 1; //第三方用户可以继续实名认证
                    break;
                } else {
                    $code = -6; //网信老用户不能绑定
                    $msg = '该手机号已经被注册，且用户未实名认证';
                    break;
                }
            }

            if (strcmp($userInfo['real_name'], $params['realName']) != 0) {
                $code = -2;
                $msg = '该用户与网信用户真实姓名不一致';
                break;
            }

            if (strcmp($userInfo['mobile'], $params['mobile']) != 0) {
                $code = -3;
                $msg = '该用户与网信用户手机号不一致';
                break;
            }

            if (strcmp(strtoupper($userInfo['idno']), strtoupper($params['idno'])) != 0) {
                $code = -4;
                $msg = '该用户与网信用户身份证不一致';
                break;
            }

            $bankInfo = (new UserBankcardService())->getBankcard($userInfo['id']);
            if (empty($bankInfo['bankcard'])) {
                $code = 2;
                break;
            }
            if (strcmp($bankInfo['bankcard'], $params['bankInfo']) != 0) {
                $code = -5;
                $msg = '该用户与网信用户银行卡不一致';
                break;
            }
            if (!in_array(intval($userInfo['user_purpose']),[EnterpriseModel::COMPANY_PURPOSE_FINANCE, EnterpriseModel::COMPANY_PURPOSE_MIX])) {
                $code = -6;
                $msg = '该用户账户类型非融资户';
                break;
            }
            if ($bankInfo['verify_status'] == 0) {
                $code = 3;
                break;
            } else {
                $code = 4;
            }
            $PhService = new PhAccountService();
            $phRes = $PhService->getUserAccountId($userInfo['id'], $userInfo['user_purpose']);
            if (!empty($phRes['accountId'])) {
                $code = 5;
            }
            $authInfo = $this->checkAuth($userInfo['id']);
            if (!$authInfo) {
                $code = 6;
            }

        } while (false);

        $response = new ResponseBase();
        if ($code >= 0) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->resData = ['code' => $code, 'authInfo' => $authInfo, 'userData' => $userInfo];
        } else {
            $response->resCode = RPCErrorCode::FAILD;
            $response->resData = ['code' => $code, 'msg' => $msg];
        }
        Logger::info('GetUserByThirdEnd.ResData:'.json_encode($response->resData));
        return $response;
    }

    /**
     * 第三方用户注册和支付开户
     * @param SimpleRequestBase $request
     * @return ResponseBase $response
     */
    public function registThirdUser(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();

        $setParams = (array) json_decode($params['appInfo']['setParams'], true);
        $siteId = $params['appInfo']['id'];

        $mobile = $params['thirdUserInfo']['mobile'];
        $idno = trim(strtoupper($params['thirdUserInfo']['idno']));
        $realName = trimSpace($params['thirdUserInfo']['realName']);
        $password = substr(md5($mobile . mt_rand(1000000, 9999999)), 0, 10);

        $userInfo  = ['mobile' => $mobile, 'site_id' => $siteId, 'password' => $password];
        if (!empty($setParams['GroupId'])) {
            $userInfo['group_id'] = intval($setParams['GroupId']);
        }
        if (!empty($setParams['CouponLevelId'])) {
            $userInfo['coupon_level_id'] = intval($setParams['CouponLevelId']);
        }
        if (!empty($params['appinfo']['invitecode'])) {
            $userInfo['invite_code'] = $params['appinfo']['invitecode'];
        }

        $response = new ResponseBase();
        $logInfo = $userInfo;
        unset($logInfo['password']);
        Logger::info('ThirdRegistUserInfo:'.json_encode($logInfo));
        try {
            $webBoObj  = new WebBO('web');
            $userInfo['user_purpose'] = EnterpriseModel::COMPANY_PURPOSE_FINANCE;
            $registRes = $webBoObj->insertInfo($userInfo, false);
            if (0 === $registRes['status']) {
                Logger::info('ThirdRegistUserSuccess:'.json_encode($registRes));
                $retData = (array) $registRes['data'];
                $retData['userId'] = $registRes['user_id'];
                $IdUserInfo = [];
                $IdUserInfo['cardNo'] = $idno;
                $IdUserInfo['realName'] = $realName;
                $PaymentSrv = new PaymentService();
                if (PaymentService::REGISTER_FAILURE == $PaymentSrv->register($registRes['user_id'], $IdUserInfo)) {
                    Logger::error('ThirdUserUcfRegistFailed:'.json_encode($IdUserInfo).',LastError:'.$PaymentSrv->getLastError());
                    throw new \Exception('第三方用户支付系统注册失败');
                }
                Logger::info('ThirdRegistUcfpaySuccess.');
                $response->resCode = RPCErrorCode::SUCCESS;
                $response->resData = $retData;
            } else {
                Logger::error('RegistThirdUserFailed.UserInfo:'.json_encode($userInfo).', RegistRes:'.json_encode($registRes));
                throw new \Exception('第三方用户注册失败');
            }
        } catch (\Exception $e) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMessage = $e->getMessage();
        }
        return $response;
    }

    /**
     * 第三方用户支付开户
     * @param SimpleRequestBase $request
     * @return ResponseBase $response
     */
    public function registUcfpay(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();
        $response = new ResponseBase();
        try {
            $idInfo = [];
            $paymentService = new PaymentService();
            if (!empty($params['idno']) && !empty($params['realName'])) {
                $idInfo = ['cardNo' => $params['idno'], 'realName' => $params['realName']];
            }
            if (false === $paymentService->hasRegister($params['userId'])) {
                $res = $paymentService->register($params['userId'], $idInfo);
                if ($res == PaymentService::REGISTER_FAILURE) {
                    Logger::info('ThirdRegUcfPayFail:'.$paymentService->getLastError());
                    throw new \Exception($paymentService->getLastError());
                }
                $response->resCode = RPCErrorCode::SUCCESS;
            }
        } catch (\Exception $e) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMessage = $e->getMessage();
        }
        return $response;
    }

    /**
     * 获取支付绑卡URL
     * @param SimpleRequestBase $request
     * @return ResponseBase $response
     */
    public function getBindCardUrl(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();
        $response = new ResponseBase();
        try {
            $service = new PaymentUserAccountService();
            $bindUrl = $service->h5AuthBindCard([
                'userId' => $params['userId'],
                'returnUrl' => $params['returnUrl'],
                'failUrl' => $params['returnUrl'],
                'reqSource' => 2
                ]);
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->resData = $bindUrl;
        } catch (\Exception $e) {
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMessage = $e->getMessage();
        }
        return $response;
    }

    /**
     * 获取存管开户URL
     * @param SimpleRequestBase $request
     * @return ResponseBase $response
     */
    public function getSupervisionUrl(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();
        $response = new ResponseBase();
        $service = new SupervisionAccountService();
        $registRes = $service->memberRegisterPage(
            $params['userId'],
            'h5',
            ['returnUrl' => $params['returnUrl']],
            false
        );
        if ($registRes['status'] == 'S') {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->resData = $registRes['data']['url'];
        } else {
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMessage = $registRes['respMsg'];
        }
        return $response;
    }

    /**
     * 获取支付系统表单html
     * @param SimpleRequestBase $request
     * @return ResponseBase $response
     */
    public function getFormData(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();
        Logger::info('getFormData:'.json_encode($params));
        $response = new ResponseBase();
        $PhService = new PhSupervisionService();
        $userService = new UserService();
        $userInfo = $userService->getUserByUserId(intval($params['userId']));
        $formData = $PhService->formFactory(
            $params['srv'],
            $params['userId'],
            $userInfo['user_purpose'],
            $params['param'],
            $params['from']
        );
        if (!empty($formData['status'])) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->resForm = $formData['form'];
            $response->resFormId = $formData['formId'];
        } else {
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMessage = $formData['msg'];
        }
        return $response;
    }

    /**
     * 查询绑卡数据
     * @param SimpleRequestBase $request
     * @return ResponseBase $response
     */
    public function queryBankCards(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();
        Logger::info('queryBankCards,ThirdUser:'.json_encode($params));
        $service = new PaymentUserAccountService();
        $res = $service->queryBankCards($params['userId'], $params['isSync']);
        $response = new ResponseBase();
        $response->resCode = RPCErrorCode::FAILD;
        if (!empty($res)) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->resData = $res;
        }
        return $response;
    }

    /**
     * Quick Bind Card
     * @param SimpleRequestBase $request
     * @return ResponseBase $response
     */
    public function quickBindCard(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();
        $logInfo = $params;
        $logInfo['idNo'] = idnoFormat($params['idNo']);
        $logInfo['cardNo'] = bankidFormat($params['cardNo']);
        $logInfo['mobile'] = moblieFormat($params['mobile']);
        Logger::info('quickBindCard,ThirdUser:'.json_encode($logInfo));
        $service = new PaymentService();
        $bindCardParams = [
            'userId' => intval($params['userId']),
            'orderId' => md5(microtime(true)),
            'userName' => $params['realName'],
            'certNo' => $params['idNo'],
            'bankCardNo' => $params['cardNo'],
            'phone' => $params['mobile'],
            ];
        $res = $service->onekeyBindcard($bindCardParams);
        $response = new ResponseBase();
        $response->resCode = RPCErrorCode::FAILD;
        if (!empty($res)) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->resData = $res;
        }
        return $response;
    }

    /**
     * Quick Bind Card
     * @param SimpleRequestBase $request
     * @return ResponseBase $response
     */
    public function quickRegisterSupervision(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();
        $logInfo = $params;
        Logger::info('quickRegisterSupervison,Req:'.json_encode($logInfo));
        $Sas = new SupervisionAccountService();
        $res = $Sas->memberRegisterPage($params['userId'], 'h5', [], false, false, true);
        Logger::info('quickRegisterSupervison,Res:'.json_encode($res));
        $response = new ResponseBase();
        $response->resCode = RPCErrorCode::FAILD;
        if (!empty($res)) {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->resData = $res;
        }
        return $response;
    }

    /**
     * 换卡
     * @param SimpleRequestBase $request
     * @return ResponseBase $response
     */
    public function modifyCard(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();
        $logInfo = $params;
        $logInfo['idNo'] = idnoFormat($params['idNo']);
        $logInfo['bankcard'] = bankidFormat($params['bankcard']);
        $logInfo['bankMobile'] = moblieFormat($params['bankMobile']);
        Logger::info('ThirdModifyCard:'.json_encode($logInfo));
        $userId = intval($params['userId']);
        $realName = $params['realName'];
        $bankcard = $params['bankcard'];
        $bankMobile = $params['bankMobile'];
        $idNo = $params['idNo'];
        $response = new ResponseBase();
        try {
            if (empty($params['outOrderId'])) {
                $accountObj = new AccountService();
                $isHasWxAssets = $accountObj->isUserHasAssets($userId);
                if ($isHasWxAssets) {
                    throw new \Exception('该用户资产不为零');
                }
            }
            $userService = new UserService();
            $userInfo = $userService->getUserByUserId($userId);
            $PhService = new PhSupervisionService();
            $checkRes = $PhService->modifyCardCheck($userId, $userInfo['user_purpose'], $params['outOrderId']);
            if (isset($checkRes['status']) && $checkRes['status'] !== 0) {
                throw new \Exception($checkRes['msg']);
            }
            $bankService = new BankService();
            $canBind = $bankService->checkBankCardCanBind($bankcard, $userId);
            if (!$canBind) {
                throw new \Exception('银行卡已经被占用');
            }
            $paymentService = new PaymentService();
            $cardBinInfo = $paymentService->getCardBinInfoByCardNo($bankcard);
            if ($cardBinInfo['respCode'] !== '00') {
                throw new \Exception('获取银行卡Bin信息失败');
            }

            $accountService = new PaymentUserAccountService();
            $params = [
                'userId' => $userId,
                'userPurpose' => $userInfo['user_purpose'],
                'bankCardNo' => $bankcard,
                'userName' => $realName,
                'phone' => $bankMobile,
                'certNo' => $idNo,
                'updateSvBank' => true,
                ];
            $res = $accountService->quickAuthChangeCard($params);
            if ($res['status'] == '00') {
                $response->resCode = RPCErrorCode::SUCCESS;
                $response->resData = $res;
            } else {
                $response->resCode = $res['status'];
                $response->resMessage = $res['respMsg'];
            }
        } catch( \Exception $e) {
            PaymentApi::log('third modifycard fail, msg:'.$e->getMessage());
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMessage = $e->getMessage();
        }
        return $response;
    }

    /**
     * redo提现
     * @param SimpleRequestBase $request
     * @return ResponseBase $response
     */
    public function redoWithdraw(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();
        Logger::info('ThirdRedoWithdraw:'.json_encode($params));
        $userId = intval($params['userId']);
        $response = new ResponseBase();
        try {
            $PhService = new PhSupervisionService();
            $redoRes = $PhService->redoWithdraw($userId, $params['outOrderId']);
            if (isset($redoRes['status']) && $redoRes['status'] !== 0) {
                throw new \Exception($redoRes['msg']);
            } else {
                $response->resCode = RPCErrorCode::SUCCESS;
                $response->resData = [];
            }
        } catch( \Exception $e) {
            PaymentApi::log('third redoWithdraw fail, msg:'.$e->getMessage());
            $response->resCode = RPCErrorCode::FAILD;
            $response->resMessage = $e->getMessage();
        }

        return $response;
    }

    /**
     * 检查借款人身份
     */
    public function checkAuth($userId)
    {
        return (new SupervisionService())->checkAuth($userId, SupervisionService::GRANT_TYPE_BORROW);

    }
}
