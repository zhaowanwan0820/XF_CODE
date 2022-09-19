<?php

/**
 * @abstract 客户端获得闪屏接口
 * @author yutao
 * @date 2015-05-09
 */
namespace api\controllers\user;

use libs\web\Form;
use core\service\BwlistService;
use api\controllers\AppBaseAction;


class Splash extends AppBaseAction {

    public function urlTail($typeId){
            return $typeId == -1 ? "": 'firstp2p://api?redirect=general&login=true&authen=true&url='.urlencode('firstp2p://api?type=native&name=other&pageno='.$typeId);
    }

    public function init() {
        parent::init();
        $this->form = new Form('POST');
        $this->form->rules = array(
            'token' => array(
                'filter' => 'string',
                ),
            'screenwidth' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_ERROR'
                ),
            'screenheight' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_ERROR'
                ),
            'site_id' => array(
                'filter' => 'int',
                'option' => array(
                     'optional' => true
                )
            ),
            'num' => array(
                'filter' => 'int',
                'option' => array(
                'optional' => true
                )
            )
        );
        if (!$this->form->validate()) {
            $this->setErr( $this->form->getErrorMsg() );
            return false;
        }
    }
    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken(false);

        $data['site_id']=empty($data['site_id']) ? 1 : $data['site_id'];
        $splashList = $this->rpc->local( "SplashService\getSplashInfo", array(
            'os' => $_SERVER['HTTP_OS'],
            'width' => $data['screenwidth'],
            'height' => $data['screenheight'],
            'siteId' => $data['site_id'],
            'num' => isset($data['num']) ? $data['num'] : ''
            )
        );
        if (!$splashList) {
            $this->setErr( 'ERR_SPLASH_EMPTY' );
            return false;
        }

        if ( !empty($data['num']) ) {
            $splashInfo = array_values($splashList)[rand(0, count($splashList)-1)]; //APP411 取出的闪屏随机给1个
        } else {
            $splashInfo = array_values($splashList)[0];//兼容旧的接口调用，给id最大的闪屏
        }

        $schema = '';
        $h5url = '';
        $urlJson = json_decode($splashInfo['link'],true);
        $unloginType = $urlJson['unlogin']['type_id'];//未登录用户跳转链接类型和链接
        $unloginUrl = $urlJson['unlogin']['url'];
        $loginType = $urlJson['login']['type_id'];//登录用户跳转链接类型和链接以及白名单
        $loginUrl = $urlJson['login']['url'];
        $whiteList = $urlJson['login']['white_list'];
        if ( !empty($whiteList)){//设置了白名单
            if ( BwlistService::inList($whiteList, $loginUser['id']) ) {//在白名单内同时说明用户已登陆
                if ( $loginType == 0 )
                    $h5url = $loginUrl;
                else
                    $schema .= $this->urlTail($loginType);
            }
            else {//未登录用户和不在白名单的登陆用户
                if ( $unloginType == 0 )
                    $h5url = $unloginUrl;
                else
                    $schema .= $this->urlTail($unloginType);
            }
        }
        elseif ( !empty($loginUser['id']) ) {//未设置白名单但登陆
            if ($loginType == 0 )
                $h5url = $loginUrl;
            else
                $schema .= $this->urlTail($loginType);
        }
        else {//未设置白名单未登录
            if ($unloginType == 0 )
                $h5url = $unloginUrl;
            else
                $schema .=$this->urlTail($unloginType);
        }

        $res = array(
            "h5title" => $splashInfo['title'],
            "h5url" => $h5url,
            "schema" => $schema,
            "imageurl" => $splashInfo['imageurl'],
            "siteId" => $splashInfo['site_id']
        );
        $this->json_data = $res;
        return true;
    }
}
