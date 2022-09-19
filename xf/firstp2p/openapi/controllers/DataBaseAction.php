<?php
namespace openapi\controllers;

use openapi\controllers\BaseAction;
use openapi\lib\Tools;
use libs\web\Form;

/**
 * DataBaseAction
 * 数据接口，用于第三方数据管理
 *
 * @author longbo 
 */
class DataBaseAction extends BaseAction
{
    protected $siteId = null;
    protected $isInnerAction = false;

    protected function _before_invoke() {
        parent::_before_invoke();
        if ($this->checkIsInnerRequest()) {
            $this->isInnerAction = true;
            return true;
        } else {
            $clientInfo = parent::getClientIdByAccessToken();
            if (empty($clientInfo) || $clientInfo['client_id'] != $this->clientConf['client_id']) {
                throw new \Exception('ERR_OAUTH_ERROR');
            }
            if (isset($this->clientConf['site_id'])) {
                $this->siteId = $this->clientConf['site_id'];
            }

            if (empty($this->siteId)) {
                throw new \Exception('ERR_SITE_ID_ERROR');
            }
            return true;
        }
    }
    //第三方数据ID加密防止遍历
    public function encodeId($id) {
        return Tools::encryptID($id, md5(strval($this->siteId), true));
    }
    public function decodeId($enId) {
        return Tools::decryptID($enId, md5(strval($this->siteId), true));
    }

}
