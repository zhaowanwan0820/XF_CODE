<?php

namespace api\controllers\apis;

use core\service\candy\CandyServiceException;
use libs\web\Form;
use api\controllers\apis\ApisBaseAction;
use libs\utils\Logger;
use core\service\candy\CandyPayService;

/**
 * 闪购用户资格检查接口
 */
class CandySaleUserCheck extends ApisBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array_merge($this->generalFormRule, [
            'userId' => ['filter' => 'int', 'message' => 'userId参数错误'],
            'discount' => ['filter' => 'string', 'message' => '折扣不能为空', 'option' => ['optional' => true]]
        ]);

        if (!$this->form->validate()) {
            return $this->echoJson(10001, $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $userId = intval($data['userId']);
        $discount = trim($data['discount']);
        if (empty($discount)) {
            $discount = 1;
        }

        $data = [
            'userId' => $userId,
            'pass' => '',
            'numLimit' => ''
        ];

        $candyPayService = new CandyPayService();
        $config = $candyPayService->getSaleUserLimitConfig();
        $data['config'] = $config;
        $data['numLimit'] = $config['numLimit'];
        Logger::info("CandySaleUserCheck. user_id: {$userId}, discount: {$discount}");

        try {
            $pass = $candyPayService->saleUserCheck($userId, $discount);
            $data['pass'] = $pass;
            return $this->echoJson(0, '成功', $data);
        } catch (CandyServiceException $e) {
            Logger::error('CandySaleUserCheck:' . $e->getMessage());
            $data['pass'] = false;
            $data['tips'] = $e->getMessage();
            return $this->echoJson(0, '成功', $data);
        } catch (\Exception $e) {
            return $this->echoJson(1001, '系统异常');
        }
    }

}
