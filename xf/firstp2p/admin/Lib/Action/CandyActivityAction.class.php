<?php
/**
 * 信力列表及充值
 */
use libs\db\Db;
use core\service\candy\CandyActivityService;

class CandyActivityAction extends CommonAction
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 添加信力模板渲染
     */
    public function applyForm()
    {
        $this->assign('sourceTypeConf', CandyActivityService::$sourceTypeConf);
        $this->display();
    }

    /**
     * 批量添加信力模板渲染
     */
    public function applyMulti()
    {
        $this->display();
    }

    //未审批
    const APPLY_STATUS_NORMAL = 0;
    //审批通过
    const APPLY_STATUS_PASSED = 1;
    //审批拒绝
    const APPLY_STATUS_REFUSED = 2;

    private static $applyStatusMap = array(
        self::APPLY_STATUS_NORMAL => '未审批',
        self::APPLY_STATUS_PASSED => '审批通过',
        self::APPLY_STATUS_REFUSED => '审批拒绝',
    );

    /**
     * 全部充值记录
     */
    public function index()
    {
        $userId = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        $status = !empty($_REQUEST['status']) ? intval($_REQUEST['status']) : self::APPLY_STATUS_NORMAL;

        $model = M('activity_charge_apply', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('activity_charge_apply');

        $condition = "1";
        if (!empty($userId)) {
            $condition .= " AND user_id = '{$userId}'";
        }
        $condition .= " AND status = '{$status}'";

        $this->_list($model, $condition);
        $list = $this->get('list');

        foreach ($list as $key => $value) {
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $list[$key]['audit_time'] = $value['audit_time'] ? date('Y-m-d H:i:s', $value['audit_time']) : '-';
            $list[$key]['status_name'] = self::$applyStatusMap[$value['status']];
        }

        $this->assign('list', $list);
        $this->assign('applyStatusMap', self::$applyStatusMap);
        $this->assign('status', $status);
        $this->display();
    }

    /**
     * 提交审核申请或拒绝
     */
    public function applyAudit()
    {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;

        $newdb = Db::getInstance('candy');
        $newdb->startTrans();
        try {
            if ($status == self::APPLY_STATUS_PASSED) {
                $this->insertApply($id);
            }
            $this->updateApply($status, $id);
            $newdb->commit();
        } catch (\Exception $e) {
            $newdb->rollback();
            $this->error($e->getMessage(), 1);
        }
        
        $this->success('操作成功', 1);
    }

    /**
     * 保存信力记录
     * @param $id 要读取数据的行数
     */
    private function insertApply($id)
    {
        $newdb = Db::getInstance('candy');
        $sql = 'select * from activity_charge_apply where id='.$id;
        $row = $newdb->getRow($sql);
        $token = 'admin_'. $row['source_type'].'_'.$row['user_id'].'_'.$row['id'];
        $userId = $row['user_id'];
        $activity = $row['activity'];
        $sourceType = $row['source_type'];
        $note = $row['note'];
        return (new CandyActivityService())->addActivity($token, $userId, $activity, $sourceType, $note);
    }

    /**
     * 更改信力充值表状态
     * @param $status 状态
     * @param $id 要读取数据的行数
     */
    private function updateApply($status, $id)
    {
        $newdb = Db::getInstance('candy');
        $auditor = es_session::get(md5(conf("AUTH_KEY")))['adm_name'];
        $auditTime = time();
        $dataCharge = array(
            'status' => $status,
            'auditor' => $auditor,
            'audit_time' => $auditTime,
        );
        $newdb->update('activity_charge_apply',$dataCharge,'id='.$id.' AND status='.self::APPLY_STATUS_NORMAL);

        if ($newdb->affected_rows() < 1) {
            throw new \Exception('审核状态更新失败');
        }
    }

    /**
     * 保存单条记录
     */
    public function applySingle()
    {
        $userId = isset($_REQUEST['userId']) ? intval($_REQUEST['userId']) : 0;
        $activity = isset($_REQUEST['activity']) ? intval($_REQUEST['activity']) : 0;
        $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
        $note = isset($_REQUEST['note']) ? addslashes(trim($_REQUEST['note'])) : '';
        $user = es_session::get(md5(conf("AUTH_KEY")))['adm_name'];
        $time = time();

        if ($userId <= 0) {
            $this->error('用户ID不合法', 1);
        }
        if ($activity <= 0) {
            $this->error('信力值不合法', 1);
        }

        $this->applySave($userId,$activity,$type,$note,$user,$time);
        $this->success('申请成功', 1);
    }

    /**
     * 写入申请记录
     */
    public function applySave($userId,$activity,$type,$note,$user,$time)
    {
        $data = array(
            'user_id' => $userId,
            'activity' => $activity,
            'source_type' => $type,
            'note' => $note,
            'create_time' => $time,
            'update_time' => $time,
            'creator' => $user,
        );
        Db::getInstance('candy')->insert('activity_charge_apply', $data);
    }


    /**
     * 批量插入申请信力充值
     */
    public function applyMultiSave()
    {
        $fileName = isset($_FILES['file']['tmp_name']) ? $_FILES['file']['tmp_name'] : '';
        if (empty($fileName)) {
            $this->error('文件不存在');
        }
        $handle = fopen($fileName, 'r');
        $result = $this->analyzCsv($handle);
        $lengthResult = count($result);
        if ($lengthResult <= 1) {
            $this->error('没有任何数据');
        }
        if ($lengthResult > 2000) {
            $this->error('数据多于2000条，请分表填写');
        }

        //将CSV文件中的数据逐条插入到申请表里
        $creator = es_session::get(md5(conf("AUTH_KEY")))['adm_name'];
        $time = time();
        Db::getInstance('candy')->startTrans();
        for ($i = 1; $i < $lengthResult; $i++) {
            $userId = $result[$i][0];
            $activity = $result[$i][1];
            if ($activity <= 0) {
                $this->error('金额输入不正确，请重新输入');
            }
            $type = iconv('gb2312', 'utf-8', $result[$i][2]);
            $note = iconv('gb2312', 'utf-8', $result[$i][3]);
            $this->applySave($userId, $activity, $type, $note, $creator,$time);
        }
        Db::getInstance('candy')->commit();
        $this->success('操作成功');
    }

    /**
     * 处理上传的CSV文件
     */
    private function analyzCsv($handle)
    {
        $dataList = array();
        while ($tableData = fgetcsv($handle, 500)) {
            $dataList[] = $tableData;
        }
        return $dataList;
    }

    /**
     * 信力规则查询
     */
    public function rules()
    {
        $array = CandyActivityService::$sourceTypeConf;
        $this->assign('list', $array);
        $this->display();
    }

    /**
     * 批量操作申请
     */
    public function batchAudit()
    {
        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;

        if ($status == 0) {
            $this->error("操作失败",1);
        }

        $db = Db::getInstance('candy');
        $allNormalData = $db->getAll("SELECT id FROM activity_charge_apply WHERE status=".self::APPLY_STATUS_NORMAL);
        $db->startTrans();
        try{
            foreach ($allNormalData as $value) {
                if ($status == self::APPLY_STATUS_PASSED) {
                    $this->insertApply($value['id']);
                }
                $this->updateApply($status, $value['id']);
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            $this->error($e->getMessage(), 1);
        }
        $this->success('操作成功', 1);
    }
}
