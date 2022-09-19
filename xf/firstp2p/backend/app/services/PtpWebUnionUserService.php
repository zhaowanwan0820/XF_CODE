<?php
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\ProtoWebUnionUser;
use NCFGroup\Protos\Ptp\RequestOauth;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use core\service\UserService;
use core\service\UserLogService;
use core\service\user\WebBO;
use core\service\user\BOFactory;
use core\dao\UserModel;
use NCFGroup\Ptp\daos\UserDAO;
use core\service\user\BOBase;
use NCFGroup\Protos\Ptp\RequestUser;
use libs\utils\Logger;
use libs\utils\Risk;
use core\service\risk\RiskServiceFactory;
use libs\utils\Aes;

class PtpWebUnionUserService extends ServiceBase
{
    private $_LogTags = 'p2peye';

    /**
     * @注册为新用户(网盟,现在只有网贷天眼一家)
     * @param ProtoWebUnionUser $request
     * @return ResponseBase $response
     */
    public function webUnionRegister(ProtoWebUnionUser $request)
    {
        if(!($GLOBALS['medalRpc'] instanceof \NCFGroup\Common\Extensions\RPC\RpcClientAdapter)){
            $medalRpcConfig = $GLOBALS['components_config']['components']['rpc']['medal'];
            $GLOBALS['medalRpc'] = new \NCFGroup\Common\Extensions\RPC\RpcClientAdapter($medalRpcConfig['rpcServerUri'], $medalRpcConfig['rpcClientId'], $medalRpcConfig['rpcSecretKey']);
        }
        $userInfo = (new UserService())->getByMobile($request->getMobile());
        $response = new ProtoUser();
        if(!empty($userInfo)){
            $response->resCode = 1;
            $response->setUserId((int)$userInfo['id']);
            $response->setUserName($userInfo['user_name']);
            $response->setMobile($userInfo['mobile']);
            return $response;
        }

        $webBo = new WebBO('web');
        $info = array('mobile'=>$request->getMobile(), 'username'=>$request->getUserName(), 'password'=>$request->getRegisterPwd(), 'email'=>'',  'invite_code'=>$request->getInviteCode());
        $res = $webBo->insertInfo($info);
        if($res['status']<0){
            \libs\utils\Logger::debug($this->_LogTags .':注册为新用户失败,WebBO->insertInfo:' . var_export($res['data'],true));
            $response->resCode = 2;
            $response->setUserId(0);
            return $response;
        }

        \libs\utils\Logger::debug($this->_LogTags . ':注册新用户成功,user_id:' . $res['user_id']);
        RiskServiceFactory::instance(Risk::BC_REGISTER)->notify(array('userId'=>$res['user_id']));
        
        $response->resCode = 0;
        $response->setUserId((int)$res['user_id']);
        $response->setUserName($res['data']['username']);
        return $response;
    }

    /**
     * @获取用户的信息
     * @param ProtoWebUnionUser $request
     * @return ResponseBase $response
     */
    public function webUnionLogin(ProtoWebUnionUser $request)
    {
        $res = (new UserService())->getByMobile($request->getMobile());
        $response = new ResponseBase();
        $response->ret = array('sess_key'=>app_conf("AUTH_KEY"), 'sess_data'=>$res->_row, 'deal_id'=>Aes::encryptForDeal($request->getDealId()), 'url_domain'=>app_conf('FIRSTP2P_DOMAIN'));

        return $response;
    }

    /**
     * @校验用户名和密码
     * @param ProtoWebUnionUser $request
     * @return ResponseBase $response
     */
    public function chkUserLoginPwd(ProtoWebUnionUser $request)
    {
        $bo = BOFactory::instance('web');
        $ret = $bo->authenticate($request->getMobile(), $request->getUserPwd() ,$this->form->data['country_code']);
        if((int)$ret['code'] == 0){
            $res = (new UserService())->getByMobile($request->getMobile());
            $sess_data = $res->_row;
        }

        $response = new ResponseBase();
        $response->ret = array('code'=>$ret['code'], 'msg'=>$ret['msg'],'sess_key'=>app_conf("AUTH_KEY"), 'sess_data'=>$sess_data, 'deal_id'=>Aes::encryptForDeal($request->getDealId()), 'url_domain'=>app_conf('FIRSTP2P_DOMAIN'));

        return $response;
    }

    /**
     * @删除因在开放平台绑定失败的p2p用户
     * @param ProtoWebUnionUser $request
     * @return ResponseBase $response
     */
    public function webUnionUserDel(ProtoWebUnionUser $request)
    {
        $mobile = $request->getMobile();
        return (new UserService())->webUnionUserDel($mobile);
    }
}










