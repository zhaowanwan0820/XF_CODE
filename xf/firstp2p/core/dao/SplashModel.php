<?php

/**
 * @abstract  闪屏dao
 * @author yutao
 * @date 2015-05-09
 */

namespace core\dao;

/**
 * 闪屏Model
 */
class SplashModel extends BaseModel {

    /**
     * 获取有效的闪屏信息
     * $site_id是分站ID
     * @return type
     */
    public function getSplash($site_id = 1) {
        $condition = "`is_effect`='1' and `site_id`='{$site_id}'" ;
        $splash = $this->findAll($condition, true, '*', null);
        return $splash;
    }

}
