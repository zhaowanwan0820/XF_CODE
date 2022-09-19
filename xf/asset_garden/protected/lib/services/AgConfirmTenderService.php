<?php

class AgConfirmTenderService extends BaseTenderService
{

    public function __construct()
    {
        parent::__construct();
    }

    const DEBT_CONFIRM     = 1;           // 已确权
    const DEBT_NOT_CONFIRM = 0;           // 未确权

    const DEBT_ENDED  = 15;          // 已转让
    const DEBT_PAYING = 1;           // 还款中

    private function getDB()
    {
        return Yii::app()->agdb;
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
                0 =>[
                    'name' => '在存债权',
                    'confirm' => 0,           // 已确权额度 本金
                    'total' => 0,             // 全部总额度 本金
                ],
            ],
        ];

        if (!isset($params['user_id']) || !is_numeric($params['user_id']) || !isset($params['platform_id'])) {

            return false;
        }

        $shengxin_sql = "select sum(wait_capital) as wait_money, is_debt_confirm from ag_tender 
                where status = 1  
                and user_id = " . $params['user_id'] . " 
                and platform_id = " . $params['platform_id'] . " 
                group by is_debt_confirm ";

        $shengxin = self::getDB()->createCommand($shengxin_sql)->queryAll();

        if ($shengxin) {
            foreach ($shengxin as $value) {
                if ($value['is_debt_confirm'] == self::DEBT_CONFIRM) {
                    $return_result['project'][0]['confirm'] = $value['wait_money'];
                }
                $return_result['project'][0]['total'] += $value['wait_money'];
            }
        }

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
                'unconfirm' => 0,              // 未确权项目数量
                'confirmed' => 0,              // 已确权项目数量
            ],
            'list' => [                        // 项目列表

            ],
        ];

        if (!isset($params['user_id']) || !is_numeric($params['user_id']) || !isset($params['platform_id'])) {
            return false;
        }

        if (!isset($params['page']) || !isset($params['size'])) {
            return false;
        }

        $data_sql = "select t.id, t.wait_capital, t.money, t.project_id, b.name, b.apr from ag_tender as t 
                         left join ag_project as b on t.project_id = b.id 
                         where t.user_id = " . $params['user_id'] . " 
                         and t.status = 1 
                         and t.platform_id = " . $params['platform_id'];

        // tender总数
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
               $return_result['list'][$k]['name'] = $v['name'];
               $return_result['list'][$k]['apr'] = $v['apr'];
               $return_result['list'][$k]['wait_capital'] = $v['wait_capital'];
               $return_result['list'][$k]['money'] = $v['money'];
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

        $update_sql = "update ag_tender set is_debt_confirm = " . self::DEBT_CONFIRM .", 
                       debt_confirm_time = " .time() . ", confirm_account = wait_capital where id in (". $params['tender_ids'] .") and user_id = " . $params['user_id'];
        $result = $this->getDB()->createCommand($update_sql)->execute();
        if ($result){
            $return_result['status'] = 1;
        }

        $chooseData = [
            'platform_id' => $params['platform_id'],
            'user_id'=> $params['user_id'],
            'confirm_status'=> 1,
        ];
        $res = AgUserService::getInstance()->bindPlatform($chooseData);

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

        $data_sql = "select t.id, t.wait_capital, t.money, t.project_id, t.bond_no, b.name, b.apr, b.type from ag_tender as t 
                         left join ag_project as b on t.project_id = b.id 
                         where t.user_id = " . $params['user_id'] . " 
                         and t.id = " . $params['tender_id'] . " 
                         and t.status = 1 ";

        $data = $this->getDB()->createCommand($data_sql)->queryRow();

        if ($data){
            $return_result['apr'] = $data['apr'];
            $return_result['surplus_capital'] = $data['wait_capital'];
            $return_result['account_init'] = $data['money'];
            $return_result['name'] = $data['name'];
            $return_result['contract_num'] = $data['bond_no'];
            $return_result['style_cn'] = Yii::app()->c->contract['project_style'][$data['style']];

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

        if (!isset($params['user_id']) || !is_numeric($params['user_id']) || !isset($params['platform_id'])) {
            return false;
        }

        if (!isset($params['page']) || !isset($params['size'])) {
            return false;
        }

        $data_sql = "select t.id, t.wait_capital, t.money, t.project_id, b.name, b.apr from ag_tender as t 
                         left join ag_project as b on t.project_id = b.id 
                         where t.user_id = " . $params['user_id'] . " 
                         and t.is_debt_confirm = 1 
                         and t.platform_id = " . $params['platform_id'];

        // tender总数
        $count = $this->getDB()->createCommand($data_sql)->query()->count();
        if ($count == 0){
            return $return_result;
        }

        if (isset($params['status']) && in_array($params['status'], [self::DEBT_PAYING, self::DEBT_ENDED])){
            $data_sql .= " and t.status = ".$params['status'];
            $paying_count = $this->getDB()->createCommand($data_sql)->query()->count();
            if ($params['status'] == self::DEBT_PAYING){
                $return_result['paying_count']['paying'] = $paying_count;
                $return_result['paying_count']['ended']  = $count - $paying_count;
            }else{
                $return_result['paying_count']['paying'] = $count - $paying_count;
                $return_result['paying_count']['ended']  = $paying_count;
            }
        }else{
            return false;
        }

        $data_sql .= " order by t.status asc , debt_confirm_time desc";


        $page = ($params['page'] - 1) * $params['size'];

        $data_sql .= " limit  $page, ".$params['size'];

        $data = $this->getDB()->createCommand($data_sql)->queryAll();
        if ($data){
            foreach ($data as $k=>$v){
                $return_result['list'][$k]['name'] = $v['name'];
                $return_result['list'][$k]['apr'] = $v['apr'];
                $return_result['list'][$k]['wait_capital'] = $v['wait_capital'];
                $return_result['list'][$k]['money'] = $v['money'];
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

        if (!isset($params['user_id']) || !is_numeric($params['user_id']) || !isset($params['platform_id'])) {
            return false;
        }
        $confirmed_money_sql = "select sum(wait_capital) as wait_money from ag_tender 
                where status = 1  
                and user_id = " . $params['user_id'] . " 
                and is_debt_confirm = 1 
                and platform_id = " . $params['platform_id'];
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
        if (!isset($params['user_id']) || !is_numeric($params['user_id']) || !isset($params['platform_id'])) {
            return false;
        }

        $wait_sql = "select sum(wait_capital) as wait_money from ag_tender 
                where status = 1  
                and user_id = " . $params['user_id'] ."
                and platform_id = " . $params['platform_id'];
        if(isset($params['is_debt_confirm']) && in_array($params['is_debt_confirm'],[0,1])){
            $wait_sql .=" and is_debt_confirm = ".$params['is_debt_confirm'];
        }
        $wait = self::getDB()->createCommand($wait_sql)->queryRow();

        if ($wait) {
            return $wait['wait_money']?$wait['wait_money']:0;
        }

        return 0;
    }

}
