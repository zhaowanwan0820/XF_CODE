<?php
/**
 * 投资列表
 * @author longbo
 */

use core\dao\deal\DealModel;
use core\dao\deal\DealLoadModel;
use core\service\deal\DealService;
use libs\utils\Logger;


class DealLoadAction extends CommonAction
{

    //订单状态
    public static $dealStatus = [
        '0' => '待等材料',
        '1' => '进行中',
        '2' => '满标',
        '3' => '流标',
        '4' => '还款中',
        '5' => '已还清'
        ];

    //表类型
    public static $dealType = [
        '0' => '普通标',
        '1' => '通知贷',
        '2' => '交易所',
        '3' => '专享',
        '5' => '小贷'
        ];

    //投资来源
    public static $sourceType = [
        '0' => 'web',
        '1' => '后台预约',
        '3' => 'ios',
        '4' => '安卓',
        '5' => '前台预约',
        '6' => 'openAPI',
        '8'=>'wap'
        ];

    protected $is_use_slave = true;

    protected $investModel = null;

    protected $pageEnable = false;

    public function __construct()
    {
        $this->investModel = M('DealLoad');
        parent::__construct();
    }

    public function index() {
        $request = array_map('trim', $_REQUEST);
        $condition = [];
        if (!empty($request['user_id'])) {
            $condition['user_id'] = intval($request['user_id']);
        }
        if (!empty($request['deal_id'])) {
            $condition['deal_id'] = intval($request['deal_id']);
        }

        if (!empty($this->investModel) && !empty($condition) ) {
            $this->_list($this->investModel, $condition, 'create_time');
        }

        $this->assign("dealType", self::$dealType);
        $this->display();
        return;
    }

    protected function form_index_list(&$voList){
        foreach ($voList as &$v) {
            $v['source_type'] = self::$sourceType[$v['source_type']];
            $v['deal_type'] = self::$dealType[$v['deal_type']];
        }
    }


}
