<?php
/**
 * 交易记录接口
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * @date 2017.05.18
 */


namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class TradeList extends GoldBaseAction {


    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array(
                        'filter' => 'required',
                        'message' => 'ERR_PARAMS_VERIFY_FAIL',
                ),
                'type' => array(
                        'filter' => 'string',
                        'option' => array('optional' => true)
                ),
                'pageNum' => array(
                        'filter' => 'int',
                        'message' => 'pageNum must int',
                        'option' => array('optional' => true)
                ),
                'pageSize' => array(
                        'filter' => 'int',
                        'message' => 'pageNum must int',
                        'option' => array('optional' => true)
                ),
        );
        if (!$this->form->validate()) {
            $this->setErr(ERR_PARAMS_VERIFY_FAIL,$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        $pageNum = isset($data['pageNum']) ? $data['pageNum'] : '';
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : '';
        $type = isset($data['type']) ? addslashes($data['type']) : '';
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $res = $this->rpc->local('GoldService\getTradList', array($user['id'],$pageSize,$pageNum,$type));

        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON',$res['errMsg']);
            return false;
        }
        $result = array();
        $this->handlTradList($res['data']['data']);
        $result['list'] = $res['data'];

        $this->json_data = $result;
    }

    public function handlTradList(&$trad_list) {
        foreach ($trad_list as $key=>&$value) {
            //为了处理黄金收益克重字段在详情页显示多一个克重的问题，增加额外一个字段，在新版本中使用，老版本还是走下面改的逻辑，新版本取这个新字段
            $value['type_ext'] = $value['type'];
            //end
            //http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-5116
            if ($value['type'] == '黄金收益克重') {
                $value['type'] = '黄金收益';
            }
            //http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-5116 end

            if ($value['label'] == '买' || $value['label'] == '补' || $value['label'] == '赠') {
                $value['gold'] = '+' . $value['gold'];
            } elseif ($value['label'] == '提') {
                $value['gold'] = $value['gold'];
            }elseif($value['label'] == '变'){
                $value['gold'] = '-' . $value['gold'];
            }
        }
    }

}

