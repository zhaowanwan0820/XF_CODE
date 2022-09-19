<?php
namespace task\controllers\ifapush;

use libs\utils\Logger;
use task\controllers\BaseAction;
use core\service\ifapush\PushLoan;
use core\dao\ifapush\IfaLoanModel;
use core\enum\MsgbusEnum;

class Loan extends BaseAction
{
    public function invoke()
    {
        $params = json_decode($this->getParams(), true);
        $topic = $this->getTopic();

        try {
            Logger::info(__CLASS__.','.__FUNCTION__.','.__LINE__.', Task receive params '.json_encode($params));

            $ifl = new IfaLoanModel();
            if (false === $ifl->isNeedReport($topic,$params)) {
                Logger::info(__CLASS__ . ',' . __FUNCTION__ . ',' . __LINE__ . ', 不需要上报此数据 ' . json_encode($params));
                return true;
            }

            $pu = new PushLoan($topic,$params);
            if ($topic == MsgbusEnum::TOPIC_DEAL_MAKE_LOANS){
                $saveRes = $pu->saveDealLoadData();
            }elseif ($topic == MsgbusEnum::TOPIC_DT_TRANSFER){
                $saveRes = $pu->saveData();
            }
            if (!$saveRes) {
                throw new \Exception('saveData error!');
            }
        } catch (\Exception $ex) {
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
            Logger::error($ex->getMessage());
        }
    }
}