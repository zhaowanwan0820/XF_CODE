<?php
/**
 * CouponExtraAction.php
 *
 * 附加优惠券管理
 * 
 * @date 2014-09-18
 * @author changlu <pengchanglu@ucfgroup.com>
 */
use libs\utils\Logger;
use core\service\UserTagService;
use core\service\DealTagService;
use core\service\CouponService;
class CouponExtraAction extends CommonAction {

    protected $is_log_for_fields_detail = true;

    //0前台正常投标 1后台预约投标 3 ios 4 Android
    /**
     * 投资来源
     * @var array
     */
    private static $souce_type = array('0'=>'web','1'=>'后台预约','3'=>'ios','4'=>'android','11'=> '一触即发','12'=>'一气呵成','13' =>'首屈一指','20' => '用户tag', '21' => '标tag');

    public function index() {
        $deal_id = intval($_REQUEST['deal_id']);
        $deal = false;
        if (!empty($deal_id)) {
            $deal = \core\dao\DealModel::instance()->find($deal_id, 'name');
        }
        $deal_name = empty($deal) ? '全局' : $deal['name'];
        $this->assign('deal_name', $deal_name);
        
        $condition['deal_id'] = 0;
        
        $this->assign("default_map", $condition);
        parent::index();
    }

    protected function form_index_list(&$list) {
        
        foreach ($list as &$item) {
            
            $item['source_type'] = $this->getByTagNameSourceType($item['source_type'],$item['tags'],$item['source_type']);
            
            $item['remark'] = "<div style='width:100px;overflow:hidden;white-space:nowrap;' title='{$item['remark']}'>{$item['remark']}</div>";

            $item['opt_edit'] = "<a href='javascript:javascript:edit(" . $item['id'] . ");'>编辑</a>";
            if ($item['deal_id'] == 0) {
                $item['opt_del'] = "<a href='javascript:javascript:foreverdel(" . $item['id'] . ");'>彻底删除</a>";
            }
        }
    }

    /**
     * 重置标所有的特殊优惠码返利规则为全局特殊优惠码返利规则
     */
    public function resetGlobal() {
        $deal_id = intval($_REQUEST['deal_id']);
        $ajax = intval($_REQUEST['ajax']);
        if (empty($deal_id)) {
            $this->error("参数错误");
        }
        $rebate_model = new \core\dao\CouponExtraModel();
        $rs = $rebate_model->copyRebate($deal_id);
        if ($rs !== false) {
            save_log($deal_id . l("LOG_STATUS_1"), 1);
            $this->display_success(l("LOG_STATUS_1"), $ajax);
        } else {
            save_log($deal_id . l("LOG_STATUS_0"), 0);
            $this->error(l("LOG_STATUS_0"), $ajax);
        }
    }

    /**
     * 把规则全部置为有效/无效
     */
    public function effectAll() {
        $deal_id = intval($_REQUEST['deal_id']);
        $is_effect = intval($_REQUEST['is_effect']);
        if (empty($deal_id)) {
            $this->error("参数错误");
        }
        $condition = array('deal_id' => $deal_id);
        parent::set_effect_all($condition, $is_effect);
    }
    
    /**
     * 根据投资获取不同的标签列表
     * @param int $sourceType 投资类型 (20用户tag，21标tag)
     */
    public function getTagList(){
        $sourceType = trim($_GET['sourceType']);
        if ($sourceType!=CouponService::TYPE_USER_TAG && $sourceType!=CouponService::TYPE_DEAL_TAG){
            $ret = array('data' => '','error_code' => '-1','error_msg' => '参数错误');
            echo json_encode($ret);
            exit;
        }
        switch($sourceType){
            case CouponService::TYPE_USER_TAG:
                $user_tag_service = new UserTagService();
                $tag_list = $user_tag_service->lists();
            break;
            case CouponService::TYPE_DEAL_TAG:
                $deal_tag_service = new DealTagService();
                $tag_list = $deal_tag_service->getTagList(true);
            break; 
        }
        $ret = array('data' => '','error_code' => 0,'error_msg' => '');
        if (!empty($tag_list)){
            $ret['data'] = $tag_list;
        }
        echo json_encode($ret);
        exit;
    }
    /**
     * 根据投资类型获取不同tag名字
     * @param int $sourceType
     * @param string $tags
     * @param string $sourceName
     */
    public function getByTagNameSourceType($sourceType,$tags,$sourceName){
        if ($sourceType !=CouponService::TYPE_DEAL_TAG && $sourceType !=CouponService::TYPE_USER_TAG){
            
            return self::$souce_type[$sourceType];
        }
        $str = self::$souce_type[$sourceType];
        
        if ($sourceType == CouponService::TYPE_USER_TAG){
            $user_tag_service = new UserTagService();
            if (!empty($tags)){
                $tagids = explode(',', $tags);
                $tagNames = $user_tag_service->getBytagsIds($tagids);
                $str .= '<br />';
                foreach($tagNames as $v){
                    $str .= $v['name'].',';
                }
                $str = trim($str,',');
            }
        }
        if ($sourceType == CouponService::TYPE_DEAL_TAG){
            $deal_tag_service = new DealTagService();
            if (!empty($tags)){
                $condition = 'id in (:tags)';
                $param = array(
                        ':tags' => $tags
                    );
                $tagNames = $deal_tag_service->getTagList(true,$condition, $param);
                $str .= '<br />';
                foreach($tagNames as $v){
                    $str .= $v['tag_name'].',';
                }
                $str = trim($str,',');
            }
        }
        
        return $str;
    }
}
