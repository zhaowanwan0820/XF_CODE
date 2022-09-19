<?php
/**
 * 机构排名
 *@author 王传路<wangchuanlu@ucfgroup.com>
 */
namespace web\controllers\roulette;

use web\controllers\BaseAction;
use libs\web\Form;

class OrgRank extends BaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
                'callback' => array(//回调方法
                        'filter' => 'string',
                        'optional' => true,
                ),
        );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg(), "", 1);
        }
    }

    public function invoke() {

        // 该接口已废弃
        $this->show_error('参数错误', "", 1);

        $callbackFun = $this->form->data['callback'];
        $result = file_get_contents('http://api.bi.corp.ncfgroup.com/api/v2/stat-plugin/orgRank');

        $data = array();
        if('' !== $result) {
            $res = json_decode($result,true);
            if($res['err_code'] == 0 ) {//有数据
                $data = $res['data'];
            }
        }
        echo $callbackFun."(".json_encode($data).")";
    }
}
