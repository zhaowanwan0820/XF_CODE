<?php
/**
 * UserBasicGroupAction.class.php
 *
 * 政策组管理
 *
 * @date 2017-01-03
 * @author gengkuan<gengkuan@ucfgroup.com>
 */
use libs\utils\Logger;
use core\service\WeiXinService;

class UserBasicGroupAction extends CommonAction {

    /**
     * 列表
     */
    public function index()
    {
        parent::index();
    }
    protected function form_index_list(&$list) {
        foreach ($list as &$item) {
            if( 0 == $item['rebate_effect_days'] ||  '' == $item['rebate_effect_days']){
                $item['rebate_effect_days'] = '不限制';
            }
            $group_ids=  M('UserGroup')->where("basic_group_id = {$item['id']}")->field('id')->select();
            $item['group_count'] =count($group_ids);
        }
    }
    
}
