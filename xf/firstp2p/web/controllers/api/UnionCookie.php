<?php

/**
 * 跨域种cookie
 * @author liuzhenpeng
 **/

namespace web\controllers\api;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use libs\utils\Logger;

class UnionCookie extends BaseAction
{
    private $_UnionCookieSecuret = 'union@~$FIRSTP2P';

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'sid'=>array("filter"=>'string'),
            'mobile'=>array("filter"=>'string'),
            'dealid'=>array("filter"=>'string'),
            'sign'=>array("filter"=>'string'),
            'sendtime'=>array("filter"=>'string'),
        );

        $this->form->validate();
    }

    public function invoke()
    {
        $params = $this->form->data;
        $sid    = Aes::decode($params['sid'], $this->_UnionCookieSecuret);
        $mobile = Aes::decode($params['mobile'], $this->_UnionCookieSecuret);
        $dealid = Aes::decode($params['dealid'], $this->_UnionCookieSecuret);
        $sendtime = (int) Aes::decode($params['sendtime'], $this->_UnionCookieSecuret);
        if((time() - $sendtime)>600){
            \libs\utils\Logger::debug("union_cookie:已过期,params:" . json_encode(array($sid, $mobile, $dealid, $params['sign'], $sendtime)));exit;
        }

        $acceptStr = ($this->_UnionCookieSecuret . $sid . $mobile . $dealid . $sendtime . $this->_UnionCookieSecuret);
        $acceptSign = md5($acceptStr);
        if($params['sign'] != $acceptSign){
            \libs\utils\Logger::debug("union_cookie:签名校验不正确,params:" . json_encode(array($sid, $mobile, $dealid, $params['sign'], $acceptSign, $acceptStr)));exit;
        }

        $domain = '.'.implode('.', array_slice((explode('.', get_host())), -2));
        header("P3P: CP=CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR");
        setcookie("OPENUNIONSID", $sid, 0, '/', $domain);
        \libs\utils\Logger::debug("union_cookie:done,domain:" . $domain . ",params:" . json_encode(array($sid, $mobile, $dealid, $params['sign'], $acceptSign)));exit;
    }

}






