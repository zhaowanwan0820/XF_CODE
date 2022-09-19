<?php

/**
 * 用户合同
 * Class UserContractController
 */
class UserContractController extends \iauth\components\IAuthController
{

    /**
     * 合同列表
     *
     * @throws Exception
     */
    public function actionContractList()
    {

        try {
            $platForm = \Yii::app()->request->getParam('p');
            $params = [
                'deal_load_id' => \Yii::app()->request->getParam('deal_load_id'),
            ];
            $res = HandleOfflineDataService::getContractList($platForm, $params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson($res, 0, '操作成功');

    }

    /**
     * 合同详情.
     */
    public function actionContractInfo()
    {
        try {
            $platForm = \Yii::app()->request->getParam('p');
            $params = [
                'deal_load_id' => \Yii::app()->request->getParam('deal_load_id'),
                'order'=>Yii::app()->request->getParam('order')
            ];
            HandleOfflineDataService::getContractInfo($platForm, $params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

}
