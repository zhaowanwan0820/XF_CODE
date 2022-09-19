<?php
/**
 *
 * @date 2016-12-28
 * @author zhaoxiaoan@ucfgroup.com
 */

namespace core\service;

use core\dao\UserTagModel;
use core\dao\UserTagRelationModel;
use core\service\UserTagService;
use libs\utils\FileCache;
use libs\utils\logger;
use core\dao\UserModel;


/**
 *
 * @package core\service
 */
class UserVipService extends BaseService {

        // 白泽推送验证参数
    const SYNC_BAIZE_TOKEN='b4de66d2aa1200db7c0cd3b6da349788';

    const GET_VIP_INFO_TOKEN = '0e4f021a3b233a3d59db44314bb48b72';

        // 同步异常code
    const SYNC_EXCEPTION_CODE = 500;

    const VIP_CACHE_KEY = 'sysnc_vip_data';

    const VIP_CAVHE_UP_KEY = 'user_vip_tag_key_';

    public static $vip_group_ids = array(
        225 => '内部员工vip',
        36 => '股东vip',
    );
    // 内部员工组id
    public static $vip_inside_group_id = 225;
    // 股东组id
    public static $vip_shareholder_group_id = 36;
    // 1为主站vip，2为市场渠道
    public static $vip_level_tag_keys = array(
        1 => array(
            1 => 'VIP_M_1',
            2 => 'VIP_M_2',
            3 => 'VIP_M_3',
            4 => 'VIP_M_4',
        ),
        2 => array(
            1 => 'VIP_C_1',
            2 => 'VIP_C_2',
            3 => 'VIP_C_3',
            4 => 'VIP_C_4',
        )

    );
    // 标签对应等级
    public static $tag_key_vip_level = array(
        1 => array(
             'VIP_M_1' => 1,
             'VIP_M_2' => 2,
             'VIP_M_3' => 3,
             'VIP_M_4' => 4
        ),
        2 => array(
            'VIP_C_1' => 1,
            'VIP_C_2' => 2,
            'VIP_C_3' => 3,
            'VIP_C_4' => 4,
        )

    );
    public  static $total_vip_level = array(
        'VIP_M_1' => 1,
        'VIP_M_2' => 2,
        'VIP_M_3' => 3,
        'VIP_M_4' => 4,
        'VIP_C_1' => 1,
        'VIP_C_2' => 2,
        'VIP_C_3' => 3,
        'VIP_C_4' => 4,
    );
    // vip 等级
    public static $vip_level = array(
        1 => array('min' => 50000,'max' => 200000),
        2 => array('min' =>200000,'max' => 500000),
        3 => array('min' =>500000,'max' =>1000000),
        4 => array('min' => 1000000,'max' => 1000000),
    );
    // vip最高等级保留天数(每30天更新一次数据)
    public static $vip_day = 35;
    // 主站
    public static $m_type = 1;
    // 市场渠道
    public static $c_type = 2;

    public function handleVip(){

        $log = __CLASS__.','.__FUNCTION__;
        Logger::info($log.' start');
        $redis = \SiteApp::init()->cache;
        //$data = FileCache::getInstance()->get(self::VIP_CACHE_KEY);
        $data = $redis->get(self::VIP_CACHE_KEY);

        //$data = '203003625,1,5000001,1232323\r\n';
        if (empty($data)){
            Logger::info($log.'get data cache faild');
            \libs\utils\Monitor::add("VIP_IMPORT_DATA_FAILD",1);
        }

        // 分割数据
        $ex_arr = explode("\r\n",trim($data,'"'));

        if (count($ex_arr) == 1){
            Logger::info($log.' explode data exception ');
            \libs\utils\Monitor::add("VIP_IMPORT_DATA_FAILD",1);
        }
        // user_id, channel(市场渠道),money (30日在途),time

        foreach($ex_arr as $key => $v){
            $u_data = explode(',',$v);
            if (!is_array($u_data) || empty($u_data)){
                Logger::info($log.','.($key+1).' line data not array  or empty');
                continue;
            }

            if (empty($u_data[0]) || empty($u_data[1])){
                Logger::info($log.','.($key+1).' line uid or money is empty ');
                continue;
            }
            $uid = intval($u_data[0]);
            $channel = intval($u_data[1]);
            $money = $u_data[2];
            $time = $u_data[3];

            $vip_tag_key = $this->handleLevelVip($channel,$money);
            if (empty($vip_tag_key)){
                Logger::info($log.','.($key+1).' line uid '.$uid.' channel '.$channel.' money '.$money.' none vip_tag_key' );
                continue;
            }
            $this->handleTagVip($uid,$vip_tag_key,$channel);
        }
        Logger::info($log.'end');
    }


