<?php

/**
 * 缓存锁
 */

namespace core\data;
use core\service\dealload\DealLoadService;
use core\dao\link\LinkModel;

class DealData extends BaseData {
    private $_arr_income_view = array("key"=>"income_view_", "time"=>172800);
    private $_arr_links = array("key"=>"links", "time"=>600);
    private $_arr_deal_oplock = array("key"=>"deal_oplock_", "time"=>180);
    private $_arr_deal_times_counter = array("key"=>"deal_times_counter_", "time"=>10);
    private $_arr_credit_china_data = array('key'=>'deal_log_','list_max'=>1000, 'time'=>86400);
    private $_arr_deal_oplock_pool = array('key'=>'deal_oplock_pool_', 'time'=>90);

    //跳过限宽门
    public static $skipPool = false;

    public function getDealOplock($deal_id) {
        return (int)\SiteApp::init()->cache->get($this->_arr_deal_oplock["key"] . $deal_id);
    }

    public function setDealOplock($deal_id, $value=1) {
        return \SiteApp::init()->cache->set($this->_arr_deal_oplock["key"] . $deal_id, $value, $this->_arr_deal_oplock["time"]);
    }

    public function lockDealBid ($deal_id) {
        $lock = \SiteApp::init()->cache->addValue($this->_arr_deal_oplock["key"] . $deal_id , 1 , $this->_arr_deal_oplock['time']);
        return $lock;
    }

    public function unlockDealBid($deal_id) {
        \SiteApp::init()->cache->deleteValue($this->_arr_deal_oplock["key"] . $deal_id);
    }

    public function getIncomeView($site_id) {
        $result = \SiteApp::init()->cache->get($this->_arr_income_view["key"] . $site_id);
        if ($result) {
            return json_decode($result, true);
        } else {
            return false;
        }
    }

    public function setIncomeView($site_id, $data) {
        $str = json_encode($data);

        //产品要求每月更新一次，所以这里不设置过期时间 @jinhaidong 2016-11-14
        //return \SiteApp::init()->cache->set($this->_arr_income_view["key"] . $site_id, $str, $this->_arr_income_view["time"]);
        return \SiteApp::init()->cache->set($this->_arr_income_view["key"] . $site_id, $str);
    }

    /**
     * 永久存储在缓存中
     * @param unknown $site_id
     * @param unknown $data
     */
    public function setIncomeViewPerp($site_id, $data,$expire = 0)
    {
        $str = json_encode($data);
        return \SiteApp::init()->cache->set($this->_arr_income_view["key"] . $site_id, $str, $expire);
    }
    public function getLinks() {
        $result = \SiteApp::init()->cache->get($this->_arr_links["key"]);
        return !empty($result) ? unserialize($result) : false;
    }

    public function setLinks($data) {
        $rawData = [];
        foreach ($data as $row)
        {
            $rawData = $row->getRow();
        }

        return \SiteApp::init()->cache->set($this->_arr_links["key"], serialize($rawData), $this->_arr_links["time"]);
    }

    public function getDealTimesCache() {
        return \SiteApp::init()->cache->get($this->_arr_deal_times_counter["key"]);
    }

    public function setDealTimesCache($count) {
        return \SiteApp::init()->cache->set($this->_arr_deal_times_counter["key"], $count, $this->_arr_deal_times_counter["time"]);
    }

    public function setCreditLoadCount($value){
        $cache = \SiteApp::init()->cache;
        $key = $this->_arr_credit_china_data['key'].'CREDIT_CHINA_DEAL_LOAD_COUNT';
        $res = $cache->set($key, $value, $this->_arr_credit_china_data['time']);
        if($res){
            return $res;
        }else{
            return false;
        }
    }

