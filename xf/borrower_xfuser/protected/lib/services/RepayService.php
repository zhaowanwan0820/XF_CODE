<?php
class RepayService extends ItzInstanceService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 借款人人数
     */
    public function borrowerNum(){
        $return_data = [
            'total' => 0,
            'no_distribution' => 0,
            'detail' => [
                [
                    'name'=>'未分配',
                    'value'=>'0',
                ]
            ]
        ];

        //查询总人数
        $n_sql = "  select status,count(1) as c_user_id from firstp2p_deal_user group by status";
        $user_total = Yii::app()->cmsdb->createCommand($n_sql)->queryAll();
        if($user_total){
            foreach ($user_total as $val){
                if($val['status'] == 1){
                    $return_data['yes_clear'] = $val['c_user_id'];
                }
                if($val['status'] == 0){
                    $return_data['no_clear'] = $val['c_user_id'];
                }
                $return_data['total'] += $val['c_user_id'];
                $return_data['detail'][0]['num'] += $val['c_user_id'];
            }
        }
        $u_sql = '  select a.company_id,a.user_id,c.name from firstp2p_borrower_distribution_detail a 
                    LEFT JOIN firstp2p_borrower_distribution d on d.id=a.distribution_id 
                    LEFT JOIN firstp2p_cs_company c on c.id=a.company_id 
                    where a.status=1 and d.status=1 group by a.user_id';
        $userList = Yii::app()->cmsdb->createCommand($u_sql)->queryAll();
        $distribution_uid = [];
        $company_ids = [];
        if($userList){
            //三方公司
            foreach ($userList as $value){
                $return_data['detail'][$value['company_id']]['name'] = $value['name'];
                $return_data['detail'][$value['company_id']]['value'] += 1;
                $distribution_uid[] = $value['user_id'];
                $company_ids[$value['company_id']] = $value['company_id'];
            }
        }

        $n_sql = "  select count(1) from firstp2p_deal_user where status=0 and user_id not in (".implode(',', $distribution_uid).") ";
        $user_total = Yii::app()->cmsdb->createCommand($n_sql)->queryScalar();
        if($user_total>0){
            $return_data['no_distribution'] = $user_total;
        }

        $return_data['company_ids'] = $company_ids;
        return $return_data;
    }

    /**
     * 已出清人数 clear_company_id
     */
    public function repayNum(){
        $return_data = [
            'total' => 0,
            'detail' => [
                [
                    'name'=>'自动划扣',
                    'value'=>'0',
                ]
            ]
        ];
        $u_sql = '  select a.company_id,a.user_id,c.name from firstp2p_deal_user a  
                    LEFT JOIN firstp2p_cs_company c on c.id=a.company_id 
                    where a.status=1 group by a.user_id';
        $userList = Yii::app()->cmsdb->createCommand($u_sql)->queryAll();
        if($userList){
            foreach ($userList as $value){
                $return_data['total'] += 1;
                $return_data['detail'][$value['company_id']]['value'] += 1;
                if($value['company_id'] > 0){
                    $return_data['detail'][$value['company_id']]['name'] = $value['name'];
                }
            }
        }
        return $return_data;
    }

    /**
     * 债权金额
     */
    public function debtAmount(){
        $return_data = [
            'total' => 0,
            'detail' => [
                [
                    'name'=>'未分配',
                    'value'=>'0',
                ]
            ]
        ];

        //查询总债权
        $n_sql = "  select sum(principal) from firstp2p_deal_repay where status=0 ";
        $principal_total = Yii::app()->cmsdb->createCommand($n_sql)->queryScalar();
        if($principal_total>0){
            $return_data['total'] = $return_data['detail'][0]['value'] = $principal_total;
        }

        //三方公司债权
        $u_sql = '  select a.add_company_id,sum(a.principal) as principal ,c.name from firstp2p_deal_repay a  
                    LEFT JOIN firstp2p_cs_company c on c.id=a.add_company_id 
                    where a.status=0 and a.paid_type=1 and a.add_company_id>0  group by a.add_company_id';
        $companyList = Yii::app()->cmsdb->createCommand($u_sql)->queryAll();
        $sum_principal = 0;
        if($companyList){
            //三方公司
            foreach ($companyList as $value){
                $sum_principal = bcadd($sum_principal, $value['principal'], 2);
                $return_data['detail'][$value['add_company_id']]['name'] = $value['name'];
                $return_data['detail'][$value['add_company_id']]['value'] += $value['principal'];
            }
            //自动划扣
            $return_data['detail'][0]['value'] = bcsub($return_data['total'], $sum_principal, 2);
        }

        return $return_data;
    }

    /**
     * 已出清 金额
     */
    public function repayAmount(){
        $return_data = [
            'total' => 0,
            'detail' => [
                [
                    'name'=>'自动划扣',
                    'value'=>'0',
                ]
            ]
        ];

        //划扣
        $n_sql = "  select sum(last_yop_repay_money)  from firstp2p_deal_repay  where last_yop_repay_status = 2 and status=1 ";
        $principal_total = Yii::app()->cmsdb->createCommand($n_sql)->queryScalar();
        if($principal_total>0){
            $return_data['total'] = $return_data['detail'][0]['value'] = $principal_total;
        }

        $u_sql = '  select a.company_id,sum(a.last_paid_principal) as paid_principal,c.name from firstp2p_deal_repay a 
                    LEFT JOIN firstp2p_cs_company c on c.id=a.company_id 
                    where a.status=1 and a.paid_type=1 group by a.company_id';
        $repayList = Yii::app()->cmsdb->createCommand($u_sql)->queryAll();
        if($repayList){
            //三方公司
            foreach ($repayList as $value){
                $return_data['total'] = bcadd($return_data['total'], $value['paid_principal'], 2);
                $return_data['detail'][$value['company_id']]['name'] = $value['name'];
                $return_data['detail'][$value['company_id']]['value'] += $value['paid_principal'];
            }
        }
        return $return_data;
    }

    /**
     * 三方催收公司数据
     */
    public function csCompanyList(){
        $return_data = [];
        $u_sql = '  select a.company_id,count(distinct a.user_id) as total_user,c.name from firstp2p_borrower_distribution_detail a 
                    LEFT JOIN firstp2p_borrower_distribution d on d.id=a.distribution_id 
                    LEFT JOIN firstp2p_cs_company c on c.id=a.company_id 
                    where a.status=1 and d.status=1 group by a.company_id';
        $return_data = $userList = Yii::app()->cmsdb->createCommand($u_sql)->queryAll();
        if(!$userList){
            return $return_data;
        }
        foreach ($userList as $key=>$value){
            $stat_data = [
                'total_amount' => 0,//总债权
                'clear_user' => 0,//已出清人数
                'no_clear' => 0,//回款未出清
                'no_repay' => 0,//未回款人数
                'repay_debt' => 0,//已回款债权
                'no_repay_debt' => 1,//未回款债权
            ];
            $return_data[$key] = array_merge($return_data[$key], $stat_data);
            //已出清人数
            $sql01 = "  select user_id  from firstp2p_deal_user  where status = 1 and company_id={$value['company_id']} ";
            $user_data = Yii::app()->cmsdb->createCommand($sql01)->queryColumn();
            $a_w = '';
            if($user_data){
                $return_data[$key]['clear_user'] = count($user_data);
                $a_w = "and user_id not in (" .implode(',', $user_data). ")";
            }
            //回款未出清
            $sql02 = "  select count(distinct user_id)   from firstp2p_deal_repay  where status = 1 and paid_type=1 and company_id={$value['company_id']} 
                         {$a_w} ";
            $no_clear = Yii::app()->cmsdb->createCommand($sql02)->queryScalar();
            if($no_clear){
                $return_data[$key]['no_clear'] = count($no_clear);
            }
            //未回款人数
            $return_data[$key]['no_repay'] = $userList[$key]['total_user'] - $return_data[$key]['clear_user'] - $return_data[$key]['no_clear'];

            //用户
            $sql03 = "  select distinct a.user_id from firstp2p_borrower_distribution_detail a 
                    LEFT JOIN firstp2p_borrower_distribution d on d.id=a.distribution_id 
                    where a.status=1 and d.status=1 and d.company_id={$value['company_id']} group by a.user_id";
            $company_user_data = Yii::app()->cmsdb->createCommand($sql03)->queryColumn();
            if($company_user_data){
                //总金额
                $sql04 = "  select status,sum(principal) as principal,sum(last_paid_principal) as last_paid_principal  from firstp2p_deal_repay  where user_id in (" .implode(',', $company_user_data). ")  group by status";;
                $total_amount = Yii::app()->cmsdb->createCommand($sql04)->queryAll();
                if($total_amount){
                    foreach ($total_amount as $v){
                        $return_data[$key]['total_amount'] = bcadd($v['principal'], $v['last_paid_principal'], 2);
                        if($v['status'] = 0){
                            $return_data[$key]['no_repay_debt'] = $v['principal'];
                        }
                        if($v['status'] = 1){
                            $return_data[$key]['repay_debt'] = $v['last_paid_principal'];
                        }
                    }
                }
            }

        }
        return $return_data;
    }

    public function userClearStat(){
        $user_clear = [
            'no_clear' => 0,
            'yop_clear' => 0,
            'RepaySlip_clear' => 0,
            'Repay_clear' => 0,
            'cs_clear' => [],
        ];

        $sql01 = "SELECT count(u.id) as c_user_num,u.`status`,u.clear_type,u.company_id,c.name  
        from firstp2p_deal_user u
        LEFT JOIN firstp2p_cs_company c on c.id=u.company_id 
        GROUP BY u.status,u.clear_type,u.company_id ";
        $clear_ret = Yii::app()->cmsdb->createCommand($sql01)->queryAll();
        if(!$clear_ret){
            return $user_clear;
        }
        foreach ($clear_ret as $value){
            //未出清
            if($value['status'] == 0){
                $user_clear['no_clear'] += $value['c_user_num'];
                continue;
            }
            //系统划扣出清
            if($value['status'] == 1 && $value['clear_type'] == 3){
                $user_clear['yop_clear'] += $value['c_user_num'];
                continue;
            }
            //管理员凭证出清
            if($value['status'] == 1 && $value['clear_type'] == 1 && $value['company_id'] == 0){
                $user_clear['RepaySlip_clear'] += $value['c_user_num'];
                continue;
            }
            //管理员线下还款出清
            if($value['status'] == 1 && $value['clear_type'] == 2 && $value['company_id'] == 0){
                $user_clear['Repay_clear'] += $value['c_user_num'];
                continue;
            }
            //催收公司凭证出清
            if($value['status'] == 1 && $value['clear_type'] == 1 && $value['company_id'] > 0){
                $user_clear['cs_clear'][$value['name']]['pz'] += $value['c_user_num'];
                $user_clear['cs_clear'][$value['name']]['rp'] += 0;
                continue;
            }
            //催收公司还款出清
            if($value['status'] == 1 && $value['clear_type'] == 2 && $value['company_id'] > 0){
                $user_clear['cs_clear'][$value['name']]['pz'] += 0;
                $user_clear['cs_clear'][$value['name']]['rp'] += $value['c_user_num'];
                continue;
            }

        }
        return $user_clear;
    }

    public function debtClearStat($company_ids=[]){
        $user_clear = [
            'no_clear' => 0,
            'yop_clear' => 0,
            'repay_total' => 0,
            'cs_clear' => [],
        ];

        //未出清
        $sql01 = "SELECT sum(wait_capital) as s_wait_capital,sum(repay_amount) as s_repay_amount, u.`status`,u.clear_type 
 from firstp2p_deal_user u 
GROUP BY u.status,u.clear_type  ";
        $clear_ret = Yii::app()->cmsdb->createCommand($sql01)->queryAll();
        if(!$clear_ret){
            return $user_clear;
        }

        foreach ($clear_ret as $value){
            //未出清
            if($value['status'] == 0){
                $user_clear['no_clear'] += $value['s_wait_capital'];
                continue;
            }
            //系统划扣出清
            if($value['status'] == 1 && $value['clear_type'] == 3){
                $user_clear['yop_clear'] += $value['s_repay_amount'];
                continue;
            }
        }

        //线下还款人数统计
        if(empty($company_ids)){
            return $user_clear;
        }

        //线下还款
        $sql02 = " SELECT count(DISTINCT d.user_id) as paid_user_num,cc.name,r.company_id,sum(r.repay_amount) as repay_amount ,sum(rd.current_principal) as current_principal 
                from firstp2p_offline_repay r 
                LEFT JOIN firstp2p_offline_repay_detail rd on r.id=rd.offline_repay_id 
                LEFT JOIN firstp2p_cs_company cc on cc.id=r.company_id 
                LEFT JOIN firstp2p_deal d on r.deal_id=d.id 
                WHERE r.`status`=2  
                GROUP BY r.company_id  ";
        $clear_ret02 = Yii::app()->cmsdb->createCommand($sql02)->queryAll();
        if(!$clear_ret02){
            return $user_clear;
        }

        foreach ($clear_ret02 as $value02){
            if(in_array($value02['company_id'], $company_ids)){
                unset($company_ids[$value02['company_id']]);
            }
            $user_clear['repay_total'] = bcadd($user_clear['repay_total'], $value02['repay_amount'], 2);
            $user_clear['cs_clear'][$value02['name']]['paid_user_num'] = $value02['paid_user_num'];
            $user_clear['cs_clear'][$value02['name']]['rp_amount'] = $value02['repay_amount'];
            $user_clear['cs_clear'][$value02['name']]['rp_debt'] = $value02['current_principal'];
            $user_clear['cs_clear'][$value02['name']]['pz_amount'] += 0;
            $user_clear['cs_clear'][$value02['name']]['pz_debt'] += 0;
            $user_clear['cs_clear'][$value02['name']]['company_id'] = $value02['company_id'];
        }

        //凭证还款
        $sql03 = " SELECT cc.name,r.company_id,sum(ry.last_paid_principal + ry.last_paid_interest) as repay_amount   from firstp2p_deal_reply_slip r 
                    LEFT JOIN firstp2p_deal_repay ry on r.deal_repay_id=ry.id  
                    LEFT JOIN firstp2p_cs_company cc on cc.id=r.company_id 
                    WHERE r.`status`=2  
                    GROUP BY r.company_id  ";
        $clear_ret03 = Yii::app()->cmsdb->createCommand($sql03)->queryAll();
        if(!$clear_ret03){
            return $user_clear;
        }
        foreach ($clear_ret03 as $value03){
            if(in_array($value03['company_id'], $company_ids)){
                unset($company_ids[$value03['company_id']]);
            }
            $user_clear['cs_clear'][$value03['name']]['paid_user_num'] += 0;
            $user_clear['cs_clear'][$value03['name']]['pz_amount'] = $value03['repay_amount'];
            $user_clear['cs_clear'][$value03['name']]['pz_debt'] = $value03['repay_amount'];
            $user_clear['cs_clear'][$value03['name']]['rp_amount'] += 0;
            $user_clear['cs_clear'][$value03['name']]['rp_debt'] += 0;
            $user_clear['cs_clear'][$value03['name']]['company_id'] = $value03['company_id'];
        }

        /* 未线下还款且未凭证录入过的三方公司初始数据给值， 待与产品讨论是否展示此类数据
        if(!empty($company_ids)){
            foreach($company_ids as $value04){

            }
        }*/


        if(!empty($user_clear['cs_clear'])){
            foreach($user_clear['cs_clear'] as $k => $value04){
                $sql04 = "  select count(distinct a.user_id) as no_paid_user_num,sum(dr.new_principal) as wait_capital  
                    from firstp2p_borrower_distribution_detail a 
                    LEFT JOIN firstp2p_borrower_distribution d on d.id=a.distribution_id 
                    left join firstp2p_deal_repay dr on dr.user_id=a.user_id and dr.status=0 and dr.last_yop_repay_status !=2 
                    where dr.status=0 and dr.last_yop_repay_status!=2 and a.status=1 and d.status=1 and d.company_id={$value04['company_id']} ";
                $company_user_data =Yii::app()->cmsdb->createCommand($sql04)->queryRow();
               // var_dump($company_user_data);
                if(!$company_user_data){
                    $user_clear['cs_clear'][$k]['no_paid_user_num'] = 0;
                    $user_clear['cs_clear'][$k]['wait_capital'] = 0;
                    continue;
                }
                $user_clear['cs_clear'][$k]['no_paid_user_num'] = $company_user_data['no_paid_user_num'];
                $user_clear['cs_clear'][$k]['wait_capital'] = $company_user_data['wait_capital'] ?: 0;
            }
        }

        return $user_clear;
    }

    public function repayStat(){
        $user_clear = [
            'repay_total' => 0,
            'yop_total' => 0,
        ];

        //未出清
        $sql01 = "SELECT sum(wait_capital) as s_wait_capital,sum(repay_amount) as s_repay_amount, u.`status`,u.clear_type,u.company_id,c.name  
 from firstp2p_deal_user u
LEFT JOIN firstp2p_cs_company c on c.id=u.company_id 
GROUP BY u.status,u.clear_type,u.company_id ";
        $clear_ret = Yii::app()->cmsdb->createCommand($sql01)->queryAll();
        if(!$clear_ret){
            return $user_clear;
        }
        foreach ($clear_ret as $value){
            //未出清
            if($value['status'] == 0){
                $user_clear['no_clear'] += $value['s_wait_capital'];
                continue;
            }
            //系统划扣出清
            if($value['status'] == 1 && $value['clear_type'] == 3){
                $user_clear['yop_clear'] += $value['s_repay_amount'];
                continue;
            }
            //管理员凭证出清
            if($value['status'] == 1 && $value['clear_type'] == 1 && $value['company_id'] == 0){
                $user_clear['RepaySlip_clear'] += $value['s_repay_amount'];
                continue;
            }
            //管理员线下还款出清
            if($value['status'] == 1 && $value['clear_type'] == 2 && $value['company_id'] == 0){
                $user_clear['Repay_clear'] += $value['s_repay_amount'];
                continue;
            }
            //催收公司凭证出清
            if($value['status'] == 1 && $value['clear_type'] == 1 && $value['company_id'] > 0){
                $user_clear['cs_clear'][$value['name']]['pz'] += $value['s_repay_amount'];
                $user_clear['cs_clear'][$value['name']]['rp'] += 0;
                continue;
            }
            //催收公司还款出清
            if($value['status'] == 1 && $value['clear_type'] == 2 && $value['company_id'] > 0){
                $user_clear['cs_clear'][$value['name']]['pz'] += 0;
                $user_clear['cs_clear'][$value['name']]['rp'] += $value['s_repay_amount'];
                continue;
            }

        }
        return $user_clear;
    }

}
