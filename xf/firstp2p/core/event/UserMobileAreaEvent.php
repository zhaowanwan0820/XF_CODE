<?php
/**
 * 用户注册时间
 * 如果注册用户是北上广深杭地区的用户 打tag标签
 *
 * User: jinhaidong
 * Date: 2015/8/12 14:01
 */

namespace core\event;

use core\service\UserTagService;
use NCFGroup\Common\Extensions\RPC\RpcClientAdapter;
use NCFGroup\Protos\Commonservice\ProtoMobile;
use libs\utils\Logger;
use SebastianBergmann\Exporter\Exception;

class UserMobileAreaEvent extends BaseEvent {

    private $userId;
    private $userMobile;

    /** 需要打标签地区和标签对应关系  */
    private $areaConf = array(
        '010' => 'USER_REG_CITY_BJ',
        '021' => 'USER_REG_CITY_SH',
        '020' => 'USER_REG_CITY_GZ',
        '0755' => 'USER_REG_CITY_SZ',
        '0571' => 'USER_REG_CITY_HZ'
    );

    public function __construct($userId,$userMobile) {
        $this->userId = $userId;
        $this->userMobile = $userMobile;
    }

    public function execute() {
        $mobileInfo = $this->getMobileArea();
        if(!isset($mobileInfo['area_code'])) {
            Logger::error("MobileService response area_code is empty");
            return true;
        }

        $mobileArea = $mobileInfo['area_code'];
        if(!isset($this->areaConf[$mobileArea])) {
            return true;
        }

        try{
            $userTagService = new UserTagService();
            $res = $userTagService->addUserTagsByConstName($this->userId,$this->areaConf[$mobileArea]);
        }catch (\Exception $ex) {
            Logger::error("MobileService userTagService error:".$ex->getMessage());
            return false;
        }
        return $res ? true : false;
    }

    public function alertMails() {
        return array('jinhaidong@ucfgroup.com');
    }

    /**
     * 获取用户手机号码归属地
     */
    private function getMobileArea() {
        if(!isset($GLOBALS['components_config']['components']['rpc']['commonservice'])) {
            throw new \Exception("commonservice rpc conf is empty");
        }

        $rpcConf = $GLOBALS['components_config']['components']['rpc']['commonservice'];
        $rpc = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter($rpcConf['rpcServerUri'], $rpcConf['rpcClientId'], $rpcConf['rpcSecretKey']);

        $request = new ProtoMobile();
        $request->setMobile($this->userMobile);

        /** 最大重试次数 */
        $maxTryTimes = 3;
        $retryTimes = 0;

        do {
            if ($retryTimes > 0) {
                Logger::error("MobileService retry {$retryTimes} request:" .json_encode($request));
            }
            ++$retryTimes;
            $response = $rpc->callByObject(array(
                'service' => "\NCFGroup\Commonservice\Services\Mobile",
                'method' => "get",
                'args' => $request
            ));
            if($response) {
                break;
            }
        }while( -- $maxTryTimes > 0);

        if($response['errCode'] !=0) {
            Logger::error("MobileService error:".json_encode($response) . " request:".json_encode($request));
        }
        return $response['data'];
    }
}