<?php
/**
 * Info.php
 *
 * @date 2016年01月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\medal;

use libs\weixin\Weixin;
use core\service\MedalService;
use web\controllers\BaseAction;
use libs\web\Form;

class Info extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'sn' => array('filter' => 'string'),
            'site_id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return $this->show_error('无效的链接');
        }
    }

    public function invoke() {
        $siteId = intval($this->form->data['site_id']);
        $siteId = $siteId ? $siteId : 1;
        $downloadUrl = get_config_db('APP_DOWNLOAD_H5_URL', $siteId);
        $this->getJsApiSignature();
        $sn = rawurldecode(urlencode(trim($this->form->data['sn'])));
        $medalId = \libs\utils\Aes::decode($sn, MedalService::MEDAL_ID_AES_KEY);
        if (!$medalId) {
            return $this->show_error('无效的链接');
        }
        // 获取medal信息
        $medalService = new MedalService();
        $medalInfo = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('MedalService\getMedal', array($medalId)), 3600);
        if (empty($medalInfo)) {
            return $this->show_error('咦，勋章找不到了(⊙﹏⊙)b');
        }
        $shareConf = $medalService->getShareConf($medalId, $medalInfo['name']);
        $this->tpl->assign('medal', $medalInfo);
        $this->tpl->assign('shareConf', $shareConf);
        $this->tpl->assign('downloadUrl', $downloadUrl);
        $this->template = "web/views/medal/info.html";
    }

    public function getJsApiSignature() {
        $appid = app_conf('WEIXIN_APPID');
        $secret = app_conf('WEIXIN_SECRET');
        $options = array(
            'appid' => $appid,
            'appsecret' => $secret,
        );
        $weObj = new Weixin($options);
        $url = 'http://' .APP_HOST . $_SERVER['REQUEST_URI'];
        $nonceStr = md5(time());
        $timeStamp = time();
        $signature = $weObj->getJsSign($url, $timeStamp, $nonceStr);

        $this->tpl->assign('appid', $appid);
        $this->tpl->assign('timeStamp', $timeStamp);
        $this->tpl->assign('nonceStr', $nonceStr);
        $this->tpl->assign('signature', $signature);
    }

    //public function _after_invoke()
    //{
    //    if ($this->cache) {
    //        ob_start();
    //    }
    //    $this->tpl->display($this->template);
    //    if ($this->cache) {
    //        $content = ob_get_contents();
    //        $fp = fopen(APP_RUNTIME_PATH . "medal_show_{$this->medalId}.htm", "w");
    //        fwrite($fp, $content);
    //        fclose($fp);
    //    }
    //}

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
                $jump = isset($_SERVER['HTTP_REFERER']) ? isset($_SERVER['HTTP_REFERER']) : '';
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
}
