<?php

class UserContractController extends XianFengExtendsController
{
    private $platform_id;
    private $contractNeedParams = [];
    private $contract_list_key = '_contract:user:platform:tender:';
    public $contractTyp2Name = [
        1 => '出借合同',
        2 => '担保合同',
        3 => '出借咨询与服务协议',
    ];

    protected function getTableName($contractNum)
    {
        // 简单hash crc32 后对64取余
        $crc = intval(abs(crc32($contractNum)));
        $tableSurfix = $crc % 64;
        $tableName = sprintf('firstp2p_contract_files_with_num_%s', $tableSurfix);

        return $tableName;
    }

    private static function getContractList($platForm, $params)
    {
        try {
            $contract = new self(0, '');
            $contract->contractNeedParams = $params;
            $contract->contract_list_key = $platForm.$contract->contract_list_key;
            $contract->platform_id = $platForm;
            switch ($platForm) {
                case 1:
                    $result = $contract->ZXContractList();
                    break;
                case 2:
                    $result = $contract->PHContractList();
                    break;
                case 3:
                    $result = $contract->JRGCContractList();
                    break;
                case 4:
                    $result = $contract->ZDXContractList();
                    break;
                default:
                    $result = [];
            }

            return $result;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private static function getContractInfo($platForm, $params)
    {
        try {
            $contract = new self(0, '');
            $contract->contractNeedParams = $params;
            $contract->contract_list_key = $platForm.$contract->contract_list_key;
            $contract->platform_id = $platForm;
            switch ($platForm) {
                case 1:
                case 2:
                case 3:
                    $result = $contract->commonContractInfo();
                    break;
                case 4:
                    $result = $contract->ZDXContractInfo();
                    break;
                default:
                    $result = [];
            }

            return $result;
        } catch (Exception $e) {
            throw $e;
        }
    }

    //尊享合同列表
    private function ZXContractList()
    {
        $list = [];
        $params = $this->contractNeedParams;

        $dealLoadInfo = Yii::app()->db->createCommand('select debt_type,deal_id from firstp2p_deal_load where id =:deal_load_id and user_id =:user_id ')->bindValues([':deal_load_id' => $params['deal_load_id'], ':user_id' => $params['user_id']])->queryRow();
        if (empty($dealLoadInfo)) {
            throw  new Exception('出借记录不存在');
        }

        if (1 == $dealLoadInfo['debt_type']) {
            $table_name = $dealLoadInfo['deal_id'] % 128;
            $contract_sql = "select * from contract_{$table_name} where deal_load_id =:deal_load_id  and user_id=:user_id and deal_id=:deal_id and status = 1 and  source_type in (2,3)  ";
            $contract_info = Yii::app()->cdb->createCommand($contract_sql)->bindValues([':deal_load_id' => $params['deal_load_id'], ':user_id' => $params['user_id'], ':deal_id' => $dealLoadInfo['deal_id']])->queryAll();
            if (!empty($contract_info)) {
                $i = 0;
                foreach ($contract_info as $item) {
                    $pathInfo = Yii::app()->cdb->createCommand('select group_id,path from '.$this->getTableName($item['number']).' where contract_number =:num and status = 1 ')->bindValues([':num' => $item['number']])->queryRow();
                    if (empty($pathInfo)) {
                        continue;
                    }
                    $contract['title'] = $item['title'];
                    $contract['url'] = self::getBaseUrl().'/user/userContract/ContractInfo?deal_load_id='.$params['deal_load_id'].'&p=1&order='.$i; //todo 合同组
                    $contract['file'] = $pathInfo['group_id'].substr($pathInfo['path'], 3);
                    $contract['bucket'] = 'xf-data';
                    $list[] = $contract;
                    ++$i;
                }
            }
        } else {
            //债权的合同
            $sql = 'select download ,oss_download from firstp2p_contract_task where user_id =:user_id and status = 2 and borrow_id =:deal_id and tender_id  =:deal_load_id ';
            $debtContract = Yii::app()->db->createCommand($sql)->bindValues([':user_id' => $params['user_id'], ':deal_id' => $dealLoadInfo['deal_id'], ':deal_load_id' => $params['deal_load_id']])->queryAll();
            if (!empty($debtContract)) {
                foreach ($debtContract as$key => $item) {
                    $info = current(json_decode($item['download'], true));
                    $_debtContract['title'] = $info['doc_name'];
                    $_debtContract['url'] = self::getBaseUrl().'/user/userContract/ContractInfo?deal_load_id='.$params['deal_load_id'].'&p=1&order='.$key; //viewpdf_url  查看的；download_url 下载的
                    $_debtContract['file'] = $item['oss_download']; //viewpdf_url  查看的；download_url 下载的
                    $_debtContract['bucket'] = 0;
                    $list[] = $_debtContract;
                }
            }
        }

        Yii::app()->rcache->set($this->contract_list_key.$params['user_id'].':'.'1'.':'.$params['deal_load_id'], json_encode($list), 3600); //缓存1小时

        return  $list;
    }

    private function PHContractList()
    {
        $list = [];
        $params = $this->contractNeedParams;

        $dealLoadInfo = Yii::app()->phdb->createCommand('select debt_type,deal_id from firstp2p_deal_load where id =:deal_load_id and user_id =:user_id ')->bindValues([':deal_load_id' => $params['deal_load_id'], ':user_id' => $params['user_id']])->queryRow();
        if (empty($dealLoadInfo)) {
            throw  new Exception('出借记录不存在');
        }

        if (1 == $dealLoadInfo['debt_type']) {
            $table_name = $dealLoadInfo['deal_id'] % 128;
            $contract_sql = "select * from contract_{$table_name} where deal_load_id =:deal_load_id  and user_id=:user_id and deal_id=:deal_id and status = 1 and source_type = 0  ";
            $contract_info = Yii::app()->cdb->createCommand($contract_sql)->bindValues([':deal_load_id' => $params['deal_load_id'], ':user_id' => $params['user_id'], ':deal_id' => $dealLoadInfo['deal_id']])->queryAll();
            
            $i = 0;
            if (!empty($contract_info)) {
                foreach ($contract_info as $item) {
                    $pathInfo = Yii::app()->cdb->createCommand('select group_id,path from '.$this->getTableName($item['number']).' where contract_number =:num and status = 1 ')->bindValues([':num' => $item['number']])->queryRow();
                    if (empty($pathInfo)) {
                        continue;
                    }
                    $contract['title'] = $item['title'];
                    $contract['url'] = self::getBaseUrl().'/user/userContract/ContractInfo?deal_load_id='.$params['deal_load_id'].'&p=2&order='.$i; //todo 合同组
                    $contract['file'] = $pathInfo['group_id'].substr($pathInfo['path'], 3);
                    $contract['bucket'] = 'xf-data';
                    $list[] = $contract;
                    ++$i;
                }
            }
        } else {
            //债权的合同
            $sql = 'select download ,oss_download from firstp2p_contract_task where user_id =:user_id and status = 2 and borrow_id =:deal_id and tender_id  =:deal_load_id ';
            $debtContract = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $params['user_id'], ':deal_id' => $dealLoadInfo['deal_id'], ':deal_load_id' => $params['deal_load_id']])->queryAll();
            if (!empty($debtContract)) {
                foreach ($debtContract as $key => $item) {
                    $info = current(json_decode($item['download'], true));
                    $_debtContract['title'] = $info['doc_name'];
                    $_debtContract['url'] = self::getBaseUrl().'/user/userContract/ContractInfo?deal_load_id='.$params['deal_load_id'].'&p=2&order='.$key; //viewpdf_url  查看的；download_url 下载的
                    $_debtContract['file'] = $item['oss_download']; //viewpdf_url  查看的；download_url 下载的
                    $_debtContract['bucket'] = 0;

                    $list[] = $_debtContract;
                }
            }
        }

        Yii::app()->rcache->set($this->contract_list_key.$params['user_id'].':'.'2'.':'.$params['deal_load_id'], json_encode($list), 3600); //缓存1小时

        return  $list;
    }

