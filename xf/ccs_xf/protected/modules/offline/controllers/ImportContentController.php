<?php

/**
 * 出借记录
 * 导入文件明细查询与导出等
 * Class OfflineFileManageController.
 */
class ImportContentController extends \iauth\components\IAuthController
{
    /**
     * 导入明细展示.
     */
    public function actionList()
    {
        $request          = \Yii::app()->request;
        $file_id          = $request->getParam('file_id');
        $type             = $request->getParam('type') ?: 0;
        $page             = $request->getParam("page") ?: 1;
        $pageSize         = $request->getParam("limit") ?: 10;
        $p_name           = $request->getParam('p_name') ?: '';
        $object_sn        = $request->getParam('object_sn') ?: '';
        $mobile_phone     = $request->getParam('mobile_phone') ?: '';
        $old_user_id      = $request->getParam('old_user_id') ?: '';
        $zdx_deal_load_id = $request->getParam('zdx_deal_load_id') ?: '';
        $order_sn         = $request->getParam('order_sn') ?: '';
        $execl            = $request->getParam('execl') ?: '';
        $p                = $request->getParam('p') ?: '';

        if ((!$fileModel = Yii::app()->offlinedb->createCommand()
                ->select("platform_id,auth_status")
                ->from(OfflineImportFile::tableName())
                ->where("id = {$file_id} and platform_id = {$p}")
                ->queryRow()) || empty($p) || empty($file_id)) {
            if ($request->isPostRequest) {
                exit(json_encode(['code' => 1, 'info' => '文件不存在']));
            } else {
                return $this->renderPartial('success', array('type' => 2, 'msg' => '文件不存在', 'time' => 3));
            }
        }

        $platform_id = $fileModel['platform_id'];
        $auth_status = $fileModel['auth_status'];
        if (!$request->isPostRequest && !$request->getParam('execl')) {
            return $this->renderPartial('importContentList', array('end' => 0, 'p' => $request->getParam('p'), 'auth_status' => $auth_status));
        }

        $where = "file_id = {$file_id}";
        if ($p_name && $p_name != 'undefined') {
            $where .= " and p_name like '%{$p_name}%'";
        }
        if ($object_sn && $object_sn != 'undefined') {
            $where .= " and object_sn = '{$object_sn}'";
        }
        if ($mobile_phone && $mobile_phone != 'undefined') {
            $where .= " and mobile_phone = '{$mobile_phone}'";
        }
        if ($old_user_id && $old_user_id != 'undefined') {
            $where .= " and old_user_id = '{$old_user_id}'";
        }
        if ($zdx_deal_load_id && $zdx_deal_load_id != 'undefined') {
            $where .= " and zdx_deal_load_id = '{$zdx_deal_load_id}'";
        }
        if ($order_sn && $order_sn != 'undefined') {
            $where .= " and order_sn = '{$order_sn}'";
        }

        $count_where = $where;
        if ($type) {
            $where .= " and status = {$type}";
        }

        if ($execl) {
            $sysConfig = include APP_DIR . '/protected/config/offline_title.php';
            $title     = $sysConfig['borrow'][$platform_id]['title'];
            $columns   = $sysConfig['borrow'][$platform_id]['columns'];
            $sql       = "select * from offline_import_content  where {$where}";
            $list      = Yii::app()->offlinedb->createCommand($sql)->queryAll();
            $list      = array_map(array("Offline", "formatArray"), $list);
            $export    = new Offline();
            exit($export->export($title, $list, $columns, '出借记录导出'));
        }

        $count = Yii::app()->offlinedb->createCommand()
            ->select("count(1)")
            ->from(OfflineImportContent::tableName())
            ->where("{$where}")
            ->queryScalar();
        if ($count > 0 && !$execl) {
            $page     = $page ?: 1;
            $pageSize = $pageSize ?: 10;
            $offset   = ($page - 1) * $pageSize;
            $sql      = "select * from offline_import_content  where {$where} LIMIT {$offset} , {$pageSize} ";
            $data     = Yii::app()->offlinedb->createCommand($sql)->queryAll();
            $data      = array_map(array("Offline", "formatArray"), $data);
        }


        //全部
        $all_num = OfflineImportContent::model()->countBySql(
            "select count(1) from offline_import_content where $count_where"
        );
        // 状态统计
        $sql    = "select status,count(1) as num from offline_import_content where $count_where and status in(1,2,4,5) group by status";
        $status = Yii::app()->offlinedb->createCommand($sql)->queryAll();
        $status = ItzUtil::array_column($status, 'num', 'status');
        exit(json_encode([
            'data'          => $data,
            'code'          => 0,
            'count'         => $count,
            'type'          => $type,
            'file_id'       => $file_id,
            'all_num'       => $all_num,
            'l_success_num' => $status[1] ?: 0,
            'l_fail_num'    => $status[2] ?: 0,
            'r_success_num' => $status[4] ?: 0,
            'r_fail_num'    => $status[5] ?: 0,
            'platform_id'   => $platform_id,
        ])
        );
    }

    /**
     * 批量导出
     * @throws CException
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function actionExportContent()
    {
        set_time_limit(10);
        $request     = Yii::app()->request;
        $type        = $request->getParam('type') ?: 0;
        $file_id     = $request->getParam('file_id') ?: 0;
        $platform_id = Yii::app()->offlinedb->createCommand()
            ->select('platform_id')
            ->from('offline_import_file')
            ->where("id = {$file_id}")
            ->queryScalar();

        $sysConfig = include APP_DIR . '/protected/config/offline_title.php';
        $title     = $sysConfig['borrow'][$platform_id]['title'];
        $columns   = $sysConfig['borrow'][$platform_id]['columns'];

        $where = "file_id = {$file_id}";
        switch ($type) {
            case 1:
                $where .= ' and status = 1 and deal_status = 1';
                break;
            case 2:
                $where .= ' and (status = 2 or (status = 1 and deal_status = 2))';
                break;
            case 3:
                $where .= ' and status = 1 and deal_status = 0';
                break;
        }

        $data = Yii::app()->offlinedb->createCommand()
            ->select("*")
            ->from(OfflineImportContent::tableName())
            ->where("{$where}")
            ->queryAll();

        $export = new Offline();
        $export->export($title, $data, $columns, '出借记录导出');
    }


}
