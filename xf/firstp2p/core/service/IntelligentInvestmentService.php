<?php
/**
 * 智投服务类
 *
 * @date 2018-03-19
 * @author wangchuanlu@ucfgroup.com
 */
namespace core\service;
use core\dao\IntelligentInvestmentModel;
use core\dao\UserReservationModel;
use libs\utils\Logger;
use core\service\intelligent\IntelligentInvestment;

class IntelligentInvestmentService extends BaseService
{
    /**
     * 投资
     * @param $deal 标的信息
     * @return bool
     */
    public function invest($deal) {
        try {
            $intelligent = new IntelligentInvestment($deal);
            $intelligent->invest();
            $intelligent->publish();
        } catch (\Exception $e) {
            Logger::error('IntelligentInvestException:'.$e->getMessage());
            return false;
        }
        return true;
    }


}