    private function commonContractInfo()
    {
        $params = $this->contractNeedParams;
        $str = $this->contract_list_key.$params['user_id'].':'.$this->platform_id.':'.$params['deal_load_id'];
        $contractList = Yii::app()->rcache->get($str);

        if ($this->platform_id==3) {
            //var_dump($contractList,$params,$str);
        }
        if (empty($contractList)) {
            throw new Exception('超时~请返回合同列表重试');
        }
        if (!isset($params['order']) || empty($contractList[$params['order']])) {
            throw new Exception('合同序号未提供');
        }

        $contractList = json_decode($contractList, true);
        $info = parse_url($contractList[$params['order']]['file']);
        $path = $info['path'];
        $filename = basename($path);
        $fileBuffer = Yii::app()->oss->getObject($contractList[$params['order']]['bucket']?:Yii::app()->oss->bucket, $path);
        if ($this->platform_id==3) {
            //var_dump(($contractList[$params['order']]['bucket']?:Yii::app()->oss->bucket),$fileBuffer);
        }
        if ($fileBuffer) {
            ob_end_clean();
            header('Content-Type: application/force-download');
            header('Content-Transfer-Encoding: binary');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename='.$filename);
            header('Content-Length: '.strlen($fileBuffer));
            echo $fileBuffer;
            flush();
            ob_flush();

            return;
        } else {
            throw new Exception('合同文件不存在');
        }
    }

