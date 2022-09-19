<?php

class ITZTenderService extends BaseTenderService
{

    public function __construct()
    {
        parent::__construct();
    }

    const DEBT_CONFIRM     = 1;           // 已确权
    const DEBT_NOT_CONFIRM = 0;           // 未确权

    const DEBT_ENDED  = 15;          // 已转让
    const DEBT_PAYING = 1;           // 还款中

//    const SHENGXIN_BORROW = 1;            // 省心
//    const WISE_BORROW = 2;                // 智选

    private function getDB()
    {
        return Yii::app()->yiidb;
    }

    /**
     * 获取用户债权确权额度（本金）
     * @param $params
     * @return array|bool
     */

    public function getTenderDebtConfirmCount($params)
    {
        $return_result = [
            'project' => [
                0 => [
                    'name' => '省心系列',
                    'confirm' => 0,           // 已确权额度 本金
                    'total' => 0,             // 全部总额度 本金
                ],
            ],
/*            'wise' => [                   // 智选系列
                'confirm' => 0,
                'total' => 0,
            ],
*/        ];

        if (!isset($params['user_id']) || !is_numeric($params['user_id'])) {

            return false;
        }

        //获取省心计划已确权及未确权金额
        $shengxin_sql = "select sum(wait_account - wait_interest) as wait_money, is_debt_confirm from dw_borrow_tender 
                where status = 1  
                and user_id = " . $params['user_id'] . " 
                and debt_type not in (33,34,35,37,38) 
                group by is_debt_confirm ";

/*        $wise_sql = "select sum(surplus_capital) as wait_money, is_debt_confirm from itz_wise_tender
                where user_id = " . $params['user_id'] . "
                and status in (2,14)
                and debt_status in (0,14)
                and surplus_capital > 0
                group by is_debt_confirm ";
*/
        $shengxin = self::getDB()->createCommand($shengxin_sql)->queryAll();
//        $wise     = self::getDB()->createCommand($wise_sql)->queryAll();

        if ($shengxin) {
            foreach ($shengxin as $value) {
                if ($value['is_debt_confirm'] == self::DEBT_CONFIRM) {
                    //已确权
                    $return_result['project'][0]['confirm'] = $value['wait_money'];
                }
                //已确权+未确权
                $return_result['project'][0]['total'] += $value['wait_money'];
            }
        }

/*        if ($wise_sql) {
            foreach ($wise as $v) {
                if ($v['is_debt_confirm'] == self::DEBT_CONFIRM) {
                    $return_result['wise']['confirm'] = $v['wait_money'];
                }
                $return_result['wise']['total'] += $v['wait_money'];
            }
        }
*/
        return $return_result;
    }

    /**
     * 项目确权列表
     * @param $params
     * @return array|bool
     */

    public function getTenderConfirmList($params)
    {
        $return_result = [
            'confirm_count' => [
                'unconfirm' => 0,              // 已确权项目数量
                'confirmed' => 0,              // 未确权项目数量
            ],
            'list' => [                        // 项目列表

            ],
        ];

        if (!isset($params['user_id']) || !is_numeric($params['user_id'])) {
            return false;
        }

/*        if (!isset($params['borrow_type'])) {
            return false;
        }
*/
        if (!isset($params['page']) || !isset($params['size'])) {
            return false;
        }

//        if ($params['borrow_type'] == self::SHENGXIN_BORROW){
            $data_sql = "select t.id, t.wait_account - t.wait_interest as surplus_capital, t.account_init, t.borrow_id, t.debt_type, b.name, b.apr from dw_borrow_tender as t 
                         left join dw_borrow as b on t.borrow_id = b.id 
                         where t.user_id = " . $params['user_id'] . " 
                         and t.status = 1 
                         and t.wait_account - t.wait_interest > 0 
                         and debt_type not in (33,34,35,37,38) ";
/*        }elseif($params['borrow_type'] == self::WISE_BORROW){
            $data_sql = "select t.id, t.surplus_capital, t.account_init, t.borrow_id, t.wise_borrow_id, t.debt_type, b.name, b.apr from itz_wise_tender as t
                         left join itz_wise_borrow as b on t.wise_borrow_id = b.id
                         where t.user_id = " . $params['user_id'] . "
                         and t.status in (2,14)
                         and t.debt_status in (0,14)
                         and t.surplus_capital > 0 ";
        }else{
            return false;
        }
*/
        // 省心计划tender总数
        $count = $this->getDB()->createCommand($data_sql)->query()->count();
        if ($count == 0){
            return $return_result;
        }

        if (isset($params['debt_confirm']) && in_array($params['debt_confirm'], [self::DEBT_CONFIRM, self::DEBT_NOT_CONFIRM])){
            $data_sql .= " and is_debt_confirm = ".$params['debt_confirm'];
            $confirm_count = $this->getDB()->createCommand($data_sql)->query()->count();
            if ($params['debt_confirm'] == self::DEBT_CONFIRM){
                $return_result['confirm_count']['confirmed'] = $confirm_count;
                $return_result['confirm_count']['unconfirm'] = $count - $confirm_count;
            }else{
                $return_result['confirm_count']['confirmed'] = $count - $confirm_count;
                $return_result['confirm_count']['unconfirm'] = $confirm_count;
            }
        }else{
            return false;
        }

        $page = ($params['page'] - 1) * $params['size'];

        $data_sql .= " limit  $page, ".$params['size'];

        $data = $this->getDB()->createCommand($data_sql)->queryAll();
        if ($data){
           foreach ($data as $k=>$v){
//               if ($params['borrow_type'] == self::SHENGXIN_BORROW){
                   $return_result['list'][$k]['name'] = $v['name'];
//               }else{
//                   $return_result['list'][$k]['name'] = (in_array($v['debt_type'], [3, 4]) ? '智选计划 I-' : ($v['borrow_id'] >= 43431 ? '阳光智选 I-' : '智选集合 I-')) . $v['wise_borrow_id'];
//               }
               $return_result['list'][$k]['apr'] = in_array($v['debt_type'], [3, 4]) ? '6.5' :$v['apr'];
               $return_result['list'][$k]['surplus_capital'] = $v['surplus_capital'];
               $return_result['list'][$k]['account_init'] = $v['account_init'];
               $return_result['list'][$k]['tender_id'] = $v['id'];
           }
        }

        return $return_result;
    }

