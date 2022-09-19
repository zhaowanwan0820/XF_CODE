<?php
/**
 * Created by PhpStorm.
 * User: wangpeipei
 * Date: 2018/11/2
 * Time: 12:57
 */

namespace api\controllers\candysnatch;

use api\controllers\AppBaseAction;
use core\service\candy\CandySnatchService;
use libs\web\Form;


class SnatchJoinOrders extends AppBaseAction
{
    const LIMITRECORD = 30;//分页显示参与记录
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'token不能为空'),
            'periodId' => array('filter'=>'required', 'message'=> '期号不能为空'),
            'offset' => array('filter'=>'required', 'message'=> '页码不能为空'),
        );
        if (!$this->form->validate()) {
            return $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }
        $offset = $data['offset'];
        $candySnatchService = new CandySnatchService();
        $periodOrders = $candySnatchService->getPeriodOrders($data['periodId'], $offset * self::LIMITRECORD, self::LIMITRECORD);
        foreach($periodOrders as $key => $value){
            $periodOrders[$key]['create_time'] = date("Y-m-d H:i:s.",$value['create_time']/1000).sprintf("%03d",$value['create_time']%1000);
            $periodOrders[$key]['time'] = date("His", $value['create_time'] / 1000) * 1000 + $value['create_time'] % 1000;
        }

        $this->json_data = [
            'periodOrders'=> $periodOrders
        ];
    }
}
