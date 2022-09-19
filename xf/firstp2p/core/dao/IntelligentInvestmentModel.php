<?php

/**
 * 智投类
 * @date 2018-03-19
 * @author wangchuanlu@ucfgroup.com
 */

namespace core\dao;

use libs\utils\Logger;

class IntelligentInvestmentModel extends BaseModel
{
    //智投特殊平均数redis存储key
    const SPECIAL_AVERAGE_REDIS_KEY = 'intelligent_investment_special_average';
    //当前vip平均投资金额，每日后台脚本分析，更新最新值到redis
    const AVERAGE_VIP_MONEY = 152569;
    //合规人数
    const MAX_USER_NUM = 200;
    //统计最近多少天的数据
    const ANALYSIS_LAST_DAYS = 60;
    // 捞取条数默认为多少条
    const RESERVE_LIST_SELECT_LIMIT = 1000;

    private $defaultSpecialConfigs = array(//默认标的借款金额区间平均投资金额
        '10000000' => array(
            'money'=>10000000,
            'average'=>86193
        ),
        '20000000' => array(
            'money'=>20000000,
            'average'=>120828
        ),
        '30000000' => array(
            'money'=>30000000,
            'average'=>172526
        ),
        '40000000' => array(
            'money'=>40000000,
            'average'=>235202
        ),
        '50000000' => array(
            'money'=>50000000,
            'average'=>300572
        ),
        '60000000' => array(
            'money'=>60000000,
            'average'=>303030
        ),
    );

    /**
     * 获取放大倍率
     * @param $dealMoney
     */
    public function getSpecialAverage($dealMoney) {
        $specialAverage = self::AVERAGE_VIP_MONEY;
        $specialConfigs = $this->defaultSpecialConfigs;
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis !== NULL) {
            $specialConfigInfo = $redis->get(self::SPECIAL_AVERAGE_REDIS_KEY);
            if(!empty($specialConfigInfo)) {
                $specialConfigs = json_decode($specialConfigInfo,true);
            }
        }

        foreach ($specialConfigs as $specialConfig) {
            if($specialConfig['money'] >= $dealMoney) {
                $specialAverage = $specialConfig['average'];
                break;
            }
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "平均起投额:",$specialAverage,"智投数据分析结果:",json_encode($specialConfig) )));

        return $specialAverage;
    }

    /**
     * 获取最大可参与普通用户人数
     * @param $dealMoney 标的金额
     * @return int
     */
    public function getMaxCommonUserNum($dealMoney) {
        //最多多少个VIP大额用户可以投满该标的
        $averageVipMoney = $this->getSpecialAverage($dealMoney);
        $maxVipNum = ceil($dealMoney/$averageVipMoney);
        if($maxVipNum >= self::MAX_USER_NUM) { //如果大户需求量超过最大限制，则事先小户
            return 0;
        }
        //剩余散户可投资人数
        $maxCommonNum = self::MAX_USER_NUM - $maxVipNum;
        return $maxCommonNum;
    }

    /**
     * 获取标的正常投资人数平均金额
     * @param $dealMoney 标的金额
     * @return string
     */
    public function getNormalAvearge($dealMoney) {
        $average = bcdiv($dealMoney,self::MAX_USER_NUM,2);
        return $average;
    }

    /**
     * 获取预约记录
     * @param $condition 条件
     * @param string $limit 每次取多少条
     * @param string $order 排序
     */
    public function getReserveList($condition, $limit='', $order='') {
        if('' != $limit) {
            $limit = "LIMIT {$limit}";
        }
        $sql = 'SELECT * FROM `firstp2p_user_reservation` WHERE %s %s %s ';
        $sql = sprintf($sql,$condition,$order,$limit);
        $list = $this->findBySql($sql, array(), true);
        return $list;
    }

    /**
     * 更新特殊平均值
     */
    public function updateSpecialAverage()
    {
        \FP::import("libs.common.dict");
        $specialAverageConfigs=\dict::get("ZHITOU_SPECIAL_AVERAGE");// 智投数据分析配置
        $specialAverages = $this->defaultSpecialConfigs;
        foreach ($specialAverageConfigs as $specialAverageConfig) {
            $moneyConfig = explode('-',$specialAverageConfig);
            if(count($moneyConfig) == 2) {
                $minDealMoney = $moneyConfig[0];
                $maxDealMoney = $moneyConfig[1];
                $dealMoneyRangeAverage = $this->getDealMoneyRangeAverage($minDealMoney,$maxDealMoney);
                if($dealMoneyRangeAverage) {
                    $specialAverages[$maxDealMoney] = $dealMoneyRangeAverage;
                }
            }
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis !== NULL) {
            if(!empty($specialAverages)) {
                //排序
                ksort ($specialAverages);
                //设置缓存
                $redis->set(self::SPECIAL_AVERAGE_REDIS_KEY, json_encode($specialAverages),'ex', 30*86400);
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "更新成功 configs:",json_encode($specialAverages) )));
        } else {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "更新失败 configs:",json_encode($specialAverages) )));
            return false;
        }
        return true;
    }

    /**
     * 获取标的金额区间平均投资值
     * @param $dealMoney 标的金额
     * @return array
     */
    private function getDealMoneyRangeAverage($minDealMoney,$maxDealMoney) {
        $timeBegin = time() - self::ANALYSIS_LAST_DAYS * 86400;//统计近多少天内的数据
        //某个金额区间的投资总人数
        $countDefaultSql = 'SELECT COUNT(DISTINCT deal_id,user_id) AS totalUser FROM `firstp2p_deal_load` WHERE deal_id IN (SELECT id FROM firstp2p_deal WHERE create_time > %d AND borrow_amount >= %d AND borrow_amount < %d AND `deal_status` IN(2,4,5))';
        $countSql = sprintf($countDefaultSql,$timeBegin,$minDealMoney,$maxDealMoney);
        $countRes = $this->findBySql($countSql, array(), true);
        $totalUser = $countRes['totalUser'];

        //某个金额区间的投资总金额
        $sumDefaultSql = 'SELECT SUM(borrow_amount) AS totalMoney FROM firstp2p_deal WHERE create_time > %d AND borrow_amount >= %d AND borrow_amount < %d AND `deal_status` IN(2,4,5)';
        $sumSql = sprintf($sumDefaultSql,$timeBegin,$minDealMoney,$maxDealMoney);
        $sumRes = $this->findBySql($sumSql, array(), true);
        $totalMoney = floor($sumRes['totalMoney']);

        //小于最低标的金额,或者投资人数为0,为无效统计
        if( ($totalMoney < $minDealMoney) || ($totalUser == 0) ) {
            return false;
        }
        //平均值
        $average = ceil($totalMoney/$totalUser);
        return array(
            'money' => $maxDealMoney,
            'average'=> $average
        );
    }
}
