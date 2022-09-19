<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Ptp\models\Firstp2pUserTagRelation;

class UserTagRelationDAO
{

    /**
     * @根据uid获取tag_id
     * @param $uid
     * @return $tagIds
     */
    public static function getTagByUid($uid)
    {
        $conditons = 'uid = :uid:';
        $parameters = [
            'uid' => $uid
        ];
        $tagInfo = Firstp2pUserTagRelation::find(array(
            $conditons,
            'bind' => $parameters
         ));
        $tagInfo = empty($tagInfo) ? array() : $tagInfo->toArray();
        $tagIds = array();
        foreach ($tagInfo as $tagItem) {
            $tagIds[$tagItem['tagId']] = $tagItem['uid'];
        }
        return $tagIds;
    }
}
