<?php
/**
 * 交易详情接口
 * @author zhaohui<zhaohui3@ucfgroup.com>
 * @date 2017.05.18
 */


namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

class TradeDetail extends GoldBaseAction {


    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array(
                        'filter' => 'required',
                        'message' => 'ERR_PARAMS_VERIFY_FAIL',
                ),
                'id' => array(
                        'filter' => 'required',
                        'message' => 'id is required'
                ),
        );
        if (!$this->form->validate()) {
            $this->setErr(ERR_PARAMS_VERIFY_FAIL,$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        $id = isset($data['id']) ? intval($data['id']) : '';
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $res = $this->rpc->local('GoldService\getTradDetail', array($user['id'],$id));
        $this->handlTradList($res['data']);
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON',$res['errMsg']);
            return false;
        }
        $result = array();
        $result['list'] = $res['data'];

        $this->json_data = $result;
    }

    public function handlTradList(&$tradInfo) {
        if ($tradInfo['type'] == '买金' || $tradInfo['type'] == '黄金收益克重' || $tradInfo['type'] == '满额赠金') {
            $tradInfo['gold'] = '+' . $tradInfo['gold'];
        } elseif ($tradInfo['type'] == '提金') {
            $tradInfo['gold'] = $tradInfo['gold'];
        }
        if ($tradInfo['type'] == '冻结' || $tradInfo['type'] == '黄金收益克重' || $tradInfo['type'] == '提现失败解冻' || $tradInfo['type'] == '变现失败解冻') {
            $tradInfo['fee'] = null;
            $tradInfo['money'] = null;
        }
        //为了处理黄金收益克重字段在详情页显示多一个克重的问题，增加额外一个字段，在新版本中使用，老版本还是走下面改的逻辑，新版本取这个新字段
        $tradInfo['type_ext'] = $tradInfo['type'];
        $tradInfo['type_tip'] = $tradInfo['type'].'克重(克)';
        //end
        //http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-5116
        if ($tradInfo['type'] == '黄金收益克重') {
            $tradInfo['type_tip'] = $tradInfo['type'].'(克)';
            $tradInfo['type'] = '黄金收益';
        }
        //http://jira.corp.ncfgroup.com/browse/FIRSTPTOP-5116 end
    }

}

