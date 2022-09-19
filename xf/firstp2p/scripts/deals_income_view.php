<?php
/**
 * 网站页面显示的收益定时更新到redis缓存
 * 每4个小时运行一次，从数据库写入redis
 *
 * @date 2015-07-15
 * @author zhaohui3 <zhaohui3@ucfgroup.com>
 */
//crontab: * 4 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php deals_income_view.php


namespace scripts;

use libs\db\MysqlDb;
use libs\db\Model;
use core\dao\BaseModel;
use core\service\EarningService;
use core\dao\DealLoanTypeModel;
use libs\utils\Logger;
use core\dao\DealModel;
use core\service\DealService;

set_time_limit(0);

require(dirname(__FILE__) . '/../app/init.php');
class Deals_Income_view
{
    private $_cacheLog = null;
    private $_incomeSite = array(2,8,11,12);
    private $_earningService = null;
    private $_deal=null;
    public function __construct()
    {
        $this->_cacheLog = array();
        // 从库
        //$GLOBALS['db'] = MysqlDb(app_conf('DB_SLAVE_HOST').":".app_conf('DB_SLAVE_PORT'), app_conf('DB_SLAVE_USER'),app_conf('DB_SLAVE_PWD'),app_conf('DB_NAME'),'utf8', 0, 1);
        $GLOBALS['db'] = MysqlDb::getInstance('firstp2p', 'slave');
        if($GLOBALS['db']->link_id === false){

            exit();
        }
        $this->_earningService = new EarningService();
        $this->_deal=new DealService();
    }

    public function run()
    {//$begin=time();
        $siteKeys = array(
                'DEAL_SITE_ALLOW',
                'MIN_INCOME_RATE',
                'MAX_INCOME_RATE'
        );
        //获取所有分站列表
        $siteList = $GLOBALS['sys_config']['TEMPLATE_LIST'];
        // 遍历所有分站站点
        foreach($siteList as $siteTpl => $siteID) {
            // 设置对应分站的属性
            foreach($siteKeys as $confKey) {
                $GLOBALS['sys_config'][$confKey] = get_config_db($confKey, $siteID);
                $GLOBALS['sys_config']['TEMPLATE_ID'] = $siteID;
            }
            $deal_site_allow = app_conf('DEAL_SITE_ALLOW');
            if(array_search(app_conf('TEMPLATE_ID'),$this->_incomeSite) !== false) {
                $cacheID = empty($deal_site_allow)?app_conf("TEMPLATE_ID"):$deal_site_allow;
                $deals_income_view = $this->_setIncome($cacheID,false);
            } else {
                $cacheID = 0;
                $deals_income_view = $this->_setIncome($cacheID,true);
            }
        }

        //rss收益统计数据
        $type_id= $this->_deal->getDealTypes();//获取type_id
        $type_1=$type_id['data']['-1']['id'];
        $type_1=explode (',',$type_1);
        $type_other=$type_id['others'];
        array_pop($type_other);
        array_push($type_1, '0');
        $type_id=array_merge($type_1,$type_other);
        //var_dump($type_id);
        $cacheCateID=null;
        foreach($type_id as $cate) {
                $deals_income_view = $this->_setIncome($cacheCateID, false, $cate);
        }//$end=time();$t=$end-$begin;echo '执行时间:'.$t.'s';
        //exit("\r\n\t******** FINISH ***************\t\n");
    }

    // 写入redis
    private function _setIncome($cacheID, $showAll, $cateID = null)
    {
        if(isset($this->_cacheLog[$cacheID])) {
            return true;
        } elseif ($cateID==null) {
            $deals_income_view = $this->_earningService->getDealsIncomeViewPerp($showAll, true);
            if(isset($deals_income_view) && !empty($deals_income_view)) {
                $this->_cacheLog[$cacheID] = $deals_income_view;
                return true;
            } else {
                return false;
            }
        } else {
            $deals_income_view = $this->_earningService->getIncomeViewByCatePerp($showAll, $cateID);
            if(isset($deals_income_view) && !empty($deals_income_view)) {
                return true;
            } else {
                return false;
            }
        }
    }
}



$emailMonitor = new Deals_Income_view();
$emailMonitor->run();

