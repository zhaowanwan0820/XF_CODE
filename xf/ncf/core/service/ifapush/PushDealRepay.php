<?php
/**
 * http://wiki.corp.ncfgroup.com/pages/viewpage.action?pageId=26772969
 * Created by PhpStorm.
 * User: jinhaidong
 * Date: 2018/11/7
 * Time: 16:07
 */
namespace core\service\ifapush;


use core\dao\deal\DealModel;
use core\dao\ifapush\IfaDealRepayModel;
use core\dao\ifapush\IfaDealStatusModel;
use core\dao\repay\DealRepayModel;
use core\service\ifapush\PushBase;
use core\service\user\UserService;
use NCFGroup\Common\Library\Idworker;

class PushDealRepay extends PushBase
{
    public $repayList;

    public $repayInfo;

    public $userInfo;

    public $dealInfo;

    public function __construct($dealId)
    {
        $this->repayList = DealRepayModel::instance()->getDealRepayListByDealId($dealId);
        $this->dealInfo = DealModel::instance()->getDealInfo($dealId);
        $this->userInfo = $userInfo = UserService::getUserById($this->dealInfo['user_id'],'id,user_type,country_code,real_name,id_type,mobile,email,create_time,idno');
        $this->dbModel = new IfaDealRepayModel();
    }

    public function collectData()
    {
        $data = [
            'order_id' => Idworker::instance()->getId(),
            'userIdcard' => $this->getUserIdcard($this->userInfo['id']),
            'sourceProductCode' => $this->repayInfo->deal_id,//标的编号
            'totalIssue' => count($this->repayList), // 总期数
            'issue' => $this->repayInfo->issue, // 当前期数
            'replanId' => $this->repayInfo->id, // 还款计划编号 ，平台内所有还款计划 中编号唯一。如果没有则填 写“散标编号+当前期数”
            'curFund' => $this->repayInfo->principal, // 当期应还本金 精确到 2  位小数
            'curInterest' => $this->repayInfo->interest, // 当期应还利息 精确到 2 位小数
            'repayTime' => date('Y-m-d H:i:s',$this->repayInfo->repay_time+28800), // 当期应还款时间点 yyyy-MM-dd HH:mm:ss
            'repayStartTime' => date('Y-m-d',$this->dealInfo->repay_start_time+28800), // 标的的放款时间 yyyy-MM-dd 不会发送给协会，但是会使用该字段生成批次号
        ];
        return $data;
    }

    public function saveData()
    {
        try{
            $GLOBALS['db']->startTrans();
            foreach($this->repayList as $key=>$repayInfo){
                $this->repayInfo = $repayInfo;
                $this->repayInfo->issue = $key + 1;
                $res = parent::saveData();
                if(!$res){
                    throw new \Exception('还款计划信息保存失败');
                }
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            throw $ex;
        }
        return true;
    }
}