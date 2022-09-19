<?php
/**
 * 获取分站配置信息
 *
 */
namespace api\controllers\open;

use libs\web\Url;
use libs\web\Open;
use libs\web\Form;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class SiteConf extends AppBaseAction
{
    const DEFAULT_APP_LOGO = "//event.firstp2p.com/upload/image/20171017/18-18-_20171017181738.png";

    public function init()
    {
        //for test
        //$_REQUEST['domain'] = 'mzhaoyahuiopen.firstp2plocal.com';

        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'domain'  => array('filter' => 'string', 'option' => array('optional' => false)),
            'preview' => array('filter' => 'int',    'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int',    'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        //找不到域名
        $domain = $this->form->data['domain'];
        if (!$siteId = Open::getSiteIdByDomain($domain)) {
            $returnUrl = $this->rpc->local('OpenService\getNewFzUrl', array($domain));
            if(!empty($returnUrl)){
                $this->json_data = array('redirectUrl' => $returnUrl);
                return true;

            }else{
                $this->setErr('ERR_THIRD_DOMAIN_NOT', 'domain not found!');
                return false;
            }
        }
        //找不到app信息
        if (!$appInfo = Open::getAppBySiteId($siteId)) {
            $this->setErr('ERR_THIRD_INFO_NOT', 'app info not found!');
            return false;
        }

        //检查app状态
        if(!(2 & intval($appInfo['onlineStatus']))) { // 2 表示 wap 端在线
            $this->setErr('ERR_THIRD_STATUS_DOWN', 'error app status!');
            return false;
        }

        $isPreview = $this->form->data['preview'];
        if ($isPreview){
            $siteId = intval($this->form->data['site_id']);
        }

        $GLOBALS['sys_config']['APP_SITE'] = $appInfo['appShortName'];
        $appAdvs = (array) Open::getSiteAdvBySiteId($siteId);

        if (empty($isPreview)) {
            $appConf = Open::getSiteConfBySiteId($siteId);
        } else {
            $appConf = Open::getPreviewData($siteId, 4);
        }

        if (false === $appConf) {
            $this->setErr('ERR_THIRD_CONF_NOT', 'get app conf fail!');
            return false;
        }

        //$response['appInfo'] = $appInfo;
        $response['id'] = $appInfo['id'];
        $response['appName'] = $appInfo['appName'];
        $response['appShortName'] = $appInfo['appShortName'];
        $response['setParams'] = (array)json_decode($appInfo['setParams'], true);
        $response['appLogo'] = empty($appInfo['appLogo']) ? self::DEFAULT_APP_LOGO : $appInfo['appLogo'];
        $response['appDesc'] = $appInfo['appDesc'];
        $response['usedWebDomain'] = $appInfo['usedWebDomain'];
        $response['usedWapDomain'] = $appInfo['usedWapDomain'];
        $response['siteName'] = $appInfo['appName'];
        $response['onlineStatus'] = $appInfo['onlineStatus'];
        $response['inviteCode'] = $appInfo['inviteCode'];
        $response['confInfo'] = Open::getWapTplData($appConf['confInfo'], array('advs' => $appAdvs));



        $this->json_data = $response;
        return true;
    }

}
