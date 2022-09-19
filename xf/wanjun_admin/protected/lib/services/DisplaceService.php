<?php


class DisplaceService extends  ItzInstanceService {


    public function __construct(  )
    {
        parent::__construct();
    }

    public function getList($params)
    {
        $where = '';
        $condition = [];
        if (!empty($params['user_id'])) {
            $condition[] = " a.user_id = ".$params['user_id'];
        }
        if (!empty($params['real_name'])) {
            $condition[] = " a.real_name = '".$params['real_name']."'";
        }
        if (!empty($params['mobile_phone'])) {
            $condition[] = " a.mobile_phone = '".$params['mobile_phone']."'";
        }
        if (!empty($params['idno'])) {
            $condition[] = " a.idno = '".$params['idno']."'";
        }
        if (!empty($params['id'])) {
            $condition[] = " a.id = '".$params['id']."'";
        }
        if (!empty($params['bank_card'])) {
            $condition[] = " a.bank_card = '".$params['bank_card']."'";
        }
        if (isset($params['status']) && $params['status'] >= 0) {
            $see_time = time()-86400;
            if($params['status'] == 6){
                $condition[] = " a.status=5 and a.displace_time<=$see_time";
            }elseif($params['status'] == 5){
                $condition[] = " a.status=5 and a.displace_time>$see_time";
            }else{
                $condition[] = " a.status = '".$params['status']."'";
            }

        }
        if (isset($params['province_name']) && $params['province_name'] >= 0) {
            $condition[] = " a.province_name = '".$params['province_name']."'";
        }
        if (isset($params['displace_type']) && $params['displace_type'] >= 0) {
            $condition[] = " a.displace_type = '".$params['displace_type']."'";
        }
        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition);
        }


        $fileList = [];
        $countFile = XfDisplaceRecord::model()->countBySql('select count(1) from xf_displace_record as a  '.$where);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select a.*  from xf_displace_record as a  
 {$where} order by a.id desc  LIMIT {$offset} , {$pageSize} ";
            $_file =  Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($_file as $item) {
                $now_time = time();
                if($item['status'] == 5 && $now_time>$item['displace_time']+864000){
                    $item['status'] = 6 ;//置换完成10天后用户可见
                }
                $item['user_sign_time']= !empty($item['user_sign_time']) ? date('Y/m/d H:i:s', $item['user_sign_time']) : '-';
                $item['displace_time']= !empty($item['displace_time']) ? date('Y/m/d H:i:s', $item['displace_time']) : '-';
                $item['move_time']= !empty($item['move_time']) ? date('Y/m/d H:i:s', $item['move_time']) : '-';
                $item['debt_time']= !empty($item['debt_time']) ? date('Y/m/d H:i:s', $item['debt_time']) : '-';
                $item['province_name_cn'] = Yii::app()->c->xf_config['province_name'][$item['province_name']];
                $item['status_cn'] = Yii::app()->c->xf_config['displace_status'][$item['status']];
                $item['displace_type_cn'] = Yii::app()->c->xf_config['displace_type'][$item['displace_type']];
                $item['ph_increase_reduce'] = number_format($item['ph_increase_reduce'] , 2 , '.' , ',');
                $item['displace_capital'] = number_format($item['displace_capital'] , 2 , '.' , ',');

                $fileList[] = $item;
            }
        }
        return ['countNum' => $countFile, 'list' => $fileList];
    }


    public function createDisplaceContract($params)
    {
        $where = '';
        $condition = [];
        if (!empty($params['user_id'])) {
            $condition[] = " a.user_id = ".$params['user_id'];
        }
        if (!empty($params['real_name'])) {
            $condition[] = " a.real_name = '".$params['real_name']."'";
        }
        if (!empty($params['mobile_phone'])) {
            $condition[] = " a.mobile_phone = '".$params['mobile_phone']."'";
        }
        if (!empty($params['idno'])) {
            $condition[] = " a.idno = '".$params['idno']."'";
        }
        if (!empty($params['id'])) {
            $condition[] = " a.id = '".$params['id']."'";
        }
        if (!empty($params['bank_card'])) {
            $condition[] = " a.bank_card = '".$params['bank_card']."'";
        }
        if (isset($params['status']) && $params['status'] >= 0) {
            $see_time = time()-86400;
            if($params['status'] == 6){
                $condition[] = " a.status=5 and a.displace_time<=$see_time";
            }elseif($params['status'] == 5){
                $condition[] = " a.status=5 and a.displace_time>$see_time";
            }else{
                $condition[] = " a.status = '".$params['status']."'";
            }
        }
        if (isset($params['province_name']) && $params['province_name'] >= 0) {
            $condition[] = " a.province_name = '".$params['province_name']."'";
        }
        if (isset($params['displace_type']) && $params['displace_type'] >= 0) {
            $condition[] = " a.displace_type = '".$params['displace_type']."'";
        }
        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition);
        }


        $sql = "select a.id,a.user_id,a.oss_contract_url,a.annex_oss_contract_url  from xf_displace_record as a  {$where} order by a.id desc  ";
        $_file =  Yii::app()->phdb->createCommand($sql)->queryAll();
        if(!$_file){
            return false;
        }
        foreach ($_file as $item) {
            $file_dir = $params['dir_path'];
            if (!file_exists($file_dir) && !mkdir($file_dir, 0777, true)) {
                throw new Exception('创建置换合同目录失败');
            }
            //合同1下载
            $oss_preview_address_01 = 'https://wj-data-contract.oss-cn-beijing.aliyuncs.com/'.$item['oss_contract_url'];
            $r = file_get_contents($oss_preview_address_01);
            $pdf_name = str_replace("contracts/xf-exchange/",'', $item['oss_contract_url']);
            file_put_contents($file_dir."/置换合同_".$pdf_name, $r);

            //合同2下载
            $oss_preview_address_02 = 'https://wj-data-contract.oss-cn-beijing.aliyuncs.com/'.$item['annex_oss_contract_url'];
            $r_02 = file_get_contents($oss_preview_address_02);
            $pdf_name = str_replace("contracts/xf-exchange/",'', $item['annex_oss_contract_url']);
            file_put_contents($file_dir."/置换补充协议_".$pdf_name, $r_02);


            //原投资合同导出
            $sql = "SELECT id,deal_id,user_id,contract_path,debt_type FROM xf_displace_deal_load  WHERE displace_id = {$item['id']} ";
            $displace_deal_load = Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($displace_deal_load as $dis_val){
                /*
                $oss_url = 'https://wj-data-contract.oss-cn-beijing.aliyuncs.com/'.$dis_val['contract_path'];
                $r_03 = file_get_contents($oss_url);
                $pdf_name = "原出借合同_{$dis_val['deal_id']}_{$dis_val['id']}_{$dis_val['user_id']}.pdf";
                file_put_contents($file_dir."/".$pdf_name, $r_03);*/
                if($dis_val['debt_type'] == 1){
                    //直投投资记录合同
                    $oss_preview_address = 'https://xf-deal-contract.oss-cn-beijing-internal.aliyuncs.com/'.$dis_val['deal_id'].'/'.$dis_val['contract_path'];
                    $r = file_get_contents($oss_preview_address);
                    file_put_contents($file_dir."/原出借直投合同_".$dis_val['contract_path'], $r);
                }elseif($dis_val['debt_type'] == 2){
                    //化债合同
                    $oss_preview_address = 'https://wj-data-contract.oss-cn-beijing-internal.aliyuncs.com/'.$dis_val['contract_path'];
                    $r = file_get_contents($oss_preview_address);
                    $pdf_name = str_replace($dis_val['deal_id']."/",'', $dis_val['contract_path']);
                    //$pdf_name = str_replace("4054802/",'', $dis_val['contract_path']);
                    $pdf_name = str_replace("xf-exchange/",'', $pdf_name);
                    file_put_contents($file_dir."/原出借债转合同_".$pdf_name, $r);
                }
            }
        }
        return true;
    }
}


