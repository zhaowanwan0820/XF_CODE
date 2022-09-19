<?php
/**
 * 第三方用户绑定检测接口
 */

namespace api\controllers\userbind;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class Check extends AppBaseAction {

    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "client_id" => array("filter" => "required", "message" => 'ERR_PARAMS_VERIFY_FAIL'),
            "client_token" => array("filter" => "required", "message" => 'ERR_PARAMS_VERIFY_FAIL'),
            "timestamp" => array("filter" => "required", "message" => 'ERR_PARAMS_VERIFY_FAIL'),
            "sign" => array("filter" => "required", "message" => 'ERR_PARAMS_VERIFY_FAIL'),
            "device" => array("filter" => "string"),
            "client_bind_sign" => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke (){
        $data = $this->form->data;

        $params   = array(
            'client_id'      => $data['client_id'],
            'client_token'   => $data['client_token'],
            'timestamp'      => $data['timestamp'],
            'sign'           => $data['sign'],
            'device'         => $data['device'],
            //'back_url'       => $_REQUEST['back_url'],
        );

        $bindSign = empty($data['client_bind_sign']) ? array() : unserialize(stripslashes($data['client_bind_sign']));
        $options = array("bind_sign" => $bindSign);

        Logger::info("API_USERBIND_CHECK:查询绑定授权, 输入:" . json_encode($params));
        $bindInfo = $this->rpc->local("UserBindService\checkUserBind", array($params, array(), $options));

        if ($bindInfo['code']) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $bindInfo['msg']);
            return false;
        }

        /*  返回值结构
        绑定过
        $bindInfo['data'] = array(
            'isUserBind'    => true,
            'openBindData'  => array(
                appInfo => 根据client_id获得的app信息
                thirdUserInfo => 根据token从第三方获得的
                bindUserInfo => 绑定关系列表    二维数组，key是openId,userId  key中是否存在第三方信息中的openID是绑定的判断标准
                userParam => open请求的参数 即：array($params, array(), $options)
                url => 如果app配置了notifyUrl有此信息
            ),
            'p2pUserId'     => 与第三方绑定的firstp2p用户的userId//来自绑定关系的userId,
            'dataIsNormal'  => true|false //app信息中配置了AnBindNotCheckCookie 或者30内授权过,
            'checkMobile'   => 与第三方绑定的firstp2p用户的mobile,
            'hasLoginUser'  => false,
            'cookBindSign'  => string, 第三方用户的openId生成的sign,将存到客户端cookie中,
        )
        没绑定过
        $bindInfo['data'] = array(
            'isUserBind'    => false,
            'openBindData'  => 同上
            'checkMobile'   => 第三方用户的mobile，可能为空,
            'cookBindSign'  => 同上,
            'isp2pUser'     => true|false 根据第三方mobile和idno是否匹配到firstp2p用户,
            'hasLoginUser'  => false,
            //isp2pUser 为true时有下面key
            'checkMobile'   => 身份证匹配到用户，对应用户的mobil。手机号匹配到用户，对应用户的mobil
            'p2pUserId'     => 身份证匹配到用户id > 手机号匹配到用户id //来自firstp2p_user表
            'isIdentify'    => true|false  //身份证匹配到用户 true > 手机号匹配到用户 !empty(user['idno'])
        )
         */
        $bindData = $bindInfo['data'];
        $isp2pUser = isset($bindData['p2pUserId']) && $bindData['p2pUserId'] > 0;
        if($bindData['isUserBind'] && $bindData['dataIsNormal']){
            $userInfo = $this->rpc->local("UserService\getUserByUserId", array($bindData['p2pUserId']));
            if(empty($userInfo)){
                $this->setErr('ERR_PARAMS_VERIFY_FAIL', '绑定用户不存在');
                return false;
            }
            $bindSign[] = $bindData['cookBindSign'];
            $bindSign = array_splice(array_unique($bindSign), -4);

            $token = $this->rpc->local("UserTokenService\genAppToken", array($bindData['p2pUserId']));
            $retUserInfo = array_merge(array("token"=>$token),$this->getRetUserInfo($userInfo));

            $this->json_data = array(
                "userInfo"  => $retUserInfo,
                "status"    => 1,
                "bindSign"  => serialize($bindSign),
                "isp2pUser" => $isp2pUser,
            );
        }else{
            $this->json_data = array(
                "bindData"  => $bindData,
                "status"    => 2,
                "isp2pUser" => $isp2pUser,
            );
        }

        return true;
    }

}