    /**
     * 处理vip等级
     * @param int $channel 渠道
     * @param $money 用户在途金额
     * @retrun string|boole tags的key 没有等级返回false
     */
    public function handleLevelVip($channel,$money){

        $ret = false;
        if(bccomp($money,self::$vip_level[1]['min'],2) == -1){
            return $ret;
        }
        $total = count(self::$vip_level);
        foreach (self::$vip_level as $key => $v){
            // 保留两位小数对比
            $min = bccomp($money,$v['min'],2);
            $max = bccomp($money,$v['max'],2);
            if (($min == 1) && $key == $total){
                $ret = self::$vip_level_tag_keys[$channel][$key];
                break;
            }
            // > min <=max
            if ($min == 1 && ($max ==-1 or $max==0)){
                $ret = self::$vip_level_tag_keys[$channel][$key];
            }

        }

        return $ret;
    }

    /**
     * 操作用户vip标签
     * @param $user_id
     * @param $tag_key
     * @param $channel
     * @return bool
     */
    public function handleTagVip($user_id, $tag_key,$channel){

        $ret = true;

        $user_tag_service = new UserTagService();
        // 获取用户所有tag
        $user_tags = $user_tag_service->getTags($user_id);
        // 存储tag_key => level
        $old_vip_tag_level = array();

        // 匹配vip标签 正常情况只有一个vip标签
        foreach($user_tags as $v){

            if (isset(self::$tag_key_vip_level[1][$v['const_name']])){
                $old_vip_tag_level[$v['const_name']] = self::$tag_key_vip_level[1][$v['const_name']];
            }
            if (isset(self::$tag_key_vip_level[2][$v['const_name']])){
                $old_vip_tag_level[$v['const_name']] = self::$tag_key_vip_level[2][$v['const_name']];
            }
        }

       // arsort($old_vip_tag_nt,SORT_NUMERIC);
        $cache = \SiteApp::init()->cache;
        $new_level = self::$tag_key_vip_level[$channel][$tag_key];
        if (!empty($old_vip_tag_level)){
            $up_level = $cache->get(self::VIP_CAVHE_UP_KEY.$user_id);
            if (empty($up_level)){
                $ret = $this->addTagAndlog($user_id, $tag_key);
            }else{
                foreach($old_vip_tag_level as $old_tag_key => $old_level) {
                    $vip_tag_info = $user_tag_service->getTagByConstNameUserIdRelationInfo($old_tag_key, $user_id);
                    $exp_time = strtotime($vip_tag_info['ctime']) + 86400 * self::$vip_day;
                    $old_m = date("n",strtotime($vip_tag_info['ctime']));
                    if ($old_level > $up_level && $old_level > $new_level && time() < $exp_time && date("n") == $old_m) {
                        $new_tag_key = $this->ReplacementChannel($channel, $old_tag_key, $old_level);
                        $this->delOldTagAddTag($user_id, $old_tag_key, $new_tag_key);
                    } else {
                        if ($up_level > $new_level) {
                            //$new_tag_key = $this->ReplacementChannel($channel, $old_tag_key, $old_level);
                            $new_tag_key = self::$vip_level_tag_keys[$channel][$up_level];
                           $this->delOldTagAddTag($user_id, $old_tag_key, $new_tag_key);
                        }else{
                            $this->delOldTagAddTag($user_id, $old_tag_key, $tag_key);
                        }
                    }
               }
            }
        }else{
            $ret = $this->addTagAndlog($user_id, $tag_key);
        }

        $set_redis = $cache->set(self::VIP_CAVHE_UP_KEY.$user_id,$new_level,intval(86400*self::$vip_day));
        if($set_redis == false){
            logger::info(__CLASS__.','.__FUNCTION__.$user_id.' set redis up level '.$new_level.' faild');
            \libs\utils\Monitor::add("VIP_IMPORT_DATA_FAILD",1);
        }

        return $ret;
    }

    /**
     *  删除旧标签 ，增加新标签
     * @param $user_id
     * @param $old_tag_key
     * @param $new_tag_key
     *
     * @return bool
     */
    public function delOldTagAddTag($user_id,$old_tag_key,$new_tag_key){
        $user_tag_service = new UserTagService();
        $user_tag_service->delUserTagsByConstName($user_id,$old_tag_key);
        // 重新开始计算天数
        $ret = $user_tag_service->addUserTagsByConstName($user_id, $new_tag_key);
        if (empty($ret)){
            $log = __CLASS__.','.__FUNCTION__;
            logger::info($log.'del old '.$old_tag_key.' write uid '.$user_id.' new tag key '.$new_tag_key.' faild');
            \libs\utils\Monitor::add("VIP_IMPORT_DATA_FAILD",1);
        }

        return $ret;
    }

