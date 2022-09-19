<?php
/**
 * Created by PhpStorm.
 * User: wangpeipei
 * Date: 2018/11/15
 * Time: 17:49
 */

namespace api\controllers\candysnatch;
use core\service\candy\CandySnatchService;
use api\controllers\AppBaseAction;
use libs\web\Form;

/**
 * Class SnatchUserProductsPaging 我的夺宝-分页展示
 * @package api\controllers\candysnatch
 */
class SnatchUserProductsPaging extends AppBaseAction
{
    const USER_PRODUCT_LIST_LIMIT = 30;//分页显示参与记录
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'token不能为空'),
            'offset' => array('filter' => 'required', 'message' => '页码不能为空'),
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
        $userId = $loginUser['id'];

        $candySnatchService = new CandySnatchService();

        $productInfo = $candySnatchService->getUserRecentProducts($userId);

        $prizePeriod = array();
        $processPeriod = array();
        $losePeriod = array();
        foreach ($productInfo as $key => $value) {
            $value['myCode'] = explode(',', $value['code_detail']);
            if ($value['user_id'] == $userId) {
                $prizePeriod[] = $value;
            } elseif ($value['status'] == CandySnatchService::PERIOD_STATUS_PROCESS) {
                $processPeriod[] = $value;
            } else {
                $losePeriod[] = $value;
            }
        }

        $offset = empty($data['offset'])? 0 : $data['offset'];
        $productInfo = array_merge($prizePeriod, $processPeriod, $losePeriod);
        $productInfo = array_slice($productInfo, $offset * self::USER_PRODUCT_LIST_LIMIT, self::USER_PRODUCT_LIST_LIMIT);
        $productInfo = $candySnatchService->attachUserInfo($productInfo);

        $this->json_data = [
            $productInfo
        ];
    }
}