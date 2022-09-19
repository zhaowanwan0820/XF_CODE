<?php

vendor('phpexcel.PHPExcel');

use libs\utils\Logger;
use core\service\UserCouponLevelService;

class UserCouponLevelAction extends CommonAction
{
    public function __construct()
    {
        parent::__construct();
        $this->is_log_for_fields_detail = true;
    }

    public function index()
    {
        $map = array();
        $comment = isset($_REQUEST['comment']) ? addslashes($_REQUEST['comment']) : '';
        $id = isset($_REQUEST['id']) ? addslashes($_REQUEST['id']) : '';
        $name = isset($_REQUEST['name']) ? addslashes($_REQUEST['name']) : '';
        $rebate_ratio = isset($_REQUEST['rebate_ratio']) ? addslashes($_REQUEST['rebate_ratio']) : '';
        $is_effect = (isset($_REQUEST['is_effect']) && '' != trim($_REQUEST['is_effect']) && 'all' != trim($_REQUEST['is_effect'])) ? intval($_REQUEST['is_effect']) : '';
        if ('' !== $comment) {
            $map['comment'] = array('like', "%{$comment}%");
        }
        if ('' !== $id) {
            $map['id'] = intval($id);
        }
        if ('' !== $name) {
            $map['name'] = trim($name);
        }
        if ('' !== $rebate_ratio) {
            $map['rebate_ratio'] = $rebate_ratio;
        }
        if ('' !== $is_effect) {
            $map['is_effect'] = $is_effect;
        }
        if (!empty($this->model)) {
            $this->_list($this->model, $map);
        }
        $this->display();
    }

    protected function form_index_list(&$list)
    {
        foreach ($list as &$item) {
            $item['is_effect'] = 0 == $item['is_effect'] ? '无效' : '有效';
        }
    }

    /**
     * 会员组选择后的用户等级二级联动下拉框取值
     */
    public function get_level_select()
    {
        $group_id = trim($_REQUEST['group_id']);
        if (empty($group_id)) {
            exit;
        }

        $userCouponLevelService = new UserCouponLevelService();
        $levels = $userCouponLevelService->getMatchedLevelsByGroupId($group_id);
        foreach ($levels as $level) {
            $select[] = array('id' => $level['id'], 'level' => $level['name']);
        }
        if (empty($select)) {
            $select[] = array('id' => '0', 'level' => '空');
        }
        echo json_encode($select);
        exit;
    }

    /**
     * 导出所有记录.
     */
    public function export_csv()
    {
        $log_info = array(__CLASS__, __FUNCTION__);

        Logger::info(implode(' | ', array_merge($log_info, array('script start'))));
        $content = implode(',', array('编号', '服务等级名称', '服务人返利系数', '有效状态', '备注说明'))."\n";
        $list = $this->_list(D('UserCouponLevel'), $map = '');
        if (empty($list)) {
            Logger::info(implode(' | ', array_merge($log_info, array('data empty'))));
        }
        $i = 0;

        foreach ($list as $item) {
            if (0 == $i) {
                Logger::info(implode(' | ', array_merge($log_info, array('export csv in', $content))));
            }
            $content .= implode(',', array($item['id'], $item['name'], $item['rebate_ratio'], $item['is_effect'], $item['comment']))."\n";
            ++$i;
        }

        header('Content-Disposition: attachment; filename=user_coupon_level_'.date('Ymd_His').'.csv');
        // 环境不同导致转换过程出错，故忽略错误
        echo iconv('utf-8', 'gbk//ignore', $content);
        Logger::info(implode(' | ', array_merge($log_info, array('script end'))));
        return;
    }

    public function update()
    {
        $id = intval($_REQUEST['id']);
        $isEffect = intval($_REQUEST['is_effect']);
        //$rebateRatio = bcadd($_REQUEST['rebate_ratio'], 0, 5);
        $name = addslashes(trim($_REQUEST['name']));

        if($isEffect == 0){
           $userCouponLevelService = new UserCouponLevelService();
           $count = $userCouponLevelService->getUserInGroupCountByLevelId($id);
           if($count >0){
                $this->error('等级下有客户，不允许置为无效');
           }
        }

        /*
        if(bccomp($rebateRatio, 0,5) == -1){
            $this->error('个人系数必须大于等于0');
        }
        */
        $userCouponLevelService = new UserCouponLevelService();
        $levelInfo = $userCouponLevelService->getByName($name);
        if(!empty($levelInfo) && $levelInfo['id']!=$id){
            $this->error('等级名称重复');
        }

        /*
        $levelInfo = $userCouponLevelService->getByRebateRatio($rebateRatio);
        if(!empty($levelInfo) && $levelInfo['id']!=$id){
            $this->error('个人系数重复');
        }
        */

        /*
        $result = $userCouponLevelService->checkLevelMatchGroups(array('id' => $id,'is_effect'=> $isEffect,'rebate_ratio' => $rebateRatio));
        if (empty($result)) {
            $this->error('该个人系数过大');
        }
        */
        parent::update();
    }

    public function insert(){
        $isEffect = intval($_REQUEST['is_effect']);
        $rebateRatio = bcadd($_REQUEST['rebate_ratio'], 0, 5);
        $name = addslashes(trim($_REQUEST['name']));
        $userCouponLevelService = new UserCouponLevelService();
        $levelInfo = $userCouponLevelService->getByName($name);
        if(!empty($levelInfo)){
            $this->error('等级名称重复');
        }
        $levelInfo = $userCouponLevelService->getByRebateRatio($rebateRatio);
        if(!empty($levelInfo)){
            $this->error('个人系数重复');
        }
        if(bccomp($rebateRatio, 0,5) == -1){
            $this->error('个人系数必须大于等于0');
        }
        parent::insert();
    }
}
