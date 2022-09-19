<?php
/**
 * RemoteTag class file.
 *
 * @author luzhengshuai<luzhengshuai@ucfgroup.com>
 * */
use core\service\RemoteTagService;
use core\dao\UserModel;
use libs\utils\RemoteTag;

class RemoteTagAction extends CommonAction{

    public static $localAttrs = array(
        'user_id' => array('type' => 'string', 'chn' => '用户id'),
        'name' => array('type' => 'string', 'chn' => '姓名'),
        'mobile' => array('type' => 'string', 'chn' => '手机'),
        'idno' => array('type' => 'string', 'chn' => '身份证'),
        'sex' => array('type' => 'string', 'chn' => '性别'),
        'bmonth' => array('type' => 'string', 'chn' => '出生月份'),
        'byear' => array('type' => 'string', 'chn' => '出生年份'),
        'mobile_type' => array('type' => 'string', 'chn' => '手机卡类型'),
        'mobile_region' => array('type' => 'string', 'chn' => '手机卡所在地'),
        'idno_region' => array('type' => 'string', 'chn' => '身份证所在地'),
        'code' => array('type' => 'string', 'chn' => '注册邀请码'),
        'money' => array('type' => 'string', 'chn' => '余额'),
        'today_deal_count' => array('type' => 'string', 'chn' => '今日投资次数'),
        'deal_money' => array('type' => 'string', 'chn' => '投资金额'),
        '7days_deal_count' => array('type' => 'string', 'chn' => '7日投资次数'),
        'left_bonus' => array('type' => 'string', 'chn' => '未用红包余额'),
        'invitee_deal_money_by_year' => array('type' => 'string', 'chn' => '年化邀请投资金额'),
        'deal_money_by_year' => array('type' => 'string', 'chn' => '年化投资金额'),
        'login_time' => array('type' => 'string', 'chn' => '最近登录时间'),
        'ytd_deal_cnt' => array('type' => 'string', 'chn' => '前日投资次数'),
        'ytd_deal_money' => array('type' => 'string', 'chn' => '前日投资金额'),
        'deal_count' => array('type' => 'string', 'chn' => '投资次数'),
        'invitee_deal_money' => array('type' => 'string', 'chn' => '邀请投资金额'),
        'invitee_count' => array('type' => 'string', 'chn' => '邀请投资人数'),
        'invitee_set' => array('type' => 'set', 'chn' => '邀请投资人'),
    );
    /**
     * tag基础属性
     */
    public static $baseAttr = array(
        'user_id','name','mobile','idno','sex','bmonth','byear','mobile_type','mobile_region','idno_region','code'
    );
    /**
     * tag投资属性
     */
    public static $investAttr = array('money','today_deal_count','deal_money', '7days_deal_count','left_bonus','invitee_deal_money_by_year','deal_money_by_year','login_time','ytd_deal_cnt','ytd_deal_money','deal_count','invitee_deal_money','invitee_count','invitee_set');
    public function __construct() {
        parent::__construct();
    }

    public function index() {

        $remoteTagService = new RemoteTagService();
        $tagAttrs = $remoteTagService->getTagAttrs();
        $this->assign('list', $tagAttrs);
        $this->display();
    }

    public function edit_tag_attr() {
        $tagKey = trim($_GET['tagKey']);
        $this->assign('typeEnum', RemoteTagService::$tagAttrTypeEnum);
        if ($tagKey) {
            $remoteTagService = new RemoteTagService();
            $tagAttr = $remoteTagService->getTagAttr($tagKey);
            $this->assign('tagAttr', $tagAttr);
            $this->assign('tagKey', $tagKey);
        }
        $this->display();
    }

