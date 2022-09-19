<?php
namespace task\controllers\dealcreate;

use core\dao\deal\DealModel;
use core\dao\project\DealProjectModel;
use task\controllers\BaseAction;
use core\service\deal\DealService;
use libs\utils\Logger;
use core\service\contract\CategoryService;

/**
 * 上标完成之后的合同初始化
 * Class Create
 * @package task\controllers\deal
 */
class InitContract extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));

            $dealId = $params['dealId'];
            $deal = DealModel::instance()->find($dealId);
            $project = DealProjectModel::instance()->find($deal->project_id);
            $projectDesc = $project->intro;

            /*项目委托投资说明*/
            /* 委托投资说明是专享的
            if(!empty($projectDesc)){
                $projectDescRequest = new RequestSetProjectDescription();
                $projectDescRequest->setProjectId($deal->project_id);
                $projectDescRequest->setType(1);
                $projectDescRequest->setSourceType(0);
                $projectDescRequest->setContent($projectDesc);
                $projectDescResponse = $rpc->go("\NCFGroup\Contract\Services\Category","setProjectDescription",$projectDescRequest);
                if($projectDescResponse->status != true){
                    throw new \Exception("合同服务项目委托投资说明调用失败：".$projectDescResponse->errorCode.":".$projectDescResponse->errorMsg);
                }
            }
            */

            //合同服务设置标的模板分类ID
            $contractResponse = CategoryService::setDealCId(intval($dealId),intval($deal->contract_tpl_type));
            if($contractResponse != true){
                throw new \Exception("合同服务调用失败");
            }
        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg = $ex->getMessage();
        }
    }
}
