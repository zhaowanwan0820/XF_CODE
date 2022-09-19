<?php
/**
 * CouponBindService.php.
 *
 * @date 2015-07-14
 *
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use NCFGroup\Task\Services\TaskService as GTaskService;
use core\event\CouponBindEvent;
use libs\utils\Logger;
use core\dao\CouponBindModel;
use core\dao\CouponBindLogModel;
use core\dao\UserModel;
use core\dao\UserYifangModel;
use core\dao\CouponLogModel;
use core\dao\DealLoadModel;
use core\service\BwlistService;
use core\service\UserProfileService;

/**
 * 用户绑定邀请码接口.
 */
class CouponBindService
{
    /**
     * 邀请码是否绑定.
     */
    const UNFIXED = 0; //邀请码未绑定
    const FIXED = 1; //邀请码已经绑定

    const TYPE_INVITE = 0; //邀请关系
    const TYPE_SERVICE = 1; //服务关系
    const TYPE_DISCOUNT = 2;//打折系数

    /**
     * 根据用户ID更新绑定邀请码
     * 如果原记录已绑定，则不处理；否则取最近一条coupon_log的邀请码，按15日规则作绑定.
     *
     * @param $user_id
     *
     * @return mixed
     */
    public function init($user_id, $short_alias = false)
    {
        $user_id = intval($user_id);
        if (empty($user_id)) {
            return false;
        }

        //初始化对象
        $couponBindModel = new CouponBindModel();
        $userModel = new UserModel();

        //查找数据是否在标里面存在
        $result = $couponBindModel->getByUserIds(array($user_id), false);

        if (empty($result)) { //如果表里面没有数据那么插入一条数据
            //通过用户id批量获取邀请人 和被邀请的用户名
            $userNames = $userModel->getUserNamesByIds($user_id);
            if (!isset($userNames[$user_id])) { //如果查不到用户名，说明没这个用户
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, __LINE__, '用户id不存在', 'user_id : '.$user_id)));

