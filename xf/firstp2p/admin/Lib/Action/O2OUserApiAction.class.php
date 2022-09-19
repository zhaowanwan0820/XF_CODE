<?php

use core\service\UserService;
use core\service\UserTagService;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use core\service\O2OService;
use core\dao\DealLoadModel;
use core\dao\OtoAcquireLogModel;
use core\service\RemoteTagService;
use core\dao\UserModel;
use core\dao\DiscountRateModel;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

/**
 * 提供给O2O用户操作的接口
 */

class O2OUserApiAction extends CommonAction {
    // 需要考虑安全性问题
    public function __construct() {

    }

    protected function signature($datas, $key = 'afjd32t4-#of=2a;2fd#c@ff') {
        ksort($datas);
        $tmp = array();
        foreach ($datas as $k => $v) {
            if ("" !== $v && null != $v && ($k !== 'signature' && $k !== 'sign')) {
                $tmp[] = $k ."=". $v;
            }
        }
        $query_string = implode('&', $tmp);
        return md5($query_string."&key=".$key);
    }

    public function getUserInfo() {
        $userService = new UserService();
        $userTagService = new UserTagService();
        $remoteTagService = new RemoteTagService();
        $coupon_service = new \core\service\CouponService();
        try {
            $sign = trim($_POST['sign']);
            if (empty($sign)) {
                throw new \Exception('sign不能为空', 1000);
            }

            $userIds = trim($_POST['userIds']);
            if (!$userIds) {
                throw new \Exception('用户id不能为空', 1001);
            }

            if ($sign != $this->signature(array('userIds'=>$userIds))) {
                throw new \Exception('sign参数不正确', 1002);
            }

            $userIdArray = explode('|', $userIds);
            $result = array();
            foreach ($userIdArray as $userId) {
                $userInfo = $userService->getUserArray($userId);
                if (!empty($userInfo)) {
                    $user_coupon = $coupon_service->getUserCoupon($userId);
                    if ($user_coupon) {
                        $userInfo['coupon'] = $user_coupon;
                    } else {
                        $userInfo['coupon'] = '';
                    }

                    $userTags = $userTagService->getTags($userId);
                    $userRemoteTags = $remoteTagService->getUserAllTag($userId);
                    $result[$userId] = array('user' => $userInfo, 'userTags' => $userTags,
                        'userRemoteTags'=>$userRemoteTags);
                }
            }
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
        $this->_response($result);
        return true;
    }

    /**
     * getUserIdByIdno根据用户身份证号查询用户id
     * @author yanbingrong <yanbingrong@ucfgroup.com>
     * @date 2015-10-20
     */
    public function getUserByIdno() {
        $userService = new UserService();
        $result = array();
        try {
            $sign = trim($_POST['sign']);
            if (empty($sign)) {
                throw new \Exception('sign不能为空', 1000);
            }

            $idno = trim($_POST['idno']);
            if (empty($idno)) {
                throw new \Exception('身份证号不能为空', 1002);
            }

            if ($sign != $this->signature(array('idno'=>$idno))) {
                throw new \Exception('sign参数不正确', 1002);
            }

            $result = $userService->getUserByIdno($idno);
            if ($result) {
                $result = $result->getRow();
            }
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
        $this->_response($result);
        return true;
    }

    /**
     * 根据code查询用户信息
     * @author yanbingrong <yanbingrong@ucfgroup.com>
     * @date 2016-05-09
     */
    public function getUserByCode() {
        $couponService = new \core\service\CouponService();
        $result = array();
        try {
            $sign = trim($_POST['sign']);
            if (empty($sign)) {
                throw new \Exception('sign不能为空', 1000);
            }

            $code = trim($_POST['code']);
            if (empty($code)) {
                throw new \Exception('邀请码不能为空', 1002);
            }

            if ($sign != $this->signature(array('code'=>$code))) {
                throw new \Exception('sign参数不正确', 1002);
            }

            $result = $couponService->checkCoupon($code);
            if ($result === false) {
                throw new \Exception('邀请码不存在', 1003);
            }
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
        $this->_response($result);
        return true;
    }

    public function getUserIdByName() {
        $userService = new UserService();
        $result = array('userId' => 0);

        try {
            $sign = trim($_POST['sign']);
            if (empty($sign)) {
                throw new \Exception('sign不能为空', 1000);
            }

            $username = trim($_POST['username']);
            if (empty($username)) {
                throw new \Exception('用户名不能为空', 1001);
            }

            if ($sign != $this->signature(array('username'=>$username))) {
                throw new \Exception('sign参数不正确', 1002);
            }

            $userIds = UserModel::instance()->getUserIdsByRealName($username);
            $result = array('userId' => $userIds);
        }catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
        $this->_response($result);
        return true;
    }

    /**
     * getUserIdByMobile根据用户手机号查询用户id
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2015-10-13
     */
    public function getUserIdByMobile() {
        $userService = new UserService();
        $result = array('userId' => 0);
        try {
            $sign = trim($_POST['sign']);
            if (empty($sign)) {
                throw new \Exception('sign不能为空', 1000);
            }

            $mobile = trim($_POST['mobile']);
            if (empty($mobile)) {
                throw new \Exception('手机号不能为空', 1001);
            }
            // 手机号验证，防止sql注入
            $mobileRule = '#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#';
            if (!preg_match($mobileRule, $mobile)) {
                throw new \Exception('不是有效的手机号', 1001);
            }

            if ($sign != $this->signature(array('mobile'=>$mobile))) {
                throw new \Exception('sign参数不正确', 1002);
            }

            $userId = $userService->getUserIdByMobile($mobile);
            if(!empty($userId)) {
                $result = array('userId' => $userId);
            }
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
        $this->_response($result);
        return true;
    }

    public function modifyUser() {
        try {
            $postParams = array(
                'idno' => array('required' => true, 'type' => 'string'),
                'groupId' => array('required' => false, 'type' => 'int'),
                'couponLevelId' => array('required' => false, 'type' => 'int'),
                'tags' => array('required' => false, 'type' => 'string'),
                'isActive' => array('required' => true, 'type' => 'int'),
                'remoteTags' => array('required' => false, 'type' => 'string'),
                'userId' => array('required' => false, 'type' => 'int')
            );
            foreach ($postParams as $field => $verifyOptions) {
                if ($verifyOptions['type'] == 'int')  {
                    $$field = intval($_POST[$field]) ? intval($_POST[$field]) : 0;
                } else {
                    $$field = trim($_POST[$field]) ? trim($_POST[$field]) : '';
                }

                if ($field == 'isActive') {
                    if ($$field !== 0 && !$$field) {
                        throw new \Exception('参数' .$field. '不能为空', 1001);
                    }
                } else if ($verifyOptions['required'] && !$$field) {
                    throw new \Exception('参数' .$field. '不能为空', 1001);
                }
            }

            if ((!$groupId && $couponLevelId) || ($groupId && !$couponLevelId)) {
                throw new \Exception('组ID和优惠码等级ID不匹配', 1001);
            }

            $groupUpdate = true;
            if (!$groupId && !$couponLevelId) {
                $groupUpdate = false;
            }

            $userService = new UserService();
            $tagService = new UserTagService();
            $remoteTagService = new RemoteTagService();
            if (empty($userId)) {
                $users = $userService->getAllUserByIdno($idno);
                if (count($users) > 1) {
                    $msg = '该身份证号对应多个用户';
                    PaymentApi::log($msg.'users: '.json_encode($users, JSON_UNESCAPED_UNICODE).', idno: '.$idno, Logger::ERR);
                    throw new \Exception($msg, 2000);
                }
                if (empty($users)) {
                    throw new \Exception('用户不存在', 2001);
                }
                $userInfo = array_pop($users);
            } else {
                $userInfo = $userService->getUserByUserId($userId);
                if (empty($userInfo)) {
                    throw new \Exception('用户不存在', 2001);
                }

                if (strtoupper($idno) != strtoupper($userInfo['idno'])) {
                    throw new \Exception('用户数据不一致, request idno: '.$idno.', user idno: '.$userInfo['idno'], 2002);
                }
            }

            if ($groupUpdate) {
                //TODO 验证groupId是否和levelId匹配
                $couponLevelInfo = $GLOBALS['db']->getRow("SELECT id FROM firstp2p_coupon_level WHERE group_id = $groupId AND id = $couponLevelId");
                if (empty($couponLevelInfo)) {
                    throw new \Exception('会员组和优惠码等级不匹配', 2001);
                }
            }
            //TODO 比对不同的信息，进行更改
            $GLOBALS['db']->startTrans();
            try {
                if ($groupUpdate) {
                    $dataToUpdate = array(
                        'group_id' => $groupId,
                        'coupon_level_id' => $couponLevelId
                    );
                    // 移动会员组
                    $res = $GLOBALS['db']->autoExecute('firstp2p_user', $dataToUpdate, 'UPDATE', " id = '{$userInfo['id']}'");
                    if (!$res) {
                        throw new \Exception('用户组移动失败', 2001);
                    }
                }
                // 打tags
                if (!empty($tags)) {
                    $tags = explode('|', $tags);
                    if ($isActive) {
                        $tagService->addUserTagsByConstName($userInfo['id'], $tags);
                    } else {
                        $tagService->delUserTagsByConstName($userInfo['id'], $tags);
                    }
                }
                // 打远程tags
                if (!empty($remoteTags)) {
                    $remoteTags = explode('|', $remoteTags);
                    foreach ($remoteTags as $remoteTag) {
                        list($key, $value) = explode(':', $remoteTag, 2);
                        if (empty($key)) continue;

                        if ($isActive) {
                            $value = explode(',', $value);
                            foreach ($value as $setValue) {
                                $res = $remoteTagService->addUserTag($userInfo['id'], $key, $setValue);
                                if (!$res) {
                                    PaymentApi::log('add remote tag failed, user_id:'.$userInfo['id'].' key:'.$key.' value:'.$setValue);
                                }
                            }
                        } else {
                            $res = $remoteTagService->delUserTag($userInfo['id'], $key);
                            if (!$res) {
                                PaymentApi::log('del remote tag failed, user_id:'.$userInfo['id'].' key:'.$key);
                            }
                        }
                    }
                }
                $GLOBALS['db']->commit();
                PaymentApi::log('O2O_MOVE_USER_GROUP:move user_id'.$userInfo['id'].' group from '.$userInfo['group_id'].' to '.$groupId.', level from '.intval($userInfo['level_id']).' to '.$couponLevelId.' succeed');
            } catch(\Exception $e) {
                $GLOBALS['db']->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            //TODO 返回信息
            $this->_response($e);
            return false;
        }
        $this->_response(array('user_id' => $userInfo['id']));
        return true;
    }

    /**
     * 添加用户的remoteTag
     */
    public function addUserRemoteTag() {
        $remoteTagService = new RemoteTagService();
        try {
            $userIds = trim($_POST['userIds']);
            $tag = trim($_POST['tag']);
            if (!$userIds) {
                throw new \Exception('用户id不能为空', 1001);
            }

            if (!$tag) {
                throw new \Exception('tag不能为空', 1002);
            }

            list($key, $value) = explode(':', $tag, 2);
            if (empty($key)) {
                throw new \Exception('tag的键值不能为空', 1003);
            }

            $value = explode(',', $value);
            $userIdArray = explode('|', $userIds);
            foreach ($userIdArray as $userId) {
                if (empty($userId)) continue;
                foreach ($value as $setValue) {
                    $res = $remoteTagService->addUserTag($userId, $key, $setValue);
                    if ($res) {
                        // 记录成功的用户id
                        $result[] = $userId;
                    }
                    PaymentApi::log('add remote tag '.($res ? 'success' : 'failed').' user_id:'.$userId.' key:'.$key.' value:'.$setValue);
                }
            }

            $this->_response($result);
            return true;
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
    }

    /**
     * 删除用户的remoteTag
     */
    public function delUserRemoteTags() {
        $remoteTagService = new RemoteTagService();
        try {
            $userIds = trim($_POST['userIds']);
            $tag = trim($_POST['tag']);
            if (!$userIds) {
                throw new \Exception('用户id不能为空', 1001);
            }

            if (!$tag) {
                throw new \Exception('tag不能为空', 1002);
            }

            list($key, $value) = explode(':', $tag, 2);
            if (empty($key)) {
                throw new \Exception('tag的键值不能为空', 1003);
            }

            $value = explode(',', $value);
            $userIdArray = explode('|', $userIds);
            $result = array();
            foreach ($userIdArray as $userId) {
                if (empty($userId)) continue;
                foreach ($value as $setValue) {
                    $res = $remoteTagService->delUserTag($userId, $key, $setValue);
                    if ($res) {
                        $result[] = $userId;
                    }
                    PaymentApi::log('delete remote tag '.($res ? 'success' : 'failed').' user_id:'.$userId.' key:'.$key.' value:'.$setValue);
                }
            }

            $this->_response($result);
            return true;
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
    }

    /**
     * 给用户添加旧tag
     */
    public function addUserTagsByUserId() {
        $tagService = new UserTagService();
        try {
            $userIds = trim($_POST['userIds']);
            $tags = trim($_POST['tag']);
            if (!$userIds) {
                throw new \Exception('用户id不能为空', 1001);
            }
            if (!$tags) {
                throw new \Exception('tag不能为空', 1002);
            }
            $userIdArray = explode('|', $userIds);
            $result = array();
            foreach ($userIdArray as $userId) {
                $res = $tagService->addUserTagsByConstName($userId, $tags);
                if ($res) {
                    $result[] = $userId;
                }
                PaymentApi::log("adminUserApi.".__FUNCTION__.' userId:'.$userId ." tag:".$tags, Logger::INFO);
            }

            $this->_response($result);
            return true;
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
    }

    /**
     * delUserTagsByUserId
     * 根据用户id(可批量)
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2015-12-22
     * @access public
     * @return void
     */
    public function delUserTagsByUserId() {
        $tagService = new UserTagService();
        try {
            $userIds = trim($_POST['userIds']);
            $tags = trim($_POST['tag']);
            if (!$userIds) {
                throw new \Exception('用户id不能为空', 1001);
            }
            if (!$tags) {
                throw new \Exception('tag不能为空', 1002);
            }
            $userIdArray = explode('|', $userIds);
            $result = array();
            foreach ($userIdArray as $userId) {
                $res = $tagService->delUserTagsByConstName($userId, $tags);
                if ($res) {
                    $result[] = $userId;
                }
                PaymentApi::log("adminUserApi.".__FUNCTION__.' userId:'.$userId ." tag:".$tags, Logger::INFO);
            }

            $this->_response($result);
            return true;
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
    }

    public function getCouponTriggerInfo() {
        $couponIdStr = trim($_POST['couponId']);
        try {
            if (!$couponIdStr) {
                throw new \Exception('券Id不能为空');
            }
            $couponIds = explode('|', $couponIdStr);
            $result = array();
            foreach ($couponIds as $couponId) {
                $acquireLogInfo = OtoAcquireLogModel::instance()->getByGiftId($couponId);
                if (empty($acquireLogInfo)) {
                    continue;
                }

                if (in_array($acquireLogInfo['trigger_mode'], O2OService::$dealActions)) {
                    $condition =" id = {$acquireLogInfo['deal_load_id']}" ;
                    $dealLoadInfo = DealLoadModel::instance()->findBy($condition, 'id, money, create_time');
                    //TODO 兼容老数据
                    if (!empty($acquireLogInfo['extra_info']) && isset($acquireLogInfo['extra_info']['deal_annual_amount'])) {
                        $annualizedAmount = $acquireLogInfo['extra_info']['deal_annual_amount'];
                        $bidAmount = $acquireLogInfo['extra_info']['deal_money'];
                    } else {
                        $annualizedAmount = \core\service\oto\O2OUtils::getAnnualizedAmountByDealLoadId($acquireLogInfo['deal_load_id']);
                        $bidAmount = $dealLoadInfo['money'];
                    }
                }
                $result[$couponId] = array(
                    'triggerMode' => $acquireLogInfo['trigger_mode'],
                    'bidAmount' => $bidAmount,
                    'annualizedAmount' => $annualizedAmount,
                );
            }
            $this->_response($result);
            return true;
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
    }

    public function getUserRemoteTags() {
        $userIds = explode('|',$_POST['userIds']);
        $tagKeys = explode('|', $_POST['tagKeys']);

        $remoteTagService = new RemoteTagService();
        $responseData = array();
        foreach ($userIds as $userId) {
            foreach ($tagKeys as $tagKey) {
                $responseData[$userId][$tagKey] = $remoteTagService->existUserTag($userId, $tagKey);
            }
        }

        $this->_response($responseData);
        return true;
    }

    private function _response($data) {
        if ($data instanceof \Exception) {
            $result = array('errCode' => $data->getCode() == 0 ? 500 : $data->getCode(), 'errMsg' => $data->getMessage());
        } else {
            $result = array('errCode' => 0, 'errMsg' => '', 'data' => $data);
        }
        header('Content-type: application/json;charset=UTF-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function getUsedDiscountByDealId() {
        $usedDiscount = array();
        $dealLoadIdList = array();
        $dealId = trim($_POST['dealId']);
        $where = " deal_id in(%s)";
        $where = sprintf($where, $dealId);
        $res = DealLoadModel::instance()->findAllViaSlave($where, true, 'id');
        if ($res) {
            foreach($res as $item) {
                $dealLoadIdList[] = $item['id'];
            }
        }
        if ($dealLoadIdList) {
            $condition = ' consume_id in ('. implode(',', $dealLoadIdList) .')';
            $usedDiscount = DiscountRateModel::instance()->findAllViaSlave($condition, true, 'discount_id, consume_id, allowance_money');
        }
        $this->_response($usedDiscount);
        return true;
    }
}
