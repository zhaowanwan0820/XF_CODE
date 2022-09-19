<?php

require_once dirname(__FILE__).'/../../../app/init.php';

use libs\utils\Logger;

class updateusercouponlevel
{
    public function getCsvFileData()
    {

        $log_info = array(__CLASS__, __FUNCTION__);

        $file = 'user_coupon_level.csv';
        $handle = fopen($file, 'r');
        $result = array();

        if(!$handle){
            exit("导入文件路径不正确");
        }

        while (false !== ($data = fgetcsv($handle))) {


            if(!is_numeric($data[0]) || !is_numeric($data[4])){
                continue;
            }

            $prefixs[$data[0]] = $data[0];

            $new_coupon_level_ids[$data[4]] = $data[4];

            $result[$data[0]] = array('prefix_id' => $data[0], 'group_id' => trim($data[2]), 'new_coupon_level_id' => trim($data[4]));

        }

        $log_info = array(__CLASS__, __FUNCTION__);
        //校验前缀ID
        $str = implode(',', $prefixs);
        $sql = 'SELECT COUNT(id) FROM firstp2p_coupon_level_rebate WHERE id IN ('.$str.')';
        $num = $GLOBALS['db']->get_slave()->getOne($sql); //查出的数据
        if (intval($num) !== sizeof($prefixs)) {
            \libs\utils\Alarm::push('CouponLevelGroupCheck', 'prefixID', $sql);
            Logger::error(implode(' | ', array_merge($log_info, array('邀请码前缀校验失败'))));
            return null;
        }

        //校验new coupon level id
        $str = implode(',', $new_coupon_level_ids);
        $sql = 'SELECT COUNT(id) FROM firstp2p_user_coupon_level WHERE id IN ('.$str.')';
        $num = $GLOBALS['db']->get_slave()->getOne($sql); //查出的数据
        if (intval($num) !== sizeof($new_coupon_level_ids)) {
            \libs\utils\Alarm::push('CouponLevelGroupCheck', 'new_coupon_level_id', $sql);
            Logger::error(implode(' | ', array_merge($log_info, array('服务等级校验失败'))));
            return null;
        }

        array_shift($result);
        return $result;
    }

    public function checkRebateRule($data)
    {
        $log_info = array(__CLASS__, __FUNCTION__);
        foreach ($data as $value) {

            $sql = "SELECT level_id FROM firstp2p_coupon_level_rebate WHERE is_effect = 1 and id = {$value['prefix_id']}";
            $level_id = $GLOBALS['db']->get_slave()->getOne($sql);
            if(!$level_id){
               \libs\utils\Alarm::push('CouponLevelGroupCheck', 'ID:'.$value['prefix_id'].',规则关系不存在', $sql);
                Logger::error(implode(' | ', array_merge($log_info, array('规则关系不存在', json_encode($value)))));
                continue;
            }
            $sql = "SELECT group_id FROM firstp2p_coupon_level WHERE is_effect = 1 and id = {$level_id}";
            $group_id = $GLOBALS['db']->get_slave()->getOne($sql);
            if(!$group_id){
               \libs\utils\Alarm::push('CouponLevelGroupCheck', 'ID:'.$value['prefix_id'].',会员组与用户等级关系不存在', $sql);
                Logger::error(implode(' | ', array_merge($log_info, array('会员组与用户等级关系不存在', json_encode($value)))));
                continue;
            }
            $value['group_id'] = $group_id;
            $this->checkNewRebateRule($value);
        }
    }


    public function checkNewRebateRule($data)
    {
        $log_info = array(__CLASS__, __FUNCTION__);
        if(!isset($data['group_id'])){
            continue;
        }
        $groupInfo = $this->getGroupInfo(intval($data['group_id']));
        $levelInfo = $this->getLevelInfo(intval($data['new_coupon_level_id']));

        if (empty($groupInfo)) {
            \libs\utils\Alarm::push('CouponLevelGroupCheck', 'ID:'.$data['prefix_id'].',用户组'.$data['group_id'].'不存在', $data);
            Logger::error(implode(' | ', array_merge($log_info, array('用户组无效或不存在',json_encode($groupInfo),json_encode($data)))));
            return false;
        }

        if( 1 != $groupInfo['is_effect']){
            \libs\utils\Alarm::push('CouponLevelGroupCheck', 'ID:'.$data['prefix_id'].',用户组'.$data['group_id'].'无效', $data);
            Logger::error(implode(' | ', array_merge($log_info, array('用户组无效',json_encode($groupInfo),json_encode($data)))));
            return false;
        }

        if (empty($levelInfo)) {
            \libs\utils\Alarm::push('CouponLevelGroupCheck', 'ID:'.$data['prefix_id'].',服务等级'.$data['new_coupon_level_id'].'不存在', $data);
            Logger::error(implode(' | ', array_merge($log_info, array('服务等级无效或者不存在',json_encode($levelInfo), json_encode($data)))));
            return false;
        }

        if(1 != $levelInfo['is_effect']){
            \libs\utils\Alarm::push('CouponLevelGroupCheck', 'ID:'.$data['prefix_id'].',服务等级'.$data['new_coupon_level_id'].'无效', $data);
            Logger::error(implode(' | ', array_merge($log_info, array('服务等级无效',json_encode($levelInfo), json_encode($data)))));
            return false;
        }

        //无服务能力组不校验联动规则
        if (0 == $groupInfo['service_status']) {
            return true;
        }

        if (1 == $groupInfo['is_related']) {
            $agency_rebate_ratio = bcsub($groupInfo['pack_ratio'], $levelInfo['rebate_ratio'], 5);
        } else {
            $agency_rebate_ratio = $groupInfo['pack_ratio'];
        }

        if (bccomp($agency_rebate_ratio, 0, 5) == -1) {
            \libs\utils\Alarm::push('CouponLevelGroupCheck', 'ID:'.$data['prefix_id'].',用户组与服务等级配置错误-机构系数小于0', $data);
            Logger::error(implode(' | ', array_merge($log_info, array('用户组与服务等级配置错误-机构系数小于0', json_encode($data)))));
        }

        if (bccomp(bcadd($agency_rebate_ratio, $levelInfo['rebate_ratio'], 5), $groupInfo['max_pack_ratio'], 5) > 0) {
            \libs\utils\Alarm::push('CouponLevelGroupCheck', 'ID:'.$data['prefix_id'].',用户组与服务等级配置错误-大于打包系数上限', $data);
            Logger::error(implode(' | ', array_merge($log_info, array('用户组与服务等级配置错误-大于打包系数上限', json_encode($data)))));
        }
    }

