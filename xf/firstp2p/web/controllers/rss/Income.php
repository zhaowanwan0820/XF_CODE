<?php
/**
 * Income.php
 * 收益统计* 
 * @date 2014-04-01
 * @author yangqing <yangqing@ucfgroup.com>
 */

namespace web\controllers\rss;

use libs\web\Form;
use web\controllers\BaseAction;

class Income extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'cate' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return false;
        }
    }

    public function invoke() {
        $cate = intval($this->form->data['cate']);
        $result = $this->rpc->local('RssService\getIncome',array($cate));
        header("Content-Type:text/xml;charset=utf-8");
        echo $result;
    }

}
