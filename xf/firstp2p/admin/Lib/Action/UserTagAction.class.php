<?php

/**
 * 1.标签管理|增加标签|修改标签|删除标记|标记标签无效
 * 2.编辑用户标签|增加标签|删除标签
 *
 * @author Wang Shi Jie <wangshijie@ucfgroup.com>
 */


use core\service\UserTagService;
use core\service\UserTagRelationService;
use core\service\PaymentService;
use core\service\UserService;
use core\dao\UserBankcardModel;
use libs\utils\PaymentApi;

class UserTagAction extends CommonAction {

    /**
     * 展示数据整理
     */
    protected function form_index_list(&$list) {
        foreach($list as &$item) {
            $item['status'] = $item['status'] == 0 ? '无效' : '有效';
        }
    }

    /**
     * 管理用户标签
     */
    public function edit_relation() {
        //ini_set('display_errors', 1);
        //error_reporting(E_ALL); 
        if (!$_REQUEST['uid']) {
            $this->error('错误的用户ID');
        }
        $userTagService = new UserTagService();
        if (isset($_POST['uid']) && isset($_POST['tags'])) {//$_REQUEST $_POST
            $uid = intval($_REQUEST['uid']);
            $tagids = $_REQUEST['tags'];
            //存管白名单标签ID
            $whiteListTagId = $userTagService->getSupervisionStaticWhitelistTagId();
            //白名单标签是否保存成功
            $isWhiteTagSaveAble = true;
            //白名单标签是否取消保存成功
            $isWhiteTagCancelAble = true;
            $saveWhitelistTagResult = $successSavedWhiteListTagResult = array();
            $cancelWhitelistTagResult = $successCanceldWhiteListTagResult = array();
            //白名单标签审查操作流程结果消息、用户类型：个人，企业
            $whitelistTagRespMsg = $userType = '';

            //更新前用户标签数组
            $userOriginTags = $userTagService->getTags($uid);
            //用户是否已有白名单标签
            $userHasWhiteListTag = array_key_exists($whiteListTagId, $userOriginTags);
            //请求中是否包含白名单标签 
            $requestHasWhiteListTag = in_array($whiteListTagId, $tagids);
            if ($requestHasWhiteListTag && !$userHasWhiteListTag) {
                //当前用户没有白名单标签，增加白名单标签流程。（已有白名单标签用户无需流程检查） 
                //新增存管白名单标签判断流程
                $saveWhitelistTagResult = $this->saveWhitelistTag($uid);
                $isWhiteTagSaveAble = $saveWhitelistTagResult['ret'];
                $userType = $saveWhitelistTagResult['userType'];
                $whitelistTagRespMsg = $saveWhitelistTagResult['respMsg'];
                if ($isWhiteTagSaveAble === false) {
                    //不能保存白名单标签，从请求中去除白名单标签ID
                    foreach ($tagids as $k => $v) {
                        if ($v == $whiteListTagId) {
                            unset($tagids[$k]);
                            break;
                        }
                    }
                }
            }//end of add whitelist tag check
            $cancelWhitelistTag = false; //是否取消选择白名单标签
            if ($userHasWhiteListTag && $requestHasWhiteListTag === false) {
                //用户已经有白名单标签，操作取消白名单标签
                $cancelWhitelistTag = true;
                //暂时不能取消用户白名单标签，需要时取消以下代码注释
                $cancelWhitelistTagResult = $this->cancelWhitelistTag($uid);
                $isWhiteTagCancelAble = $cancelWhitelistTagResult['ret'];
                $whitelistTagRespMsg = $cancelWhitelistTagResult['respMsg'];
                $userType = $cancelWhitelistTagResult['userType'];
                //不能取消白名单标签，在POST请求中补充增加白名单标签ID
                if ($isWhiteTagCancelAble === false) {
                    $tagids[] = $whiteListTagId;
                }
            }//end of cancel whitelist tag check
            //保存标签
            $userTagService->setTags($uid, $tagids);

            //增加白名单标签处理结果返回
            if (!$userHasWhiteListTag && $requestHasWhiteListTag && $isWhiteTagSaveAble === false) {
                $this->error('网信存管白名单标签保存失败，原因：' . $whitelistTagRespMsg);
            }
            if (!$userHasWhiteListTag && $requestHasWhiteListTag && $isWhiteTagSaveAble === true) {// && $whitelistTagRespMsg
                $successSavedWhiteListTagResult = $this->successSavedWhiteListTag($saveWhitelistTagResult);
                if ($successSavedWhiteListTagResult['ret'] === false) {
                    $this->success('网信存管白名单标签保存成功！但：' . $successSavedWhiteListTagResult['respMsg']);
                }
            }

            //取消白名单标签处理结果返回
            if ($userHasWhiteListTag && $cancelWhitelistTag && $isWhiteTagCancelAble === false) {
                $this->error('网信存管白名单标签取消失败，原因：' . $whitelistTagRespMsg);
            }
            if ($userHasWhiteListTag && $cancelWhitelistTag && $isWhiteTagCancelAble === true) {// && $whitelistTagRespMsg
                $successCanceldWhiteListTagResult = $this->successCanceldWhiteListTag($cancelWhitelistTagResult);
                if ($successCanceldWhiteListTagResult['ret'] === false) {
                    $this->success('网信存管白名单标签取消成功！但：' . $successCanceldWhiteListTagResult['respMsg']);
                }
            }
        }//end of post uid and tags
        $uid = intval($_REQUEST['uid']);
        $user_tags = $userTagService->getTags($uid);
        $tag_list = $userTagService->lists();
        $list = array();
        foreach ($tag_list as $tag) {
            $list[] = array('tag_id' => $tag['id'], 'tag_name' => $tag['name'], 'checked' => (isset($user_tags[$tag['id']]) ? '1' : 0));
        }
        $this->assign('uid', $uid);
        $this->assign('user_name', M('User')->where("`id`=$uid")->getField('user_name'));
        $this->assign('tags', $list);
        $this->display('edit_relation');
    }