    public function getGroupInfo($groupId)
    {
        $sql = 'SELECT * FROM firstp2p_user_group WHERE id = '.$groupId;
        return $GLOBALS['db']->get_slave()->getRow($sql);
    }

    public function getLevelInfo($levelId)
    {
        $sql = 'SELECT * FROM firstp2p_user_coupon_level WHERE id = '.$levelId;
        return $GLOBALS['db']->get_slave()->getRow($sql);
    }

    //查询user表比对信息，更新覆盖
    public function updateFirstp2pUserTable($import)
    {
        $csvFileData = $this->getCsvFileData();

        if (null == $csvFileData) {
            \libs\utils\Alarm::push('CouponLevelGroupCheck', 'csvfile data is wrong');
            return false;
        }

        $this->checkRebateRule($csvFileData);

        $log_info = array(__CLASS__, __FUNCTION__);
        Logger::info(implode(' | ', array_merge($log_info, array('script start'))));

        $sql = 'select Max(id) as Maxid from firstp2p_user '; //获取user表数值最大的用户ID
        $MAX = $GLOBALS['db']->get_slave()->getAll($sql);

        //最大用户数
        $num = ceil($MAX[0]['Maxid'] / 2000) + 1;

        for ($i = 1; $i <= $num; ++$i) {
            $start = ($i - 1) * 2000; //起始id大小
            $end = ($i * 2000) - 1; //末位id大小

            $sql = 'select id,coupon_level_id from firstp2p_user WHERE id >='.$start.' and id <= '.$end; //获取user表2000一轮的用户ID与他们的coupon_level_id
            $user2000 = $GLOBALS['db']->get_slave()->getAll($sql);

            foreach ($user2000 as $key => $value) {
                $userid = $value['id'];
                $coupon_level_id = $value['coupon_level_id'];

                if(empty($coupon_level_id)){
                    Logger::error(implode(' | ', array_merge($log_info, array('用户'.$userid.',会员等级不存在',json_encode($value)))));
                    \libs\utils\Alarm::push('CouponLevelGroupCheck', '用户'.$userid.',会员等级不存在', json_encode($value));
                    continue;
                }

                /*根据用户的coupon_level_id 获取用户对应的第一个前缀ID*/
                $sql = 'SELECT id FROM firstp2p_coupon_level_rebate WHERE is_effect = 1 AND level_id = '.$coupon_level_id.' AND deal_id = 0 ORDER BY referer_rebate_ratio desc limit 1';
                $prefixID = $GLOBALS['db']->get_slave()->getOne($sql);

                if (empty($prefixID)) {
                    Logger::error(implode(' | ', array_merge($log_info, array('用户'.$userid.',会员等级'.$coupon_level_id.',对应返利规则不存在'))));
                    \libs\utils\Alarm::push('CouponLevelGroupCheck', '用户'.$userid.',会员等级'.$coupon_level_id.'对应返利规则不存在', json_encode($value));
                    continue;
                }

                //csv文件中没有对应的前缀ID项 continue
                if (empty($csvFileData[$prefixID])) {
                    Logger::error(implode(' | ', array_merge($log_info, array('用户'.$userid.',会员等级'.$coupon_level_id.',邀请码前缀id'.$prefixID.',对应返利规则不存在'))));
                    \libs\utils\Alarm::push('CouponLevelGroupCheck', '用户'.$userid.',会员等级'.$coupon_level_id.',邀请码前缀id'.$prefixID.',对应返利规则不存在', json_encode($value));
                    continue;
                }


                if (1 == $import) {
                    //user表中id=user_id new_coupon_level_id     其对应为以前缀id为下标的$csvFileData[][]
                    $sqlUpdate = 'UPDATE firstp2p_user SET new_coupon_level_id = '.$csvFileData[$prefixID]['new_coupon_level_id'].' WHERE id  ='.$userid;
                    $res = $GLOBALS['db']->query($sqlUpdate);

                    if (empty($res)) {
                        Logger::error(implode(' | ', array_merge($log_info, array('更新用户'.$userid.'服务等级失败',$sqlUpdate))));
                        \libs\utils\Alarm::push('CouponLevelGroupCheck', '更新用户服务等级失败', json_encode($value));
                        continue;
                    }
                }
            }
        }
        Logger::info(implode(' | ', array_merge($log_info, array('script end'))));
    }
}

$initCouponLevelInfo = new updateusercouponlevel();
$import = isset($argv[1]) ? intval($argv[1]) : 0;
$initCouponLevelInfo->updateFirstp2pUserTable($import);
exit('updateusercouponlevel.done');
