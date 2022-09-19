<?php

namespace NCFGroup\Ptp\Apis;
use core\service\WeiXinService;
use core\service\UserService;
use NCFGroup\Common\Library\SignatureLib;

class WeixinApi {


    public function getList()
    {
        $this->request = getDI()->get('request');
        $wxId = $this->request->getQuery('wxId');
        $openId = $this->request->getQuery('openId');
        $userId = $this->request->getQuery('userId');
        $page = $this->request->getQuery('page');
        $size = $this->request->getQuery('size');
        $sign = $this->request->getQuery('sign');
        $timestamp = $this->request->getQuery('timestamp');
        $params = [
            'wxId' => $wxId,
            'openId' => $openId,
            'userId' => $userId,
            'page' => $page,
            'size' => $size,
            'sign' => $sign,
            'timestamp' => $timestamp,
        ];

        //验证签名
        if(!SignatureLib::verify($params, WeiXinService::BIND_SALT)){  //不通过
            //返回错误信息
            return $this->getDataJson(1, '参数不正确', null);
        }
        $data = (new WeiXinService)->getListBinded($wxId, $openId, $userId, $page, $size);
        return $this->getDataJson(0, 'ok', $data);
    }


    //检查
    public function getUserInfo(){

       $this->request = getDI()->get('request');
       $openid = $this->request->getQuery('openid');
       $sign = $this->request->getQuery('sign');
       $timestamp = $this->request->getQuery('timestamp');
       $params = array(
           'openid' => $openid,
           'sign'   => $sign,
           'timestamp' =>$timestamp
       );
       //验证签名
       if(!SignatureLib::verify($params, WeiXinService::BIND_SALT)){  //不通过
           //返回错误信息
           return $this->getDataJson(1, '参数不正确', null);
       }
        $wxSeivice = new WeiXinService();
        //检查是否绑定
        if(!$wxSeivice->isBindOpenid($openid)){  //没有绑定
            return $this->getDataJson(0,'ok',null);
        }
        //绑定，通过openid获取user_id
        $user = $wxSeivice->getByOpenid($openid,"user_id");
        //通过user_id获取userInfo
        $userService = new UserService();
        $userInfo = $userService->getUser($user['user_id']);

        $vipInfo = (new \core\service\vip\VipService())->getVipInfo(intval($userInfo['id']));
        $data = [
            "userId" => $userInfo['id'],
            "name" => $userInfo['real_name'],
            "level" => intval($vipInfo['service_grade']),
            "mobile" => substr_replace($userInfo['mobile'],'****', 3, 4),
        ];

        return $this->getDataJson(0, 'ok', $data);
    }

    public function unbind()
    {
        $this->request = getDI()->get('request');;
        $openId = $this->request->getPost('openId');
        $params = [
            'openId' => $openId,
            'timestamp' => $this->request->getPost('timestamp'),
            'sign'   => $this->request->getPost('sign'),
        ];
        //验证签名
        if(!SignatureLib::verify($params, WeiXinService::BIND_SALT)) {  //不通过
            //返回错误信息
            return $this->getDataJson(1, '参数不正确');
        }

        $wxSeivice = new WeiXinService;
        if (!$wxSeivice->isBindOpenid($openId)) {
            return $this->getDataJson(10001, 'unbind');
        }

        // 获取用户ID
        $info = $wxSeivice->getByOpenid($openId);
        $userId = $info['user_id'];

        if ($wxSeivice->delByOpenid($openId)) {
            WeiXinService::syncBind2CallCenter($userId, $openId, 'delete');
            return $this->getDataJson(0, 'ok');
        } else {
            return $this->getDataJson(20001, 'failed');
        }
    }

     protected function getDataJson($code, $msg = '', $data = '')
    {
        $json = array('code' => $code, 'msg' => $msg, 'data' => $data);
        return $json;
       // return json_encode($json, JSON_UNESCAPED_UNICODE);

    }


}
