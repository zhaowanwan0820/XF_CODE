<?php
/**
 * @abstract openapi 获取分站配置信息
 * @date 2016年 03月 22日 星期二 11:47:18 CST
 *
 * @author Wang Shi Jie<wangshijie@ucfgroup.com>
 */

namespace openapi\controllers\open;

use libs\web\Url;
use libs\web\Open;
use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class SiteConf extends BaseAction
{
    public function init()
    {
        /* test case
        $_REQUEST['domain'] = 'wangge.mulandailocal.cn';
        $_REQUEST['preview'] = 0;
        */
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'domain'  => array('filter' => 'string', 'option' => array('optional' => false)),
            'preview' => array('filter' => 'int',    'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int',    'option' => array('optional' => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
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
            $this->errorCode = -1;
            $this->errorMsg  = 'domain not found!';
            return false;
        }
        //找不到app信息
        if (!$appInfo = Open::getAppBySiteId($siteId)) {
            $this->errorCode = -1;
            $this->errorMsg  = 'app info not found!';
            return false;
        }

        //检查app状态
        if(!(2 & intval($appInfo['onlineStatus']))) { // 2 表示 wap 端在线
            $this->errorCode = -1;
            $this->errorMsg  = 'error app status!';
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
            $this->errorCode = -1;
            $this->errorMsg  = 'get app conf fail!';
            return false;
        }

        $response['appInfo'] = $appInfo;
        $response['siteId'] = $appInfo['id'];
        $response['siteName'] = $appInfo['appName'];
        $response['confInfo'] = Open::getWapTplData($appConf['confInfo'], array('advs' => $appAdvs));

        $this->json_data = $response;
        return true;
    }

}
