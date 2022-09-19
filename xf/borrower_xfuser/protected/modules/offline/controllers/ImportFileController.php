<?php

/**
 * 出借记录
 * 导入文件表的展示与管理
 * Class OfflineFileManageController.
 */
class ImportFileController extends \iauth\components\IAuthController
{
    public $pageSize = 10;

    private $jrgc_p_id = 3;
    private $zdx_p_id = 4;
    private $jys_p_id = 5;
    /**
     * 金融工厂导入文件列表.
     *
     * @throws Exception
     */
    public function actionFileListP3(){

        $platForm = $this->jrgc_p_id;
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
            ];
            //获取用户列表
            $importFileInfo         = HandleOfflineDataService::importFileList($platForm, $params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }
        return $this->renderPartial('importFileList', ['p' => $platForm]);
    }

    /**
     * 金融工厂审核导入文件.
     */
    public function actionAuthFileP3()
    {
        try {
            $platForm = $this->jrgc_p_id;
            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'auth_status' => \Yii::app()->request->getParam('auth_status'),
            ];
            HandleOfflineDataService::authImportFile($platForm, $params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

    /**
     * 金融工厂撤回导入文件.
     */
    public function actionCancelP3()
    {
        try {
            $platForm = $this->jrgc_p_id;
            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'auth_status' => 3,
            ];
            HandleOfflineDataService::authImportFile($platForm, $params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }



    /**
     * 智多新导入文件列表
     * @return string
     * @throws CException
     */
    public function actionFileListP4()
    {
        $platForm = $this->zdx_p_id;
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params = [
                'page' => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
            ];
            //获取用户列表
            $importFileInfo = HandleOfflineDataService::importFileList($platForm, $params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);die;
        }
        return $this->renderPartial('importFileList',['p' => $platForm]);
    }


    /**
     * 智多新审核导入文件.
     */
    public function actionAuthFileP4()
    {
        try {
            $platForm = $this->zdx_p_id;
            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'auth_status' => \Yii::app()->request->getParam('auth_status'),
            ];
            HandleOfflineDataService::authImportFile($platForm, $params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

    /**
     * 智多新撤回导入文件.
     */
    public function actionCancelP4()
    {
        try {
            $platForm = $this->zdx_p_id;
            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'auth_status' => 3,
            ];
            HandleOfflineDataService::authImportFile($platForm, $params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

    /**
     * 交易所导入文件列表.
     *
     * @throws Exception
     */
    public function actionFileListP5(){

        $platForm = $this->jys_p_id;
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
            ];
            //获取用户列表
            $importFileInfo         = HandleOfflineDataService::importFileList($platForm, $params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }
        return $this->renderPartial('importFileList', ['p' => $platForm]);
    }

    /**
     * 交易所审核导入文件.
     */
    public function actionAuthFileP5()
    {
        try {
            $platForm = $this->jys_p_id;
            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'auth_status' => \Yii::app()->request->getParam('auth_status'),
            ];
            HandleOfflineDataService::authImportFile($platForm, $params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

    /**
     * 交易所撤回导入文件.
     */
    public function actionCancelP5()
    {
        try {
            $platForm = $this->jys_p_id;
            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'auth_status' => 3,
            ];
            HandleOfflineDataService::authImportFile($platForm, $params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }




}
