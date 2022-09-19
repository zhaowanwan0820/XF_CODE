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

        $content = $this->handleAdv($adv['code']);
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

    /**
     * 闪屏数据
     */
    public static function getSplashInfo($os, $width, $height, $siteId) {
        $params = array(
            'os' => $os,
            'width' => $width,
            'height' => $height,
            'siteId' => $siteId
        );
        return self::rpc('ncfwx', 'ncfph/getSplashInfo', $params);
    }

    /**
     * 处理广告位带样式的文本内容，输出json串返回给app
     */
    public function handleRegexData($content){
        //匹配p标签、a标签、img标签、以及html标签和换行符
        $p_regex = '/<p.*?>(.*?)<\/p>/ies';
        $a_regex = '/<a .*?href="(.*?)".*?>/ies';
        $img_regex = '/<img .*?src="(.*?)".*?>/';
        $html_regex = '/<\/?.+?\/?>|\r|\n|\s/';
        $result = array();
        //取出所有p标签内的内容
        $res = preg_match_all($p_regex,$content,$match);
        foreach($match[1] as $k =>$v){
            $html_res = preg_replace($html_regex,'',$v);
            if(empty($html_res)){
                continue;
            }
            $a_match = array();
            $img_match = array();
            $img_url = '';
            //取出a标签内的内容以及href链接作为超链接
            preg_match($a_regex,$v,$a_match);
            //取出图片链接
            preg_match($img_regex,$v,$img_match);

            //因为文件资源器会过滤http请求头，但是app没有办法识别请求头，所以图片地址如果不为空，拼接一个带https头的地址
            if (!empty($img_match[1])) {
                $img_url_info = parse_url($img_match[1]);
                $img_url = get_http().$img_url_info['host'].$img_url_info['path'];
            }
            $result[$k]['url'] = empty($a_match[1])?'':$a_match[1];
            $result[$k]['imgUrl'] = $img_url;
            $result[$k]['info'] = $html_res;
        }
        return array_values($result);
    }
}
