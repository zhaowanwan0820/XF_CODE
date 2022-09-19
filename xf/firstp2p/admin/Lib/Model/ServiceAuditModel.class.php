<?php


/*
 * ab角色 提交和审核分离
 *
 * @author wangge@ucfgroup.com
 * @date   2015/10/12
 */
class ServiceAuditModel extends CommonModel {

    /**
     * 业务类型 红包类型
     *
     * @var int
     */
    const SERVICE_TYPE_BONUS = 1;

    /**
     * 业务类型 放款
     *
     * @var int
     */
    const SERVICE_TYPE_LOAN = 2;

    /**
     * 业务类型 劵
     *
     * @var int
     */
    const SERVICE_TYPE_COUPON = 3;

    /**
     * 业务类型 强制还款
     *
     * @var int
     */
    const SERVICE_TYPE_REPAY = 4;

    /**
     * 业务类型 提前还款
     *
     * @var int
     */
    const SERVICE_TYPE_PREPAY = 5;

    /**
     * 业务类型 专享项目放款
     *
     * @var int
     */
    const SERVICE_TYPE_PROJECT_LOAN = 6;

    /**
     * 业务类型 专享项目还款
     *
     * @var int
     */
    const SERVICE_TYPE_PROJECT_REPAY = 7;

    /**
     * 业务类型 专享项目提前还款
     *
     * @var int
     */
    const SERVICE_TYPE_PROJECT_PREPAY = 8;

    /**
     * 业务类型 尤金项目
     *
     * @var int
     */
    const SERVICE_TYPE_GOLD_PROJECT_LOAN = 100;

    /**
     * 操作类型 新增操作
     *
     * @var string
     */
    const OPERATION_ADD  = 'add';

    /**
     * 操作类型 更新操作
     *
     * @var string
     */
    const OPERATION_SAVE = 'save';

    /**
     * 审核状态 未审核
     *
     * @var int
     */
    const NOT_AUDIT  = 1;

    /**
     * 审核状态 审核成功
     *
     * @var int
     */
    const AUDIT_SUCC = 2;

    /**
     * 审核状态 审核失败
     *
     * @var int
     */
    const AUDIT_FAIL = 3;

    /**
     * 业务审核状态
     *
     * @var array
     */
    public static $auditStatus = array(
        0 => ' 全部 ',
        1 => '待审核',
        2 => '已通过',
        3 => '未通过',
    );

    /**
     * 新增或更新审核任务
     *
     * @param $operation   string 操作类型 只能取 add新增 save更新
     * @param $param array 要保存的数据, 传入数据格式如下
     * array(
     *    service_type => 1,  //必填, 业务类型ID  SERVICE_TYPE_BONUS 代表红包业务 ...
     *    service_id   => 2,  //必填, 业务ID
     *    standby_1    => ''  //选填, 业务检索字段，根据某些条件检索待审数据时候用得上
     *    standby_2    => ''  //选填, 业务检索字段，根据某些条件检索待审数据时候用得上
     *    status       => 1   //必填, 审核状态 AUDIT_FAIL ...
     *    mark         => ''  //选填, 审核失败原因
     * )
     *
     * @return bool 操作成功返回true否则返回false
     */
    public function opServiceAudit($param, $operation = self::OPERATION_ADD) {
        if (!in_array($operation, array(self::OPERATION_ADD, self::OPERATION_SAVE))) {
            throw new Excetion("operation 参数错误");
        }

        $findRes = $this->queryTaskByServiceId($param);
        if ((self::OPERATION_ADD == $operation) ^ empty($findRes)) {
            return false;
        }

        $time = time();
        $param['update_time'] = $time;
        if (self::OPERATION_ADD == $operation) {
            $param['create_time'] = $time;
        }

        $param = array_merge((array)$findRes, $param);
        return $this->data($param)->$operation();
    }

