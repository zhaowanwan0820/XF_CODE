<?php
/**
 * TagsAction class file.
 *
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 * */
class TagsAction extends CommonAction{

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        $groups = \core\dao\UserGroupModel::instance()->findAll();
        $this->assign('groups', $groups);
        $this->display();
    }


    public function doGroupTags() {
        $tagsHelper = new \core\service\TagsHelperService();
        $groupId = isset($_REQUEST['groupId']) ? intval($_REQUEST['groupId']) : 0;
        if (empty($groupId)) {
            $this->error('会员组必须选择');
            return;
        }
        $groupTags = isset($_REQUEST['groupTags']) ? explode(',', trim($_REQUEST['groupTags'])) : array();
        if (empty($groupTags)) {
            $this->error('Tags键名不能为空');
            return;
        }
        $result = $tagsHelper->addTagsByGroup($groupTags, $groupId);
        if ($result) {
            $this->success('给用户组{'.$groupId.'}打Tag成功');
            return;
        }
        else {
            $this->error('打Tags失败');
        }

    }
}
