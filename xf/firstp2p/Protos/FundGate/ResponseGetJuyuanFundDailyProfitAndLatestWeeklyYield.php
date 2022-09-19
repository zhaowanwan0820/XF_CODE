<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 最近xx天七日年化与万份收益
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseGetJuyuanFundDailyProfitAndLatestWeeklyYield extends ResponseBase
{
    /**
     * 最近xx天每天的七日年化与万份收益
     *
     * @var Array<ProtoDailyProfitAndLatestWeeklyYield>
     * @required
     */
    private $juyuanFundDailyProfitAndLatestWeeklyYieldList;

    /**
     * @return Array<ProtoDailyProfitAndLatestWeeklyYield>
     */
    public function getJuyuanFundDailyProfitAndLatestWeeklyYieldList()
    {
        return $this->juyuanFundDailyProfitAndLatestWeeklyYieldList;
    }

    /**
     * @param Array<ProtoDailyProfitAndLatestWeeklyYield> $juyuanFundDailyProfitAndLatestWeeklyYieldList
     * @return ResponseGetJuyuanFundDailyProfitAndLatestWeeklyYield
     */
    public function setJuyuanFundDailyProfitAndLatestWeeklyYieldList(array $juyuanFundDailyProfitAndLatestWeeklyYieldList)
    {
        $this->juyuanFundDailyProfitAndLatestWeeklyYieldList = $juyuanFundDailyProfitAndLatestWeeklyYieldList;

        return $this;
    }

}