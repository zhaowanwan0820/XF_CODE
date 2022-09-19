<?php
/**
 * DealClassifyList.php
 * 标的分类列表
 * @date 2016-09-09
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\DealService;
use core\service\GoldService;

class DealClassifyList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array(
                    'filter' => 'string',
                    'option' => array('optional' => true)
            ),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $this->show_error('改接口已作废', "", 1);
        $userId = 0;
        if (isset($this->form->data['token'])) {
            $userInfo = $this->getUserByToken();
            if (empty($userInfo)) {
                $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
                return false;
            }
            $userId = $userInfo->id;
        }
        $result = $this->rpc->local('DealService\getDealCategoryInfo', array($userId));
        $res = array();
        if (!empty($result)) {
            if ($this->app_version < 430) {
                unset($result['YUE']);
            }
            if ($this->app_version < 470) {
                unset($result['DT']);
            }
            $res = array_values($result);
        }
        //获取可投优长今标的的数量,黄金不走后台的配置，后台有没有配置都会有这一项（和产品确认后台配置现在没有用了）
        $goldCount = $this->rpc->local('GoldService\getCountOnSale', array());
        $goldRes = array(
            'type' => 'GOLD',
            'name' => '优长金',
            'rate' => '0',
            'desc' => '',
            'isIndexShow' => '1',
            'num' => $goldCount,
        );
        $res[] = $goldRes;
        $this->json_data = $res;
    }

}