                return false;
            }
            $userName = $userNames[$user_id];
            unset($userNames);

            $info = $this->getLastCouponAfterIO($user_id, $short_alias);
            $info['user_id'] = $user_id;
            $info['user_name'] = $userName;
            $info['create_time'] = $info['update_time'] = get_gmtime();

            //初始化一条数据
            $res = $couponBindModel->insertOneData($info);
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, __LINE__, 'insert:', $res, 'user_id : '.$user_id, 'info :'.json_encode($info))));

            return $res;
        } else { //如果结果不为空，更新下非绑定状态的数据
            if (!$result[$user_id]['is_fixed']) {
                $info = $this->getLastCouponAfterIO($user_id, $short_alias);
                $info['update_time'] = get_gmtime();

                if ($info['is_fixed']) {
                    //更新邀请码绑定状态
                    $res = $couponBindModel->upateDataByUserid($info, $user_id);
                    Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, __LINE__, 'update:', $res, 'user_id : '.$user_id, 'info :'.json_encode($info))));

                    return $res;
                }
            }
        }

        return true;
    }

    /**
     * 获取用户最近一个优惠码信息，经过IO处理.
     */
    private function getLastCouponAfterIO($user_id, $short_alias = false)
    {
        $log_info = array(__CLASS__, __FUNCTION__, APP, $user_id);
        $couponService = new CouponService();
        $coupon_log_dao = new CouponLogModel();
        $userModel = new UserModel();

        $result_default = array('is_fixed' => self::FIXED, 'short_alias' => '', 'invite_code'=>'', 'refer_user_id' => 0, 'invite_user_id'=> 0 , 'refer_user_name' => '', 'invite_user_name' => '', 'invite_user_group_id' => 0, 'type' => 2 );
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        //获取投标人最后一次填写邀请码投标记录

        $lastCoupon = array();
        //如果从coupon_log 表里面取出的邀请码记录为空，那么在从deal_load 表里面取一次，是为了防止高并导致coupon_log表里及时写入邀请记录，导致下次投资不能获取邀请码的绑定关系。
        $dealLoadModel = new DealLoadModel();
        $firstCoupon = $dealLoadModel->getCouponFirstByUserId($user_id); //从deal_log 获取用户第一次使用邀请码的记录

        if (!empty($firstCoupon)) {
            $lastCoupon['short_alias'] = $firstCoupon['short_alias'];
            $lastCoupon['type'] = CouponService::TYPE_DEAL; //type = 2 为投标时填码
        } else {
        //如果deal_load 表里面数据为空，那么查询用户注册是否绑码，邀请码则绑定
            $userInfo = UserModel::instance()->find($user_id,'invite_code , site_id');
            $couponSign = $couponService->checkCoupon($userInfo['invite_code']);
            if (!empty($couponSign)) {
                $lastCoupon['short_alias'] = $couponSign['short_alias'];
                $lastCoupon['type'] = CouponService::TYPE_SIGNUP;
            }
        }

        /*
         * 子系统 short_alias直接传过来，绑定
         */
        if (empty($lastCoupon) && !empty($short_alias)) {
            $lastCoupon['short_alias'] = $short_alias;
            $lastCoupon['type'] = CouponService::TYPE_DEAL;
            Logger::info(implode(' | ', array_merge($log_info, array('子系统绑码:'.$short_alias))));
        }

        if (empty($lastCoupon)) {
            $result_default['is_fixed'] = $couponService->isFixedWithoutCoupon($user_id) ? self::FIXED : self::UNFIXED;
            Logger::info(implode(' | ', array_merge($log_info, array('return default'))));

            return $result_default;
        }
        $log_info[] = json_encode($lastCoupon);
        //邀请码转成大写
        $lastCoupon['short_alias'] = strtoupper($lastCoupon['short_alias']);
        //邀请码里面的IO转成数字10
        $short_alias_01 = str_replace(array('I', 'O'), array('1', '0'), $lastCoupon['short_alias']);
        $is_special = $lastCoupon['short_alias'] != $short_alias_01; //是否要进行特殊处理
        $coupon = $couponService->checkCoupon($short_alias_01);
        $log_info[] = 'coupon_01:'.json_encode($coupon);

        //01码无效，尝试转YZ
        if ($is_special && empty($coupon)) {
            $short_alias_YZ = str_replace(array('I', 'O'), array('Y', 'Z'), $lastCoupon['short_alias']);
            $coupon = $couponService->checkCoupon($short_alias_YZ);
            $log_info[] = 'coupon_YZ:'.json_encode($coupon);
        }

        //YZ,01都无效，使用01码
        if ($is_special && empty($coupon)) {
            $refer_user_id = $couponService->hexToUserId($short_alias_01);
            $refer_user = $userModel->find($refer_user_id, 'user_name', true);
            if (!empty($refer_user)) { //01码对应ID有效
                $coupon = array('short_alias' => $short_alias_01, 'refer_user_id' => $refer_user_id, 'refer_user_name' => $refer_user['user_name'],'group_id'=>0);
                $log_info[] = 'coupon_YZ_01:'.json_encode($coupon);
            } else {
                $coupon = $result_default;
            }
        }

        if (empty($coupon)) {
            $coupon = $lastCoupon;
        }
        //  使用自己码不做绑定,如果在超过有效期直接绑定
        if (!empty($coupon['refer_user_id']) && $coupon['refer_user_id'] == $user_id) {
            $result_default['is_fixed'] = $couponService->isFixedWithoutCoupon($user_id) ? self::FIXED : self::UNFIXED;
            Logger::info(implode(' | ', array_merge($log_info, array('return default input self coupon'))));

            return $result_default;
        }
        //正对特殊优惠码最特殊处理，特殊优惠码有refer_user_id,但是没有返回refer_user_name
        if (!empty($coupon['refer_user_id']) && empty($coupon['refer_user_name'])) {
            $referUserNames = $userModel->getUserNamesByIds(array($coupon['refer_user_id']));
            $coupon['refer_user_name'] = isset($referUserNames[$coupon['refer_user_id']]) ? $referUserNames[$coupon['refer_user_id']] : '';
        }

        //触发绑定关系初始化邀请人和服务人
        $result['is_fixed'] = self::FIXED;
        $result['type'] = $lastCoupon['type'];

        //绑码拦截逻辑
        $bwlistService = new BwlistService();
        $coupon_exist = $bwlistService->inList('DISABLED_CN_COUPON',$coupon['short_alias']);
        if($coupon_exist){
            if($result['type'] == CouponService::TYPE_SIGNUP && $bwlistService->inList('DISABLED_CN_COUPON',$coupon['short_alias'],$userInfo['site_id'])){
                $result['invite_code'] = empty($coupon['short_alias']) ? '' : $coupon['short_alias'];
                $result['invite_user_id'] = empty($coupon['refer_user_id']) ? 0 : $coupon['refer_user_id'];
                $result['invite_user_name'] = empty($coupon['refer_user_name']) ? '' : $coupon['refer_user_name'];
                $result['invite_user_group_id'] = empty($coupon['group_id'])? 0 : $coupon['group_id'];
            }else{
                Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__, APP, 'shortAlias : '.$coupon['short_alias'] ,'user_id : ' .$user_id ,  "邀请关系绑码拦截")));
            }
        }else{
            $result['invite_code'] = empty($coupon['short_alias']) ? '' : $coupon['short_alias'];
            $result['invite_user_id'] = empty($coupon['refer_user_id']) ? 0 : $coupon['refer_user_id'];
            $result['invite_user_name'] = empty($coupon['refer_user_name']) ? '' : $coupon['refer_user_name'];
            $result['invite_user_group_id'] = empty($coupon['group_id'])? 0 : $coupon['group_id'];
        }

        //初始化服务关系
        $result['short_alias'] = empty($coupon['short_alias']) ? '' : $coupon['short_alias'];
        $result['refer_user_id'] = empty($coupon['refer_user_id']) ? 0 : $coupon['refer_user_id'];
        $result['refer_user_name'] = empty($coupon['refer_user_name']) ? '' : $coupon['refer_user_name'];

        $log_info[] = 'result:'.json_encode($result);
        Logger::info(implode(' | ', array_merge($log_info, array('done'))));

        return $result;
    }

    /**
     * 通过user_id 获取一条优惠码绑定记录.
     *
     * @param int $user_id
     *
     * @return array
     */
    public function getByUserId($user_id, $short_alias = false)
    {
        $user_id = intval($user_id);
        if (empty($user_id)) {
            return array();
        }

        $couponBindModel = new CouponBindModel();
        $result = $couponBindModel->getByUserIds(array($user_id), true); //读存库

        //如果没查到需要在初始化一条数据
        if (!isset($result[$user_id]) || !$result[$user_id]['is_fixed']) {
            $this->init($user_id, $short_alias);
            $result = $couponBindModel->getByUserIds(array($user_id), false); //读主库
        }

        $data = isset($result[$user_id]) ? $result[$user_id] : array();
        //动态计算邀请码
        if(!empty($data)){
            $couponService = new CouponService();
            $data['short_alias'] = $couponService->userIdToHex($data['refer_user_id']);
            $data['invite_code'] = $couponService->userIdToHex($data['invite_user_id']);
        }
        return $data;
    }

    /**
     * 通过user_id 批量获取绑定信息.
     */
    public function getByUserIds($user_ids)
    {
        if (empty($user_ids)) {
            return array();
        }

        $couponBindModel = new CouponBindModel();
        $result = $couponBindModel->getByUserIds($user_ids);

        return $result;
    }

    /**
     * 更新用户邀请码信息.
     *
     * @param string $short_alias
     * @param array  $user_ids
     * @param number $admin_id
     * type = 0初始化修改服务关系，1 修改邀请关系，3 两者都修改
     * @return bool
     */
    public function updateByUserIds($short_alias, $user_ids, $admin_id = 0, $type = 1)
    {
        if (empty($user_ids)) {
            return false;
        }
        $short_alias = strtoupper(trim($short_alias)); //去掉邀请码空格，和转大写

        $user_ids = array_map('intval', $user_ids);

        $couponService = new CouponService();

        $refer_user_name = '';
        $refer_user_id = 0;
        //验证邀请码是否有效
        if (!empty($short_alias)) {
            $result = $couponService->checkCoupon($short_alias);
            if (!$result) {
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, __LINE__, $type, '邀请码无效', 'admin_id :'.$admin_id, 'user_ids :'.json_encode($user_ids), 'short_alias :'.$short_alias)));

                return false;
            }

            $short_alias = $result['short_alias'];
            $refer_user_id = intval($result['refer_user_id']);
            $refer_user_group_id = intval($result['group_id']);
            if ($refer_user_id) {
                //更新邀请码绑定状态
                $userModel = new UserModel();
                $referUserNames = $userModel->getUserNamesByIds(array($refer_user_id));
                $refer_user_name = isset($referUserNames[$refer_user_id]) ? $referUserNames[$refer_user_id] : '';
            }
        }

        if (self::TYPE_SERVICE == $type) {
            //要更新或者插入邀请人信息
            $info = array(
                    'short_alias' => $short_alias,
                    'admin_id' => intval($admin_id),
                    'refer_user_id' => $refer_user_id,
                    'refer_user_name' => $refer_user_name,
                    'update_time' => get_gmtime(),
            );
        } elseif (self::TYPE_INVITE == $type) {
            $info = array(
                    'invite_code' => $short_alias,
                    'admin_id' => intval($admin_id),
                    'invite_user_id' => $refer_user_id,
                    'invite_user_name' => $refer_user_name,
                    'invite_user_group_id' => $refer_user_group_id,
                    'update_time' => get_gmtime(),
            );
        }

        $infoLog = array(
                'new_short_alias' => $short_alias,
                'admin_id' => intval($admin_id),
                'new_refer_user_id' => $refer_user_id,
                'type' => $type,
                'create_time' => get_gmtime(),
                'update_time' => get_gmtime(),
            );

        //更新邀请码绑定状态
        $couponBindModel = new CouponBindModel();
        $couponBindLogModel = new CouponBindLogModel();

        try {
            $GLOBALS['db']->startTrans();
            foreach ($user_ids as $user_id) {
                unset($info['is_fixed']);
                //更新邀请码的时候如果，填写了邀请码，或者邀请码过了绑定的有效期，邀请码状态设置成绑定状态
                if (!empty($short_alias) || $couponService->isFixedWithoutCoupon($user_id)) {
                    $info['is_fixed'] = 1;
                }
                $result = $couponBindLogModel->insertData($infoLog, array($user_id), false);
                if (!$result) {
                    throw new \Exception('数据操作失败');
                }
                $res = $couponBindModel->updateDataByUserIds($info, array($user_id));
                if (!$res) {
                    throw new \Exception('数据操作失败');
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, __LINE__, $type, '数据库更新失败', 'user_ids :'.json_encode($user_ids), 'info :'.json_encode($info))));
            $GLOBALS['db']->rollback();

            return false;
        }

        //用户统计埋点
        $userProfileService = new UserProfileService();
        foreach ($user_ids as $userId) {
            $userProfileService->updateCouponProfile($userId);
        }

        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, __LINE__, $type, '邀请码更新成功', 'user_ids :'.json_encode($user_ids), 'info :'.json_encode($info))));

        return true;
    }

    /**
     * 添加 更新用户绑定邀请码 异步任务
     *
     * @param $user_id
     *
     * @return string
     */
    public function addInitTask($user_id)
    {
        $event = new CouponBindEvent($user_id);
        $task_service = new GTaskService();
        $rs = $task_service->doBackground($event);
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, $user_id, $rs)));

        return $rs;
    }

    /**
     * 通过推荐人id更新绑定的邀请码
     * 说明：理财师改变分组之后，邀请码前缀会发生改变，需要更新投资人绑定的邀请码
     *
     * @param int $refer_user_id
     *
     * @return bool
     */
    public function refreshByReferUserId($refer_user_id, $admin_id = 0, $old_coupon = '')
    {
        $refer_user_id = intval($refer_user_id);
        $couponService = new CouponService();
        //todo 这个方法需要修改，获取绑定
        $coupon = $couponService->getOneUserCoupon($refer_user_id, false); //不取缓存
        if (!empty($coupon)) {
            if (!empty($old_coupon) && trim($old_coupon) == $coupon['short_alias']) {
                return true;
            }

            try {
                $GLOBALS['db']->startTrans();
                $couponBindModel = new CouponBindModel();
                $data = array('short_alias' => $coupon['short_alias'], 'update_time' => get_gmtime());
                $dataInvite = array('invite_code' => $coupon['short_alias'], 'update_time' => get_gmtime());
                if (!empty($admin_id)) {
                    $data['admin_id'] = $admin_id;
                }
                $res = $couponBindModel->updateBy($data, ' refer_user_id='.$refer_user_id);
                if (empty($res)) {
                    throw new \Exception('修改服务关系失败');
                }
                $res = $couponBindModel->updateBy($dataInvite, ' invite_user_id='.$refer_user_id);
                if (empty($res)) {
                    throw new \Exception('修改邀请关系失败');
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, 'refer_user_id:'.$refer_user_id, 'short_alias:'.$coupon['short_alias'], '理财师改变用户群组，'.$e->getMessage())));
                $GLOBALS['db']->rollback();

                return false;
            }

            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'refer_user_id:'.$refer_user_id, 'short_alias:'.$coupon['short_alias'], '理财师改变用户群组，更新投资人邀请关系成功')));

            return true;
        }

        return false;
    }

    public function changeGroupAndLevel($correct, $adm_session)
    {
        try {
            $GLOBALS['db']->startTrans();
            foreach ($correct as $correct_key => $correct_row) {
                $userid = $correct_row['user_id'];
                $couponService = new CouponService();
                $coupon = $couponService->getOneUserCoupon($userid, false);
                $short_alias = empty($coupon) ? '' : $coupon['short_alias'];
                $params = array(
                        'group_id' => $correct_row['group_id'],
                        'coupon_level_id' => $correct_row['level_id'],
                        //'new_coupon_level_id' => $correct_row['level_id'],
                        );
                $userModel = new UserModel();
                $res = $userModel->updateBy($params, sprintf("id ='%d'", $userid));
                if (false === $res) {
                    throw new \Exception(sprintf('序号:%s，用户名：%s，更新分组处理失败', $correct_key, $correct_row['user_name']));
                } else {
                    $userYifang = new UserYifangModel();
                    $userYifang->user_name = $correct_row['user_name'];
                    $userYifang->real_name = $correct_row['real_name'];
                    $userYifang->old_groupid = $correct_row['old_groupid'];
                    $userYifang->old_levelid = $correct_row['old_levelid'];
                    $userYifang->mobile = $correct_row['mobile'];
                    $userYifang->new_groupid = $correct_row['group_id'];
                    $userYifang->new_levelid = $correct_row['level_id'];
                    $userYifang->adm_id = $adm_session['adm_id'];
                    $userYifang->adm_name = $adm_session['adm_name'];
                    $userYifang->update_time = date('Y-m-d H:i:s');
                    $add_res = $userYifang->insert();
                    if (!$add_res) {
                        throw new \Exception(sprintf('序号:%s，用户名：%s，处理失败', $correct_key, $correct_row['user_name']));
                    }
                }
                $ret = $this->refreshByReferUserId($userid, $adm_session['adm_id'], $short_alias);
                if (!$ret) {
                    throw new \Exception(sprintf('序号:%s，用户名：%s，更新投资用户绑定邀请码失败', $correct_key, $correct_row['user_name']));
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__.' | '.__FUNCTION__.' | '.'error : '.$e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * 获取推荐人的邀请好友个数.
     *
     * @param int $refer_user_id 推荐人id
     *
     * @return int
     */
    public function getUserFriendCount($refer_user_id)
    {
        if (empty($refer_user_id) || !is_numeric($refer_user_id)) {
            return 0;
        }

        $couponBindModel = new CouponBindModel();
        $condition = 'refer_user_id = :refer_user_id';
        $param = array(
            ':refer_user_id' => $refer_user_id,
        );

        return $couponBindModel->countViaSlave($condition, $param);
    }


    /**
     * 获取服务人的所谓服务的人个数.
     *
     * @param int $refer_user_id 服务人id
     *
     * @return int
     */
    public function getCountByReferUserId($refer_user_id)
    {
        if (empty($refer_user_id) || !is_numeric($refer_user_id)) {
            return 0;
        }

        $couponBindModel = new CouponBindModel();
        $condition = 'refer_user_id = :refer_user_id';
        $param = array(
            ':refer_user_id' => $refer_user_id,
        );

        return $couponBindModel->countViaSlave($condition, $param);
    }



    /**
     * 获取推荐人的邀请记录.
     *
     * @param int $refer_user_id 推荐人id
     * @param int $page          起始行
     * @param int $page_size     每页显示条数
     *
     * @return array
     */
    public function getAllReferUserId($refer_user_id, $page = 1, $page_size = 10)
    {
        if (!is_numeric($refer_user_id) || !is_numeric($page) || !is_numeric($page_size)) {
            return array();
        }
        $data = array('data' => array(), 'total' => 0);
        $couponBindModel = new CouponBindModel();
        $page = ($page <= 0) ? 1 : intval($page);
        $first_row = ($page - 1) * $page_size;
        $ret = $couponBindModel->getByReferUserId($refer_user_id, $first_row, $page_size);
        if (!empty($ret)) {
            $condition = ' refer_user_id=:refer_user_id';
            $param = array(
                ':refer_user_id' => $refer_user_id,
            );
            $total = $couponBindModel->countViaSlave($condition, $param);
            $data['total'] = $total;
            // 格式化数据
            $userModel = new UserModel();
            foreach ($ret as $key => $v) {
                $ret[$key]['real_name'] = '';
                $ret[$key]['mobile'] = '';
                $ret[$key]['create_time'] = to_date($ret[$key]['create_time']);
                if ($v['user_id']) {
                    $userInfo = $userModel->find($v['user_id'], 'real_name,mobile', true);
                    $ret[$key]['real_name'] = $userInfo['real_name'];
                    $ret[$key]['mobile'] = $userInfo['mobile'];
                }
            }
        }

        $data['data'] = $ret;

        return $data;
    }

    /**
     * 检查绑定关系是否对应.
     *
     * @param int $user_id
     * @param int $refer_user_id
     *
     * @return bool
     */
    public function checkComparedUserId($user_id, $refer_user_id)
    {
        if (!is_numeric($user_id) || !is_numeric($refer_user_id)) {
            return false;
        }
        $couponBindModel = new CouponBindModel();
        // 必须传数组 。。。。。
        $userInfoBind = $couponBindModel->getByUserIds(array($user_id));
        if (empty($userInfoBind[$user_id])) {
            return false;
        }
        if ($userInfoBind[$user_id]['user_id'] != $user_id || $userInfoBind[$user_id]['refer_user_id'] != $refer_user_id) {
            return false;
        }

        return true;
    }

    /**
     * 根据用户id绑定用户邀请码
     *
     * @param string $shortAlias
     * @param intval $userId
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function modifyShortAliasByUserId($shortAlias, $userId)
    {
        $userId = intval($userId);
        if (0 == $userId) {
            throw new \Exception('用户id不能为空');
        }

        //去掉邀请码空格，和转大写
        $shortAlias = strtoupper(trim($shortAlias));
        if (empty($shortAlias)) {
            throw new \Exception('邀请码不能为空');
        }

        $couponService = new CouponService();
        $isFixed = $couponService->isFixedWithoutCoupon($userId);
        if ($isFixed) {
            throw new \Exception('超过有效的绑定期限，不能再修改邀请码');
        }

        $couponUserId = $couponService->hexToUserId($shortAlias);
        if ($couponUserId == $userId) {
            throw new \Exception('不能绑定自己的邀请码');
        }

        try {
            $GLOBALS['db']->startTrans();
            $result = $this->updateByUserIds($shortAlias, array($userId), $userId,self::TYPE_SERVICE);
            if(!$result){
                throw new \Exception('绑定服务码失败');
            }


            //渠道邀请码逻辑，除注册时可以填码，其余所有情况不允许绑定渠道邀请码
            $bwlistService = new BwlistService();
            $is_cn_coupon_exist = $bwlistService->inList('DISABLED_CN_COUPON',$shortAlias);
            if(empty($is_cn_coupon_exist)){
                $result = $this->updateByUserIds($shortAlias, array($userId), $userId,self::TYPE_INVITE);
                if(!$result){
                    throw new \Exception('绑定邀请码失败');
                }   
            }else{
                Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__, APP, 'shortAlias : '.$shortAlias ,'user_id : ' .$userId ,  "绑码拦截")));
            }

            $GLOBALS['db']->commit();
            Logger::info(__CLASS__.' | '.__FUNCTION__. ' | userid:' .$userId. ' | shortAlias:' .$shortAlias. ' | '.'绑码成功');
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__.' | '.__FUNCTION__. ' | userid:' .$userId. ' | shortAlias:' .$shortAlias. ' | '.'error : '.$e->getMessage());
            return false;
        }

        return true;
    }

    /**
     *设置用户打折系数
     */
    public function setDiscountRatioByUserIds($discountRatio,$userIds,$admin_id){
        $userIds = array_map('intval', $userIds);
        $discountRatio = bcadd($discountRatio, 0,5);
        $couponBindModel = new CouponBindModel();
        $info = array(
                'discount_ratio' => $discountRatio,
                'admin_id' =>intval($admin_id),
                'update_time' => get_gmtime(),

        );
        return $couponBindModel->updateDataByUserIds($info, $userIds);  
    }
}