    /**
     * 确权
     * @param $params
     * @return array|bool
     */
    public function confirmDebt($params)
    {
        $return_result = [
            'status' => 0,
        ];
        if (!isset($params['user_id']) || !is_numeric($params['user_id'])) {
            return false;
        }

        if (!isset($params['tender_ids']) || empty($params['tender_ids'])) {
            return false;
        }

        if (!isset($params['platform_id']) || empty($params['platform_id'])) {
            return false;
        }

//        if ($params['borrow_type'] == self::SHENGXIN_BORROW){
            $table = 'dw_borrow_tender';
//        }elseif($params['borrow_type'] == self::WISE_BORROW){
//            $table = 'itz_wise_tender';
//        }else{
//            return false;
//        }

        $update_sql = "update ". $table ." set is_debt_confirm = " . self::DEBT_CONFIRM .", 
                       debt_confirm_time = " .time() . ", confirm_account = wait_account - wait_interest where id in (". $params['tender_ids'] .") and user_id = " . $params['user_id'];
        $result = $this->getDB()->createCommand($update_sql)->execute();
        if ($result){
            $return_result['status'] = 1;
        }
        $update_sql = "update ag_user_platform set confirm_status = 1 
                      where platform_user_id =". $params['user_id'] ." and platform_id = " . $params['platform_id'];
        Yii::app()->agdb->createCommand($update_sql)->execute();

        return $return_result;
    }

    /**
     * 投资项目详情
     * @param $params
     * @return array|bool
     */
    public function getTenderDetail($params)
    {
        $return_result = [
            'name' => '',
            'apr'  => '',
            'surplus_capital' => '',
            'contract_num' => '',
            'style_cn' => '',
            'gura_company' => '',
            'account_init' => '',
        ];
        if (!isset($params['user_id']) || !is_numeric($params['user_id'])) {
            return false;
        }

        if (!isset($params['tender_id'])) {
            return false;
        }
//        if ($params['borrow_type'] == self::SHENGXIN_BORROW){
            $data_sql = "select t.id, t.wait_account - t.wait_interest as surplus_capital, t.account_init, t.borrow_id, t.debt_type, t.addtime, b.name, b.apr, b.style, b.guarantors, b.type from dw_borrow_tender as t 
                         left join dw_borrow as b on t.borrow_id = b.id 
                         where t.user_id = " . $params['user_id'] . " 
                         and t.id = " . $params['tender_id'] . " 
                         and t.status = 1 
                         and t.wait_account - t.wait_interest > 0 ";
//        }
/*        elseif($params['borrow_type'] == self::WISE_BORROW){
            $data_sql = "select t.id, t.surplus_capital, t.account_init, t.borrow_id, t.wise_borrow_id, t.debt_type, t.addtime, b.name, b.apr, b.style from itz_wise_tender as t 
                         left join itz_wise_borrow as b on t.wise_borrow_id = b.id 
                         where t.user_id = " . $params['user_id'] . " 
                         and t.id = " . $params['tender_id'] . " 
                         and t.status in (2,14) 
                         and t.debt_status in (0,14) 
                         and t.surplus_capital > 0 ";
        }else{
            return false;
        }
*/
        $data = $this->getDB()->createCommand($data_sql)->queryRow();

        if ($data){
            $return_result['apr'] = $data['apr'];
            $return_result['surplus_capital'] = $data['surplus_capital'];
            $return_result['account_init'] = $data['account_init'];
//            if ($params['borrow_type'] == self::SHENGXIN_BORROW){
                $return_result['name'] = $data['name'];
                $gura = $this->getDB()->createCommand("select * from dw_guarantor_new where gid = " . $data['guarantors'])->queryRow();
                if ($gura){
                    $return_result['gura_company'] = $gura['name'];
                }
                $return_result['contract_num'] = implode('-', [date('Ymd', $data['addtime']),$data['type'],$data['borrow_id'],$data['id']]);
                $return_result['style_cn'] = Yii::app()->c->itouzi['itouzi']['borrow_style'][$data['style']];

//            }
/*            elseif($params['borrow_type'] == self::WISE_BORROW){
                $return_result['name'] = (in_array($data['debt_type'], [3, 4]) ? '智选计划 I-' : ($data['borrow_id'] >= 43431 ? '阳光智选 I-' : '智选集合 I-')) . $data['wise_borrow_id'];
                if (in_array($data['debt_type'], [3, 4])){
                    $return_result['apr'] = 6.5;
                }else{
                    $return_result['style_cn'] = Yii::app()->c->itouzi['itouzi']['borrow_style'][$data['style']];
                }
            }
*/
        }

        return $return_result;
    }

