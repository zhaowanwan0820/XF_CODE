<?php

namespace NCFGroup\Ptp\Apis;

/**
 * 信仔机器人回款接口
 */
class AbcontrolApi{

    private $params;

    private function init(){
        $di = getDI();
        $this->params = $di->get('requestBody');
        if (empty($this->params['mobile'])) {
            throw new \Exception("缺少参数 mobile");
        }
    }

    public function hit() {
        try{
            $this->init();
            if (empty($this->params['grayName'])) {
                throw new \Exception("缺少参数grayName");
            }
        }catch (\Exception $ex){
            return array('errorCode' => -1, 'errorMsg' => $ex->getMessage(), 'data' => array());
        }

        $mobile = $this->params['mobile'];
        $grayName = $this->params['grayName'];

        $userInfo = (new \core\service\UserService())->getUserByMobile($mobile);

        if (empty($userInfo)) {
            $userInfo = ['mobile' => $mobile];
        }

        $returnData = array('errorCode' => 0, 'errorMsg' => 'success', 'data' => false);
        $returnData['data'] = \libs\utils\ABControl::getInstance()->hit($grayName, $userInfo);
        return $returnData;
    }
}
