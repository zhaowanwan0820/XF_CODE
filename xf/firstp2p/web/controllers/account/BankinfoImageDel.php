<?php
/**
 * BankinfoImageDel.php
 *
 * @date 2014年5月26日
 * @author 杨庆 <yangqing@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\web\Form;

class BankinfoImageDel extends BaseAction {

    private $allowPostfix =  array('jpg','jpeg','pjpeg','png');
    private $returnMessage = array('code'=>'0000','message'=>'操作成功');
    
    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter"=>"int"),
        );
        if (!$this->form->validate()) {
            return false;
        }
    }

    /**
     * 禁止用户前台删除图片
     */
    public function invoke() {
        $id = $this->form->data['id'];
        $message = $this->returnMessage;
        echo json_encode($message);
    }
    
}