    /**
     * 增加存管白名单标签 用户状态判断流程 
     * @param type $uid 
     * @return array $ret array(
     *      userType : 用户类型 user: 个人 enterprise: 企业
     *      respMsg  : 消息
     *      respCode : 结果编码 
     *                  >0成功 
     *                  <0失败  
     *                  -1000 to -2000 企业类型用户保存失败 
     *                  -2000 to -3000 个人用户类型保存失败
     *      
     */
    public function saveWhitelistTag($uid) {

        $userService = new UserService($uid);
        $ret = array('ret' => true, 'user_id' => $uid, 'respMsg' => '');
        try {
            if ($userService->isEnterpriseUser()) {
                //企业会员
                $ret['userType'] = 'enterprise';
                if (!$userService->isBankCardBinded()) {
                    throw new Exception('企业用户，尚未绑定银行卡', -1001);
                }
            } else {
                //个人会员
                $ret['userType'] = 'user';
                if (!$userService->isIdCardPassed()) {
                    throw new Exception('个人用户，尚未通过实名验证', -2001);
                }
                if (!$userService->isBankCardBinded()) {
                    throw new Exception('个人用户，尚未绑定银行卡', -2002);
                }
                if (!$userService->isBankCardUnverfied()) {
                    throw new Exception('个人用户，银行卡已通过验证', -2003);
                }
                //个人会员保存白名单标签后调用 白名单支付认证类型接口
                $params = $userService->paymentWhiteListApi($uid);
                if (!((isset($params['userId']) && $params['userId']) && (isset($params['cardNo']) && $params['cardNo']))) {
                    throw new Exception('支付白名单认证类型接口调用错误(' . $params['respMsg'] . ',ERROR CODE=' . $params['status'] . ')', -2004);
                }
            }
        } catch (\Exception $ex) {
            $ret['respCode'] = $errorCode = $ex->getCode();
            $ret['respMsg'] = $ex->getMessage();
            if ($errorCode < 0) {
                $ret['ret'] = false;
            }
        }
        return $ret;
    }

