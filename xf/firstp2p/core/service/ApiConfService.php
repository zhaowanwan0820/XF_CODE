<?php
/**
 * ApiConfService.php
*
* @date 2015-11-11
* @author zhaohui <zhaohui3@ucfgroup.com>
*/

namespace core\service;

use core\dao\ApiConfModel;
/**
 * Class ApiconfService
 * @package core\service
 */
class ApiConfService extends BaseService {

    /**
     * 根据查询条件获取后台配置信息
     * @param type $site_id,$conf_type
     * @return boolean
     */
    public function getApiConfBySiteId($site_id = '1',$conf_type = '1') {
        $userDao = ApiConfModel::instance();
        $fields = 'name,value,conf_type,site_id';
        $condition = "((conf_type = '{$conf_type}' and site_id = '0') or (conf_type = '0' and site_id = '{$site_id}')) and is_effect = '1'";

        return $userDao->getConfInfoByCondition($condition,$is_array=true,$fields, $params = array());
    }
    /**
     * 根据查询条件获取客户端配置信息
     * @param type $site_id,$conf_type
     * @return boolean
     */
    public function getApiAdvConf($key = '',$site_id = '1',$conf_type = '2') {
        $userDao = ApiConfModel::instance();
        $fields = 'title,name,value';
        $condition = "conf_type = '{$conf_type}' and site_id = '{$site_id}' and is_effect = '1'";
        if ($key) {
            $condition .= "and name = '$key'";
        }
        return $userDao->getConfInfoByCondition($condition,$is_array=true,$fields, $params = array());
    }

    public function getDiscountCenterUrl($type = 1)
    {
        $result = $this->getApiAdvConf('center_suspend_icon');
        if (empty($result) || !is_array($result)) {
            return false;
        }
        $conf = array_pop($result);
        $conf = json_decode($conf['value'], true);

        $url = $conf[0]['url'];
        if ($url != '') {
            $symbol = strpos($url, '?') === false ? '?' : '&';
            return $url . $symbol . "type=$type";
        }

        return '';
    }

    public function getGoldSwitchStatus(){
        $result = $this->getApiAdvConf('feature_gold',1,0);
        if (empty($result) || !is_array($result)) {
            return false;
        }
        return $result[0]['value'] == 'true';
    }

    public function getAgreementList($site_id)
    {
        $res = [];
        $confData = get_config_db('AGREEMENT_LIST', $site_id);
        $data = explode('|', $confData);
        foreach($data as $v) {
            if ($a = explode('=', $v)) {
                $resVal['key'] = $a[0];
                $resVal['title'] = $a[1];
            }
            $res[] = $resVal;
        }
        return $res;
    }

    /**
     * 获取账户类型名称
     * @return array
     */
    public function getAccountNameConf()
    {
        $accountInfo = [];
        $accountConf = explode(",",str_replace(array('，', ' ', '|'),',',app_conf('ACCOUNT_NAME_CONFIG')));
        foreach ($accountConf as $val) {
            $accountInfo[] = explode(":", str_replace('：', ':', $val));
        }

        foreach ($accountInfo as $k => $v) {
            $accountInfo[$k] = ['name' => $v[0], 'desc' => $v[1]];
        }
        return $accountInfo;
    }

    public function isWhiteList($key)
    {
        if ($key && \libs\utils\ABControl::getInstance()->hit($key)) {
            return 1;
        }
        return 0;
    }

    /**
     * 获取公告配置
     * 主站 $pageId: 1首页，22网贷账户充值，23网信账户提现，24网贷账户充值, 25网贷账户提现
     * 分站/普惠 $pageId: 1首页，7充值，8提现
     */
    public function getNoticeConf($siteId = 1, $pageId = 0) {
        $data = [];
        $openService = new \core\service\OpenService();
        $siteConf = $openService->getSiteConf($siteId, 'param_notice');
        if (empty($siteConf['site'])) {
            return $data;
        }
        $noticeConf = json_decode($siteConf['site'][0]['value'], true);

        if (!is_array($noticeConf)) {
            return $data;
        }
        foreach ($noticeConf as $val) {
            if ($pageId === 0 || $val['pageid'] == $pageId) {
                //增加pc站url
                $host = get_http() . get_host();
                $val['pc'] = $host . '/adv/info?adv_name=' . urlencode($val['advid']);
                $data[] = $val;
            }
        }
        return $data;
    }

    /**
     * 获取api配置里的提现时效
     * @param string $default 默认的提现时效
     * @return string
     */
    public function getWithdrawTime($default = '1') {
        $result = $this->getApiAdvConf('withdraw_day', 0, 1);
        if (empty($result) || !is_array($result) || empty($result[0]['value'])) {
            return $default;
        }
        return $result[0]['value'];
    }
}