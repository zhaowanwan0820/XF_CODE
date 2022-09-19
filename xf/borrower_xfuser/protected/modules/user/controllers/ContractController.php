<?php
use iauth\models\AuthAssignment;

class ContractController extends \iauth\components\IAuthController
{
    private $contract_dir = '';
    private $deal_type = 1;
    protected function getTableName($contractNum)
    {
        // 简单hash crc32 后对64取余
        $crc = intval(abs(crc32($contractNum)));
        $tableSurfix = $crc % 64;
        $tableName = sprintf('firstp2p_contract_files_with_num_%s', $tableSurfix);

        return $tableName;
    }

    protected function addFileToZip($path, $zip)
    {
        // 打开文件夹资源
        $handler = opendir($path);
        // 循环读取文件夹内容
        while (($filename = readdir($handler)) !== false) {
            // 过滤掉Linux系统下的.和..文件夹
          
            if ($filename != '.' && $filename != '..') {
                // 文件指针当前位置指向的如果是文件夹，就递归压缩
               
                if (is_dir($path.'/'.$filename)) {
                    $this->addFileToZip($path.'/'.$filename, $zip);
                } else {
                    // 为了在压缩文件的同时也将文件夹压缩，可以设置第二个参数为文件夹/文件的形式，文件夹不存在自动创建压缩文件夹
                    $zip->addFile($path.'/'.$filename);
                }
            }
        }
        @closedir($handler);
    }



    private function dealUploadFileData($deal_type=1)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

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
            if ($total_line > 201) {
                throw new Exception('数据量过大');
            }

            array_shift($execlData);
            if (empty($execlData)) {
                throw new Exception('表格数据为空');
            }
           
            
            if ($deal_type==1) {
                $db = 'fdb';
            } else {
                $db = 'phdb';
            }
            $base_dir      = 'upload/contract';
            $time = date('YmdHis', time());
            
            $dir = $this->contract_dir =  $base_dir. '/' .$time. '/';
            
        
            foreach ($execlData as $v) {
                $name_array[] = "'".$v[0]."'";
            }
            $sql = "select id,name from firstp2p_deal where name  in (".implode(',', $name_array).")";
            $dealsInfo = Yii::app()->{$db}->createCommand($sql)->queryAll();
          
            if (!$dealsInfo) {
                throw new Exception("项目不存在");
            }

            //处理合同信息
            $this->makeContractFileByDealType($dealsInfo);
        
        
            $zipName = $base_dir.'/'.$time.'.zip';
            // 如果压缩文件不存在，就创建压缩文件
            if (! is_file($zipName)) {
                $fp = fopen($zipName, 'w');
                fclose($fp);
            }
            $zip = new \ZipArchive();
            // OVERWRITE选项表示每次压缩时都覆盖原有内容，但是如果没有那个压缩文件的话就会报错，所以事先要创建好压缩文件
            // 也可以使用CREATE选项，此选项表示每次压缩时都是追加，不是覆盖，如果事先压缩文件不存在会自动创建
            if ($zip->open($zipName, \ZipArchive::OVERWRITE) === true) {
                $this->addFileToZip($dir, $zip);
                $zip->close();
            } else {
                exit('下载失败！');
            }
            //ob_clean();
            $file = fopen($zipName, "r");
            //返回的文件类型
            Header("Content-type: application/octet-stream");
            //按照字节大小返回
            Header("Accept-Ranges: bytes");
            //返回文件的大小
            Header("Accept-Length: ".filesize($zipName));
            //这里设置客户端的弹出对话框显示的文件名
            Header("Content-Disposition: attachment; filename=contract-".$time.".zip");
            //一次只传输1024个字节的数据给客户端
            //向客户端回送数据
            $buffer=1024;//
            //判断文件是否读完
            while (!feof($file)) {
                //将文件读入内存
                $file_data = fread($file, $buffer);
                //每次向客户端回送1024个字节的数据
                echo $file_data;
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }


