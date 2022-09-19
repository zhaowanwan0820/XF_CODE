<?php
/**
 * AdvService.php
 * @date 2014-04-14
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace core\service;
use core\dao\AdvModel;
use core\data\AdvData;
use libs\utils\Logger;

/**
 * Class AdvService
 * @package core\service
 */
class AdvService extends BaseService {
    /**
     * 获取广告位
     * @param $adv_id
     * @return string
     */
    public function getAdv($adv_id, $tpl_dir = null) {
        $data = new AdvData();
        $content = $data->getAdv($adv_id, $tpl_dir);
        if($content){
            return $content;
        }

        $adv = AdvModel::instance()->getAdv($adv_id, $tpl_dir);
        if (!empty($adv['city_ids'])) {
            $city_ids = explode(',', $adv['city_ids']);
            if (in_array($GLOBALS['deal_city']['id'], $city_ids)) {
                $content = $this->handleAdv($adv['code']);
            }
        } else {
            $content = $this->handleAdv($adv['code']);
        }
         
        $data->setAdv($adv_id, $content, $tpl_dir);
        
        return $content;
    }

    /**
     * 清空广告位缓存
     */
    public function flushAdv($adv_id, $tmpl) {
        $data = new AdvData();
        return $data->flushAdv($adv_id, $tmpl);
    }

    /**
     * 处理广告位内容
     * @param array $code
     * @return string
     */
    public function handleAdv($code) {
        $search = array(
            './attachment',
            \SiteApp::init()->asset->getUploadRoot(),
            'http:'.\SiteApp::init()->asset->getStaticHost(),
        );
        $replace = array(
            \SiteApp::init()->asset->getStaticHost(),
            \SiteApp::init()->asset->getStaticHost(),
            \SiteApp::init()->asset->getStaticHost(),
        );
        // 上传的资源走cdn目录
        $code = str_ireplace($search, $replace, $code);
        return $code;
    }
}
