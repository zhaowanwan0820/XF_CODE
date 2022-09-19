<?php
/**
 * 返回各机构用户投资tender的类实例
 */
class BaseTenderService extends ItzInstanceService
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function run($params){
        switch ($params['platform']){
            case Yii::app()->c->itouzi['itouzi']['platform_id'] :
                return ITZTenderService::getInstance();
                break;
            default :
                return AgConfirmTenderService::getInstance();
                break;
        }
    }

    /**
     * 获取用户债权确权额度（本金）
     * @param $params
     * @return array|bool
     */

    public function getTenderDebtConfirmCount($params)
    {
        return [];
    }

    /**
     * 项目确权列表
     * @param $params
     * @return array|bool
     */

    public function getTenderConfirmList($params)
    {
        return [];
    }

    /**
     * 确权
     * @param $params
     * @return array|bool
     */
    public function confirmDebt($params)
    {
        return [];
    }

    /**
     * 投资项目详情
     * @param $params
     * @return array|bool
     */
    public function getTenderDetail($params)
    {
        return [];
    }
}