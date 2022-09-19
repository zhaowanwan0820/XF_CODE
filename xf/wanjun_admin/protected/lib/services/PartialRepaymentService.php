<?php

class PartialRepaymentService extends ItzInstanceService
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 部分还款列表 通过 移除 拒绝
     * @param $data [
     * status 2:审核通过3:审核未通过6:已撤销
     * partial_repayment_id 部分还款列表主键id
     * admin_user_id 后台用户id
     * remark 拒绝理由
     * ]
     * @return array
     */
    public function updatePartialRepayment($data = array())
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $status = $data['status'];
        $partial_repayment_id = $data['partial_repayment_id'];
        $admin_user_id = $data['admin_user_id'];
        $remark = $data['remark'];
        if(!in_array($status,[2,3,6]) || !is_numeric($partial_repayment_id) || !is_numeric($admin_user_id)){
            $return_result['info'] = '参数有误';
            $return_result['code'] = 2056;
            return $return_result;
        }
        $model = Yii::app()->fdb;
        $partialepayment = $model->createCommand("select * from ag_wx_partial_repayment where id = {$partial_repayment_id}")->queryRow();
        if(empty($partialepayment)) {
            $return_result['info'] = "部分还款列表信息不存在";
            $return_result['code'] = 2098;
            return $return_result;
        }
        //移除
        if($status == 6){
            if(!in_array($partialepayment['status'],[1,3])){
                $return_result['info'] = "只有待审核与审核未通过状态才能移除";
                $return_result['code'] = 2099;
                return $return_result;
            }
            $updateSql = ItzUtil::get_update_db_sql("ag_wx_partial_repayment",["status" => $status, 'updatetime' => time()]," id = $partial_repayment_id");
            $res = $model->createCommand($updateSql)->execute();
            $sql = ItzUtil::get_update_db_sql("ag_wx_partial_repay_detail",["status" => 3 , 'repay_status' => 2]," partial_repay_id = $partial_repayment_id");
            $res_a = $model->createCommand($sql)->execute();
            if (!$res) {
                $return_result['info'] = "更新部分还款列表失败";
                $return_result['code'] = 2103;
                return $return_result;
            }
        }
        //拒绝
        if($status == 3){
            if($partialepayment['status'] != 1){
                $return_result['info'] = "只有待审核状态才能拒绝";
                $return_result['code'] = 2100;
                return $return_result;
            }
            if(empty($remark)){
                $return_result['info'] = "请填写拒绝理由";
                $return_result['code'] = 2101;
                return $return_result;
            }
            $updateSql = ItzUtil::get_update_db_sql("ag_wx_partial_repayment",["status" => $status, 'remark' => $remark, 'updatetime' => time()]," id = $partial_repayment_id");
            $res = $model->createCommand($updateSql)->execute();
            if (!$res) {
                $return_result['info'] = "更新部分还款列表失败";
                $return_result['code'] = 2103;
                return $return_result;
            }
        }
        //通过
        if($status == 2){
            $ret = $this->checkexamine($partial_repayment_id);
            if($ret['code'] != 0){
                $return_result['info'] = $ret['info'];
                $return_result['code'] = $ret['code'];
                return $return_result;
            }
            //更新ag_wx_partial_repayment信息
            $repayDetail = $model->createCommand("SELECT sum(repay_money) as repay_money,count(*) as num,status FROM ag_wx_partial_repay_detail WHERE partial_repay_id = $partial_repayment_id GROUP BY status")->queryAll();
            $success_number = 0;
            $total_successful_amount = 0;
            $fail_number = 0;
            $total_fail_amount = 0;
            foreach($repayDetail as $key => $val){
                if($val['status'] == 1){
                    $success_number = $val['num'];
                    $total_successful_amount = $val['repay_money'];
                }elseif($val['status'] == 2){
                    $fail_number =  $val['num'];
                    $total_fail_amount =  $val['repay_money'];
                }
            }
            //都是失败的时候更新状态为审核未通过
            $remark = '';
            if(empty($success_number)){
                $return_result['info'] = "没有审核成功的还款信息";
                $return_result['code'] = 2118;
                return $return_result;
            }
            $repaymentArr = ["status" => $status,
                            'examine_user_id' => $admin_user_id,
                            'remark' => $remark,
            ];
            $updateSql = ItzUtil::get_update_db_sql("ag_wx_partial_repayment", $repaymentArr," id = {$partial_repayment_id}");
            $res = $model->createCommand($updateSql)->execute();
            if (!$res) {
                $return_result['info'] = "更新部分还款列表失败";
                $return_result['code'] = 2103;
                return $return_result;
            }
            if(empty($success_number)){
                $return_result['info'] = "没有审核成功的还款信息";
                $return_result['code'] = 2118;
                return $return_result;
            }
        }

    }

    public function updatePHPartialRepayment($data = array())
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $status = $data['status'];
        $partial_repayment_id = $data['partial_repayment_id'];
        $admin_user_id = $data['admin_user_id'];
        $remark = $data['remark'];
        if(!in_array($status,[2,3,6]) || !is_numeric($partial_repayment_id) || !is_numeric($admin_user_id)){
            $return_result['info'] = '参数有误';
            $return_result['code'] = 2056;
            return $return_result;
        }
        $model = Yii::app()->phdb;
        $partialepayment = $model->createCommand("select * from ag_wx_partial_repayment where id = {$partial_repayment_id}")->queryRow();
        if(empty($partialepayment)) {
            $return_result['info'] = "部分还款列表信息不存在";
            $return_result['code'] = 2098;
            return $return_result;
        }
        //移除
        if($status == 6){
            if(!in_array($partialepayment['status'],[1,3])){
                $return_result['info'] = "只有待审核与审核未通过状态才能移除";
                $return_result['code'] = 2099;
                return $return_result;
            }
            $updateSql = ItzUtil::get_update_db_sql("ag_wx_partial_repayment",["status" => $status, 'updatetime' => time()]," id = $partial_repayment_id");
            $res = $model->createCommand($updateSql)->execute();
            $sql = ItzUtil::get_update_db_sql("ag_wx_partial_repay_detail",["status" => 3 , 'repay_status' => 2]," partial_repay_id = $partial_repayment_id");
            $res_a = $model->createCommand($sql)->execute();
            if (!$res) {
                $return_result['info'] = "更新部分还款列表失败";
                $return_result['code'] = 2103;
                return $return_result;
            }
        }
        //拒绝
        if($status == 3){
            if($partialepayment['status'] != 1){
                $return_result['info'] = "只有待审核状态才能拒绝";
                $return_result['code'] = 2100;
                return $return_result;
            }
            if(empty($remark)){
                $return_result['info'] = "请填写拒绝理由";
                $return_result['code'] = 2101;
                return $return_result;
            }
            $updateSql = ItzUtil::get_update_db_sql("ag_wx_partial_repayment",["status" => $status, 'remark' => $remark, 'updatetime' => time()]," id = $partial_repayment_id");
            $res = $model->createCommand($updateSql)->execute();
            if (!$res) {
                $return_result['info'] = "更新部分还款列表失败";
                $return_result['code'] = 2103;
                return $return_result;
            }
        }
        //通过
        if($status == 2){
            $ret = $this->checkPHexamine($partial_repayment_id);
            if($ret['code'] != 0){
                $return_result['info'] = $ret['info'];
                $return_result['code'] = $ret['code'];
                return $return_result;
            }
            //更新ag_wx_partial_repayment信息
            $repayDetail = $model->createCommand("SELECT sum(repay_money) as repay_money,count(*) as num,status FROM ag_wx_partial_repay_detail WHERE partial_repay_id = $partial_repayment_id GROUP BY status")->queryAll();
            $success_number = 0;
            $total_successful_amount = 0;
            $fail_number = 0;
            $total_fail_amount = 0;
            foreach($repayDetail as $key => $val){
                if($val['status'] == 1){
                    $success_number = $val['num'];
                    $total_successful_amount = $val['repay_money'];
                }elseif($val['status'] == 2){
                    $fail_number =  $val['num'];
                    $total_fail_amount =  $val['repay_money'];
                }
            }
            //都是失败的时候更新状态为审核未通过
            $remark = '';
            if(empty($success_number)){
                $return_result['info'] = "没有审核成功的还款信息";
                $return_result['code'] = 2118;
                return $return_result;
            }
            $repaymentArr = ["status" => $status,
                            'examine_user_id' => $admin_user_id,
                            'remark' => $remark,
            ];
            $updateSql = ItzUtil::get_update_db_sql("ag_wx_partial_repayment", $repaymentArr," id = {$partial_repayment_id}");
            $res = $model->createCommand($updateSql)->execute();
            if (!$res) {
                $return_result['info'] = "更新部分还款列表失败";
                $return_result['code'] = 2103;
                return $return_result;
            }
            if(empty($success_number)){
                $return_result['info'] = "没有审核成功的还款信息";
                $return_result['code'] = 2118;
                return $return_result;
            }
        }

    }

    public function updateXFPartialRepayment($data = array())
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $status = $data['status'];
        $partial_repayment_id = $data['partial_repayment_id'];
        $admin_user_id = $data['admin_user_id'];
        $remark = $data['remark'];
        if(!in_array($status,[2,3,6]) || !is_numeric($partial_repayment_id) || !is_numeric($admin_user_id)){
            $return_result['info'] = '参数有误';
            $return_result['code'] = 2056;
            return $return_result;
        }
        $model = Yii::app()->offlinedb;
        $partialepayment = $model->createCommand("select * from offline_partial_repay where id = {$partial_repayment_id}")->queryRow();
        if(empty($partialepayment)) {
            $return_result['info'] = "部分还款列表信息不存在";
            $return_result['code'] = 2098;
            return $return_result;
        }
        //移除
        if($status == 6){
            if(!in_array($partialepayment['status'],[1,3])){
                $return_result['info'] = "只有待审核与审核未通过状态才能移除";
                $return_result['code'] = 2099;
                return $return_result;
            }
            $updateSql = ItzUtil::get_update_db_sql("offline_partial_repay",["status" => $status, 'updatetime' => time()]," id = $partial_repayment_id");
            $res = $model->createCommand($updateSql)->execute();
            $sql = ItzUtil::get_update_db_sql("offline_partial_repay_detail",["status" => 3 , 'repay_status' => 2]," partial_repay_id = $partial_repayment_id");
            $res_a = $model->createCommand($sql)->execute();
            if (!$res) {
                $return_result['info'] = "更新部分还款列表失败";
                $return_result['code'] = 2103;
                return $return_result;
            }
        }
        //拒绝
        if($status == 3){
            if($partialepayment['status'] != 1){
                $return_result['info'] = "只有待审核状态才能拒绝";
                $return_result['code'] = 2100;
                return $return_result;
            }
            if(empty($remark)){
                $return_result['info'] = "请填写拒绝理由";
                $return_result['code'] = 2101;
                return $return_result;
            }
            $updateSql = ItzUtil::get_update_db_sql("offline_partial_repay",["status" => $status, 'remark' => $remark, 'updatetime' => time()]," id = $partial_repayment_id");
            $res = $model->createCommand($updateSql)->execute();
            if (!$res) {
                $return_result['info'] = "更新部分还款列表失败";
                $return_result['code'] = 2103;
                return $return_result;
            }
        }
        //通过
        if($status == 2){
            $ret = $this->checkXFexamine($partial_repayment_id);
            if($ret['code'] != 0){
                $return_result['info'] = $ret['info'];
                $return_result['code'] = $ret['code'];
                return $return_result;
            }
            //更新ag_wx_partial_repayment信息
            $repayDetail = $model->createCommand("SELECT sum(repay_money) as repay_money,count(*) as num,status FROM offline_partial_repay_detail WHERE partial_repay_id = $partial_repayment_id GROUP BY status")->queryAll();
            $success_number = 0;
            $total_successful_amount = 0;
            $fail_number = 0;
            $total_fail_amount = 0;
            foreach($repayDetail as $key => $val){
                if($val['status'] == 1){
                    $success_number = $val['num'];
                    $total_successful_amount = $val['repay_money'];
                }elseif($val['status'] == 2){
                    $fail_number =  $val['num'];
                    $total_fail_amount =  $val['repay_money'];
                }
            }
            //都是失败的时候更新状态为审核未通过
            $remark = '';
            if(empty($success_number)){
                $return_result['info'] = "没有审核成功的还款信息";
                $return_result['code'] = 2118;
                return $return_result;
            }
            $repaymentArr = ["status" => $status,
                            'examine_user_id' => $admin_user_id,
                            'remark' => $remark,
            ];
            $updateSql = ItzUtil::get_update_db_sql("offline_partial_repay", $repaymentArr," id = {$partial_repayment_id}");
            $res = $model->createCommand($updateSql)->execute();
            if (!$res) {
                $return_result['info'] = "更新部分还款列表失败";
                $return_result['code'] = 2103;
                return $return_result;
            }
            if(empty($success_number)){
                $return_result['info'] = "没有审核成功的还款信息";
                $return_result['code'] = 2118;
                return $return_result;
            }
        }

    }

    /**
     * 审核通过验证
     * $partial_repayment_id 部分还款列表ID
     */
    public function checkexamine($partial_repay_id)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $model = Yii::app()->fdb;
        //验证参数
        if(!is_numeric($partial_repay_id)){
            $return_result['code'] = 2056;
            return $return_result;
        }
        $partialepayment = $model->createCommand("select * from ag_wx_partial_repayment where id = {$partial_repay_id}")->queryRow();
        if(empty($partialepayment)) {
            $return_result['info'] = "部分还款列表信息不存在";
            $return_result['code'] = 2098;
            return $return_result;
        }
        if($partialepayment['status'] != 1){
            $return_result['info'] = "只有待审核状态才能通过";
            $return_result['code'] = 2102;
            return $return_result;
        }
        //校验计划还款时间
        $today_midnight = strtotime("midnight");
        if ($partialepayment['pay_plan_time'] < $today_midnight) {
            $return_result['info'] = "计划还款时间必须大于等于今日凌晨";
            $return_result['code'] = 2123;
            return $return_result;
        }
        //校验还款凭证是否存在
        if (empty($partialepayment['proof_url'])) {
            $return_result['info'] = "请上传还款凭证";
            $return_result['code'] = 2125;
            return $return_result;
        }
        $partialepayDetail = $model->createCommand("select sum(repay_money) as repay_money,status,count(*) as number  from ag_wx_partial_repay_detail where partial_repay_id = $partial_repay_id group by status")->queryAll();
        if(empty($partialepayDetail)){
            $return_result['info'] = "部分还款详情不存在";
            $return_result['code'] = 2104;
            return $return_result;
        }
        //校验还款总额
        $total_repayment = array_sum(ItzUtil::array_column($partialepayDetail,"repay_money"));
        if($total_repayment != $partialepayment['total_repayment']){
            $return_result['info'] = "部分还款总额不一致";
            $return_result['code'] = 2105;
            return $return_result;
        }
        foreach($partialepayDetail as $key => $val){
            if($val['status'] == 1){
                //校验成功金额合计
                if($val['repay_money'] != $partialepayment['total_successful_amount']){
                    $return_result['info'] = "成功金额合计不一致";
                    $return_result['code'] = 2106;
                    return $return_result;
                }
                //校验导入成功条数
                if($val['number'] != $partialepayment['success_number']){
                    $return_result['info'] = "导入成功条数不一致";
                    $return_result['code'] = 2107;
                    return $return_result;
                }
            }
            if($val['status'] == 2){
                //校验失败金额合计
                if($val['repay_money'] != $partialepayment['total_fail_amount']){
                    $return_result['info'] = "失败金额合计不一致";
                    $return_result['code'] = 2108;
                    return $return_result;
                }
                //校验导入失败条数
                if($val['number'] != $partialepayment['fail_number']){
                    $return_result['info'] = "导入失败条数不一致";
                    $return_result['code'] = 2109;
                    return $return_result;
                }
            }
        }

        $partialepaydInfo = $model->createCommand("SELECT * FROM ag_wx_partial_repay_detail WHERE partial_repay_id = $partial_repay_id AND status = 1 ")->queryAll();
        foreach($partialepaydInfo as $key => $value){
            //导入成功单笔验证
           $checkData = $this->checkRepayDetail($value,$partialepayment['pay_plan_time']);
            if($checkData['code'] != 0){
                //更新错误原因和status
                $updateSql = ItzUtil::get_update_db_sql("ag_wx_partial_repay_detail",["status" => 2, 'remark' => $checkData['info']]," id = {$value['id']}");
                $res = $model->createCommand($updateSql)->execute();
                if (!$res) {
                    $return_result['info'] = "部分还款详情更新失败";
                    $return_result['code'] = 2111;
                    return $return_result;
                }
            }
        }
        return $return_result;
    }

    /**
     * 审核通过验证
     * $partial_repayment_id 部分还款列表ID
     */
    public function checkPHexamine($partial_repay_id)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $model = Yii::app()->phdb;
        //验证参数
        if(!is_numeric($partial_repay_id)){
            $return_result['code'] = 2056;
            return $return_result;
        }
        $partialepayment = $model->createCommand("select * from ag_wx_partial_repayment where id = {$partial_repay_id}")->queryRow();
        if(empty($partialepayment)) {
            $return_result['info'] = "部分还款列表信息不存在";
            $return_result['code'] = 2098;
            return $return_result;
        }
        if($partialepayment['status'] != 1){
            $return_result['info'] = "只有待审核状态才能通过";
            $return_result['code'] = 2102;
            return $return_result;
        }
        //校验计划还款时间
        $today_midnight = strtotime("midnight");
        if ($partialepayment['pay_plan_time'] < $today_midnight) {
            $return_result['info'] = "计划还款时间必须大于等于今日凌晨";
            $return_result['code'] = 2123;
            return $return_result;
        }
        //校验还款凭证是否存在
        if (empty($partialepayment['proof_url'])) {
            $return_result['info'] = "请上传还款凭证";
            $return_result['code'] = 2125;
            return $return_result;
        }
        $partialepayDetail = $model->createCommand("select sum(repay_money) as repay_money,status,count(*) as number  from ag_wx_partial_repay_detail where partial_repay_id = $partial_repay_id group by status")->queryAll();
        if(empty($partialepayDetail)){
            $return_result['info'] = "部分还款详情不存在";
            $return_result['code'] = 2104;
            return $return_result;
        }
        //校验还款总额
        $total_repayment = array_sum(ItzUtil::array_column($partialepayDetail,"repay_money"));
        if($total_repayment != $partialepayment['total_repayment']){
            $return_result['info'] = "部分还款总额不一致";
            $return_result['code'] = 2105;
            return $return_result;
        }
        foreach($partialepayDetail as $key => $val){
            if($val['status'] == 1){
                //校验成功金额合计
                if($val['repay_money'] != $partialepayment['total_successful_amount']){
                    $return_result['info'] = "成功金额合计不一致";
                    $return_result['code'] = 2106;
                    return $return_result;
                }
                //校验导入成功条数
                if($val['number'] != $partialepayment['success_number']){
                    $return_result['info'] = "导入成功条数不一致";
                    $return_result['code'] = 2107;
                    return $return_result;
                }
            }
            if($val['status'] == 2){
                //校验失败金额合计
                if($val['repay_money'] != $partialepayment['total_fail_amount']){
                    $return_result['info'] = "失败金额合计不一致";
                    $return_result['code'] = 2108;
                    return $return_result;
                }
                //校验导入失败条数
                if($val['number'] != $partialepayment['fail_number']){
                    $return_result['info'] = "导入失败条数不一致";
                    $return_result['code'] = 2109;
                    return $return_result;
                }
            }
        }

        $partialepaydInfo = $model->createCommand("SELECT * FROM ag_wx_partial_repay_detail WHERE partial_repay_id = $partial_repay_id AND status = 1 ")->queryAll();
        foreach($partialepaydInfo as $key => $value){
            //导入成功单笔验证
           $checkData = $this->checkPHRepayDetail($value,$partialepayment['pay_plan_time']);
            if($checkData['code'] != 0){
                //更新错误原因和status
                $updateSql = ItzUtil::get_update_db_sql("ag_wx_partial_repay_detail",["status" => 2, 'remark' => $checkData['info']]," id = {$value['id']}");
                $res = $model->createCommand($updateSql)->execute();
                if (!$res) {
                    $return_result['info'] = "部分还款详情更新失败";
                    $return_result['code'] = 2111;
                    return $return_result;
                }
            }
        }
        return $return_result;
    }

    public function checkXFexamine($partial_repay_id)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $model = Yii::app()->offlinedb;
        //验证参数
        if(!is_numeric($partial_repay_id)){
            $return_result['code'] = 2056;
            return $return_result;
        }
        $partialepayment = $model->createCommand("select * from offline_partial_repay where id = {$partial_repay_id}")->queryRow();
        if(empty($partialepayment)) {
            $return_result['info'] = "部分还款列表信息不存在";
            $return_result['code'] = 2098;
            return $return_result;
        }
        if($partialepayment['status'] != 1){
            $return_result['info'] = "只有待审核状态才能通过";
            $return_result['code'] = 2102;
            return $return_result;
        }
        //校验计划还款时间
        $today_midnight = strtotime("midnight");
        if ($partialepayment['pay_plan_time'] < $today_midnight) {
            $return_result['info'] = "计划还款时间必须大于等于今日凌晨";
            $return_result['code'] = 2123;
            return $return_result;
        }
        //校验还款凭证是否存在
        if (empty($partialepayment['proof_url'])) {
            $return_result['info'] = "请上传还款凭证";
            $return_result['code'] = 2125;
            return $return_result;
        }
        $partialepayDetail = $model->createCommand("select sum(repay_money) as repay_money,status,count(*) as number  from offline_partial_repay_detail where partial_repay_id = $partial_repay_id group by status")->queryAll();
        if(empty($partialepayDetail)){
            $return_result['info'] = "部分还款详情不存在";
            $return_result['code'] = 2104;
            return $return_result;
        }
        //校验还款总额
        $total_repayment = array_sum(ItzUtil::array_column($partialepayDetail,"repay_money"));
        if($total_repayment != $partialepayment['total_repayment']){
            $return_result['info'] = "部分还款总额不一致";
            $return_result['code'] = 2105;
            return $return_result;
        }
        foreach($partialepayDetail as $key => $val){
            if($val['status'] == 1){
                //校验成功金额合计
                if($val['repay_money'] != $partialepayment['total_successful_amount']){
                    $return_result['info'] = "成功金额合计不一致";
                    $return_result['code'] = 2106;
                    return $return_result;
                }
                //校验导入成功条数
                if($val['number'] != $partialepayment['success_number']){
                    $return_result['info'] = "导入成功条数不一致";
                    $return_result['code'] = 2107;
                    return $return_result;
                }
            }
            if($val['status'] == 2){
                //校验失败金额合计
                if($val['repay_money'] != $partialepayment['total_fail_amount']){
                    $return_result['info'] = "失败金额合计不一致";
                    $return_result['code'] = 2108;
                    return $return_result;
                }
                //校验导入失败条数
                if($val['number'] != $partialepayment['fail_number']){
                    $return_result['info'] = "导入失败条数不一致";
                    $return_result['code'] = 2109;
                    return $return_result;
                }
            }
        }

        $partialepaydInfo = $model->createCommand("SELECT * FROM offline_partial_repay_detail WHERE partial_repay_id = $partial_repay_id AND status = 1 ")->queryAll();
        foreach($partialepaydInfo as $key => $value){
            //导入成功单笔验证
           $checkData = $this->checkXFRepayDetail($value,$partialepayment['pay_plan_time']);
            if($checkData['code'] != 0){
                //更新错误原因和status
                $updateSql = ItzUtil::get_update_db_sql("offline_partial_repay_detail",["status" => 2, 'remark' => $checkData['info']]," id = {$value['id']}");
                $res = $model->createCommand($updateSql)->execute();
                if (!$res) {
                    $return_result['info'] = "部分还款详情更新失败";
                    $return_result['code'] = 2111;
                    return $return_result;
                }
            }
        }
        return $return_result;
    }

    /**
     * 单笔验证还款详情
     * @param array $data
     * @param array $pay_plan_time 计划还款时间
     * @return array
     */
    public function checkRepayDetail($data = array(),$pay_plan_time)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $model = Yii::app()->fdb;
        $deal_loan_id = $data['deal_loan_id'];
        $user_id = $data['user_id'];
        $repay_money = $data['repay_money'];
        $end_time = $data['end_time'];
        $name = $data['name'];
        $deal_id = $data['deal_id'];
        //验证参数
        if(!is_numeric($deal_loan_id) || !is_numeric($user_id) || !is_numeric($repay_money)){
            $return_result['info'] = "参数有误";
            $return_result['code'] = 2056;
            return $return_result;
        }
        if(empty($deal_loan_id) && !is_numeric($deal_loan_id)){
            $return_result['info'] = "没有匹配的投资记录";
            $return_result['code'] = 2082;
            return $return_result;
        }
        if(empty($user_id) && !is_numeric($user_id)){
            $return_result['info'] = "用户ID不能为空";
            $return_result['code'] = 2057;
            return $return_result;
        }
        if(empty($deal_id) && !is_numeric($deal_id)){
            $return_result['info'] = "项目ID输入错误";
            $return_result['code'] = 2114;
            return $return_result;
        }
        if(empty($name)){
            $return_result['info'] = "项目名称输入错误";
            $return_result['code'] = 2115;
            return $return_result;
        }
        if(empty($repay_money)){
            $return_result['info'] = "还款金额不能为空";
            $return_result['code'] = 2116;
            return $return_result;
        }
        //校验项目ID
        $dealInfo = $model->createCommand("SELECT id from firstp2p_deal WHERE name = '{$name}' AND id = {$deal_id} AND deal_status = 4")->queryRow();
        if(empty($dealInfo)){
            $return_result['info'] = "借款信息不存在";
            $return_result['code'] = 2110;
            return $return_result;
        }
        //兼容等额本息还款方式
        $sql = "SELECT loanrepay.loan_user_id,loanrepay.deal_loan_id,loanrepay.deal_id,sum(loanrepay.money) as repay_money,max(loanrepay.time) as time,dealload.wait_capital,dealload.debt_status,dealload.black_status FROM firstp2p_deal as deal
                LEFT JOIN firstp2p_deal_load as dealload ON deal.id = dealload.deal_id
                LEFT JOIN firstp2p_deal_loan_repay loanrepay ON dealload.id = loanrepay.deal_loan_id
                WHERE dealload.user_id = $user_id AND loanrepay.deal_loan_id = $deal_loan_id
                AND dealload.deal_id = {$dealInfo['id']} AND loanrepay.status = 0 AND loanrepay.type = 1 AND loanrepay.time <= '{$end_time}' 
                group by loanrepay.deal_loan_id ";
        $partialepaydInfo = $model->createCommand($sql)->queryRow();
        if(empty($partialepaydInfo['loan_user_id'])){
            $return_result['info'] = "用户信息不存在";
            $return_result['code'] = 2110;
            return $return_result;
        }
        if(empty($partialepaydInfo['deal_loan_id'])){
            $return_result['info'] = "没有匹配的投资记录";
            $return_result['code'] = 2082;
            return $return_result;
        }
        if($partialepaydInfo['time'] != $end_time){
            $return_result['info'] = "计划回款时间与还款到期时间不一致";
            $return_result['code'] = 2112;
            return $return_result;
        }
        if(bccomp($partialepaydInfo['repay_money'],0,2) != 1 || bccomp($repay_money,0,2) != 1){
            $return_result['info'] = "卖家待还本金不可为负数";
            $return_result['code'] = 2030;
            return $return_result;
        }

        //仅校验(1:待审核2:审核已通过)，还款状态未还，导入状态成功
        $sum_repay_money_sql = " select sum(d.repay_money) from ag_wx_partial_repay_detail d 
                                  left join ag_wx_partial_repayment r on d.partial_repay_id=r.id 
                                  where d.deal_loan_id = {$deal_loan_id} and d.user_id = $user_id and d.deal_id = $deal_id and d.status = 1
                                  and d.repay_status=0 and r.status in (1,2)";
        //所有导入成功的还款金额之和
        $repay_money = $model->createCommand($sum_repay_money_sql)->queryScalar();
        if(FunctionUtil::float_bigger($repay_money, $partialepaydInfo['repay_money'], 3)){
            Yii::log("checkRepayDetail repay_money:$repay_money,p_repay_money:{$partialepaydInfo['repay_money']}");
            $return_result['info'] = "还款金额错误，不可大于待还本金";
            $return_result['code'] = 2117;
            return $return_result;
        }
        //校验是否有兑换中的债权
        $dataArr = ["type" => 1,"repay_status" => 2, "user_id" => $user_id, "deal_id" => $dealInfo['id'],"tender_id" => $deal_loan_id];
        $checkDebtExLog = AddpaymentService::getInstance()->checkDebtExchangeLog($dataArr);
        if($checkDebtExLog['code'] != 0){
            $return_result['code'] = $checkDebtExLog['code'];
            $return_result['info'] = $checkDebtExLog['info'];
            return $return_result;
        }
        $checkRepaymentPlan = $this->checkRepaymentPlan(['deal_id' => $dealInfo['id'],'user_id' => $user_id, 'deal_loan_id' => $deal_loan_id]);
        if($checkRepaymentPlan['code'] != 0){
            $return_result['code'] = $checkRepaymentPlan['code'];
            $return_result['info'] = $checkRepaymentPlan['info'];
            return $return_result;
        }
        return $return_result;
    }

    /**
     * 单笔验证还款详情
     * @param array $data
     * @param array $pay_plan_time 计划还款时间
     * @return array
     */
    public function checkPHRepayDetail($data = array(),$pay_plan_time)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $model = Yii::app()->phdb;
        $deal_loan_id = $data['deal_loan_id'];
        $user_id = $data['user_id'];
        $repay_money = $data['repay_money'];
        $end_time = $data['end_time'];
        $name = $data['name'];
        $deal_id = $data['deal_id'];
        //验证参数
        if(!is_numeric($deal_loan_id) || !is_numeric($user_id) || !is_numeric($repay_money)){
            $return_result['info'] = "参数有误";
            $return_result['code'] = 2056;
            return $return_result;
        }
        if(empty($deal_loan_id) && !is_numeric($deal_loan_id)){
            $return_result['info'] = "没有匹配的投资记录";
            $return_result['code'] = 2082;
            return $return_result;
        }
        if(empty($user_id) && !is_numeric($user_id)){
            $return_result['info'] = "用户ID不能为空";
            $return_result['code'] = 2057;
            return $return_result;
        }
        if(empty($deal_id) && !is_numeric($deal_id)){
            $return_result['info'] = "项目ID输入错误";
            $return_result['code'] = 2114;
            return $return_result;
        }
        if(empty($name)){
            $return_result['info'] = "项目名称输入错误";
            $return_result['code'] = 2115;
            return $return_result;
        }
        if(empty($repay_money)){
            $return_result['info'] = "还款金额不能为空";
            $return_result['code'] = 2116;
            return $return_result;
        }
        //校验项目ID
        $dealInfo = $model->createCommand("SELECT id from firstp2p_deal WHERE name = '{$name}' AND id = {$deal_id} AND deal_status = 4")->queryRow();
        if(empty($dealInfo)){
            $return_result['info'] = "借款信息不存在";
            $return_result['code'] = 2110;
            return $return_result;
        }
		//兼容等额本息
        $sql = "SELECT loanrepay.loan_user_id,loanrepay.deal_loan_id,loanrepay.deal_id,sum(loanrepay.money) as repay_money,max(loanrepay.time) as time,dealload.wait_capital,dealload.debt_status,dealload.black_status FROM firstp2p_deal as deal
                LEFT JOIN firstp2p_deal_load as dealload ON deal.id = dealload.deal_id
                LEFT JOIN firstp2p_deal_loan_repay loanrepay ON dealload.id = loanrepay.deal_loan_id
                WHERE dealload.user_id = $user_id AND loanrepay.deal_loan_id = $deal_loan_id
                AND dealload.deal_id = {$dealInfo['id']} AND loanrepay.status = 0 AND loanrepay.type = 1 AND loanrepay.time <= '{$end_time}' 
                group by loanrepay.deal_loan_id ";
        $partialepaydInfo = $model->createCommand($sql)->queryRow();
        if(empty($partialepaydInfo['loan_user_id'])){
            $return_result['info'] = "用户信息不存在";
            $return_result['code'] = 2110;
            return $return_result;
        }
        if(empty($partialepaydInfo['deal_loan_id'])){
            $return_result['info'] = "没有匹配的投资记录";
            $return_result['code'] = 2082;
            return $return_result;
        }
        if($partialepaydInfo['time'] != $end_time){
            $return_result['info'] = "计划回款时间与还款到期时间不一致";
            $return_result['code'] = 2112;
            return $return_result;
        }
        if(bccomp($partialepaydInfo['repay_money'],0,2) != 1 || bccomp($repay_money,0,2) != 1){
            $return_result['info'] = "卖家待还本金不可为负数";
            $return_result['code'] = 2030;
            return $return_result;
        }

        //仅校验(1:待审核2:审核已通过)，还款状态未还，导入状态成功
        $sum_repay_money_sql = " select sum(d.repay_money) from ag_wx_partial_repay_detail d 
                                  left join ag_wx_partial_repayment r on d.partial_repay_id=r.id 
                                  where d.deal_loan_id = {$deal_loan_id} and d.user_id = $user_id and d.deal_id = $deal_id and d.status = 1
                                  and d.repay_status=0 and r.status in (1,2)";
        //所有导入成功的还款金额之和
        $repay_money = $model->createCommand($sum_repay_money_sql)->queryScalar();
        if(FunctionUtil::float_bigger($repay_money, $partialepaydInfo['repay_money'], 3)){
            Yii::log("checkPHRepayDetail repay_money:$repay_money,p_repay_money:{$partialepaydInfo['repay_money']}");
            $return_result['info'] = "还款金额错误，不可大于待还本金";
            $return_result['code'] = 2117;
            return $return_result;
        }
        //校验是否有兑换中的债权
        $dataArr = ["type" => 2,"repay_status" => 2, "user_id" => $user_id, "deal_id" => $dealInfo['id'],"tender_id" => $deal_loan_id];
        $checkDebtExLog = AddpaymentService::getInstance()->checkDebtExchangeLog($dataArr);
        if($checkDebtExLog['code'] != 0){
            $return_result['code'] = $checkDebtExLog['code'];
            $return_result['info'] = $checkDebtExLog['info'];
            return $return_result;
        }
        $checkPHRepaymentPlan = $this->checkPHRepaymentPlan(['deal_id' => $dealInfo['id'],'user_id' => $user_id, 'deal_loan_id' => $deal_loan_id]);
        if($checkPHRepaymentPlan['code'] != 0){
            $return_result['code'] = $checkPHRepaymentPlan['code'];
            $return_result['info'] = $checkPHRepaymentPlan['info'];
            return $return_result;
        }
        return $return_result;
    }

    public function checkXFRepayDetail($data = array(),$pay_plan_time)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $model = Yii::app()->offlinedb;
        $deal_loan_id = $data['deal_loan_id'];
        $user_id = $data['user_id'];
        $repay_money = $data['repay_money'];
        $end_time = $data['end_time'];
        $name = $data['name'];
        $deal_id = $data['deal_id'];
        //验证参数
        if(!is_numeric($deal_loan_id) || !is_numeric($user_id) || !is_numeric($repay_money)){
            $return_result['info'] = "参数有误";
            $return_result['code'] = 2056;
            return $return_result;
        }
        if(empty($deal_loan_id) && !is_numeric($deal_loan_id)){
            $return_result['info'] = "没有匹配的投资记录";
            $return_result['code'] = 2082;
            return $return_result;
        }
        if(empty($user_id) && !is_numeric($user_id)){
            $return_result['info'] = "用户ID不能为空";
            $return_result['code'] = 2057;
            return $return_result;
        }
        if(empty($deal_id) && !is_numeric($deal_id)){
            $return_result['info'] = "项目ID输入错误";
            $return_result['code'] = 2114;
            return $return_result;
        }
        if(empty($name)){
            $return_result['info'] = "项目名称输入错误";
            $return_result['code'] = 2115;
            return $return_result;
        }
        if(empty($repay_money)){
            $return_result['info'] = "还款金额不能为空";
            $return_result['code'] = 2116;
            return $return_result;
        }
        //校验项目ID
        $dealInfo = $model->createCommand("SELECT id from offline_deal WHERE name = '{$name}' AND id = {$deal_id} AND deal_status = 4")->queryRow();
        if(empty($dealInfo)){
            $return_result['info'] = "借款信息不存在";
            $return_result['code'] = 2110;
            return $return_result;
        }
        //兼容等额本息还款方式
        $sql = "SELECT loanrepay.loan_user_id,loanrepay.deal_loan_id,loanrepay.deal_id,sum(loanrepay.money) as repay_money,max(loanrepay.time) as time,dealload.wait_capital,dealload.debt_status,dealload.black_status FROM offline_deal as deal
                LEFT JOIN offline_deal_load as dealload ON deal.id = dealload.deal_id
                LEFT JOIN offline_deal_loan_repay loanrepay ON dealload.id = loanrepay.deal_loan_id
                WHERE dealload.user_id = $user_id AND loanrepay.deal_loan_id = $deal_loan_id
                AND dealload.deal_id = {$dealInfo['id']} AND loanrepay.status = 0 AND loanrepay.type = 1 AND loanrepay.time <= '{$end_time}' 
                group by loanrepay.deal_loan_id ";
        $partialepaydInfo = $model->createCommand($sql)->queryRow();
        if(empty($partialepaydInfo['loan_user_id'])){
            $return_result['info'] = "用户信息不存在";
            $return_result['code'] = 2110;
            return $return_result;
        }
        if(empty($partialepaydInfo['deal_loan_id'])){
            $return_result['info'] = "没有匹配的投资记录";
            $return_result['code'] = 2082;
            return $return_result;
        }
        if($partialepaydInfo['time'] != $end_time){
            $return_result['info'] = "计划回款时间与还款到期时间不一致";
            $return_result['code'] = 2112;
            return $return_result;
        }
        if(bccomp($partialepaydInfo['repay_money'],0,2) != 1 || bccomp($repay_money,0,2) != 1){
            $return_result['info'] = "卖家待还本金不可为负数";
            $return_result['code'] = 2030;
            return $return_result;
        }

        //仅校验(1:待审核2:审核已通过)，还款状态未还，导入状态成功
        $sum_repay_money_sql = " select sum(d.repay_money) from offline_partial_repay_detail d 
                                  left join offline_partial_repay r on d.partial_repay_id=r.id 
                                  where d.deal_loan_id = {$deal_loan_id} and d.user_id = $user_id and d.deal_id = $deal_id and d.status = 1
                                  and d.repay_status=0 and r.status in (1,2)";
        //所有导入成功的还款金额之和
        $repay_money = $model->createCommand($sum_repay_money_sql)->queryScalar();
        if(FunctionUtil::float_bigger($repay_money, $partialepaydInfo['repay_money'], 3)){
            Yii::log("checkRepayDetail repay_money:$repay_money,p_repay_money:{$partialepaydInfo['repay_money']}");
            $return_result['info'] = "还款金额错误，不可大于待还本金";
            $return_result['code'] = 2117;
            return $return_result;
        }
        //校验是否有兑换中的债权
        $dataArr = ["repay_status" => 2, "user_id" => $user_id, "deal_id" => $dealInfo['id'],"tender_id" => $deal_loan_id];
        $checkDebtExLog = $this->checkXFDebtExchangeLog($dataArr);
        if($checkDebtExLog['code'] != 0){
            $return_result['code'] = $checkDebtExLog['code'];
            $return_result['info'] = $checkDebtExLog['info'];
            return $return_result;
        }
        // $checkRepaymentPlan = $this->checkRepaymentPlan(['deal_id' => $dealInfo['id'],'user_id' => $user_id, 'deal_loan_id' => $deal_loan_id]);
        // if($checkRepaymentPlan['code'] != 0){
        //     $return_result['code'] = $checkRepaymentPlan['code'];
        //     $return_result['info'] = $checkRepaymentPlan['info'];
        //     return $return_result;
        // }
        return $return_result;
    }

    public function checkXFDebtExchangeLog($data)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $user_id = $data['user_id'];
        $tender_id = $data['tender_id'];
        $deal_id = $data['deal_id'];
        $repay_type = $data['repay_type'];
        $repay_status = isset($data['repay_status']) ? $data['repay_status'] : 1;
        if($repay_status == 1){
            if(!in_array($repay_type,[1,2]) || !is_numeric($repay_type)){
                $return_result['info'] = '参数有误';
                $return_result['code'] = 2000;
                return $return_result;
            }
        }
        if(empty($deal_id) || !is_numeric($deal_id) || !in_array($repay_status,[1,2])){
            $return_result['info'] = '参数有误';
            $return_result['code'] = 2000;
            return $return_result;
        }
        if($repay_status == 1){
            //特殊还款验证
            if($repay_type == 2){
                //出借人ID与投资记录ID二选一必填
                if (empty($user_id) && empty($tender_id)) {
                    $return_result['info'] = "出借人ID与投资记录ID二选一必填";
                    $return_result['code'] = 2017;
                    return $return_result;
                }
            }
        }
        $where = "borrow_id = $deal_id";
        if(!empty($user_id)){
            $where .= " and user_id in ($user_id)";
        }
        if(!empty($tender_id)){
            $where .= " and tender_id in ($tender_id)";
        }
        $exchangeLogInfo = Yii::app()->offlinedb->createCommand("select * from offline_debt_exchange_log where $where and status = 1")->queryRow();
        if(!empty($exchangeLogInfo)){
            $return_result['info'] = '您有兑换中的债权，无法添加';
            $return_result['code'] = 2023;
            return $return_result;
        }
        $return_result['info'] = '校验成功';
        return $return_result;
    }

    /**
     * @param $data[
     * deal_id 项目id
     * user_id 用户id
     * deal_loan_id 投资记录id
     * ]
     * @return mixed
     */
    public function checkRepaymentPlan($data)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $deal_id = $data['deal_id'];
        $user_id = $data['user_id'];
        $deal_loan_id = $data['deal_loan_id'];
        $repayplan_info = Yii::app()->fdb->createCommand("select * from ag_wx_repayment_plan WHERE deal_id = {$deal_id} and status IN(0,1,2)")->queryRow();
        if (!empty($repayplan_info)) {
            //常规还款
            if(empty($repayplan_info['loan_user_id']) && empty($repayplan_info['deal_loan_id'])){
                $return_result['code'] = 2121;
                $return_result['info'] = '此投资记录已经处于还款中或还款审核中，无法添加';
                return $return_result;
            }
            //特殊还款loan_user_id
            if(!empty($repayplan_info['loan_user_id'])){
                if(ItzUtil::intersec($user_id, $repayplan_info['loan_user_id'])){
                    $return_result['code'] = 2121;
                    $return_result['info'] = '此投资记录已经处于还款中或还款审核中，无法添加';
                    return $return_result;
                }
            }
            //特殊还款deal_loan_id
            if(!empty($repayplan_info['deal_loan_id'])){
                if(ItzUtil::intersec($deal_loan_id, $repayplan_info['deal_loan_id'])){
                    $return_result['code'] = 2121;
                    $return_result['info'] = '此投资记录已经处于还款中或还款审核中，无法添加';
                    return $return_result;
                }
            }
        }
        return $return_result;
    }

    /**
     * @param $data[
     * deal_id 项目id
     * user_id 用户id
     * deal_loan_id 投资记录id
     * ]
     * @return mixed
     */
    public function checkPHRepaymentPlan($data)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $deal_id = $data['deal_id'];
        $user_id = $data['user_id'];
        $deal_loan_id = $data['deal_loan_id'];
        $repayplan_info = Yii::app()->phdb->createCommand("select * from ag_wx_repayment_plan WHERE deal_id = {$deal_id} and status IN(0,1,2)")->queryRow();
        if (!empty($repayplan_info)) {
            //常规还款
            if(empty($repayplan_info['loan_user_id']) && empty($repayplan_info['deal_loan_id'])){
                $return_result['code'] = 2121;
                $return_result['info'] = '此投资记录已经处于还款中或还款审核中，无法添加';
                return $return_result;
            }
            //特殊还款loan_user_id
            if(!empty($repayplan_info['loan_user_id'])){
                if(ItzUtil::intersec($user_id, $repayplan_info['loan_user_id'])){
                    $return_result['code'] = 2121;
                    $return_result['info'] = '此投资记录已经处于还款中或还款审核中，无法添加';
                    return $return_result;
                }
            }
            //特殊还款deal_loan_id
            if(!empty($repayplan_info['deal_loan_id'])){
                if(ItzUtil::intersec($deal_loan_id, $repayplan_info['deal_loan_id'])){
                    $return_result['code'] = 2121;
                    $return_result['info'] = '此投资记录已经处于还款中或还款审核中，无法添加';
                    return $return_result;
                }
            }
        }
        return $return_result;
    }

    /**
     * 导出execle
     * @param array $data[
     * $letter array('A','B','C','D','E','F','G','H')
     * $tableheader array('序号','借款标题','到期日','投资记录ID','用户ID','还款金额','导入状态','失败原因')
     * $dataArr 填充数据
     * ]
     * @param $filename
     * @return array
     */
    public function exportList($data = array(), $filename)
    {
        Yii::import("application.extensions.phpexcel.*");
        //创建对象
        $excel = new PHPExcel();
        //Excel表格式
        $letter = $data['letter'];
        //表头数组
        $tableheader = $data['tableheader'];
        //填充数据
        $dataArr = $data['dataArr'];
        //填充表头信息
        for($i = 0;$i < count($tableheader);$i++) {
            $excel->getActiveSheet()->setCellValue("$letter[$i]1","$tableheader[$i]");
        }
        //填充表格信息
        for ($i = 2;$i <= count($dataArr) + 1;$i++) {
            $j = 0;
            foreach ($dataArr[$i - 2] as $key=>$value) {
                $excel->getActiveSheet()->setCellValue("$letter[$j]$i","$value");
                $j++;
            }
        }
        //创建Excel输入对象
        $write = new PHPExcel_Writer_Excel5($excel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename='.$filename);
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');
    }
}