    private function JRGCContractList()
    {
        $list = [];
        $params = $this->contractNeedParams;

        $dealLoadInfo = Yii::app()->offlinedb->createCommand('select debt_type,deal_id from offline_deal_load where id =:deal_load_id and user_id =:user_id and platform_id =:platform_id ')->bindValues([':deal_load_id' => $params['deal_load_id'], ':user_id' => $params['user_id'], ':platform_id' => $this->platform_id])->queryRow();
        if (empty($dealLoadInfo)) {
            throw  new Exception('出借记录不存在');
        }

        if (1 == $dealLoadInfo['debt_type']) {
            $sql = 'select  contract_type,oss_download from offline_contract_task where tender_id =:deal_load_id and platform_id =:platform_id   and user_id =:user_id and `type` = 1 and status = 2';
            $contract_info = Yii::app()->offlinedb->createCommand($sql)->bindValues([':deal_load_id' => $params['deal_load_id'], ':platform_id' => $this->platform_id, ':user_id' => $params['user_id']])->queryAll();
            if (!empty($contract_info)) {
                foreach ($contract_info as $key=> $item) {
                    $contract['title'] = $this->contractTyp2Name[$item['contract_type']];
                    $contract['url'] = self::getBaseUrl().'/user/userContract/ContractInfo?deal_load_id='.$params['deal_load_id'].'&p=3&order='.$key; //viewpdf_url  查看的；download_url 下载的
                    $contract['file'] = 'resources'.$item['oss_download'];
                    $contract['bucket']='gc-ht';
                    $list[] = $contract;
                }
            }
        } else {
            //债权的合同
            $sql = 'select  contract_type,oss_download from offline_contract_task where tender_id =:deal_load_id and platform_id =:platform_id  and user_id =:user_id and `type` = 0 and status = 2';
            $debtContract = Yii::app()->offlinedb->createCommand($sql)->bindValues([':deal_load_id' => $params['deal_load_id'], ':platform_id' => $this->platform_id, ':user_id' => $params['user_id']])->queryAll();
            if (!empty($debtContract)) {
                foreach ($debtContract as $k => $item) {
                    $info = current(json_decode($item['download'], true));
                    $_debtContract['title'] = $info['doc_name'];
                    $_debtContract['url'] = self::getBaseUrl().'/user/userContract/ContractInfo?deal_load_id='.$params['deal_load_id'].'&p=3&order='.$k; //viewpdf_url  查看的；download_url 下载的
                    $_debtContract['file'] = $item['oss_download']; //viewpdf_url  查看的；download_url 下载的
                    $_debtContract['bucket'] = 0;
                }
            }
        }
        $str = $this->contract_list_key.$params['user_id'].':'.'3'.':'.$params['deal_load_id'];
        $res = Yii::app()->rcache->set($str, json_encode($list), 3600); //缓存1小时
        if ($this->platform_id==3) {
            //var_dump($res,$str);
        }
        return  $list;
    }

    private function ZDXContractList()
    {
        $list = [];
        $params = $this->contractNeedParams;

        $dealLoadInfo = Yii::app()->offlinedb->createCommand('select debt_type,deal_id,order_sn,money,create_time from offline_deal_load where id =:deal_load_id and user_id =:user_id and platform_id = 4')->bindValues([':deal_load_id' => $params['deal_load_id'], ':user_id' => $params['user_id']])->queryRow();
        if (empty($dealLoadInfo)) {
            throw  new Exception('出借记录不存在');
        }

        if (1 == $dealLoadInfo['debt_type']) {
            $contract['title'] = '《顾问协议》';
            $contract['url'] = self::getBaseUrl().'/user/userContract/ContractInfo?deal_load_id='.$params['deal_load_id'].'&p=4&order=0';
            $list[] = $contract;
        } else {
            //债权的合同
            $sql = 'select  contract_type,oss_download from offline_contract_task where tender_id =:deal_load_id and platform_id =:platform_id  and user_id =:user_id and `type` = 0 and status = 2';
            $debtContract = Yii::app()->offlinedb->createCommand($sql)->bindValues([':deal_load_id' => $params['deal_load_id'], ':platform_id' => $this->platform_id, ':user_id' => $params['user_id']])->queryAll();
            if (!empty($debtContract)) {
                foreach ($debtContract as $k => $item) {
                    $contract['title'] = $this->contractTyp2Name[$item['contract_type']];
                    $contract['file'] = $item['oss_download'];
                    $contract['url'] = self::getBaseUrl().'/user/userContract/ContractInfo?deal_load_id='.$params['deal_load_id'].'&p=4&order='.$k; //viewpdf_url  查看的；download_url 下载的
                    $contract['bucket'] = 0;
                    $list[] = $contract;
                }
            }
        }
        Yii::app()->rcache->set($this->contract_list_key.$params['user_id'].':'.'4'.':'.$params['deal_load_id'], json_encode($list), 3600); //缓存1小时
        return  $list;
    }

