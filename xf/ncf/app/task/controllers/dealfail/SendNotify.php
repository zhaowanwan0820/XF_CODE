<?php
namespace task\controllers\dealfail;

use task\controllers\BaseAction;
use core\service\deal\DealService;
use libs\utils\Logger;
use core\dao\deal\DealModel;
use core\dao\deal\DealLoadModel;
use core\service\user\UserService;

/**
 * 流标后发送 开始发邮件和短信等通知
 */
class SendNotify extends BaseAction {

    public function invoke() {
        $params = json_decode($this->getParams(),true);
        try{
            Logger::info(__CLASS__ . "," .__FUNCTION__ ."," .__LINE__ . ", Task receive params ".json_encode($params));
            $dealId =$params['dealId'];
            $deal = DealModel::instance()->find($dealId);

            if( empty($deal) || intval($deal['deal_status']) != 3 ){
                throw new \Exception('流标发送消息失败，标信息不存在');
            }

            if($deal['is_send_bad_msg']==0){
                $result = DealModel::instance()->updateBy(array('is_send_bad_msg'=>1), "id=".$dealId);
                if(!$result){
                    throw new \Exception('更新字段is_send_bad_msg失败');
                }

                // TODO 发送邮件、短信
                send_full_failed_deal_message($deal, 'failed');

                $log = array(
                        "type" => "deal",
                        "act" => "fail",
                        "is_succ" => 1,
                        "id" => $deal['id'],
                        "name" => $deal['name'],
                        );
                Logger::info(implode(" | ", $log));
            }

        }catch (\Exception $ex){
            $this->errorCode = -1;
            $this->errorMsg= $ex->getMessage();
        }
    }
}

