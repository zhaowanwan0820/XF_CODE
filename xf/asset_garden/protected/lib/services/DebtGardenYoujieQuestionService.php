<?php

/**
 *
 */
class DebtGardenYoujieQuestionService extends ItzInstanceService
{
    const TempDir = '/tmp/youjiecontractV2/';
    protected $logFile = 'DebtGardenYoujie';
    private $table_prefix = 'firstp2p_';
    /**
     * @param $info [
     * qstn_id 问卷id
     * answerArr 答案 [{"qst_id":1,"answer":2},{"qst_id":2,"answer":2}]
     * @return array
     */
    public function GetQuestionnaire($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $type = $info['type'];
        $user_id = $info['user_id'];
        //验证登录
        if (empty($user_id)) {
            $return_result['code'] = 4007;
            return $return_result;
        }
        if (!in_array($type, [1, 2, 3]) || !is_numeric($type)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        $fdbModel = Yii::app()->db;
        $ret = '';
        $AgQnrQuestionnaireInfo = $fdbModel->createCommand("select * from ag_qnr_questionnaire where type = $type and status = 1")->queryRow();
        if (empty($AgQnrQuestionnaireInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        $AgQnrQuestionInfo = $fdbModel->createCommand("select * from ag_qnr_question where qstn_id = {$AgQnrQuestionnaireInfo['id']} and status = 1")->queryAll();
        if (empty($AgQnrQuestionInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        $qst_ids = implode(',', ItzUtil::array_column($AgQnrQuestionInfo, 'id'));
        $qnrOptionInfo = $fdbModel->createCommand("select * from ag_qnr_option where qst_id IN({$qst_ids}) and status = 1")->queryAll();
        //排序选项数据
        foreach ($qnrOptionInfo as $k => $v) {
            $qnrOptionSortInfo[$v['qst_id']][] = array(
                'qto_id' => $v['id'],
                'serial' => $v['serial'],
                'option' => $v['option'],
                'point' => $v['point'],
            );
        }
        foreach ($AgQnrQuestionInfo as $key => $val) {
            $ret[] = array(
                'qstn_id' => $AgQnrQuestionnaireInfo['id'],//问卷id
                'qst_id' => $val['id'],//题干id
                'question' => $val['question'],//题干
                'type' => $val['type'],//0:单选题,1:填空题,2:多选题
                'data' => $qnrOptionSortInfo[$val['id']],//选项
            );
        }
        $return_result['data'] = $ret;
        return $return_result;
    }

    /**
     * C1提交问卷接口
     * @param $info [
     * qstn_id 问卷id
     * answerArr 答案 [{"qto_id":1,"answer":2},{"qto_id":2,"answer":2}]
     * answer_time 答题时间s（按秒计算）
     * ]
     * @return array
     */
    public function SendQuestionnaire($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $qstn_id = $info['qstn_id'];
        $answerArr = $info['answerArr'];
        $answer_time = $info['answer_time'];
        $user_id = $info['user_id'];
        //验证登录
        if (empty($user_id)) {
            $return_result['code'] = 4007;
            return $return_result;
        }
        if (!is_numeric($qstn_id) || !is_numeric($answer_time)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        //验证answerArr数据
        if (!ItzUtil::checkJson($answerArr, 'qto_id') || !ItzUtil::checkJson($answerArr, 'qst_id') || empty($answerArr)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        $answerArr = json_decode($answerArr, true);

        $fdbModel = Yii::app()->db;
        $AgQnrQuestionnaireInfo = $fdbModel->createCommand("select * from ag_qnr_questionnaire where id = $qstn_id and status = 1 ")->queryRow();
        if (empty($AgQnrQuestionnaireInfo)) {
            $return_result['code'] = 2099;
            return $return_result;
        }
        //验证问卷是否限答一次
        $agQnrInfo = $fdbModel->createCommand("select count(*) as num,aqq.reply_num_type from ag_qnr_questionnaire aqq left join ag_investigation_answer aia on aqq.id = aia.qstn_id where aqq.id = $qstn_id and aqq.status = 1 and aia.user_id = $user_id")->queryRow();
        if ($agQnrInfo['reply_num_type'] == 1) {
            if ($agQnrInfo['num'] > 0) {
                $return_result['code'] = 2208;
                return $return_result;
            }
        }
        //验证提交题目与问卷类型
        $qstIds = ItzUtil::array_column($answerArr, 'qst_id');
        $questionInfo = $fdbModel->createCommand("select * from ag_qnr_question where qstn_id  = $qstn_id and status = 1")->queryAll();
        if (empty($questionInfo)) {
            $return_result['code'] = 2204;
            return $return_result;
        }

        $questionIds = ItzUtil::array_column($questionInfo, 'id');
        $diffArr = array_diff($qstIds, $questionIds);
        if (!empty($diffArr)) {
            $return_result['code'] = 2205;
            return $return_result;
        }
        //过滤掉填空题的答案
        foreach ($answerArr as $key => $val) {
            if (is_numeric($val['qto_id'])) {
                $answerIdsArr[] = $val;
            }
        }
        $qtoIds = ItzUtil::array_column($answerIdsArr, 'qto_id');
        $qto_ids = implode(",", $qtoIds);
        //计算C1用户答题分数入库
        $total_score = $fdbModel->createCommand("select sum(point) from ag_qnr_option where id in($qto_ids)")->queryScalar();
        $investigationLevelInfo = $fdbModel->createCommand("select * from ag_investigation_level where end_score >= {$total_score}")->queryRow();
        if (empty($investigationLevelInfo)) {
            $return_result['code'] = 2210;
            return $return_result;
        }
        $insertData = array(
            "user_id" => $user_id,
            "qstn_id" => $qstn_id,
            "total_score" => $total_score,
            "level_name" => $investigationLevelInfo['level_name'],
            "total_time" => $answer_time,
            "level_id" => $investigationLevelInfo['level_id'],
            "add_time" => time(),
        );

        //添加到调查问卷回答表
        $sql = ItzUtil::get_insert_db_sql('ag_investigation_answer', $insertData);
        $ret = $fdbModel->createCommand($sql)->execute();
        if (!$ret) {//添加失败
            $return_result['code'] = 2200;
            return $return_result;
        }
        $answer_id = $fdbModel->getLastInsertID();
        //验证提交答案与题目匹配
        $option = $fdbModel->createCommand("SELECT aqo.id as id,aqo.type,aqq.id as aqrn_id from ag_qnr_question aqq LEFT JOIN ag_qnr_option aqo on aqq.id = aqo.qst_id  where aqq.qstn_id = $qstn_id and aqq.status = 1 AND aqo.status = 1")->queryAll();
        if (empty($option)) {
            $return_result['code'] = 2206;
            return $return_result;
        }
        $optiontypeArr = ItzUtil::array_column($option, 'type', 'aqrn_id');
        foreach ($answerArr as $key => $value) {
            if ($optiontypeArr[$value['qst_id']] != 2) {
                //如果是非填空题
                $total = $fdbModel->createCommand("select count(*) from ag_qnr_question aqq
                    LEFT JOIN ag_qnr_option aqo ON aqq.id = aqo.qst_id where aqq.id = {$value['qst_id']}
                    and aqo.id = {$value['qto_id']} and aqq.status = 1 and aqo.status = 1")->queryScalar();
                if ($total == 0) {
                    $return_result['code'] = 2207;
                    return $return_result;
                }
            }
            $user_write = '';
            $answer = $value['qto_id'];
            //如果是填空题
            if ($optiontypeArr[$value['qst_id']] == 2) {
                $user_write = $value['qto_id'];
                $answer = 0;
            }
            $anInsertData[] = array(
                'user_id' => $user_id,
                'qstn_id' => $qstn_id,
                'qst_id' => $value['qst_id'],
                'answer' => $answer,
                'user_write' => $user_write,
                'answer_id' => $answer_id,
                'addtime' => time(),
            );
        }

        $sqlaqa = ItzUtil::get_all_insert_sql('ag_qnr_answer', array_keys($anInsertData[0]), $anInsertData);
        $res = $fdbModel->createCommand($sqlaqa)->execute();
        if (!$res) {//添加失败
            $return_result['code'] = 2200;
            return $return_result;
        }
        $returnData = array('level_id' => 0, 'level_name' => '');
        //如果是风险评测答卷更新用户表中风险等级
        if ($AgQnrQuestionnaireInfo['type'] == 1) {
            //判断用户状态
            $userLevelInfo = $fdbModel->createCommand("select * from firstp2p_user where id = $user_id")->queryRow();
            if (empty($userLevelInfo)) {
                $return_result['code'] = 1027;
                return $return_result;
            }
            //帐户状态，1为有效果，0为无效
            if ($userLevelInfo['is_effect'] == 0) {
                $return_result['code'] = 1034;
                return $return_result;
            }
            //帐户已删除放入回收站，1为删除，0为未删除
            if ($userLevelInfo['is_delete'] == 1) {
                $return_result['code'] = 1035;
                return $return_result;
            }
            //用户第二次进行风险评测如果未更新level_id，直接返回数据
            $returnData = array('level_id' => $investigationLevelInfo['level_id'], 'level_name' => $investigationLevelInfo['level_name']);
            if (!empty($userLevelInfo['level_risk_id'])) {
                $fdbModel->createCommand("UPDATE firstp2p_user SET level_risk_id = {$investigationLevelInfo['level_id']} WHERE id = {$user_id} ")->execute();
                $return_result['data'] = $returnData;
                return $return_result;
            }
            $userInfo = $fdbModel->createCommand("UPDATE firstp2p_user SET level_risk_id = {$investigationLevelInfo['level_id']} WHERE id = {$user_id} ")->execute();
            if (!$userInfo) {
                $return_result['code'] = 2201;
                return $return_result;
            }
        }
        $return_result['data'] = $returnData;
        return $return_result;
    }
    /**
     * 普惠限制可查看的deal_id
     */
    public function addPhdbWhere()
    {
        $dealIds = Yii::app()->rcache->get("ph_deal_ids");
        if(!$dealIds){
            $model = Yii::app()->phdb;
            $sql = "select DISTINCT deal.id deal_id from firstp2p_deal deal  left join firstp2p_deal_agency b on deal.advisory_id = b.id
                left join firstp2p_deal_tag dt on deal.id = dt.deal_id
                where b.name not in('杭州大树网络技术有限公司','北京掌众金融信息服务有限公司') and dt.tag_id not in(42,44)";
            $dealinfo = $model->createCommand($sql)->queryAll();
            if(!empty($dealinfo)){
                $dealIds = implode(",",ItzUtil::array_column($dealinfo,"deal_id"));
                $redisData = Yii::app()->rcache->set("ph_deal_ids",$dealIds,86400 * 15);
                if(!$redisData){
                    Yii::log("ph_deal_ids set error","error");
                }
            }
            return '';
        }
        return $dealIds;
    }
    /**
     * 债转市场转让中的列表
     * @param $info
     * @return array
     */
    public function GetZqscDebtList($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        //尊享转让中的记录
        $fdbModel = Yii::app()->db;
        $limit = $info['limit'];
        $now = time();
        $retfdb = [];
        $retphdb = [];
        $offdb = [];
        $fdbcount = $fdbModel->createCommand("select count(*) from firstp2p_debt debt left join firstp2p_deal deal ON debt.borrow_id = deal.id
                                             where debt.status = 1 and debt.starttime <= {$now} and debt.endtime >= {$now} and debt.debt_src = 2 ")->queryScalar();
        if ($fdbcount > 0) {
            $debtInfo = $fdbModel->createCommand("select debt.*,deal.name from firstp2p_debt debt left join firstp2p_deal deal ON debt.borrow_id = deal.id
                                             where debt.status = 1 and debt.starttime <= {$now} and debt.endtime >= {$now} and debt.debt_src = 2 order by debt.discount desc limit $limit")->queryAll();
            foreach ($debtInfo as $key => $value) {
                $retfdb[] = array(
                    "debt_id" => $value['id'],
                    "name" => $value['name'],
                    "discount" => $value['discount'],
                    "money" => $value['money'],
                    "products" => 1,//尊享
                    "endtime" => $value['endtime'],
                    "transferprice" => round($value['discount']*$value['money']*0.1, 2),
                );
            }
        }
        //普惠供应链转让中的债转记录
        $phdbModel = Yii::app()->phdb;
//        $sqladd = '';
//        $dealIds = $this->addPhdbWhere();
//        if(!empty($dealIds)){
//            $sqladd = " and deal.id in($dealIds)";
//        }
        $sqladd = " and deal.product_class_type = 223";
        $phdbcount = $phdbModel->createCommand("select count(*) from firstp2p_debt debt left join firstp2p_deal deal ON debt.borrow_id = deal.id
                                             where debt.status = 1 and debt.debt_src = 2 and debt.starttime <= {$now} and debt.endtime >= {$now} $sqladd")->queryScalar();
        if ($phdbcount > 0) {
            $debtInfo = $phdbModel->createCommand("select debt.*,deal.name from firstp2p_debt debt left join firstp2p_deal deal ON debt.borrow_id = deal.id
                                             where debt.status = 1 and debt.debt_src = 2 and debt.starttime <= {$now} and debt.endtime >= {$now} $sqladd order by debt.discount desc limit $limit")->queryAll();
            foreach ($debtInfo as $key => $value) {
                $retphdb[] = array(
                    "debt_id" => $value['id'],
                    "name" => $value['name'],
                    "discount" => $value['discount'],
                    "products" => 2,//普惠供应链
                    "money" => $value['money'],
                    "endtime" => $value['endtime'],
                    "transferprice" => round($value['discount']*$value['money']*0.1, 2),
                );
            }
        }
        // 工场微金 智多新 交易所
        $offModel = Yii::app()->offlinedb;
        $offcount = $offModel->createCommand("select count(*) from offline_debt debt left join offline_deal deal ON debt.borrow_id = deal.id
                                             where debt.status = 1 and debt.starttime <= {$now} and debt.endtime >= {$now} and debt.debt_src = 2 ")->queryScalar();
        if ($offcount > 0) {
            $debtInfo = $offModel->createCommand("select debt.*,deal.name from offline_debt debt left join offline_deal deal ON debt.borrow_id = deal.id
                                             where debt.status = 1 and debt.starttime <= {$now} and debt.endtime >= {$now} and debt.debt_src = 2 order by debt.discount desc limit $limit")->queryAll();
            foreach ($debtInfo as $key => $value) {
                $offdb[] = array(
                    "debt_id" => $value['id'],
                    "name" => $value['name'],
                    "discount" => $value['discount'],
                    "money" => $value['money'],
                    "products" => $value['platform_id'],
                    "endtime" => $value['endtime'],
                    "transferprice" => round($value['discount']*$value['money']*0.1, 2),
                );
            }
        }
        $dataArr = array_merge($retfdb, $retphdb, $offdb);
        if (empty($dataArr)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        //根据字段last_name对数组$data进行降序排列
        $dataArr = ItzUtil::arraySort($dataArr, 'discount', 'desc');
        //截取数组
        $dataArr = array_slice($dataArr, 0, $limit);
        $result_data = array('count' => count($dataArr), 'page_count' => ceil(count($dataArr) / $limit), 'data' => $dataArr);
        $return_result['data'] = $result_data;
        return $return_result;
    }

    /**
     * 债转市场转让中的列表
     * @param $info
     * @return array
     */
    public function GetRgDebtList($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $products = $info['products'];
        $page = $info['page'];
        $limit = $info['limit'];
        $order = $info['order'];
        $field = $info['field'];
        $name = $info['name'];
        $user_id = $info['user_id'];
        if (!in_array($products, Yii::app()->c->xf_config['platform_type']) || !in_array($order, [1, 2]) || !in_array($field, [1, 2]) || empty($products)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        $sqladd = '';
        if ($products == 2) {
//            $dealIds = $this->addPhdbWhere();
//            if(!empty($dealIds)){
//                $sqladd = " and deal.id in($dealIds)";
//            }
            $sqladd = " and deal.product_class_type = 223";
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $sqladd = " and debt.platform_id = {$products} ";
        }
        if (!empty($name)) {
            $sqladd .= " and (deal.name='{$name}' or debt.serial_number='{$name}') ";
        }
        $now = time();
        // 尊享 普惠
        $count = $model->createCommand("select count(*) from {$this->table_prefix}debt debt left join {$this->table_prefix}deal deal ON debt.borrow_id = deal.id
                                        left join {$this->table_prefix}deal_load dload on debt.tender_id = dload.id where debt.status = 1 and
                                        debt.starttime <= {$now} and debt.endtime >= {$now} and debt.debt_src = 2 and dload.black_status = 1 {$sqladd}")->queryScalar();
        if (empty($count)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        if (!empty($field)) {
            if ($field == 1) {
                $field_name = 'debt.addtime';
            } elseif ($field == 2) {
                $field_name = 'debt.discount';
            }
        }
        if (!empty($order)) {
            if ($order == 1) {
                $order = " order by {$field_name} asc";
            } elseif ($order == 2) {
                if ($field_name == 'debt.discount') {
                    //折扣相等按时间倒序
                    $order = " order by {$field_name} desc,debt.addtime desc";
                } else {
                    $order = " order by {$field_name} desc";
                }
            }
        }
        if (!empty($limit)) {
            $pass = ($page - 1) * $limit;  //跳过数据
            $data_limit = "LIMIT {$pass},{$limit}";
        }
        $debtInfo = $model->createCommand("select debt.*,deal.name from {$this->table_prefix}debt debt left join {$this->table_prefix}deal deal ON debt.borrow_id = deal.id
                                               left join {$this->table_prefix}deal_load dload on debt.tender_id = dload.id where debt.status = 1 and debt.starttime <= {$now} and
                                               debt.endtime >= {$now}  and dload.black_status = 1 {$sqladd} {$order} {$data_limit}")->queryAll();

        foreach ($debtInfo as $key => $value) {
            $ret[] = array(
                "debt_id" => $value['id'],
                "name" => $value['name'],
                "discount" => $value['discount'],
                "products" => $products,
                "money" => $value['money'],
                "endtime" => $value['endtime'],
                "transferprice" => round($value['discount']*$value['money']*0.1, 2),
            );
        }
        $result_data = array('count' => $count, 'page_count' => ceil($count / $limit), 'data' => $ret);
        $return_result['data'] = $result_data;
        return $return_result;
    }

    /**
     * 可转让债权列表
     * @param $info
     * @return array
     */
    public function transferableDebtList($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $products = $info['products'];
        $page = $info['page'];
        $limit = $info['limit'];
        $user_id = $info['user_id'];
        if (!in_array($products, Yii::app()->c->xf_config['platform_type']) || !is_numeric($limit) || !is_numeric($page) || empty($products)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if ($limit > 100) {
            $return_result['code'] = 4003;
            return $return_result;
        }
        if (!empty($limit)) {
            $pass = ($page - 1) * $limit;  //跳过数据
            $data_limit = "LIMIT {$pass},{$limit}";
        }
        if (empty($user_id)) {
            $return_result['code'] = 4007;
            return $return_result;
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        $sqladd = '';
        if ($products == 2) {
//            $dealIds = $this->addPhdbWhere();
//            if(!empty($dealIds)){
//                $sqladd = " and deal.id in($dealIds)";
//            }
            $sqladd = " and deal.product_class_type = 223";
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $sqladd = " and dealload.platform_id = {$products} ";
        }

        //专区债权查询
        $purchase_id = $info['purchase_id'];
       // $area_ids = array_keys(Yii::app()->c->xf_config['area_list']);
        if($purchase_id > 0  && is_numeric($purchase_id)){
            $deal_ids = $this->getPurchaseDeal($purchase_id);
            if(!empty($deal_ids)){
                $sqladd .= " and dealload.deal_id in (".implode(',', $deal_ids).") ";
            }
        }
        $productArr = ["1" => "尊享", '2' => '普惠供应链', '3' => '工场微金', '4' => '智多新', '5' => '交易所'];
        //确权未加入黑名单无债权的数据
        // 尊享 普惠
        $sql = "select count(*) from {$this->table_prefix}deal_load dealload left join {$this->table_prefix}deal deal on dealload.deal_id = deal.id
                where  dealload.user_id = {$user_id} and  dealload.debt_status = 0 and dealload.status = 1 and dealload.xf_status =0 and dealload.wait_capital > 0 and dealload.black_status = 1  {$sqladd}";
        $count = $model->createCommand($sql)->queryScalar();
        if (empty($count)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        $sql = "select dealload.id,deal.name,dealload.wait_capital from {$this->table_prefix}deal_load dealload left join {$this->table_prefix}deal deal on dealload.deal_id = deal.id
                    where  dealload.user_id = {$user_id} and  dealload.debt_status = 0 and dealload.status = 1  and dealload.xf_status =0 and dealload.wait_capital > 0 and dealload.black_status = 1  {$sqladd} order by dealload.create_time desc {$data_limit}";
        $dealloadInfo = $model->createCommand($sql)->queryAll();
        if (empty($dealloadInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        $deal_load_ids = implode(',',ItzUtil::array_column($dealloadInfo,'id'));
        //查询debt中有债转中的
        $debtInfo = $model->createCommand("select count(*) as num,debt.tender_id from {$this->table_prefix}debt debt where debt.tender_id IN ($deal_load_ids) and debt.user_id = $user_id and debt.status = 1 group by debt.tender_id")->queryAll();
        $debtArr = ItzUtil::array_column($debtInfo,"num","tender_id");
        foreach ($dealloadInfo as $key => $value) {
            //待处理线下还款校验
            if(DebtService::getInstance()->checkTenderRepay($value['id'], $products)){
                $ret[] = array(
                    "deal_load_id" => $value['id'],//投资记录id
                    "product_class" => $productArr[$products],//项目类型
                    "name" => $value['name'],//项目名称
                    "wait_capital" => $value['wait_capital'],//待还本金
                    "products" => $products,//1:尊享2：普惠供应链
                    "status" => !empty($debtArr[$value['id']]) && $debtArr[$value['id']] > 0 ? 2 : 1,//1:可转让2：不可转让
                );
            }
        }
        $result_data = array('count' => $count, 'page_count' => ceil($count / $limit), 'data' => $ret);
        $return_result['data'] = $result_data;
        return $return_result;
    }

    /**
     * 获取求购ID对应的项目ID
     * @param $purchase_id
     * @return array
     */
    public function getPurchaseDeal($purchase_id){
        $return_data = [];
        if(empty($purchase_id)){
            return $return_data;
        }
        $sql = "SELECT deal_id FROM xf_plan_purchase_deal WHERE  status = 1 AND purchase_id = $purchase_id ";
        $deal_ids = Yii::app()->phdb->createCommand($sql)->queryColumn();
        return $deal_ids;
    }


    public function checkUserDealload($purchase_id, $user_id){
        if(empty($purchase_id) || empty($user_id)){
            return false;
        }
        $deal_ids = $this->getPurchaseDeal($purchase_id);
        if($deal_ids){
            $sql = "SELECT id FROM firstp2p_deal_load WHERE  status = 1 and xf_status=0 AND wait_capital>0 and deal_id in (".implode(',', $deal_ids) .") ";
            $deal_load = Yii::app()->phdb->createCommand($sql)->queryRow();
            if($deal_load){
                return true;
            }
        }
        return false;
    }

    /**
     * 项目详情(发布转让)接口
     * @param $info
     * @return array
     */
    public function DebtDetails($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $user_id = $info['user_id'];
        $products = $info['products'];
        $deal_load_id = $info['deal_load_id'];
        $status = $info['status'];
        $debt_id = $info['debt_id'];
        if (!in_array($products, Yii::app()->c->xf_config['platform_type']) || !is_numeric($user_id) || !is_numeric($products) || empty($products) || !in_array($status,[1,2])) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if (empty($user_id)) {
            $return_result['code'] = 4007;
            return $return_result;
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        if($status == 1){
            $sql = "select deal.name,dealload.wait_capital,dealload.id from {$this->table_prefix}deal_load dealload left join {$this->table_prefix}deal deal on dealload.deal_id = deal.id
                        where  dealload.black_status = 1 and dealload.debt_status = 0 and dealload.status = 1 and dealload.xf_status =0  and dealload.wait_capital > 0 and dealload.black_status = 1 and dealload.id = $deal_load_id";
        }elseif($status == 2){
            $sql = "select deal.name,dealload.wait_capital,dealload.id from {$this->table_prefix}debt debt  left join {$this->table_prefix}deal_load dealload on debt.tender_id = dealload.id
                        left join {$this->table_prefix}deal deal on dealload.deal_id = deal.id  where debt.id = $debt_id  and dealload.black_status = 1
                        and debt.status in(3,4) and dealload.status = 1  and dealload.xf_status =0  and dealload.wait_capital > 0 and dealload.black_status = 1";
        }
        $dealloadInfo = $model->createCommand($sql)->queryRow();
        if (empty($dealloadInfo)) {
            $return_result['code'] = 2228;
            return $return_result;
        }
        $userInfo = Yii::app()->db->createCommand("select * from firstp2p_user where id = $user_id")->queryRow();
        //帐户状态，1为有效果，0为无效
        if ($userInfo['is_effect'] == 0) {
            $return_result['code'] = 1034;
            return $return_result;
        }
        //帐户已删除放入回收站，1为删除，0为未删除
        if ($userInfo['is_delete'] == 1) {
            $return_result['code'] = 1035;
            return $return_result;
        }
        //收款银行卡
        $bankArr = [];
        $bankInfo = Yii::app()->db->createCommand("select card.id,card.bankcard,b.name from firstp2p_user u inner join  firstp2p_user_bankcard card on u.id = card.user_id
                                                   left join firstp2p_bank b on card.bank_id = b.id where card.verify_status = 1 and u.id = {$user_id}")->queryAll();
        if (!empty($bankInfo)) {
            foreach ($bankInfo as $k => $v) {
                $bancard = GibberishAESUtil::dec($v['bankcard'], Yii::app()->c->idno_key);//解密银行卡号
                $bankArr[] = array(
                    "bankcard_id" => $v['id'],
                    "bankcard" => ItzUtil::formatBankCardNo($bancard, "X"),
                    "name" => $v['name'],
                );
            }
        }
        $productArr = ["1" => "尊享", '2' => '普惠供应链', '3' => '工场微金', '4' => '智多新', '5' => '交易所'];
        $ret = array(
            "deal_load_id" => $dealloadInfo['id'],//投资记录id
            "name" => $dealloadInfo['name'],//项目名称
            "real_name" => $userInfo['real_name'],//账户名
            "wait_capital" => $dealloadInfo['wait_capital'],//待还本金
            "product_class" => $productArr[$products],//项目类型
            "bank_info" => $bankArr,//银行卡号
        );
        $return_result['data'] = $ret;
        return $return_result;
    }

    /**
     * 转让中债权项目详情接口
     * @param $info
     * @return array
     */
    public function TransferDetails($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $products = $info['products'];
        $debt_id = $info['debt_id'];
        if (!in_array($products, Yii::app()->c->xf_config['platform_type']) || !is_numeric($products) || !is_numeric($debt_id) || empty($products)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        $now = time();
        $sql = "select debt.id,debt.discount,debt.endtime,debt.money,deal.name,deal.loantype,agency.name agency_name,debt.buy_code 
                    from {$this->table_prefix}debt debt 
                    left join {$this->table_prefix}deal deal on debt.borrow_id = deal.id
                    left join {$this->table_prefix}deal_agency agency on agency.id = deal.agency_id
                    left join {$this->table_prefix}deal_load dload on debt.tender_id = dload.id
                    where debt.id = $debt_id and debt.status = 1 and debt.starttime <= {$now} and debt.endtime >= {$now} and dload.black_status = 1";
        $debtInfo = $model->createCommand($sql)->queryRow();
        if (empty($debtInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        $loantypeArr = Yii::app()->c->xf_config['loantype'];
        $ret = array(
            "debt_id" => $debtInfo['id'],
            "discount" => $debtInfo['discount'],//转让折扣
            "endtime" => $debtInfo['endtime'],//转让结束时间
            "money" => $debtInfo['money'],//转让金额
            "transferprice" => round($debtInfo['discount']*$debtInfo['money']*0.1, 2),//转让价格
            "name" => $debtInfo['name'],//项目名称
            "loantype" => $debtInfo['loantype'],//还款方式 1:按季等额还款；2:按月等额还款；3:一次性还本付息 4:按月付息一次还本 5:按天一次性还款
            "loantype_name" => $loantypeArr[$debtInfo['loantype']],//还款方式 1:按季等额还款；2:按月等额还款；3:一次性还本付息 4:按月付息一次还本 5:按天一次性还款
            "agency_name" => $debtInfo['agency_name'],//保障机构
            "is_orient" => !empty($debtInfo['buy_code']) ? 1 : 2,//定向转让 1是 2不是
        );
        $return_result['data'] = $ret;
        return $return_result;
    }

    /**
     * 我的认购列表接口[交易取消、交易成功、待卖方收款、待付款]
     * @param $info
     * @return array
     */
    public function SubscriptionOwn($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $products = $info['products'];
        $status = $info['status'];//1-待付款 2-交易成功 3-交易取消 4-待卖方收款 默认10-全部
        $user_id = $info['user_id'];
        $page = $info['page'];
        $limit = $info['limit'];
        if (empty($user_id)) {
            $return_result['code'] = 4007;
            return $return_result;
        }
        if (!in_array($products, Yii::app()->c->xf_config['platform_type']) || !is_numeric($products) || !is_numeric($status) || !in_array($status, [1, 2, 3, 4, 10]) || empty($products)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if ($limit > 100) {
            $return_result['code'] = 4003;
            return $return_result;
        }
        if (!empty($limit)) {
            $pass = ($page - 1) * $limit;  //跳过数据
            $data_limit = "LIMIT {$pass},{$limit}";
        }
        $sqladd = '';
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
            $sqladd .= " and dt.platform_id = {$products} ";
        }
        if (in_array($status, [1, 2])) {
            //待付款,交易成功
            $sqladd .= " and dt.status = $status";
        } elseif ($status == 3) {
            //交易取消
            $sqladd .= " and dt.status in(3,4,5)";
        }elseif ($status == 4) {
            //待卖方收款
            $sqladd .= " and dt.status = 6";
        }
        $debtcount = $model->createCommand("select count(*) from {$this->table_prefix}debt_tender dt
                                                left join {$this->table_prefix}debt debt on dt.debt_id = debt.id
                                                left join {$this->table_prefix}deal deal on deal.id = debt.borrow_id
                                                left join {$this->table_prefix}deal_load dload on dload.id = debt.tender_id where dload.black_status = 1 and debt.debt_src = 2 and dt.user_id = $user_id {$sqladd}")->queryScalar();
        if (empty($debtcount)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        $debtTenderInfo = $model->createCommand("select dt.new_tender_id, dt.id,deal.name,debt.discount,debt.money,dt.status,debt.endtime,dt.submit_paytime,debt.id debt_id,dt.money wait_capital,dt.addtime  from {$this->table_prefix}debt_tender dt
                                                    left join {$this->table_prefix}debt debt on dt.debt_id = debt.id
                                                    left join {$this->table_prefix}deal deal on deal.id = debt.borrow_id
                                                    left join {$this->table_prefix}deal_load dload on dload.id = debt.tender_id where dload.black_status = 1 and debt.debt_src = 2 and dt.user_id = $user_id {$sqladd} order by dt.id desc $data_limit")->queryAll();
        
        $statusArr = ['1' => '待付款', '2' => '交易成功', '3' => '交易取消', '4' => '交易取消', '5' => '交易取消', '6' => '待卖方收款', '7' => '待卖方收款(客服介入)'];
        foreach ($debtTenderInfo as $key => $val) {
            $contractInfo = $model->createCommand("select tender_id,oss_download,status from {$this->table_prefix}contract_task where tender_id = {$val['new_tender_id']}")->queryRow();
            $endtime = '';
            if ($val['status'] == 1) {
                //待付款
                $endtime = strval($val['addtime'] + ConfUtil::get('youjie-undertake-endtime') - time());
            }elseif($val['status'] == 6){
                //待卖方收款
                $endtime = strval($val['submit_paytime'] + ConfUtil::get('youjie-payment-endtime') - time());
                //待卖方收款资金未到账客服介入
                $appealCount = Yii::app()->db->createCommand("select count(*) from ag_wx_debt_appeal where products = $products
                                                         and debt_id = {$val['debt_id']} and debt_tender_id = {$val['id']} and status = 1")->queryScalar();
                if ($appealCount > 0) {
                    $val['status'] = 7;
                }
            }
            $remark_status = ['0' => 1, '2' => 2,'1' => 3,3 => 3];
            $ret[] = array(
                "debt_tender_id" => $val['id'],//债权投资记录ID
                "name" => $val['name'],//项目名称
                "wait_capital" => $val['wait_capital'],//待还本金
                "discount" => $val['discount'],//折扣
                "transferprice" => round($val['discount']*$val['money']*0.1, 2),//转让价格
                "status" => $val['status'],
                "status_name" => $statusArr[$val['status']],
                "endtime" => $endtime,//到期时间s
                "remark_status" => !empty($contractInfo) ? $remark_status[$contractInfo['status']] : '',
                "oss_download" => !empty($contractInfo['oss_download']) ? "http://".ConfUtil::get('OSS-ccs-yj-dashboard.bucket').".".ConfUtil::get('OSS-ccs-yj.endpoint').DIRECTORY_SEPARATOR.$contractInfo['oss_download'] : '',//债转合同地址
            );
        }
        $result_data = array('count' => $debtcount, 'page_count' => ceil($debtcount / $limit), 'data' => $ret);
        $return_result['data'] = $result_data;
        return $return_result;
    }

    /**
     * 认购项目详情接口
     */
    public function SubscriptionDetails($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $products = $info['products'];
        $debt_tender_id = $info['debt_tender_id'];
        if (!in_array($products, Yii::app()->c->xf_config['platform_type']) || !is_numeric($products) || !is_numeric($debt_tender_id) || empty($products)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        $sql = "select dt.money wait_capital,dt.id,dt.debt_id,dp.product_class,deal.name,debt.discount,dt.payer_bankcard,dt.payer_name,dt.payer_bankzone,debt.money,debt.buy_code,dt.new_tender_id,debt.serial_number,dt.addtime,debt.successtime,dt.cancel_time,dt.submit_paytime,dt.action_money,dt.payment_voucher,dt.status 
                    from {$this->table_prefix}debt_tender dt
                    left join {$this->table_prefix}debt debt on dt.debt_id = debt.id
                    left join {$this->table_prefix}deal_load dload on dload.id = debt.tender_id
                    left join {$this->table_prefix}deal deal on deal.id = debt.borrow_id
                    left join {$this->table_prefix}deal_project dp on deal.project_id = dp.id where dload.black_status = 1 and dt.id = $debt_tender_id";
        $tenderInfo = $model->createCommand($sql)->queryRow();
        if (empty($tenderInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        if (!empty($tenderInfo['payment_voucher'])) {
            if (strpos($tenderInfo['payment_voucher'], ',') !== false) {
                $payment_voucher = explode(',', $tenderInfo['payment_voucher']);
                foreach($payment_voucher as $key => $val){
                    if ($tenderInfo['addtime'] <= 1578394800) {
                        $payment_voucher_oss[] = 'https://service.zichanhuayuan.com'.DIRECTORY_SEPARATOR.$val;
                    } else {
                        $payment_voucher_oss[] = Yii::app()->c->itouzi['oss_preview_address'].DIRECTORY_SEPARATOR.$val;
                    }
                }
            }else{
                if ($tenderInfo['addtime'] <= 1578394800) {
                    $payment_voucher_oss = ['https://service.zichanhuayuan.com'.DIRECTORY_SEPARATOR.$tenderInfo['payment_voucher']];
                }else{
                    $payment_voucher_oss = [Yii::app()->c->itouzi['oss_preview_address'].DIRECTORY_SEPARATOR.$tenderInfo['payment_voucher']];
                }

            }
        }
        $contractInfo = $model->createCommand("select tender_id,oss_download,status from {$this->table_prefix}contract_task where tender_id = {$tenderInfo['new_tender_id']} ")->queryRow();
        $payer_bankcard = GibberishAESUtil::dec($tenderInfo['payer_bankcard'], Yii::app()->c->idno_key);//解密银行卡号
        $statusArr = ['1' => '待付款', '2' => '交易成功', '3' => '交易取消', '4' => '交易取消', '5' => '交易取消', '6' => '待卖方收款', '7' => '待卖方收款(客服介入)'];
        $endtime = '';
        $agdebtAppeal = Yii::app()->db->createCommand("select id,addtime from ag_wx_debt_appeal where products = $products
                                                         and debt_id = {$tenderInfo['debt_id']} and debt_tender_id = {$tenderInfo['id']} ")->queryRow();
        if ($tenderInfo['status'] == 1) {
            //待付款
            $endtime = strval($tenderInfo['addtime'] + ConfUtil::get('youjie-undertake-endtime') - time());
        }elseif($tenderInfo['status'] == 6){
            //待卖方收款
            $endtime = strval($tenderInfo['submit_paytime'] + ConfUtil::get('youjie-payment-endtime') - time());
            //待卖方收款资金未到账客服介入
            if (!empty($agdebtAppeal)) {
                $tenderInfo['status'] = 7;
            }
        }
        $remark_status = ['0' => 1, '2' => 2,'1' => 3,3 => 3];
        $ret = array(
            "debt_tender_id" => $tenderInfo['id'],
            "product_class" => $tenderInfo['product_class'],//项目类型
            "wait_capital" => $tenderInfo['wait_capital'],//待还本金
            "name" => $tenderInfo['name'],//项目名称
            "discount" => $tenderInfo['discount'],//转让折扣
            "buy_code" => $tenderInfo['buy_code'],//认购码
            "transferprice" => round($tenderInfo['discount']*$tenderInfo['money']*0.1, 2),//转让价格
            "order_number" => $tenderInfo['serial_number'],//订单编号
            "addtime" => $tenderInfo['addtime'],//下单时间
            "submit_paytime" => $tenderInfo['submit_paytime'],//付款时间
            "adtime" => !empty($agdebtAppeal['addtime']) ? $agdebtAppeal['addtime'] : 0,//平台介入时间
            "successtime" => $tenderInfo['successtime'],//交易成功时间
            "cancel_time" => $tenderInfo['cancel_time'],//交易取消时间或真实过期时间
            "payer_name" => $tenderInfo['payer_name'],//付款人姓名
            "payer_bankzone" => $tenderInfo['payer_bankzone'],//付款人开户行
            "payer_bankcard" => ItzUtil::formatBankCardNo($payer_bankcard),//付款人卡号
            "account" => $tenderInfo['action_money'],//付款金额
            "payment_voucher" => $payment_voucher_oss,//付款凭证
            "status" => $tenderInfo['status'],//认购状态：1-待付款 2-交易成功 6-待卖方收款 3-手动交易取消 4-待付款过期 5-客服判定无效 7-待卖方收款(客服介入)
            "end_time" => $endtime,//剩余到期时间（s）
            "status_name" => $statusArr[$tenderInfo['status']],
            "remark_status" => !empty($contractInfo) ? $remark_status[$contractInfo['status']] : '',
            "oss_download" => !empty($contractInfo['oss_download']) ? "http://".ConfUtil::get('OSS-ccs-yj-dashboard.bucket').".".ConfUtil::get('OSS-ccs-yj.endpoint').DIRECTORY_SEPARATOR.$contractInfo['oss_download'] : '',//债转合同地址
        );
        $return_result['data'] = $ret;
        return $return_result;
    }

    /**
     * 债权发布接口
     * @param $info
     * @return array
     */
    public function ProjectTransfer($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $products = $info['products'];
        $deal_load_id = $info['deal_load_id'];
        $user_id = $info['user_id'];
        $is_orient = $info['is_orient'];
        $money = $info['money'];
        $discount = $info['discount'];
        $effect_days = $info['effect_days'];
        $bankcard_id = $info['bankcard_id'];
        $transaction_password = $info['transaction_password'];
        if (!in_array($products, Yii::app()->c->xf_config['platform_type']) || !is_numeric($products) || !is_numeric($deal_load_id) || empty($products)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if (empty($user_id)) {
            $return_result['code'] = 2057;
            return $return_result;
        }
        if (empty($effect_days) || empty($discount) || empty($money) || empty($bankcard_id) || empty($transaction_password)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        //校验交易密码
        $checkInfo = $this->checkPassWord($user_id, $transaction_password);
        if($checkInfo['code'] != 0){
            $return_result['code'] = $checkInfo['code'];
            return $return_result;
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        $sqladd = '';
        if ($products == 2) {
//            $dealIds = $this->addPhdbWhere();
//            if(!empty($dealIds)){
//                $sqladd = " and deal.id in($dealIds)";
//            }
            $sqladd = " and deal.product_class_type = 223";
        }
        $tenderInfo = $model->createCommand("select dl.* from {$this->table_prefix}deal_load dl left join {$this->table_prefix}deal deal on dl.deal_id = deal.id where dl.black_status = 1 and dl.status = 1 and dl.xf_status = 0 and dl.id = $deal_load_id {$sqladd}")->queryRow();
        if (empty($tenderInfo)) {
            $return_result['code'] = 2228;
            return $return_result;
        }
        $userInfo = Yii::app()->db->createCommand("select * from firstp2p_user where id = $user_id")->queryRow();
        //银行卡信息
        $bankCardInfo = Yii::app()->db->createCommand("select ub.id,ub.bankzone,ub.bankcard,ub.card_name,b.name from firstp2p_user_bankcard ub left join firstp2p_bank b on b.id = ub.bank_id where ub.id = $bankcard_id and verify_status = 1")->queryRow();
        if (empty($bankCardInfo)) {
            $return_result['code'] = 3008;
            return $return_result;
        }
        if (empty($bankCardInfo['name'])) {
            $return_result['code'] = 2212;
            return $return_result;
        }
        $params = [
            'user_id' => $user_id,
            'money' => $money,
            'discount' => $discount,
            'deal_loan_id' => $deal_load_id,
            'debt_src' => 2,
            'is_orient' => $is_orient,
            'effect_days' => $effect_days,
            'payee_name' => $userInfo['real_name'] == $bankCardInfo['card_name'] ? $userInfo['real_name'] : $bankCardInfo['card_name'],
            'payee_bankzone' => $bankCardInfo['name'],
            'payee_bankcard' => $bankCardInfo['bankcard'],
        ];
        $model->beginTransaction();
        if ($products == 1) {
            $ret = DebtService::getInstance()->createDebt($params);//尊享发布
        } elseif ($products == 2) {
            $ret = PhDebtService::getInstance()->createDebt($params);//普惠供应链发布
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            // 工场微金 智多新 交易所
            $ret = OfflineDebtService::getInstance()->createDebt($params);
        }
        if ($ret['code'] != 0) {
            $return_result['code'] = $ret['code'];
            $return_result['info'] = $ret['info'];
            $model->rollback();
            return $return_result;
        }
        $model->commit();
        $debt_id = $ret['data']['debt_id'];
        $debtInfo = $model->createCommand("select id,buy_code from {$this->table_prefix}debt where id = $debt_id")->queryRow();
        $return_result['data'] = ['debt_id' => $debt_id, 'buy_code' => $debtInfo['buy_code']];
        return $return_result;
    }

    /**
     * 获取债转合同签约地址
     */
    public function getDebtContract($info){
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $products = $info['products'];
        $deal_load_id = $info['deal_load_id'];
        $user_id = $info['user_id'];
        $is_orient = $info['is_orient'];
        $money = $info['money'];
        $discount = $info['discount'];
        $effect_days = $info['effect_days'];
        $bankcard_id = $info['bankcard_id'];
        $transaction_password = $info['transaction_password'];
        if (!in_array($products, Yii::app()->c->xf_config['platform_type']) || !is_numeric($products) || !is_numeric($deal_load_id) || empty($products)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if (empty($user_id)) {
            $return_result['code'] = 2057;
            return $return_result;
        }
        if (empty($effect_days) || empty($discount) || empty($money) || empty($bankcard_id) || empty($transaction_password)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        //校验交易密码
        $checkInfo = $this->checkPassWord($user_id, $transaction_password);
        if($checkInfo['code'] != 0){
            $return_result['code'] = $checkInfo['code'];
            return $return_result;
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        $sqladd = '';
        if ($products == 2) {
            $sqladd = " and deal.product_class_type = 223";
        }
        $tenderInfo = $model->createCommand("select dl.* from {$this->table_prefix}deal_load dl left join {$this->table_prefix}deal deal on dl.deal_id = deal.id where dl.black_status = 1 and dl.status = 1 and dl.xf_status = 0 and dl.id = $deal_load_id {$sqladd}")->queryRow();
        if (empty($tenderInfo)) {
            $return_result['code'] = 2228;
            return $return_result;
        }
        $userInfo = Yii::app()->db->createCommand("select * from firstp2p_user where id = $user_id")->queryRow();
        if($userInfo['fdd_real_status'] != 1){
            $return_result['code'] = 3027;
            return $return_result;
        }
        //银行卡信息
        $bankCardInfo = Yii::app()->db->createCommand("select ub.id,ub.bankzone,ub.bankcard,ub.card_name,b.name from firstp2p_user_bankcard ub left join firstp2p_bank b on b.id = ub.bank_id where ub.id = $bankcard_id and verify_status = 1")->queryRow();
        if (empty($bankCardInfo)) {
            $return_result['code'] = 3008;
            return $return_result;
        }
        if (empty($bankCardInfo['name'])) {
            $return_result['code'] = 2212;
            return $return_result;
        }
        $params = [
            'user_id' => $user_id,
            'money' => $money,
            'discount' => $discount,
            'deal_loan_id' => $deal_load_id,
            'debt_src' => 2,
            'is_orient' => $is_orient,
            'effect_days' => $effect_days,
            'payee_name' => $userInfo['real_name'] == $bankCardInfo['card_name'] ? $userInfo['real_name'] : $bankCardInfo['card_name'],
            'payee_bankzone' => $bankCardInfo['name'],
            'payee_bankcard' => $bankCardInfo['bankcard'],
            'sign_type' => 1,
        ];
        $model->beginTransaction();
        if ($products == 1) {
            $ret = DebtService::getInstance()->createDebt($params);//尊享发布
        } elseif ($products == 2) {
            $ret = PhDebtService::getInstance()->createDebt($params);//普惠供应链发布
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            // 工场微金 智多新 交易所
            $ret = OfflineDebtService::getInstance()->createDebt($params);
        }
        if ($ret['code'] != 0) {
            $return_result['code'] = $ret['code'];
            $return_result['info'] = $ret['info'];
            $model->rollback();
            return $return_result;
        }
        $model->commit();
        $debt_id = $ret['data']['debt_id'];
        $debtInfo = $model->createCommand("select id,buy_code from {$this->table_prefix}debt where id = $debt_id")->queryRow();
        $return_result['data'] = ['debt_id' => $debt_id, 'buy_code' => $debtInfo['buy_code']];
        return $return_result;
    }
    /**
     * 重新发布
     */
    public function AgainProjectTransfer($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $products = $info['products'];
        $debt_id = $info['debt_id'];
        $user_id = $info['user_id'];
        $transaction_password = $info['transaction_password'];
        if (!in_array($products, Yii::app()->c->xf_config['platform_type']) || !is_numeric($products) || !is_numeric($debt_id)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if (empty($user_id)) {
            $return_result['code'] = 2057;
            return $return_result;
        }
        if (empty($debt_id) || empty($products) || empty($transaction_password)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        //校验交易密码
        $checkInfo = $this->checkPassWord($user_id, $transaction_password);
        if ($checkInfo['code'] != 0) {
            $return_result['code'] = $checkInfo['code'];
            return $return_result;
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        $sqladd = '';
        if ($products == 2) {
//            $dealIds = $this->addPhdbWhere();
//            if(!empty($dealIds)){
//                $sqladd = " and deal.id in($dealIds)";
//            }
            $sqladd = " and deal.product_class_type = 223";
        }
        $debtInfo = $model->createCommand("select debt.* from {$this->table_prefix}debt debt left join {$this->table_prefix}deal deal on debt.borrow_id = deal.id
                                           left join {$this->table_prefix}deal_load dload on dload.id = debt.tender_id
                                           where  debt.id = $debt_id and dload.black_status = 1 {$sqladd}")->queryRow();
        if (empty($debtInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        $params = [
            'user_id' => $user_id,
            'money' => $debtInfo['money'],
            'discount' => $debtInfo['discount'],
            'deal_loan_id' => $debtInfo['tender_id'],
            'debt_src' => $debtInfo['debt_src'],
            'is_orient' => empty($debtInfo['buy_code']) ? 2 : 1,
            'effect_days' => round(($debtInfo['endtime'] - $debtInfo['starttime']) / 86400),
            'payee_name' => $debtInfo['payee_name'],
            'payee_bankzone' => $debtInfo['payee_bankzone'],
            'payee_bankcard' => $debtInfo['payee_bankcard'],
        ];
        $model->beginTransaction();
        if ($products == 1) {
            $ret = DebtService::getInstance()->createDebt($params);//尊享发布
        } elseif ($products == 2) {
            $ret = PhDebtService::getInstance()->createDebt($params);//普惠供应链发布
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            // 工场微金 智多新 交易所
            $ret = OfflineDebtService::getInstance()->createDebt($params);
        }
        if ($ret['code'] != 0) {
            $return_result['code'] = $ret['code'];
            $return_result['info'] = $ret['info'];
            $model->rollback();
            return $return_result;
        }
        $model->commit();
        $return_result['data'] = $ret;
        return $return_result;
    }

    /**
     * 债权认购接口
     * @param $info
     * @return array
     */
    public function transferBuy($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $products = $info['products'];
        $debtArr = $info['debtArr'];
        $user_id = $info['user_id'];
        $buy_code = $info['buy_code'];
        $transaction_password = $info['transaction_password'];
        if (!in_array($products, Yii::app()->c->xf_config['platform_type']) || !is_numeric($products) || empty($products)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if (empty($user_id)) {
            $return_result['code'] = 2057;
            return $return_result;
        }
        $tenderInfo = json_decode($debtArr, true);
        if (count($tenderInfo) > 1) {
            //目前只支持单笔认购
            $return_result['code'] = 2213;
            return $return_result;
        }
        $debt_id = $tenderInfo[0]['debt_id'];
        $money = $tenderInfo[0]['money'];
        if (empty($debt_id) || empty($money) || empty($products) || empty($transaction_password)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        //所传的金额必须是两位小数
        if (!ItzUtil::checkMoney($money)) {
            $return_result['code'] = 2092;
            return $return_result;
        }
        // 获取债权信息数组
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        $debt = $model->createCommand("select * from {$this->table_prefix}debt where id = $debt_id")->queryRow();
        if(empty($debt)){
            Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id; {$this->table_prefix}debt.id[{$debt_id}] not exist", 'error');
            $return_result['code'] = 2017;
            return $return_result;
        }
        //校验交易密码
        $checkInfo = $this->checkPassWord($user_id, $transaction_password);
        if ($checkInfo['code'] != 0) {
            $return_result['code'] = $checkInfo['code'];
            return $return_result;
        }
        $params = [
            'user_id' => $user_id,
            'money' => $money,
            'debt_id' => $debt_id,
            'buy_code' => $buy_code,
            'products' => $products,
        ];
        // 1尊享 2普惠 3工场微金 4智多新 5交易所
        $ret = DebtService::getInstance()->undertakeDebt($params);
        if ($ret['code'] != 0) {
            $return_result['code'] = $ret['code'];
            $return_result['info'] = $ret['info'];
            return $return_result;
        }
        $undertake_endtime = ConfUtil::get('youjie-undertake-endtime');
        $return_result['data']['undertake_endtime'] = $undertake_endtime;
        $return_result['data']['products']          = $products;
        $return_result['data']['debt_tender_id']    = $ret['data']['debt_tender_id'];
        return $return_result;
    }

    /**
     * 债权取消or过期
     * @param $info [
     * debt_id 债转记录ID
     * status 3取消4过期
     * products 所属产品1尊享 2普惠供应链
     * checkuser 检验用户1:是 2:否
     * ]
     * @return array
     */
    public function CancelDebt($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $debt_id = $info['debt_id'];
        $status = $info['status'];
        $products = $info['products'];
        $user_id = $info['user_id'];
        $checkuser = !empty($info['checkuser']) ? $info['checkuser'] : 1;
        if (empty($debt_id) || !in_array($status, [3, 4]) || !in_array($products, Yii::app()->c->xf_config['platform_type']) || empty($products) || !in_array($checkuser,[1,2])) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if($checkuser == 1){
            //取消时，用户ID必传
            if ($status == 3 && empty($user_id)) {
                $return_result['code'] = 2057;
                return $return_result;
            }
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        $debt_id = FunctionUtil::verify_id($debt_id);
        if (!empty($user_id)) {
            $user_info = Yii::app()->db->createCommand("SELECT id as user_id FROM firstp2p_user WHERE id = {$user_id}")->queryRow();
            if (empty($user_info)) {
                $return_result['code'] = 2026;
                return $return_result;
            }
        }
        $sqladd = '';
        if ($products == 2) {
//            $dealIds = $this->addPhdbWhere();
//            if(!empty($dealIds)){
//                $sqladd = " and deal.id in($dealIds)";
//            }
            $sqladd = " and deal.product_class_type = 223";
        }
        $debt = $model->createCommand("select debt.* from {$this->table_prefix}debt debt left join {$this->table_prefix}deal deal on debt.borrow_id = deal.id
                                       left join {$this->table_prefix}deal_load dload on dload.id = debt.tender_id
                                       where dload.black_status = 1 and debt.id = $debt_id {$sqladd}")->queryRow();
        if (empty($debt)) {
            $return_result['code'] = 2059;
            return $return_result;
        }
        if($checkuser == 1){
            //只能取消自己债权
            if ($status == 3 && $debt['user_id'] != $user_id) {
                $return_result['code'] = 2060;
                return $return_result;
            }
        }
        //该笔债权已售出
        if (5 == $debt['status']) {
            $return_result['code'] = 2313;
            return $return_result;
        }
        //非可取消状态
        if (1 != $debt['status']) {
            $return_result['code'] = 2061;
            return $return_result;
        }
        //变更firstp2p_debt、firstp2p_deal_load
        $debtUpSql = ItzUtil::get_update_db_sql("{$this->table_prefix}debt", ['status' => $status], "id = {$debt_id}");
        $new_debt_ret = $model->createCommand($debtUpSql)->execute();
        if (!$new_debt_ret) {
            $return_result['code'] = 2062;
            return $return_result;
        }
        $dealLoadUpSql = ItzUtil::get_update_db_sql("{$this->table_prefix}deal_load", ['debt_status' => 0], "id = {$debt['tender_id']}");
        $new_dealload_ret = $model->createCommand($dealLoadUpSql)->execute();
        if (!$new_dealload_ret) {
            $return_result['code'] = 2062;
            return $return_result;
        }
        if($status == 4){
            //发送短信通知
            $remind = array();
            $remind['sms_code'] = "wx_seller_order_cancel_expire";
            $remind['mobile'] = $this->getPhone($debt['user_id']);
            //$remind['data']['url'] = Yii::app()->c->youjie_base_url."/debt/#/subscribeDetail/1?products={$products}&debt_id={$debt['tender_id']}";
            $smaClass = new XfSmsClass();
            $send_ret = $smaClass->sendToUserByPhone($remind);
            if($send_ret['code'] != 0){
                Yii::log("CancelDebt user_id:{$debt['user_id']}, debt_id:$debt_id; sendToUser buyer error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
            }
        }
        //取消成功
        return $return_result;
    }

    /**
     * 认购取消接口（买家取消）
     * @param $info [
     * products 所属产品1尊享 2普惠供应链
     * user_id 买家用户ID
     * debt_tender_id 认购债权记录ID
     * status 1:买家主动取消 2:超时脚本自动取消
     * ]
     * @return array
     */
    public function CancelTenderDebt($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $debt_tender_id = $info['debt_tender_id'];
        $products = $info['products'];
        $user_id = $info['user_id'];
        $status = !empty($info['status']) ? $info['status'] : 1;
        if (empty($debt_tender_id) || !in_array($products, Yii::app()->c->xf_config['platform_type']) || empty($products)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        $this->table_prefix = 'firstp2p_';
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        //买家主动取消校验
        if ($status == 1) {
            if (empty($user_id)) {
                $return_result['code'] = 2057;
                return $return_result;
            }
            $user_info = Yii::app()->db->createCommand("SELECT id as user_id FROM firstp2p_user WHERE id = {$user_id}")->queryRow();
            if (empty($user_info)) {
                $return_result['code'] = 2026;
                return $return_result;
            }
            //只能取消自己认购的债权
            if ($user_info['user_id'] != $user_id) {
                $return_result['code'] = 2214;
                return $return_result;
            }
        }
        $sqladd = '';
        if ($products == 2) {
//            $dealIds = $this->addPhdbWhere();
//            if(!empty($dealIds)){
//                $sqladd = " and deal.id in($dealIds)";
//            }
            $sqladd = " and deal.product_class_type = 223";
        }
        $tenderInfo = $model->createCommand("select tender.*,debt.user_id as debt_user_id,debt.serial_number 
                                                from {$this->table_prefix}debt_tender tender 
                                                left join {$this->table_prefix}debt debt on tender.debt_id = debt.id
                                                left join {$this->table_prefix}deal deal on debt.borrow_id = deal.id
                                                left join {$this->table_prefix}deal_load dload on dload.id = debt.tender_id
                                                where dload.black_status = 1 and tender.id = $debt_tender_id {$sqladd}")->queryRow();
        if (empty($tenderInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        //非可取消状态
        if ($tenderInfo['status'] != 1) {
            $return_result['code'] = 2215;
            return $return_result;
        }

        //变更firstp2p_debt变成转让中的状态
        $debtUpSql = ItzUtil::get_update_db_sql("{$this->table_prefix}debt", ['status' => 1], "id = {$tenderInfo['debt_id']}");

        $new_debt_ret = $model->createCommand($debtUpSql)->execute();
        if (!$new_debt_ret) {
            $return_result['code'] = 2062;
            return $return_result;
        }
        //变更firstp2p_debt_tender 手动交易取消或待付款过期
        if($status == 2){
            $upstatus = 4;
        }elseif($status == 1){
            $upstatus = 3;
        }
        $tenderUpSql = ItzUtil::get_update_db_sql("{$this->table_prefix}debt_tender", ['status' => $upstatus], "id = {$debt_tender_id}");
        $tender = $model->createCommand($tenderUpSql)->execute();
        if (!$tender) {
            $return_result['code'] = 2062;
            return $return_result;
        }
        $remind = array();
        $remind['mobile'] = $this->getPhone($tenderInfo['debt_user_id']);
        $remind['data']['order_no'] = $tenderInfo['serial_number'];
        //$remind['data']['url'] = Yii::app()->c->youjie_base_url."/debt/#/subscribeDetail/1? products={$products}&debt_id={$tenderInfo['id']}";
        $smaClass = new XfSmsClass();
        if($status == 2){
            //认购人超时未付款，系统取消交易——转让人
            $remind['sms_code'] = "wx_seller_order_cancel_no_pay";

        }elseif($status == 1){
            //订单取消(买家手工取消)——转让人
            $remind['sms_code'] = "wx_seller_order_cancel_by_buyer";
        }
        $send_ret = $smaClass->sendToUserByPhone($remind);
        if($send_ret['code'] != 0){
            Yii::log("CancelTenderDebt user_id:$user_id, debt_id:{$tenderInfo['debt_id']}; sendToUser buyer error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
        }
        //取消成功
        return $return_result;
    }

    /**
     * 待卖方确认超时->客服介入
     * @param $info [
     * debt_tender_id 认购债权记录ID
     * products 所属产品1尊享 2普惠供应链
     * ]
     * @return array
     */
    public function TimeoutCustomer($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $debt_tender_id = $info['debt_tender_id'];
        $products = $info['products'];
        if (empty($debt_tender_id) || !in_array($products, Yii::app()->c->xf_config['platform_type']) || empty($products)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        $now = time();
        //付款待确认有效期
        $youjie_payment_endtime = ConfUtil::get('youjie-payment-endtime');

        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        $sqladd = '';
        if ($products == 2) {
//            $dealIds = $this->addPhdbWhere();
//            if(!empty($dealIds)){
//                $sqladd = " and deal.id in($dealIds)";
//            }
            $sqladd = " and deal.product_class_type = 223";
        }
        $tenderInfo = $model->createCommand("select tender.id,debt.id debt_id,debt.serial_number,debt.user_id debt_user_id,tender.user_id 
                                                from {$this->table_prefix}debt_tender tender 
                                                left join {$this->table_prefix}debt debt on tender.debt_id = debt.id
                                                left join {$this->table_prefix}deal deal on debt.borrow_id = deal.id
                                                left join {$this->table_prefix}deal_load dload on dload.id = debt.tender_id
                                                where tender.id = $debt_tender_id and dload.black_status = 1 and tender.status = 6 and tender.submit_paytime < $now - $youjie_payment_endtime $sqladd")->queryRow();

        if (empty($tenderInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        //添加债转申诉记录
        $debtAppeal = [
            'products' => $products,
            'debt_id' => $tenderInfo['debt_id'],
            'debt_tender_id' => $debt_tender_id,
            'type' => 1,
            'status' => 1,
            'addtime' => time(),
            'addip' => FunctionUtil::ip_address(),
        ];
        $appealInfo = Yii::app()->db->createCommand("select * from ag_wx_debt_appeal where products = $products and debt_id = {$tenderInfo['debt_id']} and debt_tender_id = $debt_tender_id")->queryRow();
        if(empty($appealInfo)){
            $tenderUpSql = ItzUtil::get_insert_db_sql("ag_wx_debt_appeal", $debtAppeal);
            $appeal = Yii::app()->db->createCommand($tenderUpSql)->execute();
            if (!$appeal) {
                $return_result['code'] = 2226;
                return $return_result;
            }
        }
        $smaClass = new XfSmsClass();

        //转让人超时未确认凭证——认购方
        $redis_key = "wx_buyer_seller_no_confirm_cert{$tenderInfo["serial_number"]}";
        $is_send = Yii::app()->rcache->get($redis_key);
        if(!$is_send){
            $remind = array();
            $remind['sms_code'] = "wx_buyer_seller_no_confirm_cert";
            $remind['data']['order_no'] = $tenderInfo["serial_number"];
            $remind['mobile'] = $this->getPhone($tenderInfo['user_id']);
            $send_ret = $smaClass->sendToUserByPhone($remind);
            if($send_ret['code'] != 0){
                Yii::log("TimeoutCustomer user_id:{$tenderInfo['user_id']}, debt_id:{$tenderInfo['debt_id']}; sendToUser buyer error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
            }
            //10天发一次
            Yii::app()->rcache->set($redis_key, 1, 86400*10);
        }

        //转让人超时未确认凭证——转让人
        $remindot = array();
        $remindot['mobile'] = $this->getPhone($tenderInfo['debt_user_id']);
        $redis_key2 = "wx_seller_seller_no_confirm_cert{$remindot['mobile']}";
        $is_send2 = Yii::app()->rcache->get($redis_key2);
        if(!$is_send2){
            $remindot['sms_code'] = "wx_seller_seller_no_confirm_cert";
            $remindot['order_no'] = $tenderInfo["serial_number"];
            $send_ret = $smaClass->sendToUserByPhone($remindot);
            if($send_ret['code'] != 0){
                Yii::log("TimeoutCustomer user_id:{$tenderInfo['user_id']}, debt_id:{$tenderInfo['debt_id']}; sendToUser buyer error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
            }
            //10天发一次
            Yii::app()->rcache->set($redis_key2, 1, 86400*10);
        }

        return $return_result;
    }

    /**
     * 转账付款接口
     */
    public function TransferPayment($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $debt_tender_id = $info['debt_tender_id'];
        $products = $info['products'];
        $user_id = $info['user_id'];
        $payer_name = $info['payer_name'];
        $payer_bankzone = $info['payer_bankzone'];
        $payer_bankcard = $info['payer_bankcard'];
        $account = $info['account'];
        $pay_voucher = $info['pay_voucher'];
        if (empty($debt_tender_id) || !in_array($products, Yii::app()->c->xf_config['platform_type']) || !is_numeric($account)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        if (empty($pay_voucher)) {
            $return_result['code'] = 2217;
            return $return_result;
        }
        if (empty($payer_name)) {
            $return_result['code'] = 2218;
            return $return_result;
        }
        if (empty($payer_bankzone)) {
            $return_result['code'] = 2219;
            return $return_result;
        }
        if (empty($account)) {
            $return_result['code'] = 2220;
            return $return_result;
        }
        if (empty($payer_bankcard)) {
            $return_result['code'] = 2225;
            return $return_result;
        }
        if (empty($user_id)) {
            $return_result['code'] = 2057;
            return $return_result;
        }
        $sql = "SELECT user_type FROM firstp2p_user WHERE id = {$user_id} ";
        $user_type = Yii::app()->db->createCommand($sql)->queryScalar();
        if ($user_type == 1) {
            $checkbank = preg_match('/^[1-9]\d{7,29}$/' , $payer_bankcard);
            if ($checkbank === 0) {
                $return_result['code'] = 1013;
                return $return_result;
            }
        } else {
            if (!ItzUtil::checkbank($payer_bankcard)) {
                $return_result['code'] = 1013;
                return $return_result;
            }
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
            $this->table_prefix = 'offline_';
        }
        $sqladd = '';
        if ($products == 2) {
//            $dealIds = $this->addPhdbWhere();
//            if(!empty($dealIds)){
//                $sqladd = " and deal.id in($dealIds)";
//            }
            $sqladd = " and deal.product_class_type = 223";
        }
        $tenderInfo = $model->createCommand("select tender.id,debt.money,debt.discount,debt.id debt_id,tender.status,debt.user_id as debt_user_id,debt.serial_number 
                                                from {$this->table_prefix}debt_tender tender 
                                                left join {$this->table_prefix}debt debt on debt.id = tender.debt_id
                                                left join {$this->table_prefix}deal deal on debt.borrow_id = deal.id
                                                left join {$this->table_prefix}deal_load dload on dload.id = debt.tender_id where dload.black_status = 1 and tender.id = $debt_tender_id {$sqladd}")->queryRow();

        if (empty($tenderInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        if ($tenderInfo['status'] != 1) {
            $return_result['code'] = 2224;
            return $return_result;
        }
        //付款金额校验
        $accountTender = round($tenderInfo['discount']*$tenderInfo['money']*0.1, 2);
        if (bccomp($accountTender, $account, 2) != 0) {
            $return_result['code'] = 2221;
            return $return_result;
        }
        //上传凭证处理
        $pay_voucher_Arr = json_decode($pay_voucher,true);
        $date = date('YmdHis');
        foreach ($pay_voucher_Arr as $key => $val) {
            $fileName = 'youjie_contract_' . $date . '-' . $debt_tender_id . '-' . $products . '-' . $user_id.'-'.$key;
            $f = $this->upload_base64($val);
            if ($f === false) {
                Yii::log("TransferPayment user_id:$user_id, debt_tender_id:{$debt_tender_id}; 上传凭证处理失败", "error");
            }
            $suffix_name = strtoupper(substr(strrchr($f,"."),1));
            // 上传到Oss
            $saveName = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR . $debt_tender_id . DIRECTORY_SEPARATOR . $fileName .".".$suffix_name;
            $re = $this->upload($f, $saveName);
            if ($re === false) {
                Yii::log("tender_id = $debt_tender_id 的付款凭证上传oss失败！", CLogger::LEVEL_ERROR, $this->logFile);
            }else{
                $payment_voucher[] = $saveName;
                Yii::log("tender_id = $debt_tender_id 的付款凭证上传oss成功！", CLogger::LEVEL_INFO, $this->logFile);
            }
        }
        //C1确认收款后更新firstp2p_debt_tender和firstp2p_debt
        $upDate = [
            "payment_voucher" => implode(",", $payment_voucher),
            "submit_paytime" => time(),
            "payer_name" => $payer_name,
            "payer_bankzone" => $payer_bankzone,
            "payer_bankcard" => GibberishAESUtil::enc($payer_bankcard, Yii::app()->c->idno_key),//解密银行卡号,
            "status" => 6,
        ];
        $tenderUpSql = ItzUtil::get_update_db_sql("{$this->table_prefix}debt_tender", $upDate, "id = {$debt_tender_id}");
        $tender = $model->createCommand($tenderUpSql)->execute();
        if (!$tender) {
            $return_result['code'] = 2223;
            return $return_result;
        }
        //确认转账-待付款状态
        $tenderUpSql = ItzUtil::get_update_db_sql("{$this->table_prefix}debt", ["status" => 6], "id = {$tenderInfo['debt_id']}");
        $tender = $model->createCommand($tenderUpSql)->execute();
        if (!$tender) {
            $return_result['code'] = 2222;
            return $return_result;
        }
        //认购人付款——转让人
        $remind = array();
        $remind['sms_code'] = "wx_seller_buyer_pay";
        $remind['data']['order_no'] = $tenderInfo["serial_number"];
        $remind['mobile'] = $this->getPhone($tenderInfo['debt_user_id']);
        $smaClass = new XfSmsClass();
        $send_ret = $smaClass->sendToUserByPhone($remind);
        if($send_ret['code'] != 0){
            Yii::log("TransferPayment user_id:$user_id, debt_id:{$tenderInfo['debt_id']}; sendToUser buyer error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
        }
        //交易成功
        return $return_result;
    }
    /**
     * 文件上传
     * @param $file
     * @param $key
     * @return bool
     */
    private function upload($file, $key)
    {
        Yii::log(basename($file).'文件正在上传!', CLogger::LEVEL_INFO,$this->logFile);
        try {
            ini_set('memory_limit', '2048M');
            $re = Yii::app()->oss->bigFileUpload($file, $key);
            unlink($file);
            return $re;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, $this->logFile);
            return false;
        }
    }

    /**
     * 上传图片
     * @param $content string  图片的base64内容
     * @return string
     */
    private function upload_base64($content)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $content, $result)) {
            $pic_type = $result[2]; // 匹配出图片后缀名
            $dir_name = date('Ymd');
            $dir_address = self::TempDir . $dir_name . '/';
            if (!file_exists($dir_address)) {
                mkdir($dir_address, 0777, true);
            }
            $pic_name = time() . rand(10000, 99999) . ".{$pic_type}";
            $pic_address = $dir_address . $pic_name;
            if (file_put_contents($pic_address, base64_decode(str_replace($result[1], '', $content)))) {
                return $pic_address;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 风险评测验证
     * @param $user_id
     * @param $type 1:单层验证是否风险评测2：双层验证是否C5积极型进行认购
     * @return array
     */
    public function checkUserRisk($user_id, $type = 1)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        if (empty($user_id)) {
            $return_result['code'] = 2057;
            return $return_result;
        }
        if (!is_numeric($user_id) || !in_array($type, [1, 2])) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        $userInfo = $tenderInfo = Yii::app()->db->createCommand("select * from firstp2p_user where id = $user_id")->queryRow();
        if (empty($userInfo)) {
            $return_result['code'] = 1027;
            return $return_result;
        }
        if ($userInfo['level_risk_id'] == 0) {
            $return_result['code'] = 2202;
            return $return_result;
        }
        //帐户状态，1为有效果，0为无效
        if ($userInfo['is_effect'] == 0) {
            $return_result['code'] = 1034;
            return $return_result;
        }
        //帐户已删除放入回收站，1为删除，0为未删除
        if ($userInfo['is_delete'] == 1) {
            $return_result['code'] = 1035;
            return $return_result;
        }
        //验证是否可认购
        $levelInfo = Yii::app()->c->itouzi['level_risk_youjie'];
        if ($type == 2) {
            $level = ConfUtil::get('level_risk_youjie');
            if ($userInfo['level_id'] >= $level) {
                $return_result['code'] = 2203;
                $return_result['info'] = "您的风险承受能力等级为：{$levelInfo[$userInfo['level_id']]}，当前不符合债权认购资质（{$levelInfo[$level]}），请重新测评";
                return $return_result;
            }
        }
        $return_result['data'] = ['level_id' => $userInfo['level_risk_id'], "level_name" => $levelInfo[$userInfo['level_risk_id']]];
        return $return_result;
    }

    /**
     * 交易密码验证
     * @param $user_id //C1用户id
     * @param $password
     * @return mixed
     */
    public function checkPassWord($user_id, $password)
    {
        if($user_id == 11866204){
            Yii::log($user_id."交易密码：".$password."加密:".md5($password),"error");
        }
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        if (empty($user_id)) {
            $return_result['code'] = 2057;
            return $return_result;
        }
        if (empty($password)) {
            $return_result['code'] = 2075;
            return $return_result;
        }
        $userInfo = $tenderInfo = Yii::app()->db->createCommand("select * from firstp2p_user where id = $user_id")->queryRow();
        if (empty($userInfo)) {
            $return_result['code'] = 1027;
            return $return_result;
        }
        //帐户状态，1为有效果，0为无效
        if ($userInfo['is_effect'] == 0) {
            $return_result['code'] = 1034;
            return $return_result;
        }
        //帐户已删除放入回收站，1为删除，0为未删除
        if ($userInfo['is_delete'] == 1) {
            $return_result['code'] = 1035;
            return $return_result;
        }
        //未设置交易密码
        if (empty($userInfo['transaction_password'])) {
            $return_result['code'] = 1021;
            return $return_result;
        }
        //交易密码校验
        $strlen = strlen($userInfo['transaction_password']);
        if ($strlen == 24) {
            if ($userInfo['transaction_password'] != GibberishAESUtil::enc($password, Yii::app()->c->idno_key)) {
                $return_result['code'] = 2076;
                return $return_result;
            }
            return $return_result;
        } else if ($strlen == 32) {
            if ($userInfo['transaction_password'] != md5($password)) {
                $return_result['code'] = 2076;
                return $return_result;
            }
            return $return_result;
        }
    }
    private function getPhone($user_id){
        if(empty($user_id) || !is_numeric($user_id)){
            return false;
        }
        //用户信息
        $userInfo = User::model()->findByPk($user_id);
        if(empty($userInfo)){
            return false;
        }
        return GibberishAESUtil::dec($userInfo->mobile, Yii::app()->c->contract['idno_key']);
    }

    /**
     * 判断当前用户是否为特殊用户
     */
    private function specificSubscribers($user_id)
    {
        if(empty($user_id)){
            return false;
        }
        //特殊用户白名单
        $specificSubscribers = Yii::app()->c->itouzi['specific_subscribers'];
        if(in_array($user_id,$specificSubscribers)){
            return true;
        }
        return false;
    }
}