    private function makeContractFileByDealType($dealsInfo)
    {
        try {
            Yii::log('download contract deals num : '.count($dealsInfo), 'info', __CLASS__);
           
            foreach ($dealsInfo as $dealInfo) {
                if ($this->deal_type ==2) {
                    $this->dealPhContract($dealInfo);
                } elseif (substr($dealInfo['name'], 0, 6) == '嘉汇') {
                    $this->dealJiaHuiContract($dealInfo);
                } else {
                    $this->dealZXContract($dealInfo);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    //处理尊享 嘉汇
    private function dealJiaHuiContract($dealInfo)
    {
        try {
            $table_name = $dealInfo['id'] % 128;
            $contract_sql = "select number,title from contract_{$table_name} where  deal_id=:deal_id and  source_type  in (2,3) " ;
            $contract_info = Yii::app()->contractdb->createCommand($contract_sql)->bindValues([':deal_id' => $dealInfo['id']])->queryAll();
            $this->makeContractFile($dealInfo, $contract_info);
        } catch (\Exception $th) {
            throw $th;
        }
    }


    //处理尊享 盈益
    private function dealZXContract($dealInfo)
    {
        try {
            $table_name = $dealInfo['id'] % 128;
            $contract_sql = "select number,title from contract_{$table_name} where  deal_id=:deal_id and  deal_load_id = 0 and status = 1  and source_type  in (2,3)  " ;
            $contract_info = Yii::app()->contractdb->createCommand($contract_sql)->bindValues([':deal_id' => $dealInfo['id']])->queryAll();
           
            $this->makeContractFile($dealInfo, $contract_info);
        } catch (\Exception $th) {
            throw $th;
        }
    }


    //处理普惠
    private function dealPhContract($dealInfo)
    {
        try {
            $table_name = $dealInfo['id'] % 128;
            $contract_sql = "select number,title from contract_{$table_name} where  deal_id=:deal_id and  deal_load_id = 0 and status = 1  and source_type = 0 " ;
            $contract_info = Yii::app()->contractdb->createCommand($contract_sql)->bindValues([':deal_id' => $dealInfo['id']])->queryAll();
            $this->makeContractFile($dealInfo, $contract_info);
        } catch (\Exception $th) {
            throw $th;
        }
    }

    private function makeContractFile($dealInfo, $contractInfo)
    {
        try {
            if (!empty($contractInfo)) {
                foreach ($contractInfo as $item) {
                    $pathInfo = Yii::app()->contractdb->createCommand('select group_id,path from '.$this->getTableName($item['number']).' where contract_number =:num and status = 1 ')->bindValues([':num' => $item['number']])->queryRow();
                    if (empty($pathInfo)) {
                        Yii::log('download contract deals name : '.$dealInfo['name']. ' contract_number: '.$item['number'].' is not find  contract file ', 'info', __CLASS__);
                        continue;
                    }
                    $oss_preview_address = 'https://xf-data.oss-cn-beijing.aliyuncs.com/'.$pathInfo['group_id'].substr($pathInfo['path'], 3);
                    $r = file_get_contents($oss_preview_address);
                    $file_dir = $this->contract_dir.'/'.$dealInfo['name'];
                    if (!file_exists($file_dir) && !mkdir($file_dir, 0777, true)) {
                        throw new Exception('创建项目合同目录失败');
                    }
                    file_put_contents($file_dir."/".$dealInfo['name'].'-'.$item['title'].'.pdf', $r);
                }
            } else {
                Yii::log('download contract deals name : '.$dealInfo['name']." is not find contract list from contract_".($dealInfo['id'] % 128), 'info', __CLASS__);
            }
        } catch (\Exception $th) {
            throw $th;
        }
    }



    public function actionUpload()
    {
        if (\Yii::app()->request->isPostRequest) {
            try {
                $deal_type = $this->deal_type = $_POST['deal_type'];
                $return = $this->dealUploadFileData($deal_type);
            } catch (Exception $e) {
                return $this->renderPartial('importFile', ['msg' => $e->getMessage()]);
            }
        }
        return $this->renderPartial('importFile', ['end' => 0]);
    }
}
