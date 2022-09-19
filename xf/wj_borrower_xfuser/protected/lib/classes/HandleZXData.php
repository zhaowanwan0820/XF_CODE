<?php

/**
 * 尊享数据类
 * Class HandleZDXData.
 */
class HandleZXData extends BaseHandleOfflineData
{
    public $platform_id = 1;

    private $contract_list_key = 'zx:contract:user:tender:';

    protected function getTableName($contractNum)
    {
        // 简单hash crc32 后对64取余
        $crc = intval(abs(crc32($contractNum)));
        $tableSurfix = $crc % 64;
        $tableName = sprintf('firstp2p_contract_files_with_num_%s', $tableSurfix);

        return $tableName;
    }

    public function getContractList()
    {
        $list = [];
        $params = $this->getContractNeedParams;

        $dealLoadInfo = Yii::app()->fdb->createCommand('select debt_type,deal_id from firstp2p_deal_load where id =:deal_load_id and user_id =:user_id ')->bindValues([':deal_load_id' => $params['deal_load_id'], ':user_id' => $params['user_id']])->queryRow();
        if (empty($dealLoadInfo)) {
            throw  new Exception('出借记录不存在');
        }

        if (1 == $dealLoadInfo['debt_type']) {
            $table_name = $dealLoadInfo['deal_id'] % 128;
            $contract_sql = "select * from contract_{$table_name} where deal_load_id =:deal_load_id  and user_id=:user_id and deal_id=:deal_id and status = 1 and  source_type in (2,3)  ";
            $contract_info = Yii::app()->contractdb->createCommand($contract_sql)->bindValues([':deal_load_id' => $params['deal_load_id'], ':user_id' => $params['user_id'], ':deal_id' => $dealLoadInfo['deal_id']])->queryAll();
            if (!empty($contract_info)) {
                foreach ($contract_info as $item) {
                    $pathInfo = Yii::app()->contractdb->createCommand('select group_id,path from '.$this->getTableName($item['number']).' where contract_number =:num and status = 1 ')->bindValues([':num' => $item['number']])->queryRow();
                    if (empty($pathInfo)) {
                        continue;
                    }
                    $contract['title'] = $item['title'];
                    $contract['path'] = $pathInfo['path'];//todo 合同组
                    $list[] = $contract;
                }
                //看看有没有转让过的
                $sql = 'select d.id from firstp2p_deal_load as t left join firstp2p_debt as d on t.id = d.tender_id where t.id =:deal_load_id and d.status = 1 ';
                $debtInfo = Yii::app()->fdb->createCommand($sql)->bindValues([':deal_load_id' => $params['deal_load_id']])->queryAll();
                if (!empty($debtInfo)) {
                    $debtId = ArrayUntil::array_column($debtInfo, 'id');
                    //看有没有认购成功
                    $sql = 'select new_tender_id from firstp2p_debt_tender where debt_id in ('.implode($debtId).')';
                    $debtTenderInfo = Yii::app()->fdb->createCommand($sql)->queryAll();
                    if (!empty($debtTenderInfo)) {
                        $newTenderId = ArrayUntil::array_column($debtTenderInfo, 'new_tender_id');
                        //看合同
                        $sql = 'select download,oss_download from firstp2p_contract_task where borrow_id =:deal_id and status = 2 and tender_id in ('.implode(',', $newTenderId).')';
                        $debtContract = Yii::app()->fdb->createCommand($sql)->bindValues([':deal_id' => $dealLoadInfo['deal_id']])->queryAll();
                        if (!empty($debtContract)) {
                            foreach ($debtContract as $item) {
                                $info = current(json_decode($item['download'], true));
                                $_debtContract['title'] = $info['doc_name'];
                                $_debtContract['path'] = $item['oss_download']; //viewpdf_url  查看的；download_url 下载的
                                $list[] = $_debtContract;
                            }
                        }
                    }
                }
            }
        } else {
            //债权的合同
            $sql = 'select download ,oss_download from firstp2p_contract_task where user_id =:user_id and status = 2 and borrow_id =:deal_id and tender_id  =:deal_load_id ';
            $debtContract = Yii::app()->fdb->createCommand($sql)->bindValues([':user_id' => $params['user_id'], ':deal_id' => $dealLoadInfo['deal_id'], ':deal_load_id' => $params['deal_load_id']])->queryAll();
            if (!empty($debtContract)) {
                foreach ($debtContract as $item) {
                    $info = current(json_decode($item['download'], true));
                    $_debtContract['title'] = $info['doc_name'];
                    $_debtContract['path'] = $item['oss_download']; //viewpdf_url  查看的；download_url 下载的
                    $list[] = $_debtContract;
                }
            }
        }

        Yii::app()->rcache->set($this->contract_list_key.$params['user_id'].':'.$params['deal_load_id'], json_encode($list), 3600); //缓存1小时

        return  $list;
    }

    /**
     * 查看指定合同.
     *
     * @throws Exception
     */
    public function getContractInfo()
    {
        $params = $this->getContractNeedParams;

        $contractList = Yii::app()->rcache->get($this->contract_list_key.$params['user_id'].':'.$params['deal_load_id']);
        if (empty($contractList)) {
            throw new Exception('超时~请返回合同列表重试');
        }
        if (!isset($params['order']) || empty($contractList[$params['order']])) {
            throw new Exception('合同序号未提供');
        }

        $contractList = json_decode($contractList, true);
        $info = parse_url($contractList[$params['order']]['path']);
        $path = $info['path'];
        $filename = basename($path);
        $fileBuffer = Yii::app()->oss->getObject(Yii::app()->oss->bucket, $path);
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
        }
    }
}
