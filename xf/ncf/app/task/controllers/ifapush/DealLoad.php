<?php
namespace task\controllers\ifapush;

use libs\utils\Logger;
use task\controllers\BaseAction;
use core\dao\ifapush\IfaDealModel;
use core\dao\jobs\JobsModel;
use core\enum\JobsEnum;


class DealLoad extends BaseAction
{

    public function invoke()
    {
        $params = json_decode($this->getParams(), true);
        try {
            Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . __LINE__ . ", Task receive params " . json_encode($params));

            $dealId = $params['dealId'];

            $ifd = new IfaDealModel();
/*            if ($ifd->isNeedReport($dealId) === false) {
                Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . __LINE__ . ", 不需要上报此标的数据 " . json_encode($params));
                return true;
            }*/

            $jobsModel = new JobsModel();
            $functions = "\core\service\ifapush\PushDealLoad::saveDataJob";
            $params = ['dealId' => $dealId];
            $jobsModel->priority = JobsEnum::PRIORITY_PUSH_DEAL_LOAD;
            $result = $jobsModel->addJob($functions, $params);
            if (!$result) {
                throw new \Exception('add insert jobs fail. 参数:'.json_encode($params));
            }
        } catch (\Exception $ex) {
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
            Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . __LINE__ . "," .json_encode($params),', 失败原因:' .$ex->getMessage());
        }
    }
}