<?php

class ExportContractCommand extends CConsoleCommand
{
    public function actionContract()
    {
        $project_name = [
            '友居贷3A-yjs-096',
            '友居贷3B-yjs-516',
            '友居贷3B-yjs-518',
            '友居贷3B-yjs-522',
            '友居贷3B-yjs-528',
            '友居贷3B-yjs-524',
            '友居贷3B-yjs-526',
            '友居贷3B-yjs-538',
            '友居贷3B-yjs-542',
            '友居贷3A-yjs-106',
            '友居贷3B-yjs-544',
            '友居贷3B-yjs-546',
            '友居贷3A-yjs-098',
            '友居贷3A-yjs-102',
            '友居贷3A-yjs-104',
            '友居贷3A-yjs-108',
            '友居贷3B-yjs-532',
            '友居贷3B-yjs-534',
            '友居贷3B-yjs-554',
            '友居贷3B-yjs-536',
            '友居贷3B-yjs-556',
            '友居贷3B-yjs-558',
            '友居贷3B-yjs-548',
            '友居贷3B-yjs-552',
            '友居贷3B-yjs-572',
            '友居贷3B-yjs-562',
            '友居贷3B-yjs-576',
        ];
        $project_name = "'".implode("','", $project_name)."'";

        $deal_sql= "SELECT d.id,p.name,d.deal_type,d.name as deal_name from firstp2p_deal as d left join firstp2p_deal_project as p on d.project_id = p.id  where p.name  in ({$project_name})";
    
        $deal_info = Yii::app()->fdb->createCommand($deal_sql)->queryAll();

        //        $importFiles = OfflineImportFile::model()->findAllByAttributes(['auth_status' => 1, 'deal_status' => 0]);
        //        if(empty($importFiles)){
        //            self::echoLog('no import file data');
        //        }
        //写文件
        $file_log = "./contract.csv";
        $tmp_file_log = fopen($file_log, "w");
        $title_log = [
                    'user_id'           =>  '用户id',
                    'user_name'         =>  '姓名',
                    'mobile'            =>  '手机号码',
                    'deal_name'         =>  '借款标题',
                    'project_name'      =>  '项目名称',
                    'wait_capital'      =>  '未兑付投资本金',
                    'card_id'           =>  '汇款账号',
                    'name'              =>  '融资方',
                    'contract_num'      =>  '合同编号',
                    'debt'              =>  '是否发送债转',

                ];
        $out_log = iconv("UTF-8", "gbk//IGNORE", implode(",", $title_log))."\n";
        fwrite($tmp_file_log, $out_log);
        
        foreach ($deal_info as $item) {
            $deal_load_sql= "SELECT *  from firstp2p_deal_load where deal_id = {$item['id']} and wait_capital > 0 ";
    
            $deal_load= Yii::app()->fdb->createCommand($deal_load_sql)->queryAll();

            if (empty($deal_load)) {
                continue;
            }

            foreach ($deal_load as $key => $value) {
                $user_info_sql  = "select real_name,mobile from firstp2p_user where id = {$value['user_id']}";
                $user_info = Yii::app()->fdb->createCommand($user_info_sql)->queryRow();
                $c_info = $this->getUserInfo($value['id']);
                if (1 == $value['debt_type']) {
                    $is_debt = '否';
                    $table_name = $value['deal_id'] % 128;
                    $contract_sql = "select * from contract_{$table_name} where deal_id={$value['deal_id']} and status = 1 and  source_type in (2,3) and title='产品认购协议' and deal_load_id = {$value['id']} ";
                    $contract_info = Yii::app()->contractdb->createCommand($contract_sql)->queryRow();
                } else {
                    $contract_info['number'] = implode('-', [date('Ymd', $value['create_time']), $item['deal_type'], $value['deal_id'], $value['id']]);
                    $is_debt = '是';
                }
              
                //excel数据
                $data = [
                    'user_id'           =>  $value['id'],
                    'user_name'         =>  $user_info['real_name'],
                    'mobile'            =>  GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key)."\t",
                    'deal_name'         =>  $item['deal_name'],
                    'project_name'      =>  $item['name'],
                    'wait_capital'      =>  $value['wait_capital'],
                    'card_id'           =>  $c_info['card_id']."\t",
                    'name'              =>  $c_info['name'],
                    'contract_num'      =>  $contract_info['number']."\t",
                    'debt'              =>  $is_debt,
                ];
                $out = iconv("UTF-8", "gbk//IGNORE", implode(",", $data))."\n";
                fwrite($tmp_file_log, $out);
            }
        }
        // }
        fclose($tmp_file_log);
    }

    public function getUserInfo($deal_load_id)
    {
        $return_data = [];
        if (!$deal_load_id || !is_numeric($deal_load_id)) {
            return false;
        }

        //融资方信息
        $dl_sql = "SELECT dl.user_id,u.real_name,uc.name as company_name 
                            from firstp2p_deal_load dl 
                            left join firstp2p_deal d on d.id=dl.deal_id
                            left join firstp2p_user u on u.id=d.user_id
							left join firstp2p_user_company uc on  uc.user_id=d.user_id
                            where dl.id=$deal_load_id and dl.status=1";
        $deal_load_info = Yii::app()->fdb->createCommand($dl_sql)->queryRow();
        if (!$deal_load_info) {
            return false;
        }

        $return_data['name'] = $deal_load_info['company_name'] ?: $deal_load_info['name'];//融资方
        $return_data['card_id'] = '';

        //用户银行卡信息
        $card_sql = "select card.bankcard  from firstp2p_user u 
                            left join  firstp2p_user_bankcard card on u.id = card.user_id
                            where card.verify_status = 1 and u.id={$deal_load_info['user_id']} ";
        $card_info = Yii::app()->fdb->createCommand($card_sql)->queryRow();
        if ($card_info) {
            $return_data['card_id'] = GibberishAESUtil::dec($card_info['bankcard'], Yii::app()->c->idno_key);
        }

        return $return_data;
    }
}
