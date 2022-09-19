<?php
/**
 * Adv class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

/**
 * Adv class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class AdvModel extends BaseModel {
    /**
     * 获取广告位信息
     * @param int $adv_id
     * @return array
     */
    public function getAdv($adv_id, $tpl_dir = null) {
        $condition = "`adv_id`='%s' AND `is_effect`='1'";
        $condition = sprintf($condition, $this->escape($adv_id));
        $adv = $this->findBy($condition, '*', null, true);
        return $adv;
    }

}