    /**
     * 成功保存白名单后流程
     * 1.更新四要素认证状态
     * 2.调用认证类型查询接口，回吐最新支付认证类型 
     * 3.完成理财侧用户认证类型更新
     * @param array $saveWhitelistTagResult
     */
    public function successSavedWhiteListTag($saveWhitelistTagResult) {
        $userId = $saveWhitelistTagResult['user_id'];
        $saveRet = $saveWhitelistTagResult['ret'];         //保存结果
        $userType = $saveWhitelistTagResult['userType'];   //用户类型 
        $respCode = $saveWhitelistTagResult['respCode'];
        $respMsg = $saveWhitelistTagResult['respMsg'];
        $ret = array('ret' => true, 'user_id' => $userId, 'respMsg' => '');
        try {

            if ($userType == 'user') {
                //个人用户
                $paymentService = new PaymentService();
                $userService = new UserService($userId);
                $userName = $userService->getUserName();
                //2.调用认证类型查询接口，回吐最新支付认证类型
                $bankInfo = $userService->paymentVerifyTypeApi($userId);
                if (isset($bankInfo['certStatus']) && $bankInfo['certStatus']) {
                    //ps:此处需要2步骤的回吐最新信息，所以先执行2取信息
                    $certStatus = $bankInfo['certStatus'];
                    $cardNo = $bankInfo['cardNo'];
                    //1.更新四要素认证状态
                    $verifyStatusUpdateResult = $userService->updateUserBankcardVerifyStatus($userId, $cardNo);
                    if ($verifyStatusUpdateResult === false) {
                        throw new Exception('个人用户，更新四要素认证状态失败！请手动更新！！', -2101);
                    }
                    //3.完成理财侧用户认证类型更新
                    $updateRet = $userService->updateUserBankcardCertStatus($userId, $cardNo, $certStatus);
                    if ($updateRet === false) {
                        throw new Exception('个人用户，理财侧用户认证类型更新失败，请手动更新认证类型！！', -2102);
                    }
                } else {
                    throw new Exception('个人用户，支付认证类型更新失败，无法完成四要素认证状态更新，请手动更新认证类型与四要素状态!!(支付认证类型查询接口回吐数据格式错误:' . $bankInfo['respMsg'] . ',ERROR CODE=' . $bankInfo['respCode'] . ')', -2103);
                }
            } else {
                //企业用户
            }
        } catch (\Exception $ex) {
            $ret['respCode'] = $errorCode = $ex->getCode();
            $ret['respMsg'] = $ex->getMessage();
            if ($errorCode < 0) {
                $ret['ret'] = false;
            }
        }
        return $ret;
    }

    /**
     * 取消存管白名单标签 用户状态判断流程 
     * @param type $userId 
     */
    public function cancelWhitelistTag($userId) {

        $userService = new UserService($userId);
        $ret = array('ret' => true, 'respMsg' => '');
        $ret['user_id'] = $userId;
        try {
            if ($userService->isEnterpriseUser()) {
                //企业会员
                $ret['userType'] = 'enterprise';
                throw new Exception('企业用户不可取消白名单认证，取消失败', -5001);
            } else {
                //个人会员
                $ret['userType'] = 'user';
                if ($userService->isSupervisionUser()) {
                    throw new Exception('个人用户已开通存管账户，取消失败', -6001);
                }
            }
            // 读取用户绑卡数据
            $bankModel = new UserBankcardModel();
            $userBankcard = $bankModel->getByUserId($userId);
            if (empty($userBankcard['bankcard'])) {
                throw new \Exception('该用户尚未绑定银行卡,取消失败', -6002);
            }

            // 调用支付接口
            $result = PaymentApi::instance()->request('cancelAuth', [
                'userId' => $userId,
                'bankCardNo' => $userBankcard['bankcard']
            ]);
            if (!isset($result['status']) || $result['status'] != '00') {
                $msg = $result['respMsg'] ?: '接口返回错误';
                throw new \Exception('支付取消白名单失败,接口返回:'.$msg, -6002);
            }

        } catch (\Exception $ex) {
            $ret['respCode'] = $errorCode = $ex->getCode();
            $ret['respMsg'] = $ex->getMessage();
            if ($errorCode < 0) {
                $ret['ret'] = false;
            }
        }
        return $ret;
    }

    /**
     * 成功取消白名单后流程
     * @param array $cancelWhitelistTagResult
     */
    public function successCanceldWhiteListTag($cancelWhitelistTagResult) {
        $userId = $cancelWhitelistTagResult['user_id'];
        $cancelRet = $cancelWhitelistTagResult['ret'];         //取消结果
        $userType = $cancelWhitelistTagResult['userType'];   //用户类型 
        $respCode = $cancelWhitelistTagResult['respCode'];
        $respMsg = $cancelWhitelistTagResult['respMsg'];
        $ret = array('ret' => true, 'user_id' => $userId, 'respMsg' => '');
        try {
            if ($userType == 'user') {
                //个人用户
                $userService = new UserService();
                $ucfUserBankCardInfo = $userService->paymentVerifyTypeApi($userId);
                if (!isset($ucfUserBankCardInfo['status']) || $ucfUserBankCardInfo['status'] == 'F') {
                    throw new Exception('理财侧用户认证类型更新失败，请手动更新认证类型！！', -6102);
                }
                $updateRet = $userService->updateUserBankcardCertStatus($userId, $ucfUserBankCardInfo['cardNo'], $ucfUserBankCardInfo['certStatus']);
                if ($updateRet === false) {
                    throw new Exception('理财侧用户认证类型更新失败，请手动更新认证类型！！', -6102);
                }
            } else {
                //企业用户
            }
        } catch (\Exception $ex) {
            $ret['respCode'] = $errorCode = $ex->getCode();
            $ret['respMsg'] = $ex->getMessage();
            if ($errorCode < 0) {
                $ret['ret'] = false;
            }
        }
        return $ret;
    }
}

