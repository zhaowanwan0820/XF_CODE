<?php
/**
 * TagsHelperService.php
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace core\service;

/**
 * Class TagsHelperService
 * @package core\service
 */
class TagsHelperService extends BaseService {

    /**
     * 给指定的用户组打tags
     */
    public function addTagsByGroup($tags, $groupId) {

        $sql = "SELECT id FROM firstp2p_user WHERE group_id = '{$groupId}'";
        $groupUsers = $GLOBALS['db']->getCol($sql);
        $GLOBALS['db']->startTrans();
        try {
            if (is_array($groupUsers)) {
                $tagsService = new \core\service\UserTagService();
                foreach ($groupUsers as $uid) {
                    $tagsService->addUserTagsByConstName($uid, $tags);
                }
            }
            $GLOBALS['db']->commit();
        }
        catch(\Exception $e) {
            $GLOBALS['db']->rollback();
            return false;
        }
        return true;
    }


}
