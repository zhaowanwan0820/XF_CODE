<?php
/**
 * CashBase.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use core\service\BonusService;
use core\service\UserService;
use core\service\BonusBindService;
use core\service\WeixinInfoService;
use core\service\CouponService;
use libs\weixin\Weixin;
use libs\utils\PaymentApi;
use core\dao\BonusConfModel;
use core\dao\BonusModel;

class CashBase extends BaseAction {

    //Tips 这是微信号未上之前的，手机存的cookie
    //USER_MOBILE_KEY = md5('firstp2p_hongbao_mobile'); 存储手机信息的key
    const USER_MOBILE_KEY = "a98d12a9b8bc3ab0b099bb463b06c712";

    //USER_WEIXIN_INFO = md5('firstp2p_weixin_info'); 存储用户微信信息的key
    const USER_WEIXIN_INFO = 'a4b3b934bb3d9c72bdfd68b8e2b1ac9c';

    const MOBILE_SESSION_KEY = 'H5VerifyPhone';

    const HONGBAO_AES_KEY = "aGpocyYqNzMqKEAqI0BRKQ==";

    // 当前请求的action
    public $action = '';

    // 是否绑定了手机，当前绑定的
    public $mobile = '';

    // 当前领取过得手机号
    public $mobiles = array();

    // 取得cookie中的值，用来判断当前福利被用户领取的信息
    public $bonusBindInfo = array();

    // 是否读取缓存页面
    public $cache = false;
    public $viewCache = false;

    // 当前用户的信息
    public $wxInfo = array();
    // 从cookie中取出来的用户的信息(微信的信息)
    public $wxCache = array();

    // 当前用户领的福利的具体信息
    public $bonusDetail = array();

    public $ajax = false;

    public $bonusGroupInfo = array();

    public $cn = '';

    public $referUid = '';

    public $currentUserInfo = '';

    public $listView = '';

    public $formRules = array(
        "cn" => array('filter' => 'required'),
        "site_id" => array("filter" => "int", "option" => array("optional" => true)),
        "bg" => array("filter" => "string"),
        "replace" => array("filter" => "int", "option" => array("optional" => true)),
    );

    public function init() {
        $this->action = $this->getCurrentUrl();
        $this->form = new Form("get");
        $this->form->rules = $this->formRules;
        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), '', 0, 1);
            return false;
        }

        $this->cn = $this->form->data['cn'];
        $site_id = $this->form->data['site_id'];
        $site_id = $site_id ? $site_id : 1;
        $replace = $this->form->data['replace'];
        $this->replace = $replace == 1 ? 1 : 0;
        $this->tpl->assign('cn', $this->cn);
        $this->tpl->assign('site_id', $site_id);
        $bg = $this->form->data['bg'];
        $bg = !empty($bg)?$bg:'';
        $this->tpl->assign("bg", $bg);

        // 邀请码检测
        $bonusService = new BonusService();
        $coupon = $this->rpc->local('CouponService\checkCoupon', array($this->cn));
        if ($coupon !== FALSE) {
            $referUid = $coupon['refer_user_id'];
        }
        if ($referUid) {
            if ($bonusService->isCashBonusSender($referUid, $site_id, $this->cn)) {
                $this->referUid = $referUid;
            }
        }

        if (!$this->referUid) {
            $this->tpl->assign('cn', $this->cn);
            $this->template = 'web/views/hongbao/cash_coupon/error.html';
            return false;
            //return $this->show_error('邀请链接有误', '', 0, 1);
        }

        // 初始化service
        $bonusService = new BonusService();

        // 获取用户的ua，生成对应客户端下载链接
        $uaInfo = $this->getUserAgent();
        // 根据各分站配置读取对应的h5下载链接
        if ($site_id != 1 && get_config_db('APP_DOWNLOAD_H5_URL', $site_id)) {
            $downloadUrl = get_config_db('APP_DOWNLOAD_H5_URL', $site_id);
            $downloadDesc = '下载客户端';
        } else {
            $downloadUrl = 'http://m.firstp2p.com/?from_platform=hongbao_touzi&cn=' . $this->cn;
            $downloadDesc = '立即使用';
        }
        $this->tpl->assign('downloadUrl', $downloadUrl);
        $this->tpl->assign('downloadDesc', $downloadDesc);
        $this->tpl->assign('uaInfo', $uaInfo);

        // 获取绑定的手机号
        if ($this->mobile = \es_session::get(self::MOBILE_SESSION_KEY)) {
            $this->setCookie(self::USER_MOBILE_KEY, $this->mobile);
        }

        $this->mobile = $this->mobile ? $this->mobile : $this->getCookie(self::USER_MOBILE_KEY);
        // 微信相关
        $appid = app_conf('WEIXIN_APPID');
        $secret = app_conf('WEIXIN_SECRET');
        if ($uaInfo['from'] == "weixin" && $appid  && $secret) {
            $options = array(
                'appid' => $appid,
                'appsecret' => $secret,
            );
            $this->getJsApiSignature($options);
            // 微信尝试读取用户已经绑定的手机号
            if (!$this->mobile) {
                //$bonusBindService = new BonusBindService();
                //$wxCache = $this->getCookie(self::USER_WEIXIN_INFO);
                //PaymentApi::log("CashLog" .__LINE__. json_encode($this->form->data).json_encode($wxCache));
                //$openid = isset($wxCache['openid']) ? $wxCache['openid'] : '';
                //$this->mobile = $bonusBindService->getBindInfoByOpenid($openid);
            }
        }
        //END 微信相关处理结束

        // 分享相关文字图片配置
        $bonusSiteLogo = get_config_db('BONUS_SITE_LOGO', $site_id);
        $linkUrl = 'http://' .APP_HOST. '/hongbao/CashGet?cn=' .$this->cn. '&site_id=' . $site_id;
        //活动
        $shareContent = get_config_db('COUPON_APP_ACCOUNT_COUPON_PAGE_SHAREMSG_CASH_BONUS', $site_id);
        $shareContent = str_replace('{$COUPON}', $this->cn, $shareContent);
        $title = get_config_db('CASH_BONUS_SHARE_TITLE', $site_id);
        $img = get_config_db('CASH_BONUS_SHARE_FACE', $site_id);

        $this->tpl->assign('bonusSiteLogo', $bonusSiteLogo);
        $this->tpl->assign('img', $img);
        $this->tpl->assign('title', $title);
        $this->tpl->assign('linkUrl', $linkUrl);
        $this->tpl->assign('desc', $shareContent);

        if (!$this->mobile && preg_match('/CashSendInvite|CashRegister|CashGet/', $this->action)) {
            $bgParam = '';
            if(!empty($bg)){
                $bgParam = '&bg='.$bg;
            }
            header('Location:http://' . APP_HOST . '/hongbao/CashBind?cn=' .$this->cn. '&site_id=' . $site_id . $bgParam);
            return false;
        }

        if (!$this->mobile && stripos($this->action, 'hongbao/CashBind') !== false) {
            return true;
        }

        $this->tpl->assign('mobile', $this->mobile);

        //TODO 读取手机号相关用户信息，注册则跳转发送页，否则是红包领取页
        $userService = new UserService();
        $this->currentUserInfo = $userService->getByMobile($this->mobile);

        if (isset($this->currentUserInfo['id']) && !preg_match('/CashSendInvite|CashRegisterSuccess|CashInviteList/', $this->action)) {
            if(!empty($bg) && $bg=='xiecheng'){
                header('Location:http://m.firstp2p.com/account/login');
            }else{
                header('Location:http://' . APP_HOST . '/hongbao/CashSendInvite?cn=' .$this->cn. '&site_id=' . $site_id);
            }
            return false;
        }

        if ($this->mobile && stripos($this->action, 'hongbao/CashBind') !== false) {
            header('Location:http://' . APP_HOST . '/hongbao/CashGet?cn=' .$this->cn. '&site_id=' . $site_id);
            return false;
        }

        return true;
    }

    public function getJsApiSignature($options) {
        $weObj = new Weixin($options);
        $url = 'http://' .APP_HOST . $_SERVER['REQUEST_URI'];
        $nonceStr = md5(time());
        $timeStamp = time();
        $signature = $weObj->getJsSign($url, $timeStamp, $nonceStr);

        $this->tpl->assign('appid', $options['appid']);
        $this->tpl->assign('timeStamp', $timeStamp);
        $this->tpl->assign('nonceStr', $nonceStr);
        $this->tpl->assign('signature', $signature);
    }

    public function getCookie($name) {

        if (!isset($_COOKIE[$name])) {
            return;
        }
        $result = Aes::decode($_COOKIE[$name], base64_decode(self::HONGBAO_AES_KEY));
        if (!$result) {
            return $result;
        }
        return json_decode($result, true);
    }

    public function setCookie($name, $value) {
        if (!$value) {
            setcookie($name, '');
            return true;
        }
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        $value = Aes::encode($value, base64_decode(self::HONGBAO_AES_KEY));
        setcookie($name, $value, time() +3600 * 24 * 30, '', '', '', true);
        return true;
    }

    public function getUserAgent() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $from = "";
        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$userAgent)
            ||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($userAgent,0,4))) {
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

    public function getCurrentUrl()
    {
        $uri_path = explode('?', $_SERVER['REQUEST_URI']);
        // 去掉部分浏览器 REQUEST_URI 有 host和端口的问题
        $url = preg_replace('#http:\/\/.*?firstp2p.com\:*\d*#i','',$uri_path[0]);
        if ($url == "/index.php") {
            $url = $this->_parseOld($url, $uri_path[1]);
        }
        return $url;
    }

    public function makeListToView($list) {

        $resultList = array();
        foreach ($list as $key => $item) {
            $item['money'] = intval($item['money']);
            $item['mobile_view'] = substr_replace($item['mobile'], '****', 3, 4);
            $item['refer_mobile_view'] = substr_replace($item['refer_mobile'], '****', 3, 4);
            $item['created_at'] = date("Y-m-d H:i:s", $item['created_at']);
            $resultList[] = $item;
        }

        return $resultList;
    }

    public function _after_invoke()
    {
        if ($this->cache) {
            ob_start();
        }
        if(!empty($this->template)){
            $this->tpl->display($this->template);
        }
        if ($this->cache) {
            $content = ob_get_contents();
            $fp = fopen(APP_WEBROOT_PATH . "hongbao_html/{$this->sn}.htm", "w");
            fwrite($fp, $content);
            fclose($fp);
        }
    }

    /**
     * 显示错误
     *
     * @param $msg 消息内容
     * @param int $ajax
     * @param string $jump 调整链接
     * @param int $stay 是否停留不跳转
     * @param int $time 跳转等待时间
     */
    public function show_error($msg, $title = '', $ajax = 0, $stay = 0, $jump = '', $refresh_time = 3)
    {
        if($ajax == 1)
        {
            $result['status'] = 0;
            $result['info'] = $msg;
            $result['jump'] = $jump;
            header("Content-type: application/json; charset=utf-8");
            echo(json_encode($result));
        }
        else
        {
            $title = empty($title) ? '服务器穿越中' : $title;
            $this->tpl->assign('page_title',$title);
            $this->tpl->assign('error_title',$msg);

            if($jump==''){
                $jump = $_SERVER['HTTP_REFERER'];
            }
            if(!$jump&&$jump==''){
                $jump = APP_ROOT."/";
            }

            $this->tpl->assign('jump',$jump);
            $this->tpl->assign("stay",$stay);
            $this->tpl->assign("host", APP_HOST);
            $this->tpl->assign("refresh_time",$refresh_time);
            $this->tpl->display("web/views/error_h5.html");
            $this->template = null;

        }
        setLog(
                array('output' => array('ajax' => $ajax, 'jump' => $jump, 'msg'=> $msg ))
        );
        return false;
    }

    public function removeEmoji($text) {

        return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
    }
}
