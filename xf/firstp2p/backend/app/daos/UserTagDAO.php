<?php

namespace NCFGroup\Ptp\daos;

use NCFGroup\Ptp\models\Firstp2pUserTag;

class UserTagDAO {
     /**
     * @根据tag_name获取tag_id
     * @return $tagIds
     */
    public static function getTagIDByTagName($tagName)
    {
        $str = implode(",",$tagName);
        $tagNames = "'".str_replace(",","','",$str)."'";
        $tagInfo = Firstp2pUserTag::find(array('conditions' => "constName IN($tagNames)"));
        $tagIds = array();
        $tagInfo = empty($tagInfo) ? array() : $tagInfo->toArray();
        foreach ($tagInfo as $tagItem) {
            $tagIds[$tagItem['id']] = $tagItem['constName'];
        }
        return $tagIds;
    }

}