    public function getCreditLoadCount($key) {
        $cache = \SiteApp::init()->cache;
        $key = $this->_arr_credit_china_data['key'].'CREDIT_CHINA_DEAL_LOAD_COUNT';
        $result = $cache->get($key);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function pushCreditLoad($value){
        $cache = \SiteApp::init()->cache;
        $key = $this->_arr_credit_china_data['key'].'CREDIT_CHINA_LOAD_LOG';
        $value = json_encode($value);
        if($cache->llen($key) > $this->_arr_credit_china_data['list_max']){
            $cache->rpop($key);
        }
        $result = $cache->lpush($key,$value);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function popCreditLoad($len){
        $cache = \SiteApp::init()->cache;
        $key = $this->_arr_credit_china_data['key'].'CREDIT_CHINA_LOAD_LOG';
        $value = json_encode($value);
        $list = array();
        //pop前10个
        for($num=1; $num <= $len; $num++){
            $value = $cache->rpop($key);
            if($value){
                $list[]= $value;
            }else{
                break;
            }
        }
        return $list;
    }

    public function setMsgTemplates($tpls){
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis !== NULL){
            $redis->set('contract_templates',serialize($tpls),'ex',(strtotime(date("Y-m-d",strtotime("+1 day")))+10800)-time());
        }else{
            return false;
        }

        return true;
    }

    public function getMsgTemplates(){
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis !== NULL){
            $tpls = unserialize($redis->get('contract_templates'));
        }else{
            return false;
        }
        return $tpls;
    }

    /*
    * 设置单条模板记录
    */
    public function setMsgTemplatesByName($key,$value){
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis !== NULL){
            $redis->set($key,serialize($value),'ex',(strtotime(date("Y-m-d",strtotime("+1 day")))+10800)-time());
        }else{
            return false;
        }

        return true;
    }

    /*
     * 获取单条模板记录
     */
    public function getMsgTemplatesByName($key){
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis !== NULL){
            $tpl = unserialize($redis->get($key));
        }else{
            return false;
        }
        return $tpl;
    }


    /**
    *   进门
    *   return bool 是否让进门
    */
    public function enterPool($deal_id){
        //跳过限宽门
        if (self::$skipPool) {
            \libs\utils\Logger::debug(sprintf("skip enterPool succ redisPool [deal_id:%s]",$deal_id));
            return true;
        }
        $dealPoolSize = app_conf('DEAL_POOL_SIZE');
        $dealPoolSize = intval($dealPoolSize)>=1?$dealPoolSize:1;
        $poolSize = \SiteApp::init()->cache->incrValue($this->_arr_deal_oplock_pool["key"] . $deal_id);
        if($poolSize === false){
            // 如果incr失败，则说明redis故障，报警并允许投资
            \libs\utils\Logger::debug(sprintf("enterPool error redis return false . redisPool [deal_id:%s , value:%s]",$deal_id, $poolSize));
            \libs\utils\Alarm::push('deal', 'enterPool error redis return false');
            return true;
        }
        // 发现门里面人已经好多了。再减了
        if(intval($poolSize) > $dealPoolSize){
            $newPoolSize = \SiteApp::init()->cache->decrValue($this->_arr_deal_oplock_pool["key"] . $deal_id);
            \libs\utils\Logger::debug(sprintf("enterPool failed redisPool [deal_id:%s , value:%s , finalValue:%s]",$deal_id, $poolSize,$newPoolSize));
            return false;
        }
        \libs\utils\Logger::debug(sprintf("enterPool succ redisPool [deal_id:%s , value:%s]",$deal_id, $poolSize));
        \SiteApp::init()->cache->setExpire($this->_arr_deal_oplock_pool["key"] . $deal_id, $this->_arr_deal_oplock_pool["time"]);
        return true;
    }

    /**
    *   出门
    *   return bool 是否出去了
    */
    public function leavePool($deal_id){
        // 能正常进来的都应该不是fatal
        DealLoadService::$fatal = 0;

        //跳过限宽门
        if (self::$skipPool) {
            \libs\utils\Logger::debug(sprintf("skip leavePool succ . redisPool [deal_id:%s]",$deal_id));
            return true;
        }

        // 先减少
        $poolSize = \SiteApp::init()->cache->decrValue($this->_arr_deal_oplock_pool["key"] . $deal_id);
        if($poolSize === false){
            \libs\utils\Logger::debug(sprintf("leavePool error redis return false . redisPool [deal_id:%s , value:%s]",$deal_id, $poolSize));
            return false;
        }
        if(intval($poolSize) >= 0){
            \libs\utils\Logger::debug(sprintf("leavePool succ . redisPool [deal_id:%s , value:%s]",$deal_id, $poolSize));
            return true;
        }
        // 如果为负，加回来
        $newPoolSize = \SiteApp::init()->cache->incrValue($this->_arr_deal_oplock_pool["key"] . $deal_id);
        \libs\utils\Logger::debug(sprintf("leavePool failed . redisPool [deal_id:%s , value:%s , finalValue:%s]",$deal_id, $poolSize ,$newPoolSize));
        return false;
    }
}
