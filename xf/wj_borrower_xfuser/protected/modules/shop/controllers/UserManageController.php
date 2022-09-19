<?php

/**
 * 用户白名单录入
 */
class UserManageController extends \iauth\components\IAuthController
{
    public $pageSize = 10;

    /**
     *列表
     */
    public function actionIndex(){


        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'appid'=>Yii::app()->request->getParam('appid'),
                'type'=>1,
            ];
            //获取用户列表
            $importFileInfo         = (new XfShopAllowListImportFile())->getImportList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $can_auth = 0;
        if (!empty($authList) && strstr(strtolower($authList),strtolower('/shop/UserManage/Auth')) || empty($authList)) {
            $can_auth = 1;
        }
        return $this->renderPartial('index',['can_auth'=>$can_auth]);
    }


    public function actionDetail(){


        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'     => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize' => $pageSize,
                'upload_id'=>Yii::app()->request->getParam('upload_id'),
            ];
            if($user_id = Yii::app()->request->getParam('user_id')){
                $params['user_id'] = $user_id;
            }
            if($mobile = Yii::app()->request->getParam('mobile')){
                $params['mobile'] = $mobile;
            }
            //获取用户列表
            $importFileInfo         = (new XfDebtExchangeUserAllowList())->getUserList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }

        return $this->renderPartial('detail',['upload_id'=>Yii::app()->request->getParam('upload_id')]);
    }
    /**
     * 上传
     */
    public function actionUpload()
    {

        if (\Yii::app()->request->isPostRequest) {
            $appid = $_POST['appid'];
            try {
                $return = $this->uploadOffline($appid);

                return $this->renderPartial(
                    'importFile',
                    [
                        'end'             => 1,
                        'total'           => $return['total'],
                        'success'         => $return['success'],
                        'fail'            => $return['fail'],
                    ]
                );
            } catch (Exception $e) {
                return $this->renderPartial('importFile', ['msg' => $e->getMessage(),'p' => 3]);
            }
        }
        $platform = (new XfDebtExchangePlatform)->getShopList(['page' => 1,'pageSize' => 1000]);
        return $this->renderPartial('importFile', ['end' => 0, 'shopList' => $platform['list']]);
    }

    /**
     * 审核
     */
    public function actionAuth()
    {
        try {

            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'status' => 1,
                'type' => 1,
            ];
            XfShopAllowListImportFile::authImportFile($params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

    /**
     * 撤销
     */
    public function actionCancel()
    {
        try {
            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'status' => 3,
                'type' => 1,
            ];
            XfShopAllowListImportFile::authImportFile($params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }


    private function uploadOffline($appid)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        Yii::app()->fdb->beginTransaction();

        try {
            if (empty($_FILES['offline']['name'])) {
                throw new Exception('请选择文件上传');
            }

            // 读取数据
            $file = CUploadedFile::getInstanceByName(key($_FILES));
            if ($file->getHasError()) {
                $error = [
                    1 => '上传文件超过了服务器限制',
                    2 => '上传文件超过了脚本限制',
                    3 => '文件只有部分被上传',
                    4 => '没有文件被上传',
                    6 => '找不到临时文件夹',
                    7 => '文件写入失败',
                ];
                throw new Exception(isset($error[$file->getError()]) ? $error[$file->getError()] : '未知错误');
            }

            Yii::$enableIncludePath = false;
            Yii::import('application.extensions.phpexcel.PHPExcel', 1);
            $excelFile     = $file->getTempName();
            $inputFileType = PHPExcel_IOFactory::identify($excelFile);
            $objReader     = PHPExcel_IOFactory::createReader($inputFileType);
            $excelReader   = $objReader->load($excelFile);
            $phpexcel      = $excelReader->getSheet(0);
            $total_line    = $phpexcel->getHighestRow();
            $execlData     = $phpexcel->toArray();

            // 验证模板
            if ($total_line > 31000) {
                throw new Exception('数据量过大');
            }

            array_shift($execlData);

            $admin_id        = \Yii::app()->user->id;
            $time            = time();

            $dir      = 'upload/offline/' . date('Ym', time()) . '/';
            $file_dir = $dir . time().mt_rand(1000,999). '.' . $file->getExtensionName();
            if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
                throw new Exception('创建目录失败');
            }
            if (!$file->saveAs($file_dir)) {
                throw new Exception('上传失败');
            }

            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];

            $fileModel                   = new XfShopAllowListImportFile();
            $fileModel->file_name        = $file->getName();
            $fileModel->type             = 1;
            $fileModel->file_path        = $file_dir;
            $fileModel->appid            = $appid;
            $fileModel->action_admin_id  = $admin_id;
            $fileModel->action_user_name = $username;
            $fileModel->addtime          = $time;
            $fileModel->update_time      = $time;
            $fileModel->total_num        = $total_line - 1;
            if (false === $fileModel->save()) {
                throw new Exception('导入记录表失败');
            }
            $file_id = $fileModel->id;

            $insert_sql = "INSERT INTO xf_debt_exchange_user_allow_list (`user_id`,`appid`,`upload_id`,`created_at`)". ' values ';
            $i = 0;
            foreach ($execlData as $line => $content) {
                $user_id = $content[0] ?:0;
                if(empty($user_id)){
                   continue;
                }
                $insert_sql .= " ($user_id,".$appid.",".$file_id.",$time),";
                $i++;
            }
            $insert_sql = rtrim($insert_sql,',');
            if (false === Yii::app()->fdb->createCommand($insert_sql)->execute()) {
                throw new Exception('数据导入失败');
            }

            $fileModel->success_num             = $i;
            $fileModel->fail_num                = 0;
            if (false === $fileModel->save()) {
                throw new Exception('统计数据保存失败');
            }
            Yii::app()->fdb->commit();

            return [
                'success'         => count($execlData),
                'fail'            => 0,
                'total'           => $total_line - 1,
            ];
        } catch (Exception $e) {
            Yii::app()->fdb->rollback();
            throw $e;
        }

    }


}