    /**
     * 查询单个审核任务的详细信息
     *
     * @param $param array 查询条件, 传入数据格式如下
     * array(
     *    service_type => 1,  //必填, 业务类型ID  SERVICE_TYPE_BONUS 代表红包业务 ...
     *    service_id   => 2,  //必填, 业务ID
     * )
     *
     * @return mix 操作成功返回array 否则返回false
     */
    public function queryTaskByServiceId($param) {
        $serviceType = $param['service_type'];
        if (!in_array($serviceType, array(self::SERVICE_TYPE_BONUS, self::SERVICE_TYPE_LOAN, self::SERVICE_TYPE_REPAY, self::SERVICE_TYPE_PREPAY, self::SERVICE_TYPE_COUPON, self::SERVICE_TYPE_PROJECT_LOAN, self::SERVICE_TYPE_PROJECT_REPAY, self::SERVICE_TYPE_PROJECT_PREPAY,self::SERVICE_TYPE_GOLD_PROJECT_LOAN))) {
            throw new Exception("service_type 业务类型错误");
        }

        $serviceId = $param['service_id'];
        if (empty($serviceId)) {
            throw new Exception("service_id 业务id必须");
        }

        return $this->where(array('service_type' => $serviceType ,'service_id' => $serviceId))->find();
    }

    /**
     * 获取最后一次审核失败时候的错误信息
     *
     * @param $param array 查询任务表的结果, 即：$this->queryTaskByServiceId($param) 的返回值
     *
     * @return string 最后一次审核失败时候的错误信息
     */
    public function getLastAuditError($param) {
        $mark = json_decode($param['mark'], true);
        return is_array($mark) ? array_pop($mark) : '';
    }

    /**
     * 根据任务数据，判断是否能修改业务数据
     *
     * @param $param array 查询任务表的结果, 即：$this->queryTaskByServiceId($param) 的返回值
     *
     * @return bool
     */
    public function isTaskCanEdit($param) {
        return !in_array($param['status'], array(ServiceAuditModel::NOT_AUDIT, ServiceAuditModel::AUDIT_SUCC));
    }


    /**
     * 添加最近一次审核失败时候的错误信息
     *
     * @param $param array 查询任务表的结果, 即：$this->queryTaskByServiceId($param) 的返回值
     * @param $param string 失败失败的原因
     *
     * @return array 包含失败信息的任务表数据
     */
    public function addLastAuditError($param, $reason) {
        $mark = json_decode($param['mark'], true);
        if (empty($mark)) {
            $mark = array();
        }

        array_push($mark, $reason);
        $param['mark'] = json_encode($mark);
        return $param;
    }

    /**
     * 根据业务类型和审核状态获取审核信息
     *
     * @param int $service_type
     * @param int $status
     *
     * @return array key 为 service_id
     */
    public function getAuditListByTypeAndStatus($service_type_arr = array(), $status_arr = array())
    {
        $cond = array();
        if (!empty($service_type_arr)) {
            $cond['service_type'] = array('IN', array_map('intval', $service_type_arr));
        }

        if (!empty($status_arr)) {
            $cond['status'] = array('IN', array_map('intval', $status_arr));
        }

        $audit_list = $this->where($cond)->select();
        $audit_list_new = array();
        foreach ($audit_list as $audit) {
            $audit_list_new[$audit['service_id']] = $audit;
        }

        return $audit_list_new;
    }

    /**
     * 审核
     *
     * @param array $service ['name', 'create_time']
     * @param string $role 当前角色 a | b
     * @param array $audit 之前的审核信息，没有则为空
     * @param int $audit_type 审核类型
     * @param boolean $is_agree B 角时，是否同意审核
     * @param string $return_reason B 角时，退回原因
     * @return int 失败 1 通过审核 2 拒绝
     */
    public function audit($service, $role, $audit, $audit_type, $is_agree = 0, $return_reason = '')
    {
        $param = array();
        $param['service_type'] = $audit_type;
        $param['service_id']   = $service['id'];
        $param['status']       = self::NOT_AUDIT;

        // 是否有过提交记录
        if (empty($audit)) {
            $param['standby_1']    = empty($service['name']) ? '' : $service['name'];
            $param['standby_2']    = empty($service['create_time']) ? '' : $service['create_time'];
            $operation = self::OPERATION_ADD;
        } else {
            $operation = self::OPERATION_SAVE;
        }

        // 分角色审核状态
        $admin = \es_session::get(md5(conf("AUTH_KEY")));
        if ($role == 'b') {
            $param['audit_uid']   = $admin['adm_id']; //审核用户
            $param['status'] = $is_agree ? self::AUDIT_SUCC : self::AUDIT_FAIL;
            $param['mark'] = $return_reason;
        } else {
            $param['submit_uid'] = $admin['adm_id']; //提交审核的用户
        }

        if (!$this->opServiceAudit($param, $operation)) {
            return 0; //审核错误
        } elseif ($role == 'b') {
            return $is_agree ? 1 : 2; // 1: 审核通过；2：退回；
        } else {
            return 3; // A 提交审核
        }
    }
}

?>
