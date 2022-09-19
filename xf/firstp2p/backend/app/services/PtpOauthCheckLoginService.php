<?php
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Cache\RedisCache;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\ProtoOauthSessionCheckLogin;
use NCFGroup\Protos\Ptp\ProtoOauthTokenCheckLogin;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use core\service\UserService;
use core\dao\EnterpriseContactModel;
use libs\utils\Sess;

/**
 * PtpOauthCheckLoginService
 * 开放平台校验用户是否登陆
 * @uses ServiceBase
 * @package default
 */
class PtpOauthCheckLoginService extends ServiceBase {

    /**
     * 校验用户是否登陆(通过session id)
     * @param \NCFGroup\Protos\Ptp\RequestUserLogin $request
     * @return type
     */
    public function checkSessionLogin(ProtoOauthSessionCheckLogin $request) {
        $sess_id = $request->getSession_id();

        $cacheObj = new RedisCache();
        $sess_key= 'PHPREDIS_SESSION:' . $sess_id;
        $sess_val = $cacheObj->get($sess_key);
        $sess_val = Sess::decode($sess_val);

        if(!empty($sess_val)){
            $terminal = $request->getTerminal();
            if ('web' == $terminal) {
                $userInfo = (array) $sess_val['fanweuser_info'];
                $userId = $userInfo['id'];
            } else {
                $userInfo = (array) $sess_val['SESSION_USER_INFO'];
                $userId = $userInfo['uid'];
            }

            if (empty($userId)) {
                $user_data = array();
            } else {
                $data = $this->getUserInfoById($userId);
                $user_data = empty($data) ? array() : array('uid' => $data['id'], 'mobile' => $data['mobile'], 'real_name' => $data['real_name'], 'sex' => $data['sex'], 'ctime' => $data['create_time']);
            }
        }else{
            $user_data = array();
        }

        $response = new ResponseBase();
        $response->user_data = $user_data;
        return $response;
    }

    public function  getUserInfoById($userId) {
        $userService = new UserService();
        $userInfo = $userService->getUserViaSlave($userId);
        if (empty($userInfo)) {
            $userInfo = $userService->getUser($userId);
        }
        $userInfo = empty($userInfo) ? array() : $userInfo->getRow();
        if ($userInfo && $userService->checkEnterpriseUser($userId)) {
            $receive_msg_mobile = EnterpriseContactModel::instance()->getReceiveMobileByUserId($userId);
            $receive_mobile_array = explode(",",$receive_msg_mobile);
            $mobile_array = explode("-",$receive_mobile_array[0]);
            $userInfo['mobile_code'] = $mobile_array[0];
            $userInfo['mobile'] = $mobile_array[1];
        }
        return $userInfo;
    }

    /**
     * 校验用户是否登陆(通过token)
     * @param \NCFGroup\Protos\Ptp\RequestUserLogin $request
     * @return type
     */
    public function checkTokenLogin(ProtoOauthTokenCheckLogin $request) {
        $token_id = $request->getToken_id();

        $userService = new UserService();
        $user_data = $userService->getUserByCode($token_id);
        if(isset($user_data['user'])){
            $data = $user_data['user'];
            $user_data = array('uid' => $data['id'], 'mobile' => $data['mobile'], 'real_name' => $data['real_name'], 'sex' => $data['sex']);
        }else{
            $user_data = array();
        }
        $response->user_data = $user_data;
        return $response;
    }


}
