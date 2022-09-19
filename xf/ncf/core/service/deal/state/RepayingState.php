<?php
namespace core\service\deal\state;

use core\dao\deal\DealModel;
use core\dao\dealqueue\DealQueueInfoModel;
use core\dao\jobs\JobsModel;
use core\enum\DealEnum;
use core\enum\JobsEnum;
use core\enum\MsgbusEnum;
use core\service\deal\state\State;
use core\service\dealqueue\DealQueueService;
use core\service\msgbus\MsgbusService;
use core\service\project\ProjectService;
use core\dao\deal\DealLoanTypeModel;
use core\dao\dealqueue\DealQueueModel;
use libs\utils\Logger;

/**
 *
 * Class FailState
 * @package core\service\deal\state
 */
class RepayingState extends State{

    public function work(StateManager $sm) {
        $deal = $sm->getDeal();

        $startTrans = false;
        try {
            $GLOBALS['db']->startTrans();
            $startTrans = true;

            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            Logger::error(__CLASS__ . "," .__FUNCTION__ . ",line:" . __LINE__ ."," . $ex->getMessage());
            $startTrans && $GLOBALS['db']->rollback();
            return false;
        }
        return true;
    }
}