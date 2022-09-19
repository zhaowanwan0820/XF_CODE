<?php
namespace NCFGroup\Protos\Duotou\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CommonEnum extends AbstractEnum {
    /** 和p2p系统交互时候的mark标示 */
    const TOKEN_MARK_BID = 1;       // 投标
    const TOKEN_MARK_INTEREST = 2;  // 结息
    const TOKEN_MARK_REDEEM = 3;    // 赎回
    const TOKEN_MARK_REDEEM_CLEAN   = 4;    // 赎回结清
    const TOKEN_MARK_BID_P2P        = 5;    // 多投投资p2p
    const TOKEN_MARK_BID_P2P_REDEEM = 6;    // 多投投资p2p转让
    const TOKEN_MARK_P2P_REPAY      = 7;    // p2p还款
    const TOKEN_MARK_REVOKE         = 8;    // 撤销投资

    const P2P_LOAN_MONEY_LIMIT = 10000; // 匹配过程中投向p2p的最小金额分
    const LOAN_MAPPING_TABLE_NUM = 2; //分表数量

    const MAPPING_SELECT_LIMIT = 1000; // 捞取条数默认为多少条

    const DISTRIBUTED_CENTRAL_ID = 0; //中心机器id
    const DISTRIBUTED_CENTRAL_REPLACE_BUSINESSID = 1; //中心机器代替执行的业务机器ID
    const DISTRIBUTED_BUSINESS_NUM = 2; //业务机器数量（包含业务机器）

    const DUOTOU_CONTRACT_TYPE = 1; //多投合同类型

    const P2P_RATE_YEAR = 6.81; //智多鑫底层资产年化利率

    /******************** JOBS 优先级配置 **************************/
    const JOBS_PRIORITY_NORMAL = 100; //借款合同
    const JOBS_PRIORITY_EMERGENCY = 200;
    const JOBS_PRIORITY_REDEEM_TRANSFER = 300; //回款
    const JOBS_PRIORITY_PROJECT_LIQUIDATION = 400;//项目清盘
    const JOBS_PRIORITY_REVOKE_DEALLOAN     = 500; //撤销投资记录

    const JOBS_PRIORITY_REPAY                       = 1000; //p2p还款jobs优先级
    const JOBS_PRIORITY_REPAY_FIXED                 = 1100; //p2p还款修正金额jobs优先级
    const JOBS_PRIORITY_REPAY_TRANSFER              = 1200; //p2p还款迁移jobs优先级
    const JOBS_PRIORITY_P2P_REPAY_TRANSFER          = 1300; //p2p还款转账jobs优先级
    const JOBS_PRIORITY_REPAY_INTEREST              = 1400; //p2p还利息jobs优先级
    const JOBS_PRIORITY_REPAY_INTEREST_SPLIT        = 1500; //p2p还利息分表处理jobs优先级
    const JOBS_PRIORITY_ADD_REPAY_PRINCIPAL_JOBS    = 1600; //添加还款本金处理jobs
    const JOBS_PRIORITY_REPAY_NOTIFY_P2P            = 1700; //通知p2p还款计算完成，可以拉取数据

    const JOBS_PRIORITY_P2P_BID = 2000;         //p2p投资优先级
    const JOBS_PRIORITY_P2P_BID_REDEEM = 2100; //p2p投资接盘赎回优先级
    const JOBS_PRIORITY_P2P_BID_NOTIFY = 2200; //p2p投资通知p2p拉取数据优先级

    const JOBS_PRIORITY_P2P_FAILDEAL = 3000;    //p2p流标优先级

    //多投给用户发送按月结息补贴
    const COUPON_GROUP_IDS_KEY = "DUOTOU_MONTH_INTEREST_SUBSIDY";

    /** 判断匹配是否进行中的 key */
    const MAPPING_BUISY_KEY = "DUOTOU_MAPPING_BUISY";

    /** Alarm 告警信息配置 */
    const DT_BID = 'dt_bid'; // 多投投资 rpc调用异常使用
    const DT_BID_REPAIR = 'dt_bid_repair'; // 多投投资修复脚本
    const DT_P2P_DEAL_BEYOND = 'dt_p2p_deal_beyond'; // p2p预约投标金额超出募集资产
    const DT_INTEREST = 'dt_interest'; // 多投结息
    const DT_MAPPING = 'dt_mapping'; // 多投匹配
    const DT_REPAY = 'dt_repay'; // 多投还款
    const DT_DEAL = 'dt_deal'; // 多投标的相关报警
    const DT_STATS_DEAL = 'dt_stats_deal'; // 多投统计相关报警
    const DT_LIQUIDATION = 'dt_liquidation'; // 多投清盘相关报警
    const DT_SYNC_P2P = 'dt_sync_p2p'; // 同步p2p到多投相关报警
}
