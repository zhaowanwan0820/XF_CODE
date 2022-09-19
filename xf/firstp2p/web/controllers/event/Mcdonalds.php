<?php
/**
 * @file Mcdonalds.php
 * @synopsis 麦当劳活动注册
 *
 * @author wangshijie
 *
 * @version v1.0
 * @date 2015-08-19
 */

namespace web\controllers\event;

use web\controllers\BaseAction;
use libs\web\Form;
use libs\weixin\Weixin;

class Mcdonalds extends BaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            "cn" => array('filter' => 'string'),
            "from_platform" => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke()
    {
        $result = array();
        $site_id = $this->form->data['site_id'] ? $this->form->data['site_id'] : 1;

        $this->tpl->assign('cn', $this->form->data['cn']);
        $this->tpl->assign('from_platform', $this->form->data['from_platform']);

        $this->tpl->assign('event_id', 'mcdonalds');
        $this->tpl->assign('event_cn_editable', 1);

        // 获取用户的ua，生成对应客户端下载链接
        $uaInfo = $this->getUserAgent();
        // 微信相关
        $appid = app_conf('WEIXIN_APPID');
        $secret = app_conf('WEIXIN_SECRET');
        if ($uaInfo['from'] == "weixin" && $appid  && $secret) {
            $options = array(
                'appid' => $appid,
                'appsecret' => $secret,
            );
            $this->getJsApiSignature($options);
        }
        //END 微信相关处理结束

        // 分享相关文字图片配置
        $bonusSiteLogo = get_config_db('BONUS_SITE_LOGO', $site_id);
        $linkUrl = 'http://'.APP_HOST.'/event/mcdonalds?cn='.$this->form->data['cn'].'&site_id='.$site_id;
        //活动
        $shareContent = get_config_db('COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_MCDONALS', $site_id);
        $shareContent = str_replace('{$COUPON}', $this->cn, $shareContent);
        $title = get_config_db('MCDONALS_BONUS_SHARE_TITLE', $site_id);
        $img = get_config_db('MCDONALS_BONUS_SHARE_FACE', $site_id);

        $this->tpl->assign('bonusSiteLogo', $bonusSiteLogo);
        $this->tpl->assign('share_img', $img);
        $this->tpl->assign('share_title', $title);
        $this->tpl->assign('share_linkUrl', $linkUrl);
        $this->tpl->assign('share_desc', $shareContent);
        $this->tpl->assign('isweixinplatform', $uaInfo['from'] == "weixin");
    }

    public function getJsApiSignature($options)
    {
        $weObj = new Weixin($options);
        $url = 'http://'.APP_HOST.$_SERVER['REQUEST_URI'];
        $nonceStr = md5(time());
        $timeStamp = time();
        $signature = $weObj->getJsSign($url, $timeStamp, $nonceStr);

        $this->tpl->assign('appid', $options['appid']);
        $this->tpl->assign('timeStamp', $timeStamp);
        $this->tpl->assign('nonceStr', $nonceStr);
        $this->tpl->assign('signature', $signature);
    }

    public function getUserAgent()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $from = "";
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)
            || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($userAgent, 0, 4))) {
            $from = 'mobile';
        } else {
            $from = 'web';
        }

        if (strpos($userAgent, 'MicroMessenger') !== false) {
            $from = "weixin";
        }

        $os = "";
        if (preg_match('/iPhone|iPad/', $userAgent)) {
            $os = "ios";
        }

        if (preg_match('/Android|Linux/', $userAgent)) {
            $os = "android";
        }

        return array("from" => $from, 'os' => $os);
    }
}
