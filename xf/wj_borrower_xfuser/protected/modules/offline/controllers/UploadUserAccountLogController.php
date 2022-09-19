<?php

/**
 * 还款计划
 * 导入明细的展示与导出等
 * Class OfflineFileManageController.
 */
class UploadUserAccountLogController extends \iauth\components\IAuthController
{
    /**
     * 导入文件列表.
     */
    public function actionList()
    {
        $request     = \Yii::app()->request;
        $file_id     = $request->getParam('file_id');
        $type        = $request->getParam('type') ?: 0;
        $page        = $request->getParam("page") ?: 1;
        $pageSize    = $request->getParam("limit") ?: 10;
        $old_user_id = $request->getParam('old_user_id') ?: 0;
        $execl       = $request->getParam('execl') ?: '';
        $p           = $request->getParam('p') ?: '';

        if ((!$fileModel = Yii::app()->offlinedb->createCommand()
                ->select("platform_id,auth_status")
                ->from(OfflineUploadUserAccountFile::tableName())
                ->where("id = {$file_id} and platform_id = {$p}")
                ->queryRow()) || empty($p) || empty($file_id)) {
            if ($request->isPostRequest) {
                exit(json_encode(['code' => 1, 'info' => '文件不存在']));
            } else {
                return $this->renderPartial('success', ['type' => 2, 'msg' => '文件不存在', 'time' => 3]);
            }
        }

        $platform_id = $fileModel['platform_id'];
        $auth_status = $fileModel['auth_status'];
        if (!$request->isPostRequest && !$request->getParam('execl')) {
            return $this->renderPartial('uploadAccountLogList', array('end' => 0, 'p' => $platform_id, 'file_id' => $file_id, 'auth_status' => $auth_status));
        }

        $where = "file_id = {$file_id}";
        if ($old_user_id) {
            $where .= " and old_user_id = '{$old_user_id}'";
        }

        $count_where = $where;
        if ($type) {
            $where .= " and status = {$type}";
        }

        if ($execl) {
            $sysConfig = include APP_DIR.'/protected/config/offline_title.php';
            $title     = $sysConfig['account'][$platform_id]['title'];
            $columns   = $sysConfig['account'][$platform_id]['columns'];
            $sql       = "select * from offline_upload_user_account_log  where {$count_where}";
            $list      = Yii::app()->offlinedb->createCommand($sql)->queryAll();
            $list      = array_map(array("Offline", "formatArray"), $list);
            $export    = new Offline();
            $export->export($title, $list, $columns, '用户待加入金额导出');
        }

        $count = Yii::app()->offlinedb->createCommand()
            ->select("count(1)")
            ->from(OfflineUploadUserAccountLog::tableName())
            ->where("{$where}")
            ->queryScalar();
        if ($count > 0 && !$execl) {
            $page     = $page ?: 1;
            $pageSize = $pageSize ?: 10;
            $offset   = ($page - 1) * $pageSize;
            $sql      = "select * from offline_upload_user_account_log where {$where} LIMIT {$offset} , {$pageSize} ";
            $data     = Yii::app()->offlinedb->createCommand($sql)->queryAll();
            $data      = array_map(array("Offline", "formatArray"), $data);
        }


        //全部
        $all_num = OfflineUploadUserAccountLog::model()->countBySql(
            "select count(1) from offline_upload_user_account_log where $count_where"
        );

        // 状态统计
        $sql    = "select status,count(1) as num from offline_upload_user_account_log where $count_where and status in(1,2,4,5) group by status";
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
}
