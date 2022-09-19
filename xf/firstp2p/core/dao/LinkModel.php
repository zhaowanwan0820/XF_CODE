<?php
/**
 * Link class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

/**
 * Link class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class LinkModel extends BaseModel {
    /**
     * 取出首页展示的友情链接
     * @param int|false $num
     */
    public function getLinks($num=false) {
        $num = intval($num);
        $condition = "`is_effect` = 1 AND `show_index` = 1 ORDER BY `sort` ASC";
        $num !== 0 && $condition .= sprintf(" LIMIT %d", $this->escape($num));
        $links = $this->findAll($condition);
        return $links;
    }
}
