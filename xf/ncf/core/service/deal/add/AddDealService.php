<?php
/**
 * 上标 service 供信贷和内部系统调用
 *
 * @author  jinhaidong
 * @date 2018-6-19 18:08:29
 */
namespace core\service\deal\add;

use core\dao\deal\DealExtModel;
use core\dao\deal\DealExtraModel;
use core\dao\deal\PlatformManagementModel;
use core\dao\project\DealProjectModel;
use core\enum\MsgbusEnum;
use core\enum\DealExtEnum;
use core\service\BaseService;
use core\service\deal\add\OverideDealService;
use core\dao\deal\DealModel;
use core\dao\deal\ProductManagementModel;
use core\service\deal\DealSiteService;
use libs\utils\Logger;

use core\service\msgbus\MsgbusService;

class AddDealService extends BaseService {

    private $approveNumber = null;

    const ADD_DEAL_KEY_PREFIX =  'ADD_DEAL_';
    const ADD_DEAL_KEY_EXPIRE = 86400;

    public function __construct($approveNumber){
        $this->approveNumber = $approveNumber;
    }

    public function getRedisKey(){
        return self::ADD_DEAL_KEY_PREFIX . $this->approveNumber;
    }

    // TODO 待完善
    public function getAddDealLock(){
        return true;
    }

    public function dealAddDealLock(){
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (empty($redis)) {
            throw new \Exception("resdis make failed");
        }
        $key = self::ADD_DEAL_KEY_PREFIX . $this->approveNumber;
        return $redis->del($key);
    }

    /**
     * 上标逻辑
     * @param array $projectData
     * @param array $dealData
     * @param array $dealExtData
     * @param array $otherData
     * @param array $dealExtraData
     * @return array|bool
     * @throws \Exception
     */
    public function addDeal(Array $projectData,Array $dealData,Array $dealExtData,Array $otherData = array(),Array $dealExtraData = array()){

        $projectFields = DealProjectModel::instance()->getFields();
        $dealFields = DealModel::instance()->getFields();
        $dealExtFields = DealExtModel::instance()->getFields();
        $dealExtraFields = DealExtraModel::instance()->getFields();
        $requestProjectFields = array_keys($projectData);
        $requestDealFields = array_keys($dealData);
        $requestDealExtFields = array_keys($dealExtData);
        $requestDealExtraFields = array_keys($dealExtraData);

        if($result = array_diff($requestProjectFields,$projectFields)){
            throw new \Exception("参数不存在:".implode(",",$result));
        }

        if($result = array_diff($requestDealFields,$dealFields)){
            throw new \Exception("参数不存在:".implode(",",$result));
        }

        if($result = array_diff($requestDealExtFields,$dealExtFields)){
            throw new \Exception("参数不存在:".implode(",",$result));
        }

        if($result = array_diff($requestDealExtraFields,$dealExtraFields)){
            throw new \Exception("参数不存在:".implode(",",$result));
        }

        $dealInfo = DealModel::instance()->getProByApproveNum($dealData['approve_number'],false);
        if($dealInfo){
            throw new \Exception("approveNumber已经存在");
        }
        $projectId = DealProjectModel::instance()->getProjectIdByName($projectData['name']);
        if(!empty($projectId)){
            throw new \Exception("项目名称已经存在");
        }

        $this->getAddDealLock();

        $data = array();

        $dealData = OverideDealService::overideDeal($dealData);

        $dealSiteService = new DealSiteService();
        try{
            $GLOBALS['db']->startTrans();

            $projectId = DealProjectModel::instance()->addProject($projectData);

            $dealId = DealModel::instance()->addDeal($projectId,$dealData);

            $dealExtId = DealExtModel::instance()->addDealExt($dealId,$dealExtData);

            $dealExtraId = DealExtraModel::instance()->addDealExtra($dealId,$dealExtraData);

            if(isset($otherData['platform_management']['use_money']) && $otherData['platform_management']['use_money'] > 0){
                $pmData = array('use_money'=>$otherData['platform_management']['use_money'],'is_warning'=>$otherData['platform_management']['is_warning']);
                $res = PlatformManagementModel::instance()->updatePlatformInfoByCondition($otherData['platform_management']['use_money'],$otherData['platform_management']['advisory_id'],$otherData['platform_management']['is_warning']);
                if(!$res){
                    throw new \Exception("更新平台用款失败");
                }
            }
            if(isset($otherData['product_management']['use_money']) && $otherData['product_management']['use_money'] > 0){
                $pmData = array('use_money'=>$otherData['product_management']['use_money'],'is_warning'=>$otherData['product_management']['is_warning']);
                $res = ProductManagementModel::instance()->updateProductInfoByCondition($otherData['product_management']['use_money'],$otherData['product_management']['product_name'],$otherData['product_management']['is_warning']);
                if(!$res){
                    throw new \Exception("更新产品用款失败");
                }
            }

            // 更新标的站点对应关系
            $res = $dealSiteService->updateDealSite($dealId,array($dealData['site_id']));
            if(!$res){
                throw new \Exception("更新标的站点对应关系失败");
            }

            // 上标成功 消息队列
            $loanFeeRateType = $dealExtData['loan_fee_rate_type'];
            if($loanFeeRateType == DealExtEnum::FEE_RATE_TYPE_BEHIND || $loanFeeRateType == DealExtEnum::FEE_RATE_TYPE_FIXED_BEHIND){
                $payType = 1;
            } else{
                $payType = 0;
            }
            $message = array('dealId'=>$dealId,'payType'=>$payType,'payAuto'=>1);
            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_CREATE,$message);
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",上标失败 errMsg:".$ex->getMessage());
            $this->dealAddDealLock();
            throw $ex;
        }
        return array('projectId'=>$projectId,'dealId'=>$dealId);
    }
}
