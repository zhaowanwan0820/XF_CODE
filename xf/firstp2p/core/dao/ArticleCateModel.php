<?php
/**
 * ArticleCateModel.php
 *
 * @date 2014-04-10
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;
use NCFGroup\Protos\Ptp\Enum\ArticleCateEnum;


/**
 * 文章分类
 *
 * Class ArticleCateModel
 * @package core\dao
 */
class ArticleCateModel extends BaseModel{

    /**
     * 根据父类id获取分类列表
     *
     * @param $pid
     * @return \libs\db\Model
     */
    public function getListByPid($pid) {
        $condition = sprintf("`pid` = '%d'",$this->escape($pid));
        return $this->findAll($condition);
    }

    /**
     * 根据分类类型获取分类列表
     *
     * @param int $type 分类类型
     * @param int $site 所属站点
     * @return \libs\db\Model
     */
    public function getListByTypeAndSite($type,$site) {
        $condition = sprintf("`type_id` = '%d' and site_id = '%d' and is_delete = 0 ",$this->escape($type),$this->escape($site));
        $order = 'order by sort asc';
        return $this->findAll($condition.$order);
    }
    
    /**
     * 根据父id 和 站点id获取文章列表
     * @param int $pid 
     * @param int $siteId
     * @return 
     */
    public function getByPidAndSiteIdList($pid,$siteId){
        if (empty($pid) || empty($siteId)) return false;
        $condition = sprintf("`pid` = '%d' and site_id = '%d' and is_delete = 0 ",$this->escape($pid),$this->escape($siteId));
        $order = 'order by sort asc';
        return $this->findAll($condition.$order);
    }


    /**
     * 读取协议分类id
     * @param string $platform 协议平台类型 wxapp wxpc phapp phpc qyapp qypc
     * @return integer
     */
    public function getPlatformAgreementCateId($platform = ArticleCateEnum::PLATFORM_WXPC) {
        $conf = app_conf("PLATFORM_AGREEMENT_CONFIG");
        if (empty($conf)) {
            return 0;
        }
        $confArr = explode('|', $conf);
        $cateArr = [];
        foreach ($confArr as $cateInfo) {
            $cateInfo = trim($cateInfo);
            if (empty($cateInfo)) {
                continue;
            }
            list($cateKey, $cateId) = explode(':', $cateInfo);
            if ($cateKey == $platform) {
                return $cateId;
            }
        }
        return 0;
    }

}
