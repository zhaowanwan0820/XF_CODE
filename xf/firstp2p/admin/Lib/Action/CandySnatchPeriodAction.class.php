<?php
/**
 * Created by PhpStorm.
 * User: wangpeipei
 * Date: 2018/10/26
 * Time: 15:33
 */

use libs\db\Db;
use Libs\utils\Logger;
use core\service\UserService;
use core\service\AddressService;

class CandySnatchPeriodAction extends CommonAction
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $status = !empty($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;
        $productName = !empty($_REQUEST['productName']) ? addslashes(trim($_REQUEST['productName'])) : '';
        $candyAmount = !empty($_REQUEST['candyAmount']) ? addslashes(trim($_REQUEST['candyAmount'])) : '';
        $userId = !empty($_REQUEST['userId']) ? intval($_REQUEST['userId']) : '';
        $periodId = !empty($_REQUEST['periodId']) ? intval($_REQUEST['periodId']) : '';
        $mobile = !empty($_REQUEST['mobile']) ? addslashes(trim($_REQUEST['mobile'])) : '';
        $startNewTime = !empty($_REQUEST['startNewTime']) ? strtotime($_REQUEST['startNewTime']) : '';
        $endNewTime = !empty($_REQUEST['endNewTime']) ? strtotime($_REQUEST['endNewTime']) : '';
        $startPrizeTime = !empty($_REQUEST['startPrizeTime']) ? strtotime($_REQUEST['startPrizeTime']) : '';
        $endPrizeTime = !empty($_REQUEST['endPrizeTime']) ? strtotime($_REQUEST['endPrizeTime']) : '';
        $model = M('snatch_period', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('snatch_period');

        $condition = "1";

        if (!empty($status)) {
            $condition .= " AND status = '{$status}'";
        } else {
            $condition .= " AND status > 1";
        }

        if (!empty($productName)) {
            $productIds = Db::getInstance('candy')->getAll("SELECT id FROM snatch_product WHERE short_title LIKE '%{$productName}%'");
            $productIds = array_column($productIds, 'id');
            $productIds = implode(',', $productIds);
            $condition .= " AND product_id in ({$productIds})";
        }

        if (!empty($candyAmount)) {
            $condition .= " AND code_total = '{$candyAmount}'";
        }

        if (!empty($userId)) {
            $condition .= " AND user_id = '{$userId}'";
        }

        if (!empty($periodId)) {
            $condition .= " AND id = '{$periodId}'";
        }

        if (!empty($mobile)) {
            $userId = (new UserService())->getUserIdByMobile($mobile);
            $condition .= " AND user_id = '{$userId}'";
        }

        if (!empty($startNewTime)) {
            $condition .= " AND create_time > '{$startNewTime}'";
        }

        if (!empty($endNewTime)) {
            $condition .= " AND create_time < '{$endNewTime}'";
        }

        if (!empty($startPrizeTime)) {
            $condition .= " AND prize_time > '{$startPrizeTime}'";
        }

        if (!empty($endPrizeTime)) {
            $condition .= " AND prize_time < '{$endPrizeTime}'";
        }

        if (empty($_REQUEST['export'])) {
            $this->_list($model, $condition);
            $list = $this->get('list');
            $list = $this->getUserExtraInfo($list);

            $this->assign('list', $list);
            $this->assign('status', $status);
            $this->assign('productName', $productName);
            $this->assign('candyAmount', $candyAmount);
            $this->assign('userId', $userId);
            $this->assign('periodId', $periodId);
            $this->assign('mobile', $mobile);
            $this->assign('startNewTime', date('Ymd', $startNewTime));
            $this->assign('endNewTime', date('Ymd', $endNewTime));
            $this->assign('startPrizeTime', date('Ymd', $startPrizeTime));
            $this->assign('endPrizeTime', date('Ymd', $endPrizeTime));
            $this->display();
        } else {
            $sql = 'SELECT * FROM snatch_period WHERE ' . $condition;
            $res = Db::getInstance('candy')->getAll($sql);
            if (!$res) {
                $this->error('??????????????????');
            }
            $res = $this->getUserExtraInfo($res);
            //??????????????????
            setLog(
                array(
                    'sensitive' => 'exportPeriodInfo',
                    'analyze' => $sql
                )
            );
            $datatime = date("YmdHis", time());
            $file_name = '????????????_' . $datatime;
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $file_name . '.csv"');
            header('Cache-Control: max-age=0');

            $fp = fopen('php://output', 'a');
            $count = 1; // ?????????
            $limit = 10000; // ??????$limit????????????????????????buffer?????????????????????????????????

            $head = array("??????", "??????", "????????????", "????????????", "????????????", "????????????", "????????????", "?????????", "????????????", "?????????", "??????");
            foreach ($head as &$item) {
                $item = iconv("utf-8", "gbk//IGNORE", $item);
            }
            fputcsv($fp, $head);

            foreach ($res as $val) {
                $status = '';
                if ($val['status'] == 2) {
                    $status = '?????????';
                } else {
                    $status = '?????????';
                }
                $count++;
                if ($count % $limit == 0) { //??????????????????buffer???????????????????????????????????????
                    ob_flush();
                    flush();
                    $count = 0;
                }

                $execute = '';
                if ($val['status'] == 2 && !empty($val['name'])) {
                    $execute = '??????';
                }
                $arr = array(
                    $val['id'],
                    $status,
                    $val['product_name'],
                    $val['code_total'],
                    $val['user_id'],
                    $val['prize_code'],
                    $val['prize_time'],
                    $val['name'],
                    $val['address'],
                    $val['mobile'],
                    $execute
                );
                foreach ($arr as &$item) {
                    $item = iconv("utf-8", "gbk//IGNORE", $item);
                }
                fputcsv($fp, $arr);
            }
            EXIT;
        }

    }

    private function getUserExtraInfo(array $list)
    {
        $cacheInfo = array();
        foreach ($list as $key => $value) {
            $list[$key]['product_name'] = Db::getInstance('candy')->getOne("SELECT short_title FROM snatch_product WHERE id = '{$value['product_id']}'");
            $list[$key]['prize_time'] = date("Y-m-d H:i:s", $value['prize_time']);
            if (array_key_exists($value['user_id'], $cacheInfo)) {
                $list[$key]['register_mobile'] = $cacheInfo[$value['user_id']];
            } else {
                $mobileInfo = (new UserService())->getUserByUserId($value['user_id'], 'mobile');
                $list[$key]['register_mobile'] = empty($mobileInfo) ? '' : $mobileInfo['mobile'];
                $cacheInfo [$value['user_id']] = $list[$key]['register_mobile'];
            }
            if ($value['address_id'] != 0) {
                try {
                    $addressInfo = (new AddressService())->getOne($value['user_id'], $value['address_id']);
                    $list[$key]['name'] = $addressInfo['consignee'];
                    $list[$key]['address'] = $addressInfo['area'] . $addressInfo['address'];
                    $list[$key]['mobile'] = $addressInfo['mobile'];
                } catch (\Exception $e) {
                    Logger::error('??????userId???:' . $value['user_id'] . '?????????id' . $value['address_id'] . '?????????????????????');
                }
            }
        }
        return $list;
    }

    public function edit()
    {
        $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $expressCompany = !empty($_REQUEST['expressCompany']) ? addslashes(trim($_REQUEST['expressCompany'])) : '';
        $expressOrderId = !empty($_REQUEST['expressOrderId']) ? addslashes(trim($_REQUEST['expressOrderId'])) : '';

        if (empty($id)) {
            throw new \Exception('?????????????????????id??????');
        } elseif (empty($expressCompany) || empty($expressOrderId)) {
            throw new \Exception('???????????????????????????');
        }

        $newdb = Db::getInstance('candy');
        $dataCharge = array(
            'status' => 3,
            'express_company' => $expressCompany,
            'express_order_id' => $expressOrderId
        );
        $newdb->update('snatch_period', $dataCharge, 'id=' . $id);
        if ($newdb->affected_rows() < 1) {
            throw new \Exception('??????????????????');
        }
        $this->success('????????????', 0, '?m=CandySnatchPeriod&a=index');

    }
}