    public function doSaveTagAttr() {
        $tagKey = trim($_POST['tagKey']);
        $chn = trim($_POST['tagChn']);
        $type = trim($_POST['tagType']);

        if (!$tagKey) {
            $this->error('Tag对应Key不能为空');
        }

        if (!$chn) {
            $this->error('Tag对应描述不能为空');
        }

        if (!$type) {
            $this->error('Tag对应类型不能为空');
        }

        $remoteTagService = new RemoteTagService();
        try {
            $res = $remoteTagService->setTagAttr($tagKey, array('chn' => $chn, 'type' => $type));
            if (!$res) {
                $this->error('tag设置失败');
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('tag设置成功');
    }

    public function delTagAttr() {
        $tagKey = trim($_GET['tagKey']);

        if (!$tagKey) {
            $this->error('Tag对应Key不能为空');
        }
        $remoteTagService = new RemoteTagService();
        try {
            $res = $remoteTagService->delTagAttr($tagKey);
            if (!$res) {
                $this->error('tag删除失败');
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('tag删除成功');
    }

    public function edit_user_tag() {
        $userId = $_GET['uid'];
        $userInfo = UserModel::instance()->find($userId, 'user_name', true);
        $remoteTagService = new RemoteTagService();
        $userTags = $remoteTagService->getUserAllTag($userId);
        $tagAttrs = $remoteTagService->getTagAttrs();
        $baseArray = array();
        $investArray = array();
        $activityArray = array();
        //获取基础属性tag
        foreach(self::$baseAttr as $base) {
            $baseArray[$base] = isset($tagAttrs[$base]) ? $tagAttrs[$base] : self::$localAttrs[$base];
            $baseArray[$base]['value'] = isset($userTags[$base]) ? $userTags[$base] : '';
            unset($tagAttrs[$base]);
            unset($userTags[$base]);
        }
        //获取投资属性tag
        foreach(self::$investAttr as $invest) {
            $investArray[$invest] = isset($tagAttrs[$invest]) ? $tagAttrs[$invest] : self::$localAttrs[$invest];
            $investArray[$invest]['value'] = isset($userTags[$invest]) ? $userTags[$invest] : '';
            unset($tagAttrs[$invest]);
            unset($userTags[$invest]);
        }
        //userTags去除基础属性和投资属性的放到活动属性中
        foreach($userTags as $activity => $value) {
            $activityTags[$activity] = isset($tagAttrs[$activity]) ? $tagAttrs[$activity] : self::$localAttrs[$activity];
            $activityTags[$activity]['value'] = isset($userTags[$activity]) ? $userTags[$activity] : '';
            unset($tagAttrs[$activity]);
        }
        //tagAttrs中去除前面三项的属性放到可添加tag中
        $avilableTags = $tagAttrs;

        $this->assign('userInfo', $userInfo);
        $this->assign('uid', $userId);
        $this->assign('baseTags', $baseArray);
        $this->assign('investTags', $investArray);
        $this->assign('userTags', $userTags);
        $this->assign('tagAttrs', $tagAttrs);
        $this->assign('activityTags', $activityTags);
        $this->assign('avilableTags', $avilableTags);
        $this->display();
    }

    public function doSaveUserTag(){
        $result = array('errCode' => 0,'data' => '');

        $userId = $_POST['uid'];
        $tagKey = $_POST['tagKey'];
        $tagValue = trim($_POST['tagVal']);
        if (empty($tagValue)) {
            $this->error('tag值不合法');
        }
        $remoteTagService = new RemoteTagService();
        //先删除旧的tag，再使用add方法增加tag
        //$remoteTagService->delUserTag($userId, $tagKey);
        $tagAttr = $remoteTagService->getTagAttr($tagKey);
        if($tagAttr['type'] == RemoteTag::TYPE_SET){
            //set类型的数据，添加时需要多次添加
            $tags = explode(',', $tagValue);
            $oldTags = $remoteTagService->getUserTag($userId, $tagKey);

            $deleteTags = array_diff($oldTags, $tags);
            if (!empty($deleteTags)) {
                foreach ($deleteTags as $tag) {
                    $res = $remoteTagService->delUserTag($userId, $tagKey, $tag);
                }
            }

            foreach($tags as $tag){
                if (!empty($tag)) {
                    $res = $remoteTagService->addUserTag($userId,$tagKey, $tag);
                }
            }
        } else {
            $res = $remoteTagService->addUserTag($userId, $tagKey, $tagValue);
        }
        if(!$res) {
            $result['errCode'] = 1;
        } else {
            $newTag = $remoteTagService->getUserTag($userId, $tagKey);
            $result['data'] = $newTag;
        }
        echo json_encode($result);
        exit;

    }

    public function doDelUserTag() {
        $result = array('errCode' => 0);
        $userId = $_POST['uid'];
        $tagKey = $_POST['tagKey'];
        $remoteTagService = new RemoteTagService();
        $res = $remoteTagService->delUserTag($userId, $tagKey);
        if(!$res) {
            $result['errCode'] = 1;
        }
        echo json_encode($result);
        exit;
    }

    public function doAddUserTags() {
        $result = array('errCode' => 0, 'success' => '', 'fail' => '');
        $userId = $_POST['uid'];
        $tags = $_POST['tags'];

        $remoteTagService = new RemoteTagService();
        foreach($tags as $key => $value) {
            $value = trim($value);
            if (empty($value)) {
                continue;
            }

            $tagAttr = $remoteTagService->getTagAttr($key);
            if($tagAttr['type'] == RemoteTag::TYPE_SET) {
                $tagArray = explode(',', $value);
                foreach($tagArray as $tag) {
                    if (!empty($tag)) {
                        $res = $remoteTagService->addUserTag($userId, $key, $tag);
                    }
                }
            } else {
                $res = $remoteTagService->addUserTag($userId, $key, $value);
            }
            if($res) {
                $result['success'] .= $key.',';
            }
            else {
                $result['fail'] .= $key. ',';
            }
        }
        echo json_encode($result);
        exit;
    }
}
?>
