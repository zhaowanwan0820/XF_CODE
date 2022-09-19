<?php
/**
 * FeeAfterGrantAction class file.
 * */
use core\service\deal\FeeAfterGrantService;
use core\dao\deal\FeeAfterGrantModel;
use libs\db\Db;


define('__DEBUG', false);

class FeeAfterGrantAction extends CommonAction{
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $map = $this->getMap();
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $this->_list(MI('FeeAfterGrant'), $map);
        $this->assign('resultMap', FeeAfterGrantService::$resultMap);
        $this->assign('p', $p);
        $this->display('index');
    }


    public function export()
    {
        $map = $this->getMap();
        $head = [
            '编号', '标的Id', '放款时间', '借款标题', '借款人', '回调时间', '代扣金额', '代扣结果', '失败原因', '创建时间',
        ];

        $conditionStr = $this->getConditionByMap($map);

        $sql = " SELECT id,deal_id,FROM_UNIXTIME(grant_time+28800) as grant_time,deal_name,deal_user_name,FROM_UNIXTIME(callback_time) as callback_time,fee_amount, charge_result, fail_reason, FROM_UNIXTIME(create_time) as create_time FROM firstp2p_fee_after_grant WHERE 1 {$conditionStr}";

        $db = Db::getInstance('firstp2p', 'slave');
        $res = $db->query($sql);
        if ($res === false) {
            $this->error('导出数据为空');
        }

        $datatime = date("YmdHis", time());
        $file_name = 'fee_after_grant_' . $datatime;
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $file_name . '.csv"');
        header('Cache-Control: max-age=0');

        $fp = fopen('php://output', 'a');
        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        //$content = iconv("utf-8", "gbk//IGNORE", "编号,付款单号,创建时间,支付时间,是否已支付,订单号,会员名称,收款方式,银行卡,付款单金额,支付平台交易号,付款单备注") . "\n";
        foreach ($head as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $head);

        while($val = $GLOBALS['db']->fetchRow($res)) {
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            $arr = array(
                    $val['id'],
                    $val['deal_id'],
                    $val['grant_time'],
                    $val['deal_name'],
                    $val['deal_user_name'],
                    $val['callback_time'],
                    $val['fee_amount'],
                    FeeAfterGrantService::$resultMap[$val['charge_result']],
                    $val['fail_reason'],
                    $val['create_time'],
            );
            foreach ($arr as &$item) {
                $item = iconv("utf-8", "gbk//IGNORE", $item);
            }
            fputcsv($fp, $arr);
        }
        EXIT;
    }

    public function resend()
    {
        $id = intval($_GET['id']);
        if (empty($id))
        {
            $this->error("重新发送的编号不正确");
        }

        $record = FeeAfterGrantModel::instance()->find($id);
        if (empty($record))
        {
            $this->error("重新发送的编号不正确");
        }

        $feeAfterGrantService = new FeeAfterGrantService();

        $result = $feeAfterGrantService->requestChargeFeeAfterGrant($record['deal_id']);
        if (!$result)
        {
            $this->error("重新发送失败");
        }
        $this->success("重新发送成功");
    }

    public function getConditionByMap($map)
    {
        if (empty($map))
        {
            return 0;
        }
        $condition = '';

        foreach ($map as $field => $value)
        {
            if (!is_array($value))
            {
                $condition .= " AND {$field} = '".addslashes($value)."'";
            } else {
                $operationName = strtoupper(array_shift($value));
                $valueString = '';
                switch($operationName)
                {
                    case 'IN':
                        $valueString = '(' .implode(',', $value[0]).')';
                        break;
                    case 'BETWEEN':
                        $valueString = " {$value[0][0]} AND {$value[0][1]} ";
                        break;
                }
                $condition .= " AND {$field} {$operationName} {$valueString}";
            }
        }


        return $condition;
    }


    /**
     * 根据表单提交的字段拼装form属性
     */
    public function getMap()
    {
        $map = [];
        if(isset($_GET['charge_result']) && $_GET['charge_result'] != '-1') {
            $map['charge_result'] =  intval($_GET['charge_result']);
        }
        else {
            $statusArray = array_keys(FeeAfterGrantService::$resultMap);
            array_shift($statusArray);
            $map['charge_result'] = ['in', $statusArray];
        }
        if (!empty($_GET['deal_id']))
        {
            $map['deal_id'] = intval($_GET['deal_id']);
        }
        if (!empty($_GET['create_time_start']) && !empty($_GET['create_time_end']))
        {
            $map['create_time'] = ['between' , [strtotime($_GET['create_time_start']), strtotime($_GET['create_time_end'])]];
        }
        if (!empty($_GET['callback_time_start']) && !empty($_GET['callback_time_start']))
        {
            $map['callback_time'] = ['between' , [strtotime($_GET['callback_time_start']), strtotime($_GET['callback_time_end'])]];
        }
        return $map;
    }

}
