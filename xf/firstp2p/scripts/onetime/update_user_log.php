<?php
/**
 * @desc 独立脚本，输出更新资金记录中的异常数据的sql
 * user: duxuefeng
 * date: 2017年12月12日
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once dirname(__FILE__).'/../../app/init.php';

use core\service\P2pIdempotentService;
use core\service\DtDepositoryService;
use core\dao\JobsModel;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Logger;


class ChangeUserLog{
    public $db;
    public $userIds = array(); // 要更新的userId
    public $countSql = 0; //sql

    public function __construct(){
        $this->db=$GLOBALS["db"];  // master上的数据库
        $this->userIds = array(9368308   ,
            1157274   ,
            10947590  ,
            3947684   ,
            7497258   ,
            8105754   ,
            9785388   ,
            3017058   ,
            3654012   ,
            2878201   ,
            9767604   ,
            651905    ,
            3510424   ,
            5399628   ,
            9352985   ,
            7256023   ,
            10941518  ,
            1489181   ,
            10938406  ,
            2217070   ,
            2382912   ,
            548693    ,
            10483100  ,
            4101529   ,
            1158665   ,
            15966     ,
            4268723   ,
            7497258   ,
            4920932   ,
            7297505   ,
            870       ,
            106146    ,
            548693    ,
            548693    ,
            4315099   ,
            2802701   ,
            5290998   ,
            10947138  ,
            10720468  ,
            10720468  ,
            10947816  ,
            3425712   ,
            8105754   ,
            8088223   ,
            8382233   ,
            842797    ,
            8159307   ,
            6185535   ,
            3759982   ,
            6982643   ,
            512494    ,
            7959328   ,
            4153437   ,
            7999658   ,
            10674121  ,
            1194908   ,
            2125303   ,
            548693    ,
            2930178   ,
            419339    ,
            3073637   ,
            9697923   ,
            7046669   ,
            7623      ,
            10938406  ,
            5196842   ,
            2974585   ,
            6200114   ,
            6173463   ,
            3637994   ,
            7117630   ,
            3177654   ,
            9139096   ,
            4087034   ,
            4867908   ,
            7479962   ,
            6770162   ,
            2885694   ,
            2124866   ,
            6196743   ,
            8847820   ,
            6084750   ,
            4104926   ,
            1240684   ,
            7832370   ,
            512494    ,
            5815367   ,
            4104926   ,
            2388537   ,
            2124866   ,
            2997605   ,
            7673692   ,
            10947318  ,
            4087034   ,
            4104926   ,
            1401596   ,
            2984838   ,
            10865011  ,
            10824859  ,
            2986500   ,
            3818607   ,
            9698      ,
            10492236  ,
            7832173   ,
            6952199   ,
            9819543   ,
            9024629   ,
            9214438   ,
            7248173   ,
            2557143   ,
            1069097   ,
            3818607   ,
            2490566   ,
            4995542   ,
            3782594   ,
            7659      ,
            8416780   ,
            9949209   ,
            512494    ,
            10438841  ,
            3873451   ,
            3208583   ,
            3951262   ,
            2223425   ,
            2490566   ,
            2997605   ,
            7988409   ,
            6978892   ,
            7412638   ,
            9615740   ,
            9383086   ,
            3208583   ,
            4325261   ,
            6250335   ,
            10367315  ,
            6205467   ,
            7242919   ,
            7999658   ,
            3549533   ,
            4409014   ,
            7958952   ,
            412401    ,
            1271535   ,
            1748927   ,
            4659106   ,
            7191018   ,
            851077    ,
            925075    ,
            1127696   ,
            1127696   ,
            10064576  ,
            9852323   ,
            10400356  ,
            10857437  ,
            10947443  ,
            10422521  ,
            5230234);
    }

    public function run(){
        //取模
        $mods = array();
        foreach($this->userIds as $v){
            $mods[($v%64)][] = $v;
        }
        //写sql
        foreach($mods as $mod => $ids){
            $sql = sprintf("SELECT `id`, `note` FROM `firstp2p_user_log_%d`  WHERE `user_id` in (%s) AND `note` like '%s' ", $mod, implode(",", $ids), "%锟斤拷锟窖达拷%" );
            $result = $this->db->getAll($sql);
            if($result === false){
                echo("update_user_log fail mod:" .$mod. "user_id:" . json_encode($ids)) . "\n";
                continue;
            }
            foreach($result as $row){
                $this->countSql ++;
                echo sprintf("UPDATE `firstp2p_user_log_%d` SET `note` = '%s' where `id` = %d ;", $mod, str_replace("锟斤拷锟窖达拷", "消费贷", $row['note']), $row['id'] ) . "\n";
            }
        }
    }
}

//对输入参数进行判断，有2个参数才执行脚本

$c = new ChangeUserLog();
$c->run();
echo "成功\n";
echo "用户个数:" . count($c->userIds) . "\n";
echo "用户资金记录个数:" . $c->countSql . "\n";


