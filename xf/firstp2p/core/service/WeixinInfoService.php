<?php
/**
 *  WeixinInfoService
 * @author luzhengshuai<luzhengshuai@ucfgroup.com>
 **/

namespace core\service;

use core\dao\WeixinInfoModel;

/**
 * WeixinInfo service
 *
 * @packaged default
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 **/
class WeixinInfoService extends BaseService
{
    /**
     * saveWeixinInfo
     *
     * @param string $openid
     * @param array $tokenInfo
     * @param array $userInfo
     * @param string $userId
     * @access public
     * @return void
     */
    public function saveWeixinInfo($openid, $tokenInfo = array(), $userInfo = array(), $userId = '') {

        if (!$openid) {
            return false;
        }
        $data['openid'] = $openid;

        if ($userId) {
            $data['user_id'] = $userId;
        }

        if ($userInfo) {
            $data['user_info'] = json_encode($userInfo, JSON_UNESCAPED_UNICODE);
        }

        if ($tokenInfo) {
            $data['token_info'] = json_encode($tokenInfo, JSON_UNESCAPED_UNICODE);
        }

        $weixinInfoModel = new WeixinInfoModel();
        if ($weixinInfoModel->getWeixinInfoByOpenid($openid)) {
            return $weixinInfoModel->updateByOpenid($openid, $data);
        } else {
            return $weixinInfoModel->saveWeixinInfo($data);
        }

    }

    /**
     * getWeixinInfo
     *
     * @param mixed $openid
     * @access public
     * @return weixinInfoModel
     */
    public function getWeixinInfo($openid, $refreshCache = false) {
        if (!$openid) {
            return false;
        }

        $result = \SiteApp::init()->dataCache->call(WeixinInfoModel::instance(), 'getWeixinInfoByOpenid', array($openid), 86400, $refreshCache);

        if ($result['openid']) {
            $result['user_info'] = json_decode($result['user_info'], true);
            $result['token_info'] = json_decode($result['token_info'], true);
            return $result;
        }
        return false;
    }

    /**
     * getWxInfoListForBonus
     *
     * @param array $openids
     * @param string $fields
     * @access public
     * @return array
     */
    public function getWxInfoListForBonus($openids, $fields = 'openid, user_info') {
        if(empty($openids)) {
            return false;
        }

        $map = array_flip($openids);
        $result = array();

        foreach ($openids as $openid) {
            $wxInfo = $this->getWeixinInfo($openid);
            if (!empty($wxInfo)) {
                $result[$map[$wxInfo['openid']]] = $wxInfo;
            }
        }
        return $result;
    }
}
// END class WeixinInfoService