    /**
     * 已确权列表（包含已结清）
     * @param $params
     * @return array|bool
     */

    public function getConfirmedList($params)
    {
        $return_result = [
            'paying_count' => [
                'paying' => 0,
                'ended' => 0,
            ],
            'list' => [                        // 项目列表

            ],
        ];

        if (!isset($params['user_id']) || !is_numeric($params['user_id'])) {
            return false;
        }

        if (!isset($params['page']) || !isset($params['size'])) {
            return false;
        }

        $data_sql = "select t.id, t.wait_account - t.wait_interest as surplus_capital, t.account_init, t.borrow_id, t.debt_type, b.name, b.apr from dw_borrow_tender as t 
                         left join dw_borrow as b on t.borrow_id = b.id 
                         where t.user_id = " . $params['user_id'] . " 
                         and t.is_debt_confirm = 1 
                         and debt_type not in (33,34,35,37,38) ";

        // tender总数
        $count = $this->getDB()->createCommand($data_sql)->query()->count();
        if ($count == 0){
            return $return_result;
        }

        if (isset($params['status']) && in_array($params['status'], [self::DEBT_PAYING, self::DEBT_ENDED])){
            if ($params['status'] == self::DEBT_PAYING){
                $data_sql .= " and t.status = ".$params['status'];
            }else{
                $data_sql .= " and t.status in (2,15)";
            }
            $paying_count = $this->getDB()->createCommand($data_sql)->query()->count();
            if ($params['status'] == self::DEBT_PAYING){
                $return_result['paying_count']['paying'] = $paying_count;
                $return_result['paying_count']['ended'] = $count - $paying_count;
            }else{
                $return_result['paying_count']['paying'] = $count - $paying_count;
                $return_result['paying_count']['ended'] = $paying_count;
            }
        }

        $data_sql .= " order by t.status asc , debt_confirm_time desc";

        $page = ($params['page'] - 1) * $params['size'];

        $data_sql .= " limit  $page, ".$params['size'];

        $data = $this->getDB()->createCommand($data_sql)->queryAll();
        if ($data){
            foreach ($data as $k=>$v){
                $return_result['list'][$k]['name'] = $v['name'];
                $return_result['list'][$k]['apr'] = in_array($v['debt_type'], [3, 4]) ? '6.5' :$v['apr'];
                $return_result['list'][$k]['surplus_capital'] = $v['surplus_capital'];
                $return_result['list'][$k]['account_init'] = $v['account_init'];
                $return_result['list'][$k]['tender_id'] = $v['id'];
            }
        }

        return $return_result;
    }

    /**
     * 获取已确权金额
     * @param $params
     * @return bool|int
     */
    public function getConfirmedTenderSum($params){
        if (!isset($params['user_id']) || !is_numeric($params['user_id'])) {
            return false;
        }
        $confirmed_money_sql = "select sum(wait_account - wait_interest) as wait_money from dw_borrow_tender 
                where status = 1  
                and user_id = " . $params['user_id'] . " 
                and is_debt_confirm = 1";
        $confirmed_money = self::getDB()->createCommand($confirmed_money_sql)->queryRow();

        if ($confirmed_money) {
            return $confirmed_money['wait_money']?$confirmed_money['wait_money']:0;
        }

        return 0;
    }

    /**
     * 获取待还本金
     * @param $params
     * @return bool|int
     */
    public function getTenderWaitMoney($params)
    {
        if (!isset($params['user_id']) || !is_numeric($params['user_id'])) {

            return false;
        }

        $shengxin_sql = "select sum(wait_account - wait_interest) as wait_money from dw_borrow_tender 
                where status = 1  
                and user_id = " . $params['user_id'] . " 
                and debt_type not in (33,34,35,37,38)";

        if(isset($params['is_debt_confirm']) && in_array($params['is_debt_confirm'],[0,1])){
            $shengxin_sql .=" and is_debt_confirm = ".$params['is_debt_confirm'];
        }
        $shengxin = self::getDB()->createCommand($shengxin_sql)->queryRow();

        if ($shengxin) {
            return $shengxin['wait_money']?$shengxin['wait_money']:0;
        }

        return 0;
    }

}
