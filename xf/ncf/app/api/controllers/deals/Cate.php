<?php
/**
 * 投资分类接口
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace api\controllers\deals;

use api\controllers\AppBaseAction;
use libs\web\Form;

class Cate extends AppBaseAction {
    public function invoke() {
        $this->form = new Form();
        $deal_cate = $this->rpc->local('DealService\getDealTypes');
        $result = array();
        foreach ($deal_cate['data'] as $k => $v) {
            if ($v['name'] == '通知贷') { // 去除通知贷
                continue;
            }
            $result[] = array(
                "type_id" => $k,
                "name" => $v['name'],
            );
        }
        $this->json_data = $result;
    }
}
