<?php
/**
 * 用于微信分享
 */

namespace api\controllers\weixin;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Curl;

class GetWxParams extends AppBaseAction
{

    const WEIXIN_ACCESS_TOKEN = "weixin_access_token_wxb233130ddc1eb7dc";
    const WEIXIN_TICKET = "weixin_api_jsticket_wxb233130ddc1eb7dc";
    const WEIXIN_APPID = "wxb233130ddc1eb7dc";
    const WEIXIN_SECRET = "eb67aa822d7fb378f8bc934ac30a602b";
    const WEIXIN_TOKEN_URL = "https://api.weixin.qq.com/cgi-bin/token?from=wap&grant_type=client_credential&appid=%s&secret=%s";
    const WEIXIN_TICKET_URL = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=jsapi";

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "url" => array("filter"=>"url", "message"=>"url不为空"),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {

        $url = $this->form->data['url'];
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $ticket = $redis->get(self::WEIXIN_TICKET);
        if(empty($ticket)) {
            $ticket = $this->getTicket();
            if(empty($ticket)) {
                $this->setErr('ERR_PARAMS_ERROR');
            }
            $redis->set(self::WEIXIN_TICKET, $ticket);
        }
        $timestamp = time();
        $noncestr = md5($timestamp);
        $signature = sha1("jsapi_ticket=" . $ticket . "&noncestr=" . $noncestr . "&timestamp=" . $timestamp . "&url=" . $url);
        $this->json_data = array(
            'appid' => self::WEIXIN_APPID,
            'noncestr' => $noncestr,
            'timestamp' => $timestamp,
            'signature' => $signature,
        );
    }

    private function getToken() {
        $url = sprintf(self::WEIXIN_TOKEN_URL, self::WEIXIN_APPID, self::WEIXIN_SECRET);
        $result = Curl::get($url);
        $result = json_decode($result, true);
        return $result['access_token'];
    }

    private function getTicket() {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $token = $redis->get(self::WEIXIN_ACCESS_TOKEN);
        if(empty($token)) {
            $token = $this->getToken();
            if(empty($token)) {
                return null;
            }
            $redis->set(self::WEIXIN_ACCESS_TOKEN, $token);
        }
        $url = sprintf(self::WEIXIN_TICKET_URL, $token);
        $result = Curl::get($url);
        $result = json_decode($result, true);
        return $result['ticket'];
    }

}
