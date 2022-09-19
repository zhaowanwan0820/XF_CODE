<?php

/**
 * 用户账户信息录入
 * Class UserContractController
 */
class UploadUserAccountController extends \iauth\components\IAuthController
{

    /**
     * 录入数据列表
     *
     * @throws Exception

     */
    public function actionFileListP4()
    {
        $platForm = 4;//智多新
        if (\Yii::app()->request->isPostRequest) {
            try {
                $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
                $params   = [
                    'page'     => \Yii::app()->request->getParam('page') ?: 1,
                    'pageSize' => $pageSize,
                ];
                $res = HandleOfflineDataService::uploadUserAccountFileList($platForm, $params);
                $res['code'] = 0;
                $res['info'] = 'success';
                echo json_encode($res);
                die;
            } catch (Exception $e) {
                $this->echoJson([], 100, $e->getMessage());
            }
        }
        return $this->renderPartial('userAccountFileList', ['p' => $platForm]);
    }

    /**
     * 审核导入文件.
     */
    public function actionAuthFileP4()
    {
        try {
            $platForm = 4;
            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'auth_status' => \Yii::app()->request->getParam('auth_status'),
            ];
            HandleOfflineDataService::authUserAccountFile($platForm, $params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

    /**
     * 取消导入文件.
     */
    public function actionCancelFileP4()
    {
        try {
            $platForm = 4;
            $params = [
                'user_id' => \Yii::app()->user->id,
                'id' => \Yii::app()->request->getParam('id'),
                'auth_status' => 3,
            ];
            HandleOfflineDataService::authUserAccountFile($platForm, $params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');

    }

    /**
     * 智多新待加入金额
     */
    public function actionUploadOffline4()
    {
        if (\Yii::app()->request->isPostRequest) {
            try {
                $return = $this->uploadOffline(4);

                return $this->renderPartial(
                    'uploadAccountFile',
                    array(
                        'end'             => 1,
                        'total'           => $return['total'],
                        'success'         => $return['success'],
                        'fail'            => $return['fail'],
                        'total_amount'    => $return['total_amount'],
                        'f_wait_amount'   => $return['f_wait_amount'],
                        's_wait_amount'   => $return['s_wait_amount'],
                    )
                );
            } catch (Exception $e) {
                return $this->renderPartial('uploadAccountFile', array('msg' => $e->getMessage()));
            }
        }
        return $this->renderPartial('uploadAccountFile', ['end' => 0, 'p' => 4]);
    }

    public function uploadOffline($type = 1)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        Yii::app()->offlinedb->beginTransaction();
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
            $total_column  = $phpexcel->getHighestColumn();
            $execlData     = $phpexcel->toArray();

            // 验证模板
            if ($total_line > 10000) {
                throw new Exception('数据量过大');
            }
            $sysConfig    = include APP_DIR.'/protected/config/offline_title.php';
            $sysConfig    = $sysConfig['account'][$type];
            $offlineClass = new Offline();
            $config       = array_map(array("Offline", "checkString"), $sysConfig['title']);
            $title        = array_map(array("Offline", "checkString"), current($execlData));
            if ($config != $title) {
                throw new Exception('模板文件不合法');
            }

            // 获取数据及验证规则
            array_shift($execlData);
            $execlData = array_map(array("Offline", "trimArray"), $execlData);
            if ($total_line <= 1 || !current($execlData)) {
                throw new Exception('文件内容为空');
            }

            // 3000条一次入库
            $groupData     = array_chunk($execlData, 3000, true);
            $validate      = $sysConfig['validate'];
            $admin_id      = \Yii::app()->user->id;
            $time          = time();
            $file_id       = 0;
            $wait_amount   = isset($sysConfig['wait_amount']) ? $sysConfig['wait_amount'] : false;
            $s_wait_amount = 0; // 成功待加入金额
            $f_wait_amount = 0; // 失败待加入金额
            $success_num   = 0; // 成功条数
            $fail_num      = 0; // 失败条数
            $total_amount  = 0; // 总金额

            // 先上传
            if ($file_id == 0) {
                $dir      = 'upload/offline/'.date('Ym', time()).'/';
                $file_dir = $dir.$offlineClass->getMillisecond().'.'.$file->getExtensionName();
                if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
                    throw new Exception('创建目录失败');
                }
                if (!$file->saveAs($file_dir)) {
                    throw new Exception('上传失败');
                }

                $userInfo = Yii::app()->user->getState("_user");
                $username = $userInfo['username'];
                $fileModel                   = new OfflineUploadUserAccountFile();
                $fileModel->file_name        = $file->getName();
                $fileModel->file_path        = $file_dir;
                $fileModel->platform_id      = $type;
                $fileModel->action_admin_id  = $admin_id;
                $fileModel->action_user_name = $username;
                $fileModel->addtime          = $time;
                $fileModel->update_time      = $time;
                $fileModel->total_num        = $total_line-1;
                if (false === $fileModel->save()) {
                    throw new Exception('导入记录表失败');
                }
                $file_id = $fileModel->id;
            }

            // 验证并入库
            foreach ($groupData as $group => &$data) {
                foreach ($data as $line => &$content) {
                    $content['remark']      = '';
                    $content['create_time'] = $time;
                    $content['update_time'] = $time;
                    $content['file_id']     = $file_id;
                    $content['platform_id'] = $type;
                    $content['status']      = 1;

                    if (!empty($validate) && empty($content['remark'])) {
                        foreach ($validate as $column => $func) {
                            if (!isset($content[$column])) {
                                $content['remark'] .= $sysConfig['title'][$column].'不能为空,';
                                $content['status'] = 2;
                            }
                            if ($func instanceof Closure) {
                                $content[$column] = call_user_func($func, $content[$column]);
                                if ($content[$column] === false) {
                                    $content['status'] = 2;
                                    $content[$column]  = '';
                                    $content['remark'] .= $sysConfig['title'][$column].'格式不正确,';
                                }
                            } elseif (is_string($func)) {
                                if (!preg_match($func, $content[$column])) {
                                    $content['status'] = 2;
                                    $content[$column]  = '';
                                    $content['remark'] .= $sysConfig['title'][$column].'格式不正确,';
                                }
                            } else {
                                throw new Exception(strtoupper(chr($column + 65)).'列未知验证规则');
                            }
                        }
                        $content['remark'] = rtrim($content['remark'], ',');
                    }

                    if (!empty($content['remark'])) {
                        $fail_num++;
                        $f_wait_amount = false !== $wait_amount ? bcadd($f_wait_amount, $content[$wait_amount], 2) : $f_wait_amount;
                    } else {
                        $success_num++;
                        $s_wait_amount = false !== $wait_amount ? bcadd($s_wait_amount, $content[$wait_amount], 2) : $s_wait_amount;
                    }
                    $total_amount = bcadd($total_amount, $content[$wait_amount], 2);
                }
                unset($content);

                // insert 列
                $columns = $sysConfig['columns'];
                $columns = array_map(
                    function ($param) {
                        return '`'.$param.'`';
                    },
                    $columns
                );
                $columns = '('.implode(',', $columns).')';

                // insert 值
                $values = '';
                foreach ($data as $val) {
                    $v      = array_map(
                        function ($param) {
                            return "'".(empty($param) ? 0 : $param)."'";
                        },
                        $val
                    );
                    $values .= '('.implode(',', $v).'),';
                }
                $values = rtrim($values, ',');

                $sql = "INSERT INTO offline_upload_user_account_log ".$columns.'values'.$values;
                if (false === Yii::app()->offlinedb->createCommand($sql)->execute()) {
                    throw new Exception('数据导入失败');
                }
            }
            unset($data);

            $fileModel->success_num         = $success_num;
            $fileModel->fail_num            = $fail_num;
            $fileModel->success_wait_amount = $s_wait_amount;
            $fileModel->fail_wait_amount    = $f_wait_amount;
            $fileModel->total_amount        = $total_amount;

            if (false === $fileModel->save()) {
                throw new Exception('统计数据保存失败');
            }

            Yii::app()->offlinedb->commit();

            return [
                'success'         => $success_num,
                'fail'            => $fail_num,
                'total'           => $total_line - 1,
                'total_amount'    => $total_amount,
                'f_wait_amount'   => $f_wait_amount,
                's_wait_amount'   => $s_wait_amount,
            ];
        } catch (Exception $e) {
            Yii::app()->offlinedb->rollback();
            throw $e;
        }


    }
}
