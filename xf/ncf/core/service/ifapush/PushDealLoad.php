<?php
/**
 * http://wiki.corp.ncfgroup.com/pages/viewpage.action?pageId=26773657
 * Created by PhpStorm.
 * User: duxuefeng
 * Date: 2018/11/7
 * Time: 16:07
 */
namespace core\service\ifapush;


use core\dao\deal\DealModel;
use core\dao\deal\DealLoadModel;
use core\dao\ifapush\IfaDealLoadModel;
use core\enum\UserEnum;
use core\service\ifapush\PushBase;
use core\service\user\UserService;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\Logger;

class PushDealLoad extends PushBase
{
    public $dealInfo;
    public $loadInfo;
    public $loadList;

    public function __construct()
    {
        // 为了使本类可以再jobs中运行，所以当输入参数为1个时，才调用初始化方法。
        $args_num = func_num_args(); // 获取参数个数
        if ($args_num == 1) {
            $this->init(func_get_arg(0));
        }
    }

    public function init($dealId)
    {
        $this->dealInfo = $dealInfo = DealModel::instance()->getDealInfo($dealId);
        $this->loadList = DealLoadModel::instance()->getDealLoanList($dealId);
        $this->dbModel = new IfaDealLoadModel();
        return $this;
    }

    public function collectData()
    {
        $data = [
            'order_id' => Idworker::instance()->getId(),
            'finClaimId' => $this->loadInfo->id,
            'sourceProductCode' => $this->loadInfo->deal_id, // 标的编号
            'sourceFinancingCode' => -1, // 原产品信息编号
            'userIdcard' => $this->getUserIdcard($this->loadInfo->user_id),
            'invAmount' => sprintf("%.2f", $this->loadInfo->money), // 投资金额(元) 精确到小数点后两位
            'invRate' => bcdiv($this->dealInfo->income_fee_rate, 100, 6), // 年化收益率 精确到小数点后6位
            'invTime' => date('Y-m-d H:i:s', $this->dealInfo->repay_start_time + 28800), // 投资金额(元) 精确到小数点后两位
            'redpackage' => 0, // 投资红包
            'lockTime' => -1, // 封闭债转截止时间 yyyy-MM-dd; 如果不允许债权转让，则该项填-1
        ];
        return $data;
    }

    public function saveData()
    {
        try {
            $GLOBALS['db']->startTrans();
            foreach ($this->loadList as $key => $loadInfo) {
                $this->loadInfo = $loadInfo;
                $res = parent::saveData();
                if (!$res) {
                    throw new \Exception('还款计划信息保存失败');
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            throw $ex;
        }
        return true;
    }

    public static function saveDataJob($dealId)
    {
        try {
            $dealId = intval($dealId);
            $pu = new PushDealLoad($dealId);
            if (empty($pu->dealInfo)) {
                throw new \Exception('标的不存在');
            }
            if (empty($pu->loadList)) {
                throw new \Exception('投资记录不存在');
            }
            $pu->saveData();
            return true;
        } catch (\Exception $ex) {
            Logger::error(implode(' | ', array(__FILE__, __FUNCTION__, __LINE__, '失败原因:' . $ex->getMessage())));
            throw $ex;
        }
    }
}