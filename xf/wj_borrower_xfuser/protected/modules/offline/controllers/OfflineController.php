<?php

use iauth\models\AuthAssignment;

class OfflineController extends \iauth\components\IAuthController
{

    /**
     * 金融工厂
     */
    public function actionUploadOffline3()
    {
        if (\Yii::app()->request->isPostRequest) {
            try {
                $return = $this->uploadOffline(3);

                return $this->renderPartial(
                    'importFile',
                    [
                        'end'             => 1,
                        'total'           => $return['total'],
                        'success'         => $return['success'],
                        'fail'            => $return['fail'],
                        'total_amount'    => $return['total_amount'],
                        'f_wait_capital'  => $return['f_wait_capital'],
                        'f_wait_interest' => $return['f_wait_interest'],
                        's_wait_capital'  => $return['s_wait_capital'],
                        's_wait_interest' => $return['s_wait_interest'],
                    ]
                );
            } catch (Exception $e) {
                return $this->renderPartial('importFile', ['msg' => $e->getMessage(),'p' => 3]);
            }
        }
        return $this->renderPartial('importFile', ['end' => 0, 'p' => 3]);
    }

    /**
     * 智多新
     */
    public function actionUploadOffline4()
    {
        if (\Yii::app()->request->isPostRequest) {
            try {
                $return = $this->uploadOffline(4);

                return $this->renderPartial(
                    'importFile',
                    [
                        'end'             => 1,
                        'total'           => $return['total'],
                        'success'         => $return['success'],
                        'fail'            => $return['fail'],
                        'total_amount'    => $return['total_amount'],
                        'f_wait_capital'  => $return['f_wait_capital'],
                        'f_wait_interest' => $return['f_wait_interest'],
                        's_wait_capital'  => $return['s_wait_capital'],
                        's_wait_interest' => $return['s_wait_interest'],
                    ]
                );
            } catch (Exception $e) {
                return $this->renderPartial('importFile', ['msg' => $e->getMessage(),'p' => 4]);
            }
        }
        return $this->renderPartial('importFile', ['end' => 0, 'p' => 4]);
    }

    /**
     *交易所
     */
    public function actionUploadOffline5()
    {
        if (\Yii::app()->request->isPostRequest) {
            try {
                $return = $this->uploadOffline(5);

                return $this->renderPartial(
                    'importFile',
                    [
                        'end'             => 1,
                        'total'           => $return['total'],
                        'success'         => $return['success'],
                        'fail'            => $return['fail'],
                        'total_amount'    => $return['total_amount'],
                        'f_wait_capital'  => $return['f_wait_capital'],
                        'f_wait_interest' => $return['f_wait_interest'],
                        's_wait_capital'  => $return['s_wait_capital'],
                        's_wait_interest' => $return['s_wait_interest'],
                    ]
                );
            } catch (Exception $e) {
                return $this->renderPartial('importFile', ['msg' => $e->getMessage(),'p' => 5]);
            }
        }
        return $this->renderPartial('importFile', ['end' => 0, 'p' => 5]);
    }

    /**
     * 中国龙
     */
    public function actionUploadOffline6()
    {
        try {
            $this->uploadOffline(6);
            //  return $this->renderPartial('UploadOffline', array('end' => 0));
        } catch (Exception $e) {
            // $this->actionError($e->getMessage(), 5);
            $this->echoJson([], 1, $e->getMessage());
        }
    }

    /**
     *  offline数据导入 lrz
     */
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
            $sysConfig    = include APP_DIR . '/protected/config/offline_title.php';
            $sysConfig    = $sysConfig['borrow'][$type];
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
            $order           = isset($sysConfig['order_sn']) ? $sysConfig['order_sn'] : false;
            if (false !== $order) {
                $arr = $offlineClass->FetchRepeatMemberInArray(
                    ArrayUtil::array_column($execlData, $sysConfig['order_sn'])
                );
                if (!empty($arr)) {
                    throw new Exception('以下订单号重复:' . implode(',', $arr));
                }
            }

            // 3000条一次入库
            $groupData       = array_chunk($execlData, 3000, true);
            $validate        = $sysConfig['validate'];
            $admin_id        = \Yii::app()->user->id;
            $time            = time();
            $file_id         = 0;
            $capital         = isset($sysConfig['wait_capital']) ? $sysConfig['wait_capital'] : false;
            $interest        = isset($sysConfig['wait_interest']) ? $sysConfig['wait_interest'] : false;
            $s_wait_capital  = 0; // 成功待还本金
            $f_wait_capital  = 0; // 失败待还本金
            $s_wait_interest = 0; // 成功待还利息
            $f_wait_interest = 0; // 失败待还利息
            $success_num     = 0; // 成功条数
            $fail_num        = 0; // 失败条数
            $total_amount    = 0; // 总金额

            // 先上传
            if ($file_id == 0) {
                $dir      = 'upload/offline/' . date('Ym', time()) . '/';
                $file_dir = $dir . $offlineClass->getMillisecond() . '.' . $file->getExtensionName();
                if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
                    throw new Exception('创建目录失败');
                }
                if (!$file->saveAs($file_dir)) {
                    throw new Exception('上传失败');
                }

