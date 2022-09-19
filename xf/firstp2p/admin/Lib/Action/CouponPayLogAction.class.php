<?php
/**
 * CouponPayLogAction.class.php
 *
 * @date 2015-02-04
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

class CouponPayLogAction extends CommonAction {
    public function index(){
        if("duotou" == $_REQUEST["model"]){
            $this->model = M('CouponPayLogDuotou');
        }
        parent::index();
    }
    /**
     * 处理列表数据显示
     */
    protected function form_index_list(&$list) {
        $i = 1;
        foreach ($list as &$item) {
            $item['seq'] = $i++;
            $item['pay_day'] = to_date($item['pay_day'], 'Y-m-d');
        }
    }

}