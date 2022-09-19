<?php

use core\service\oto\O2OCouponGroupService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use libs\utils\PaymentApi;
use libs\utils\Logger;

/**
 * 提供给O2O操作的接口
 */
class O2OCouponApiAction extends CommonAction {
    // 需要考虑安全性问题
    public function __construct() {
    }

    /**
     * 校验邀请人
     */
    public function validateInviter() {
        $service = new \core\service\oto\O2OStoreRelationService();
        try {
            $idno = trim($_POST['idno']);
            if (empty($idno)) {
                throw new \Exception('身份证号不能为空', 110);
            }

            $user = $this->getUserByIdno($idno);
            // 获取该身份证对应的零售店人员
            $inviter = $service->getStoreRelation($idno, 1, 1);
            if ($inviter === false) {
                throw new \Exception($service->getErrorMsg(), $service->getErrorCode());
            }

            if ($inviter) {
                $remoteTagService = new \core\service\RemoteTagService();
                $tag = $remoteTagService->getUserTag($user['id'], 'O2O_GENERATE_COUPON');
                // 已经存在，且包含相应的tag
                if ($tag) {
                    throw new \Exception('身份证：'.$idno.'，姓名：'.$user['real_name'].'，该用户不能成为邀请人', 111);
                }
            }
            // 将当前的用户信息返回
            $this->_response($user);
            return true;
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
    }

    /**
     * 添加券组信息
     */
    public function addCouponGroup() {
        $service = new O2OCouponGroupService();
        try {
            // 添加券组
            $data = $_POST;
            $data['discount'] = array();
            $couponGroupId = $service->addCouponGroup($data['product'], $data['group'], $data['discount']);
            // 添加券组失败的处理
            if ($couponGroupId === false) {
                throw new \Exception($service->getErrorMsg(), $service->getErrorCode());
            }

            // 处理零售店关系
            $this->handleStoreRelation($data['person'], $couponGroupId, $data['group']['supplierUserId']);
            // 更新券组的inviterTag
            $data['group']['id'] = $couponGroupId;
            $data['group']['newInviterTag'] = 'O2O_GENERATE_COUPON:'.$couponGroupId;
            // 可以考虑优化这步
            $res = $service->updateCouponGroup($data['product'], $data['group'], $data['discount']);
            // 更新券组失败处理
            if ($res === false) {
                throw new \Exception($service->getErrorMsg(), $service->getErrorCode());
            }

            $this->_response($couponGroupId);
            return true;
        } catch (\Exception $e) {
            $this->_response($couponGroupId, $e->getCode(), $e->getMessage());
            return false;
        }
    }

    /**
     * 给用户打tag
     */
    private function userTagAddProcess($persons, $couponGroupId, $triggerMode) {
        $service = new O2OCouponGroupService();
        // 处理零售店人员tag
        if (isset($persons['storers'])) {
            $storers = $persons['storers'];
            foreach ($storers as $storer) {
                $this->addTag($storer['idno'], 'O2O_SELLER');
            }
        }

        // 处理邀请人tag
        if (isset($persons['inviters'])) {
            $inviters = $persons['inviters'];
            foreach ($inviters as $inviter) {
                $user = $this->getUserByIdno($inviter['idno']);
                // 添加首投相关tag
                $this->addTag($user, 'O2O_FIRSTLOAN_NORED');
                $this->addTag($user, 'O2O_GENERATE_COUPON', $couponGroupId);

                if (in_array(CouponGroupEnum::TRIGGER_SECOND_DOBID, $triggerMode)) {
                    // 添加复投相关tag
                    $this->addTag($user, 'O2O_PBDECTZ');
                    $isAllowanceRedPacket = $persons['isAllowanceRedPacket'];
                    if ($isAllowanceRedPacket == 1) {
                        $tagValue = '';
                        $redPacketType = $persons['redPacketType'];
                        if ($redPacketType == 1) {
                            $tagValue = '20VS10';
                        } else if ($redPacketType == 2){
                            $tagValue = '20VS0';
                        } else if ($redPacketType == 3) {
                            $tagValue = '30VS0';
                        } else {
                            throw new \Exception('红包类型不对, type: '.$redPacketType, 102);
                        }

                        $this->addTag($user, 'O2O_STHB', $tagValue);
                    }
                }
            }
        }

        return true;
    }

    /**
     * 给用户删tag
     */
    private function userTagDelProcess($persons, $couponGroupId, $triggerMode) {

        $remoteTagService = new \core\service\RemoteTagService();
        // 处理邀请人tag
        $inviters = $persons['inviters'];
        foreach ($inviters as $inviter) {
            $user = $this->getUserByIdno($inviter['idno']);
            $this->delTag($user, 'O2O_GENERATE_COUPON', $couponGroupId);
            $currentValue = $remoteTagService->getUserTag($user['id'], 'O2O_GENERATE_COUPON');
            if (!$currentValue) {
                // 删除tag
                $this->delTag($user, 'O2O_FIRSTLOAN_NORED');
                $this->delTag($user, 'O2O_PBDECTZ');
                $this->delTag($user, 'O2O_STHB', $tagValue);
            }
        }

        return true;
    }

    /**
     * 添加tag
     */
    private function addTag($idno, $tagName, $tagValue = false) {
        if (is_array($idno)) {
            $user = $idno;
        } else {
            $user = $this->getUserByIdno($idno);
        }

        // 添加旧tag
        if ($tagValue === false) {
            $userTagService = new \core\service\UserTagService();
            $res = $userTagService->addUserTagsByConstName($user['id'], $tagName);
            if (!$res) {
                throw new \Exception('身份证:'.$idno.', 姓名：'.$user['real_name'].', 增加'.$tagName.'标签失败', 100);
            }
        } else {
            // 添加新tag
            $remoteTagService = new \core\service\RemoteTagService();
            // 保证tagValue是字符串
            $tagValue = strval($tagValue);
            $res = $remoteTagService->addUserTag($user['id'], $tagName, $tagValue);
            if ($res === false) {
                throw new \Exception('身份证:'.$idno.', 姓名：'.$user['real_name'].', 增加'.$tagName.':'.$tagValue.'标签失败', 101);
            }
        }
    }

    /**
     * 删除TAG
     */
    private function delTag($idno, $tagName, $tagValue = false) {
        if (is_array($idno)) {
            $user = $idno;
        } else {
            $user = $this->getUserByIdno($idno);
        }

        // 删除旧tag
        if ($tagValue === false) {
            $userTagService = new \core\service\UserTagService();
            $res = $userTagService->delUserTagsByConstName($user['id'], $tagName);
            if (!$res) {
                throw new \Exception('身份证:'.$idno.', 姓名：'.$user['real_name'].', 删除'.$tagName.'标签失败', 100);
            }
        } else {
            // 删除新tag
            $remoteTagService = new \core\service\RemoteTagService();
            // 保证tagValue是字符串
            $tagValue = strval($tagValue);
            //先删除旧的tag，再使用add方法增加tag
            $res = $remoteTagService->delUserTag($user['id'], $tagName, $tagValue);
            if ($res === false) {
                throw new \Exception('身份证:'.$idno.', 姓名：'.$user['real_name'].', 增加'.$tagName.':'.$tagValue.'标签失败', 101);
            }
        }
    }

    /**
     * 零售店人员管理
     */
    private function handleStoreRelation(array $persons, $couponGroupId, $supplierUserId) {
        if (empty($persons['inviters'])) {
            throw new \Exception('邀请人不能为空');
        }

        $peoples = array();
        foreach ($persons['inviters'] as $inviter) {
            $idno = $inviter['idno'];
            $peoples[$idno] = $inviter;
        }

        foreach ($persons['storers'] as $storer) {
            $idno = $storer['idno'];
            if (isset($peoples[$idno])) {
                // 可能存在同时为邀请人和核销人的情况
                $peoples[$idno]['type'] = 0;
            } else {
                $peoples[$idno] = $storer;
            }
        }

        foreach ($peoples as $people) {
            $this->syncStoreRelationData($people, $couponGroupId, $supplierUserId);
        }
    }

    /**
     * 构造用户关系数据
     */
    private function syncStoreRelationData(array $person, $couponGroupId, $supplierUserId) {
        $user = $this->getUserByIdno($person['idno']);
        $storeRelationService = new \core\service\oto\O2OStoreRelationService();
        // 邀请人身份验证
//         if ($person['type'] == 1) {
//             $remoteTagService = new \core\service\RemoteTagService();
//             // 获取该身份证对应的零售店人员
//             $inviter = $storeRelationService->getStoreRelation($person['idno'], 1, 1);
//             if ($inviter === false) {
//                 throw new \Exception($storeRelationService->getErrorMsg(), $storeRelationService->getErrorCode());
//             }

//             if ($inviter) {
//                 $tag = $remoteTagService->getUserTag($user['id'], 'O2O_GENERATE_COUPON');
//                 // 已经存在，且包含相应的tag
//                 if ($tag) {
//                     throw new \Exception('身份证：'.$person['idno'].'，姓名：'.$user['real_name'].'，该用户不能成为邀请人', 111);
//                 }
//             }
//         }

        $data = array();
        $data['storeId'] = $user['id'];
        // 邀请人不需要对应到某个供应商
        $data['supplierId'] = $supplierUserId;
        $data['idno'] = $person['idno'];
        $data['channelLevel1'] = $person['remark1'];
        $data['channelLevel2'] = $person['remark2'];
        $data['channelLevel3'] = $person['remark3'];
        $data['channelLevel4'] = $person['remark4'];
        $data['channelLevel5'] = $person['remark5'];
        $data['channelLevel6'] = $person['remark6'];
        $data['channelLevel7'] = $person['remark7'];
        $data['channelLevel8'] = $person['remark8'];
        $data['channelLevel9'] = '';
        $data['channelLevel10'] = '';
        $data['channelLevel11'] = '';
        $data['channelLevel12'] = '';
        $data['channelLevel13'] = '';
        $data['channelLevel14'] = '';
        $data['channelLevel15'] = '';
        $data['channelLevel16'] = '';
        $data['channelLevel17'] = '';
        $data['channelLevel18'] = '';
        $data['channelLevel19'] = '';
        $data['channelLevel20'] = '';
        $data['isActive'] = $person['status'];
        $data['couponGroupId'] = $couponGroupId;
        $data['userType'] = $person['type'];
        // 同步零售店人员信息
        $res = $storeRelationService->syncStoreRelation($data);
        if ($res === false) {
            throw new \Exception($storeRelationService->getErrorMsg(), $storeRelationService->getErrorCode());
        }
        return $res;
    }

    /**
     * 通过身份证号获取用户信息
     * 如果存在一个用户对应多个身份证，则异常警告
     */
    private function getUserByIdno($idno) {
        $userService = new \core\service\UserService();
        $users = $userService->getAllUserByIdno($idno);
        if (count($users) > 1) {
            $msg = '该身份证号对应多个用户';
            PaymentApi::log($msg.'users: '.json_encode($users, JSON_UNESCAPED_UNICODE).', idno: '.$idno, Logger::ERR);
            throw new \Exception('身份证：'.$idno.', '.$msg, 200);
        }

        if (empty($users)) {
            throw new \Exception('身份证：'.$idno.', 用户不存在', 201);
        }

        return array_pop($users);
    }

    /**
     * 更新券组信息
     */
    public function updateCouponGroup() {
        $service = new O2OCouponGroupService();
        try {
            // 需要保证下面的所有操作的幂等，要不然会出现重复数据，很重要
            // 更新券组
            $data = $_POST;
            if (empty($data['group']['id'])) {
                throw new \Exception('券组id不能为空', 300);
            }

            $couponGroupId = $data['group']['id'];

            // 处理零售店人员
            $this->handleStoreRelation($data['person'], $couponGroupId, $data['group']['supplierUserId']);

            // 判断上线时间
            // 如果申请上线时间早于终审通过时间，则先打Tag，之后上线券组，上线时间即为当时上线时间；
            // 如果申请上线时间晚于终审审核时间，则在申请上线时间进行相应的打Tag 操作成功后券组上线；
            $currentTime = time();
            if ($data['group']['availableTime'] < $currentTime) {
                $data['group']['availableTime'] = $currentTime;
            }
            // 更新券组的inviterTag
            if (!isset($data['group']['newInviterTag'])) {
                $data['group']['newInviterTag'] = 'O2O_GENERATE_COUPON:'.$couponGroupId;
            }

            // 如果都执行成功后，将券组上线
            $data['group']['couponGroupStatus'] = CouponGroupEnum::STATUS_EFFECT;
            // 可以考虑优化这步
            $res = $service->updateCouponGroup($data['product'], $data['group'], $data['discount']);
            if ($res === false) {
                throw new \Exception($service->getErrorMsg(), $service->getErrorCode());
            }

            $this->_response($res);
            return true;
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
    }

    /**
     * 获取券组详情
     */
    public function getCouponGroup() {
        $service = new O2OCouponGroupService();
        try {
            $couponGroupId = trim($_POST['couponGroupId']);
            if (empty($couponGroupId) || !is_numeric($couponGroupId)) {
                throw new \Exception('券组id为空或不是有效的数字', 400);
            }
            $result = $service->getCouponGroup($couponGroupId);
            if ($result === false) {
                throw new \Exception($service->getErrorMsg(), $service->getErrorCode());
            }

            $this->_response($result);
            return true;
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }
    }

    /**
     * tag处理单独接口
     */
    public function tagProcess() {
        $service = new O2OCouponGroupService();
        $data = $_POST;
        try {
            if ($data['mode'] == 'add') {
                $this->userTagAddProcess($data['persons'], $data['couponGroupId'], $data['triggerMode'], $data['action']);
                $res = $service->updateCouponGroupStatus($data['couponGroupId'], CouponGroupEnum::STATUS_EFFECT);
                if (!$res) {
                    throw new \Exception('券组上线失败');
                }
            } else {
                $this->userTagDelProcess($data['persons'], $data['couponGroupId'], $data['triggerMode'], $data['action']);
            }
            $this->_response($res);
        } catch (\Exception $e) {
            $this->_response($e);
            return false;
        }

        return true;
    }

    private function _response($data, $code = 0, $msg = 'success') {
        if ($data instanceof \Exception) {
            $result = array('errCode' => $data->getCode() == 0 ? 500 : $data->getCode(), 'errMsg' => $data->getMessage());
        } else {
            $result = array('errCode' => $code, 'errMsg' => $msg, 'data' => $data);
        }
        header('Content-type: application/json;charset=UTF-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
