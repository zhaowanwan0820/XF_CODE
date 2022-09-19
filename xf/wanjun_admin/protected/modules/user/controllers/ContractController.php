<?php
use iauth\models\AuthAssignment;

class ContractController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
             'DownLoadUserContract',
         );
    }
    private $contract_dir = '';
    private $deal_type = 2;
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
            if ($total_line > 11) {
                throw new Exception('数据量过大,每次建议不超过10条');
            }

            array_shift($execlData);
            if (empty($execlData)) {
                throw new Exception('表格数据为空');
            }
            
            $base_dir      = 'upload/contract';
            $time = date('YmdHis', time());
            
            $dir = $this->contract_dir =  $base_dir. '/' .$time. '/';
            
        
            foreach ($execlData as $v) {
                $name_array[] = "'".trim($v[0])."'";
            }
            $sql = "select id,name,contract_path from firstp2p_deal where deal_status=4 and name  in (".implode(',', $name_array).")";
            $dealsInfo = Yii::app()->phdb->createCommand($sql)->queryAll();
            if (!$dealsInfo) {
                throw new Exception("表格中不含万峻在途标的");
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
            if ($zip->open($zipName, \ZipArchive::CREATE) === true) {
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
            //unlink($file);
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
                //项目合同文件
                if(empty($dealInfo['contract_path'])){
                    Yii::log('download contract deals name : '.$dealInfo['name']. ' is not find  contract file ');
                    continue;
                }
                $file_dir = $this->contract_dir.'/'.$dealInfo['name'];
                if (!file_exists($file_dir) && !mkdir($file_dir, 0777, true)) {
                    throw new Exception('创建项目合同目录失败');
                }
                $deal_contracts = explode(';',$dealInfo['contract_path']);
                foreach ($deal_contracts as $d_c){
                    $oss_preview_address = 'https://xf-deal-contract.oss-cn-beijing-internal.aliyuncs.com/'.$dealInfo['id'].'/'.$d_c;
                    $r = file_get_contents($oss_preview_address);
                    file_put_contents($file_dir."/".$d_c, $r);
                }
                //直投投资记录合同
                /*
                $load_info = Yii::app()->phdb->createCommand("select id,contract_path from firstp2p_deal_load where deal_id={$dealInfo['id']} and status = 1 and debt_type=1 and contract_path!='' ")->queryAll();
                if(empty($load_info)){
                    Yii::log('download contract deals_load for deal_id: '.$dealInfo['id']. ' is not find  contract file ');
                    continue;
                }
                foreach ($load_info as $dl_c){
                    $oss_preview_address = 'https://xf-deal-contract.oss-cn-beijing-internal.aliyuncs.com/'.$dealInfo['id'].'/'.$dl_c['contract_path'];
                    $r = file_get_contents($oss_preview_address);
                    file_put_contents($file_dir."/".$dl_c['contract_path'], $r);
                }*/
                //债转合同
                $sql = "select contract_path,deal_id   from firstp2p_deal_load where deal_id={$dealInfo['id']} and status = 1 and debt_type=2 and contract_path!='' group by contract_path ";
                $load_info = Yii::app()->phdb->createCommand($sql)->queryAll();
                if(empty($load_info)){
                    Yii::log('download contract deals_load for deal_id: '.$dealInfo['id']. ' is not find  contract file ');
                    continue;
                }
                foreach ($load_info as $dl_c){
                    $deal_load_contracts = explode(';',$dl_c['contract_path']);
                    foreach ($deal_load_contracts as $d_c){
                        $oss_preview_address = 'https://wj-data-contract.oss-cn-beijing-internal.aliyuncs.com/'.$d_c;
                        $r = file_get_contents($oss_preview_address);
                        $pdf_name = str_replace($dl_c['deal_id']."/",'', $d_c);
                        $pdf_name = str_replace("xf-exchange/",'', $pdf_name);
                        file_put_contents($file_dir."/".$pdf_name, $r);
                    } 
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
            $contract_sql = "select number,title,user_id from contract_{$table_name} where  deal_id=:deal_id and  source_type  in (2,3) " ;
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
            $contract_sql = "select number,title,user_id from contract_{$table_name} where  deal_id=:deal_id and status = 1  and source_type  in (2,3)  " ;
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
            $contract_sql = "select number,title,user_id from contract_{$table_name} where  deal_id=:deal_id and status = 1  and source_type = 0 " ;
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
                    $user_id = $item['user_id']? '-'.$item['user_id']:'';
                    file_put_contents($file_dir."/".$dealInfo['name'].'-'.$item['title'].'-'.$user_id.'-'.$item['number'].'.pdf', $r);
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
                set_time_limit(0); // 设置脚本最大执行时间 为0 永不过期

                //$deal_type = $this->deal_type = $_POST['deal_type'];
                $deal_type = 2;
                $return = $this->dealUploadFileData($deal_type);
            } catch (Exception $e) {
                return $this->renderPartial('importFile', ['msg' => $e->getMessage()]);
            }
        }
        return $this->renderPartial('importFile', ['end' => 0]);
    }


    public function actionGetUserInfo()
    {
        if (\Yii::app()->request->isPostRequest) {
            $user_id = $_POST['user_id'];
            $sql = "select id,real_name,idno from firstp2p_user where id =:user_id";
            $user_info = Yii::app()->fdb->createCommand($sql)->bindValues([':user_id' => $user_id])->queryRow();
            if (empty($user_info)) {
                $this->echoJson([], 100, '用户不存在！');
            }
            $user_info['idno'] = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->idno_key);
            $this->echoJson($user_info, 0, '操作成功');
        }
        return $this->renderPartial('contract');
    }


    private $is_has_contract = false;
    
    public function actionDownLoadUserContract()
    {
        set_time_limit(0); // 设置脚本最大执行时间 为0 永不过期

        $user_id = $_POST['query_user_id'];

        $base_dir      = 'upload/contract';
        $time = 'user_all_contract/'.$user_id;
        
        $dir = $this->contract_dir =  $base_dir. '/' .$time. '/';
        
        
        $zipName = $base_dir.'/'.$time.'.zip';
        // 如果压缩文件不存在，就创建压缩文件
        if (! is_file($zipName)) {
            $this->ZXContractList(['user_id'=>$user_id]);
            $this->PHContractList(['user_id'=>$user_id]);

            if ($this->is_has_contract == false) {
                echo "没有查询到该用户的合同文件";
                die;
            }

            $fp = fopen($zipName, 'w');
            fclose($fp);
            $zip = new \ZipArchive();
            // OVERWRITE选项表示每次压缩时都覆盖原有内容，但是如果没有那个压缩文件的话就会报错，所以事先要创建好压缩文件
            // 也可以使用CREATE选项，此选项表示每次压缩时都是追加，不是覆盖，如果事先压缩文件不存在会自动创建
            if ($zip->open($zipName, \ZipArchive::OVERWRITE) === true) {
                $this->addFileToZip($dir, $zip);
                $zip->close();
            } else {
                exit('下载失败！');
            }
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

        # code...
    }


    //尊享合同列表
    private function ZXContractList($params)
    {
        $dealLoadInfos = Yii::app()->fdb->createCommand('select dl.id,dl.debt_type,dl.deal_id,d.name from firstp2p_deal_load as dl left join firstp2p_deal as d on dl.deal_id = d.id where dl.user_id =:user_id and (wait_capital > 0 or wait_interest > 0 )')->bindValues([ ':user_id' => $params['user_id']])->queryAll();
       
        if ($dealLoadInfos) {
            foreach ($dealLoadInfos as $dealLoadInfo) {
                if (1 == $dealLoadInfo['debt_type']) {
                    $table_name = $dealLoadInfo['deal_id'] % 128;
                    $contract_sql = "select * from contract_{$table_name} where deal_load_id =:deal_load_id  and user_id=:user_id and deal_id=:deal_id and status = 1 and  source_type in (2,3)  ";
                    $contract_info = Yii::app()->contractdb->createCommand($contract_sql)->bindValues([':deal_load_id' => $dealLoadInfo['id'], ':user_id' => $params['user_id'], ':deal_id' => $dealLoadInfo['deal_id']])->queryAll();
                    if (!empty($contract_info)) {
                        foreach ($contract_info as $item) {
                            $pathInfo = Yii::app()->contractdb->createCommand('select group_id,path from '.$this->getTableName($item['number']).' where contract_number =:num and status = 1 ')->bindValues([':num' => $item['number']])->queryRow();
                            if (empty($pathInfo)) {
                                continue;
                            }
                            $oss_preview_address = 'https://xf-data.oss-cn-beijing.aliyuncs.com/'.$pathInfo['group_id'].substr($pathInfo['path'], 3);
                            $r = file_get_contents($oss_preview_address);
                            $file_dir = $this->contract_dir.'/'.$dealLoadInfo['name'].'/'.$dealLoadInfo['id'];
                            if (!file_exists($file_dir) && !mkdir($file_dir, 0777, true)) {
                                throw new Exception('创建项目合同目录失败');
                            }
                            $this->is_has_contract = true;
                            file_put_contents($file_dir."/".$item['title'].'.pdf', $r);
                        }
                    }
                } else {
                    //债权的合同
                    $sql = 'select download ,oss_download from firstp2p_contract_task where user_id =:user_id and status = 2  ';
                    $debtContract = Yii::app()->fdb->createCommand($sql)->bindValues([':user_id' => $params['user_id']])->queryAll();
                    if (!empty($debtContract)) {
                        foreach ($debtContract as$key => $item) {
                            $info = current(json_decode($item['download'], true));
                            $r = file_get_contents('https://oss.xfuser.com/'.$item['oss_download']);
                            $file_dir = $this->contract_dir.'/'.$dealLoadInfo['name'].'/'.$dealLoadInfo['id'];
                            if (!file_exists($file_dir) && !mkdir($file_dir, 0777, true)) {
                                throw new Exception('创建项目合同目录失败');
                            }
                            $this->is_has_contract = true;
                            file_put_contents($file_dir."/".$info['doc_name'].'.pdf', $r);
                        }
                    }
                }
            }
        }
    }

    private function PHContractList($params)
    {
        $dealLoadInfos = Yii::app()->phdb->createCommand('select dl.id,dl.debt_type,dl.deal_id,d.name from firstp2p_deal_load as dl left join firstp2p_deal as d on dl.deal_id = d.id where dl.user_id =:user_id and (wait_capital > 0 or wait_interest > 0 )')->bindValues([ ':user_id' => $params['user_id']])->queryAll();
       
        if ($dealLoadInfos) {
            foreach ($dealLoadInfos as $dealLoadInfo) {
                if (1 == $dealLoadInfo['debt_type']) {
                    $table_name = $dealLoadInfo['deal_id'] % 128;
                    $contract_sql = "select * from contract_{$table_name} where deal_load_id =:deal_load_id  and user_id=:user_id and deal_id=:deal_id and status = 1 and  source_type = 0 ";
                    $contract_info = Yii::app()->contractdb->createCommand($contract_sql)->bindValues([':deal_load_id' => $dealLoadInfo['id'], ':user_id' => $params['user_id'], ':deal_id' => $dealLoadInfo['deal_id']])->queryAll();
                    if (!empty($contract_info)) {
                        foreach ($contract_info as $item) {
                            $pathInfo = Yii::app()->contractdb->createCommand('select group_id,path from '.$this->getTableName($item['number']).' where contract_number =:num and status = 1 ')->bindValues([':num' => $item['number']])->queryRow();
                            if (empty($pathInfo)) {
                                continue;
                            }
                            $oss_preview_address = 'https://xf-data.oss-cn-beijing.aliyuncs.com/'.$pathInfo['group_id'].substr($pathInfo['path'], 3);
                            $r = file_get_contents($oss_preview_address);
                            $file_dir = $this->contract_dir.'/'.$dealLoadInfo['name'].'/'.$dealLoadInfo['id'];
                            if (!file_exists($file_dir) && !mkdir($file_dir, 0777, true)) {
                                throw new Exception('创建项目合同目录失败');
                            }
                            $this->is_has_contract = true;
                            file_put_contents($file_dir."/".$item['title'].'.pdf', $r);
                        }
                    }
                } else {
                    //债权的合同
                    $sql = 'select download ,oss_download from firstp2p_contract_task where user_id =:user_id and status = 2 and borrow_id =:deal_id and tender_id  =:deal_load_id ';
                    $debtContract = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $params['user_id'], ':deal_id' => $dealLoadInfo['deal_id'] ,':deal_load_id' => $dealLoadInfo['id']])->queryAll();
                    if (!empty($debtContract)) {
                        foreach ($debtContract as$key => $item) {
                            $info = current(json_decode($item['download'], true));
                            $r = file_get_contents('https://oss.xfuser.com/'.$item['oss_download']);
                            $file_dir = $this->contract_dir.'/'.$dealLoadInfo['name'].'/'.$dealLoadInfo['id'];
                            if (!file_exists($file_dir) && !mkdir($file_dir, 0777, true)) {
                                throw new Exception('创建项目合同目录失败');
                            }
                            $this->is_has_contract = true;
                            file_put_contents($file_dir."/".$info['doc_name'].'.pdf', $r);
                        }
                    }
                }
            }
        }
    }
}
