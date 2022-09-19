<?php
/**
 * 网信房贷 贷款日期
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.10.17
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;

class LoanDateList extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'selectedDate' => array('filter' => 'int', 'option' => array('optional' => true)),
            'house_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'selectedCity' => array('filter' => 'string', 'option' => array('optional' => true))
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $conf = $this->rpc->local('HouseService\getHouseConf', array(), 'house');
        $conf['dateAdd'] = intval($conf['dateAdd']) ? intval($conf['dateAdd']) : 1;
        // 通过期限的上下限获取日期list集合
        $dateList = $this->getDateList($conf['downDate'], $conf['upDate'], $conf['dateAdd']);

        $seletedDate = !empty($data['selectedDate']) ? $data['selectedDate'] : '';
        $houseId = !empty($data['house_id']) ? $data['house_id'] : '';
        $selectedCity = !empty($data['selectedCity']) ? $data['selectedCity'] : '';

        $this->tpl->assign('selectedCity', $selectedCity);
        $this->tpl->assign('house_id', $houseId);
        $this->tpl->assign('selectedDate', $seletedDate);
        $this->tpl->assign('dateList', $dateList);
        $this->tpl->assign('token', $data['token']);
        $this->template = $this->getTemplate('expected_loan_date');
    }

    /**
     * @param $downDate int 期限下限
     * @param $upDate int 期限上限
     * @return array
     */
    private function getDateList($downDate, $upDate, $dateAdd = 1)
    {
        for ($index = $downDate; $index <= $upDate; $index+=$dateAdd) {
            $dateList[] = $index;
        }
        return $dateList;
    }
}
