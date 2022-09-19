<?php
/**
 * 获取投资券页面
 * @author 王振<wangzhen3@ucfgroup.com>
 **/

namespace web\controllers\marketing;

use core\service\WeiXinService;
use core\service\BonusBindService;
use libs\web\Form;
use core\service\DiscountShareService;
use libs\web\Url;



class DiscountShareInfo extends DiscountShareBase {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "code" => array("filter" => "string", "option" => array("optional" => true)),
        );
        $this->form->validate();
        parent::init();
    }

    public function invoke() {
        if(!$this->autoCheck()){
            $this->error();return false;
        }
        // 已授权处理
        $this->weiXinAuthorized();
        $this->weiXinShare();
        $this->getDisCountList();
        $shareLink = $this->weixin_host.'/marketing/DiscountShareInfo?id='.$this->ec_id.'&cn='.$this->cn.'&from_platform=' . $this->fromPlatform;;
        $this->tpl->assign('shareLink', $shareLink);
        $this->tpl->assign('code',empty($this->form->data['code']) ? '' : $this->form->data['code']);
        $this->template = "web/views/v3/marketing/discount_share_info.html";
    }

    /**
     * 微信浏览器下授权绑定
     */
    protected function weiXinAuthorized(){
        $weixin_service = new WeiXinService();
        $is_weixin = $weixin_service->isWinXin();
        $wx_cache = $weixin_service->getCookie($weixin_service::TYPE_MARKETING);
        // 直接提交到领取逻辑
        if ($is_weixin === false || (empty($wx_cache) && !empty($this->form->data['code']))){
            return false;
        }

        // 是否授权
        $callback = $this->weixin_host.'/marketing/DiscountShareInfo?id='.$this->ec_id.'&cn='.$this->cn.'&from_platform=' . $this->fromPlatform;

        if (empty($wx_cache)){
            $weixin_service->grantAuthorization($callback);
            exit;
        }
        $openid = $wx_cache['openid'];
        $weixin_bind_service = new BonusBindService();
        $bindInfo = $weixin_bind_service->getBindInfoByOpenid($openid,'mobile');
        // 之前数据已经存在，但是数据又被删除的情况
        if (empty($bindInfo['mobile']) && empty($this->form->data['code'])){
            $weixin_service->grantAuthorization($callback);
            exit;
        }
        if (!empty($bindInfo['mobile'])){
            // 处理领券逻辑
            $t = self::encode($bindInfo['mobile'].'_'.$this->sharinfo_private_key.'_'.time());
            $post = array(
                't' => $t,
                'id' => $this->ec_id,
                'cn' => $this->cn,
                'from_platform' => $this->fromPlatform,
                'from' => 'weixin'
            );
            $web_url = new Url();
            $url = $web_url::getConfHttpProtocol().APP_HOST.'/marketing/DiscountShareGet';
            $discount_share_service = new DiscountShareService();

            $ret = $discount_share_service->Curl($url, $post);
            $jump_url_error =  $web_url::getConfHttpProtocol().APP_HOST."/marketing/DiscountShareError?id=".$this->ec_id;
            $jump_url_host = $web_url::getConfHttpProtocol().APP_HOST.'/marketing/';
            if (empty($ret)){
                // 跳转到错误页
               header("Location:".$jump_url_error);
               exit;
            }
            $data = json_decode($ret, true);
            $data_error = json_last_error();
            if ($data_error != JSON_ERROR_NONE){
                header("Location:".$jump_url_error);
                exit;
            }
            if ($data['code'] ==0){
                // curl 结果后种植cookie，防止领取结果页获取不到cookie循环跳转
                setcookie('ma',md5($bindInfo['mobile']));
                header("Location:".$jump_url_host.$data['jumpUrl']);
                exit;
            }else{
                if (!empty($data['jumpUrl'])){
                    header("Location:".$jump_url_host.$data['jumpUrl']);
                    exit;
                }else{
                    // 有错误提示
                    $this->tpl->assign('errorMsg',$data['msg']);
                }
            }

        }
    }

}
