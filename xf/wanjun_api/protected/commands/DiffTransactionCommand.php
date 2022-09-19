<?php
class DiffTransactionCommand extends CConsoleCommand {

    /**
     * @param type int 1-普惠 2-尊享
     * @param id int 项目ID
     */
	public function actionRun($type = 2 , $id = 0){
        if ($type == 1) {
            $source_type = ' AND source_type = 0 ';
            $model  = Yii::app()->phdb;
        } else if ($type == 2) {
            $source_type = ' AND source_type != 0 ';
            $model  = Yii::app()->db;
        }else{
            $model  = Yii::app()->cdb;
        }
        if (!empty($id)) {
            $deal_id = " AND deal_id = {$id} ";
        } else {
            $deal_id = '';
        }
        $sql    =  "SELECT count(DISTINCT  deal_loan_id) as countNum FROM firstp2p_deal_loan_repay  
                    WHERE
                    money > 0
                    AND
                    type = 1
                    AND
                    status = 0
                    ";

        $count  = $model->createCommand($sql)->queryScalar();
        $total  = ceil($count/500);
        $myfile = fopen("test.txt", "w") or die("Unable to open file!");
        $modelcdb  = Yii::app()->cdb;
        for ($i = 0; $i < $total; $i++) {
            $a      = $i*500;
            $limit  = "{$a},500";
            $sql    =  "SELECT
                        deal_id,
                        deal_loan_id,
                        loan_user_id
                        FROM
                        firstp2p_deal_loan_repay
                        WHERE
                        money > 0
                        AND
                        type = 1
                        AND
                        status = 0
                        {$deal_id}
                        GROUP BY
                        deal_loan_id
                        LIMIT {$limit}";
            $result = $model->createCommand($sql)->queryAll();
            $ok     = 0;
            $not_ok = 0;
            foreach ($result as $key => $value) {
                $table_name = 'contract_'.($value['deal_id']%128);
                $sql    =  "SELECT
                        id
                        FROM
                        {$table_name}
                        WHERE
                        deal_load_id = {$value['deal_loan_id']}
                        AND
                        user_id = {$value['loan_user_id']}
                        AND
                        type = 1
                        AND
                        status = 1
                        {$source_type}
                        {$deal_id}
                        LIMIT 1";
                $result = $modelcdb->createCommand($sql)->queryRow();
                if (!$result) {
                    $txt = "没有找到对应合同的deal_id：{$value['deal_id']}\n";
                    fwrite($myfile, $txt);
                    echo '没有找到对应合同的deal_id：'.$value['deal_id'].'<br>';
                    $not_ok++;
                } else {
                    $ok++;
                }
            }
        }
        echo '成功找到对应合同的数量：'.$ok.'<br>';
        echo '没有找到对应合同的数量：'.$not_ok.'<br>';
        $txt = "成功找到对应合同的数量：{$ok}\n";
        fwrite($myfile, $txt);
        $txt = "没有找到对应合同的数量：{$not_ok}\n";
        fwrite($myfile, $txt);
        fclose($myfile);
	}
}