    public function ZDXContractInfo()
    {
        $params = $this->contractNeedParams;

        $dealLoadInfo = Yii::app()->offlinedb->createCommand('select debt_type,deal_id,order_sn,money,create_time from offline_deal_load where id =:deal_load_id and user_id =:user_id and platform_id = 4')->bindValues([':deal_load_id' => $params['deal_load_id'], ':user_id' => $params['user_id']])->queryRow();
        if (empty($dealLoadInfo)) {
            throw  new Exception('出借记录不存在');
        }

        if ($dealLoadInfo['debt_type']==1) {
            $userInfo = Yii::app()->db->createCommand('select * from firstp2p_user where id =:user_id')->bindValues([':user_id' => $params['user_id']])->queryRow();
            if (empty($userInfo)) {
                throw  new Exception('用户不存在');
            }

            $notice['loan_money'] = $dealLoadInfo['money']; //原始出借金额
            $notice['uppercase_loan_money'] = FunctionUtil::get_amount($dealLoadInfo['money']); //人民币大写

            $notice['fee_rate'] = '1.000';
            $notice['fee_days'] = '10';
            $notice['sign_time'] = date('Y-m-d', $dealLoadInfo['create_time']); //签名时间

            //甲方  用户信息
            $notice['loan_real_name'] = $userInfo['real_name'];
            $notice['loan_user_number'] = FunctionUtil::numTo32($userInfo['id']); //会员编号 见上面加密方式
            $notice['loan_user_idno'] = GibberishAESUtil::dec($userInfo['idno'], Yii::app()->c->contract['idno_key']); //证件号

            $number = str_pad('1004', 8, '0', STR_PAD_LEFT);
            $number .= str_pad(1, 2, '0', STR_PAD_LEFT);
            $number .= str_pad(10, 2, '0', STR_PAD_LEFT);
            $number .= str_pad($params['user_id'], 10, '0', STR_PAD_LEFT);
            $number .= str_pad($dealLoadInfo['order_sn'], 10, '0', STR_PAD_LEFT);
            $notice['number'] = $number;

            //修改合同模版缓存名
            $file_path = '/tmp/'.$number.'.pdf';

            $tmp_file_name = APP_DIR.'/views/default/zdx_guwenxieyi_1.tpl';
            $smarty = Yii::app()->viewRenderer;
            $html = $smarty->renderFile('', $tmp_file_name, $notice, true);
            $mkpdf = new \Mkpdf();

            $mkpdf->mk($file_path, $html);
            header('Content-Type: application/force-download');
            header('Content-Transfer-Encoding: binary');
            header('Content-type: application/pdf');
            header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
            header('Content-Length: '.filesize($file_path));
            echo readfile($file_path);
            @unlink($file_path);
            exit();
        }

        $this->commonContractInfo();
    }

    /**
     * 合同列表.
     *
     * @throws Exception
     */
    public function actionContractList()
    {
        $u = Yii::app()->request->getParam('u');
        if ($u) {
            $this->user_id = $u;
        }
        if (!$this->user_id) {
            $this->echoJson([], 100, '登录超时，请重新登录');
        }
        if (!$platForm = \Yii::app()->request->getParam('p')) {
            $this->echoJson([], 100, '平台参数不存在');
        }
        if (!$deal_load_id = Yii::app()->request->getParam('deal_load_id')) {
            $this->echoJson([], 100, '出借记录参数不存在');
        }

        try {
            $platForm = \Yii::app()->request->getParam('p');
            $params = [
                'deal_load_id' => $deal_load_id,
                'user_id' => $this->user_id,
            ];
            $res = self::getContractList($platForm, $params);
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
        $u = Yii::app()->request->getParam('u');
        if ($u) {
            $this->user_id = $u;
        }

        if (!$this->user_id) {
            $this->echoJson([], 100, '登录超时，请重新登录');
        }
        if (!$platForm = \Yii::app()->request->getParam('p')) {
            $this->echoJson([], 100, '平台参数不存在');
        }
        $order = Yii::app()->request->getParam('order');
        if ('' === $order) {
            $this->echoJson([], 100, '协议序号参数不存在');
        }

        try {
            $params = [
                'deal_load_id' => \Yii::app()->request->getParam('deal_load_id'),
                'order' => $order,
                'user_id' => $this->user_id,
            ];
            self::getContractInfo($platForm, $params);
        } catch (Exception $e) {
            $this->echoJson([], 100, $e->getMessage());
        }
        $this->echoJson([], 0, '操作成功');
    }

    private static function getBaseUrl()
    {
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        return $http_type . $_SERVER['HTTP_HOST'] ;
    }
}