    /**
     * 给用户添加标签如果失败记录日志和报警
     * @param $user_id
     * @param $tag_key
     */
    public function addTagAndlog($user_id,$tag_key){
        $user_tag_service = new UserTagService();
        $ret = $user_tag_service->addUserTagsByConstName($user_id, $tag_key);
        if (empty($ret)){
            $log = __CLASS__.','.__FUNCTION__;
            logger::info($log.' write uid '.$user_id.' new tag key '.$tag_key.' faild');
            \libs\utils\Monitor::add("VIP_IMPORT_DATA_FAILD",1);
        }
    }
    /**
     * 返回用户vip级别
     * @param $mobile
     * @return array
     */
    public function getVipInfo($mobile){
        $ret = array();
        $userModel = new UserModel();
        if (is_mobile($mobile)) {
            $condition = "`mobile`=':mobile'";
        }else{
            return $ret;
        }
        $param = array(
            ':mobile' => $mobile,
        );
        $userInfo = $userModel->findByViaSlave($condition, 'id,group_id',$param);
        if (empty($userInfo)){
            return $ret;
        }
        $userTagService = new UserTagService();
        // // 优先显示内部员工和股东,没有设置，查询vip标签
        if (!isset(self::$vip_group_ids[$userInfo->group_id])){

            $vip_tag_id = $this->getVipTag($userInfo->id);
            if (empty($vip_tag_id)){
                return $ret;
            }
            $tag_info = $userTagService->getBytagsIds(array($vip_tag_id));
            if (empty($tag_info)){
                return $ret;
            }
            //返回的是一个多维数组
            $level = explode('_',$tag_info[0]['const_name']);
            $ret['level'] = $level[2];
            if (isset(self::$tag_key_vip_level[1][$tag_info[0]['const_name']])){
                $ret['type'] = '1';
            }elseif(isset(self::$tag_key_vip_level[2][$tag_info[0]['const_name']])){
                $ret['type'] = '2';
            }

        }else{
            switch($userInfo->group_id){
                case self::$vip_inside_group_id:
                    $ret['type'] = '4';
                    $ret['level'] = '0';
                    break;
                case self::$vip_shareholder_group_id:
                    $ret['type'] = '3';
                    $ret['level'] = '0';
                    break;
            }
        }

        return $ret;
    }
    /**
     * 返回最终的vip标签
     * @param $uid
     */
    public function getVipTag($uid){

        $vip_tag_time = array();
        $userTagService = new UserTagService();
        $vip_tag_name = array_merge(self::$vip_level_tag_keys[1],self::$vip_level_tag_keys[2]);
        foreach($vip_tag_name as $v){
            $vip_tag_info = $userTagService->getTagByConstNameUserIdRelationInfo($v, $uid);
            if (!empty($vip_tag_info)){
                if ((strtotime($vip_tag_info['ctime']) + 86400*self::$vip_day) < time()){
                    $tag_ids = array();
                    $tag_ids[$vip_tag_info['tag_id']] = $vip_tag_info['tag_id'];
                    // 删除过期的vip标签
                    $ret = $userTagService->delUserTags($uid, $tag_ids);
                    if (empty($ret)) {
                        logger::info(__CLASS__.' '.__FUNCTION__. 'del expired  '.$uid.' faild tag_id '.$vip_tag_info['tag_id']);
                    }
                    continue;
                }
                $vip_tag_time[$vip_tag_info['tag_id']] = strtotime($vip_tag_info['ctime']);
            }

        }
        if (empty($vip_tag_time)){
            return false;
        }
        // 防止添加多个vip标签只取最新的一个
        arsort($vip_tag_time);
        foreach ($vip_tag_time as $tag_id => $time){
            return $tag_id;
            break;
        }

        return false;
    }

    /**
     * 根据渠道替换相应的标签
     * @param $channel
     * @param $old_tag_key
     * @param $old_level
     * @return mixed
     */
    public function ReplacementChannel($channel,$old_tag_key, $old_level){
        $new_tag_key = $old_tag_key;
        if ($channel == self::$m_type && stripos($old_tag_key, 'm') === false) {
            $new_tag_key = self::$vip_level_tag_keys[$channel][$old_level];
        }
        if ($channel == self::$c_type && stripos($old_tag_key, 'c') === false) {
            $new_tag_key = self::$vip_level_tag_keys[$channel][$old_level];
        }

        return $new_tag_key;
    }
}
