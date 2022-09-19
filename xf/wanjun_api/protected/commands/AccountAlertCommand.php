<?php

class AccountAlertCommand extends CConsoleCommand
{

    public function actionHandel(){
        self::echoLog("runing....");
        $file_path = APP_DIR."/upload/load.csv";
        if(!file_exists($file_path)){
            self::echoLog("no such file....");
            return false;
        }

        //存储地址
        $file_ret = APP_DIR."/upload/handel_user_ret.csv";
        $tmp_file_ret = fopen($file_ret, "w");
        $title_user = [
            'deal_name' => '借款标题',
            'load_id' => '投资记录ID',
            'user_id' => '用户ID',
            'capital' => '还款金额（单位元，保留两位小数）',
        ];
        $out_user = iconv("UTF-8","GB2312//IGNORE",implode(",", $title_user))."\n";
        fwrite($tmp_file_ret, $out_user);

        //所有明细
        $handle = fopen($file_path, "r");
        //循环处理
        while(!feof($handle)){
            $data = fgetcsv($handle);
            if(!$data || !is_numeric($data['0']) || !is_numeric($data['1']) || $data['1']<=0 ){
                self::echoLog("no such file....num:".$data['0']);
                break;
            }

            $user_id = $data['0'];
            $sup_capital = $total_capital = $data['1'];
            self::echoLog("handing user_id:$user_id start …… ");
            $sql = "select dl.user_id,dd.name as deal_name,dl.id as load_id,dl.deal_id,dl.wait_capital 
                    from firstp2p_deal_load dl 
                    left join firstp2p_deal dd on dd.id=dl.deal_id 
                    where dl.user_id=$user_id and dl.wait_capital>0 and dl.status=1 order by dl.creat_time asc";
            $load_list = Yii::app()->phdb->createCommand($sql)->queryAll();
            if(!$load_list){
                self::echoLog("user_id:$user_id deal_load data empty....");
                break;
            }
            //选定投资记录
            $total_load = count($load_list);
            foreach ($load_list as $key=>$value){
                if($value['wait_capital'] >= $total_capital){
                    $tmp_capital = $total_capital;
                    $sup_capital = 0;
                }else{
                    $tmp_capital = $value['wait_capital'];
                    $sup_capital = bcsub($total_capital, $tmp_capital, 2);
                }

                $data = [
                    'deal_name' => $value['deal_name'],
                    'load_id' => $value['load_id'],
                    'user_id' => $value['user_id'],
                    'capital' => $tmp_capital,
                ];
                $out = iconv("UTF-8","GB2312//IGNORE", implode(",",$data))."\n";
                fwrite($tmp_file_ret, $out);
                if($sup_capital == 0){
                    self::echoLog("user_id:$user_id deal_load end...");
                    break;
                }
                if($key+1 == $total_load){
                    self::echoLog("user_id:$user_id deal_load error  sub_capital:$sup_capital");
                    break;
                }
            }
            fclose($tmp_file_ret);
        }
        fclose($handle);
        self::echoLog("end....");
        return true;
    }




    /**
     * 日志记录
     * @param $yiilog
     * @param string $level
     */
    public function echoLog($yiilog, $level = "info") {
        echo date('Y-m-d H:i:s ')." ".microtime()."AccountAlert {$yiilog} \n";
        Yii::log("AccountAlert: {$yiilog}", $level);
    }
}