                $userInfo = Yii::app()->user->getState("_user");
                $username = $userInfo['username'];

                $fileModel                   = new OfflineImportFile();
                $fileModel->file_name        = $file->getName();
                $fileModel->file_path        = $file_dir;
                $fileModel->platform_id      = $type;
                $fileModel->action_admin_id  = $admin_id;
                $fileModel->action_user_name = $username;
                $fileModel->addtime          = $time;
                $fileModel->update_time      = $time;
                $fileModel->total_num        = $total_line - 1;
                if (false === $fileModel->save()) {
                    throw new Exception('导入记录表失败');
                }
                $file_id = $fileModel->id;
            }

            // 验证并入库
            foreach ($groupData as $group => &$data) {

                foreach ($data as $line => &$content) {

                    $content['remark']      = '';
                    $content['addtime']     = $time;
                    $content['update_time'] = $time;
                    $content['file_id']     = $file_id;
                    $content['platform_id'] = $type;
                    $content['status']      = 1;

                    if (false !== $order && !empty($content[$order])) {
                        $sql = "SELECT * FROM offline_import_content WHERE order_sn = {$content[$order]} and platform_id = {$type} and status in(1,4)";
                        if (Yii::app()->offlinedb->createCommand($sql)->queryRow()) {
                            $content['remark'] = '订单号重复,';
                            $content['status'] = 2;
                        }
                    }

                    if (!empty($validate)) {
                        foreach ($validate as $column => $func) {
                            if (!isset($content[$column])) {
                                $content['remark'] .= $sysConfig['title'][$column] . '不能为空,';
                                $content['status'] = 2;
                            }
                            if ($func instanceof Closure) {
                                $content[$column] = call_user_func($func, $content[$column]);
                                if ($content[$column] === false) {
                                    $content['status'] = 2;
                                    $content[$column]  = '';
                                    $content['remark'] .= $sysConfig['title'][$column] . '格式不正确,';
                                }
                            } elseif (is_string($func)) {
                                if (!preg_match($func, $content[$column])) {
                                    $content['status'] = 2;
                                    $content[$column]  = '';
                                    $content['remark'] .= $sysConfig['title'][$column] . '格式不正确,';
                                }
                            } else {
                                throw new Exception(strtoupper(chr($column + 65)) . '列未知验证规则');
                            }
                        }
                        $content['remark'] = rtrim($content['remark'], ',');
                    }

                    if (!empty($content['remark'])) {
                        $fail_num++;
                        $f_wait_capital  = false !== $capital ? bcadd($f_wait_capital, $content[$capital],2) : $f_wait_capital;
                        $f_wait_interest = false !== $interest ? bcadd($f_wait_interest, $content[$interest], 2) : $f_wait_interest;
                    } else {
                        $success_num++;
                        $s_wait_capital  = false !== $capital ? bcadd($s_wait_capital, $content[$capital], 2) : $s_wait_capital;
                        $s_wait_interest = false !== $interest ? bcadd($s_wait_interest, $content[$interest], 2) : $s_wait_interest;
                    }
                    $total_amount = false !== $capital ? bcadd($total_amount, $content[$capital], 2) : $total_amount;
                    $total_amount = false !== $interest ? bcadd($total_amount, $content[$interest], 2) : $total_amount;
                }
                unset($content);


                // insert 列
                $columns = $sysConfig['columns'];
                $columns = array_map(
                    function ($param) {
                        return '`' . $param . '`';
                    },
                    $columns
                );
                $columns = '(' . implode(',', $columns) . ')';

                // insert 值
                $values = '';
                foreach ($data as $k =>$val) {
                    $v      = array_map(
                        function ($param) {
                            return "'" . (empty($param) ? 0 : $param) . "'";
                            //return "'" . (empty($param) ? 0 : str_replace(' ','',$param)) . "'";
                        },
                        $val
                    );
                    $values .= '(' . implode(',', $v) . '),';
                }
                $values = rtrim($values, ',');


                $sql = "INSERT INTO offline_import_content " . $columns . 'values' . $values;
                //echo $sql;die;
                if (false === Yii::app()->offlinedb->createCommand($sql)->execute()) {
                    throw new Exception('数据导入失败');

                }
            }
            unset($data);

            $fileModel->success_num             = $success_num;
            $fileModel->fail_num                = $fail_num;
            $fileModel->success_capital_amount  = $s_wait_capital;
            $fileModel->fail_capital_amount     = $f_wait_capital;
            $fileModel->success_interest_amount = $s_wait_interest;
            $fileModel->fail_interest_amount    = $f_wait_interest;
            $fileModel->total_amount            = $total_amount;

            if (false === $fileModel->save()) {
                throw new Exception('统计数据保存失败');
            }
            Yii::app()->offlinedb->commit();

            return [
                'success'         => $success_num,
                'fail'            => $fail_num,
                'total'           => $total_line - 1,
                'total_amount'    => $total_amount,
                'f_wait_capital'  => $f_wait_capital,
                'f_wait_interest' => $f_wait_interest,
                's_wait_capital'  => $s_wait_capital,
                's_wait_interest' => $s_wait_interest,
            ];
        } catch (Exception $e) {
            Yii::app()->offlinedb->rollback();
            throw $e;
        }

    }


}