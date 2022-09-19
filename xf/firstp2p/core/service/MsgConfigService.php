<?php
/**
 * 用户设置
 * @date 2015-04-29
 * @author xiaoan <zhaoxiaoan@ucfgroup.com>
 */

namespace core\service;

use core\dao\UserMsgConfigModel;
use libs\utils\Logger;
use core\dao\UserModel;
/**
 * @package core\service
 */
class MsgConfigService extends BaseService {

    //  初始默认选项
    public static $default_checked = 1;

    // sms
    const TYPE_SMS = 1;

    //email
    const TYPE_EMAIL = 2;

    // 新注册用户默认停发短信和邮件的时间 TODO 上线前需要改为实际的时间
    const NEW_USER_DEFAULT_STOP = '2015-08-05';

    // 新注册用户默认停发的邮件和短信

    public static $sms_email_default_stop = array(
                                        1 => array(
                                            1140 => 34,
                                            77 => 18,
                                            1139 => 31,
                                            1146 => 18,
                                        ),
                                        2 => array(
                                            'TPL_DEAL_LOAD_REPAY_EMAIL_LAST' => 9,
                                            //'TPL_SEND_CONTRACT_EMAIL' => 32,
                                            'content_monthlyMail' => 33,
                                        ),
                                    );

    // 短信订阅
    public static $sms_config = array(
                    0 => array ( // 项目进度
                        18 => '项目投资',

                        9 => '项目回款',
                        11 => '项目流标',
                        38 => '捐赠',
                    ),
                    1 => array( // 充值/提现
                        6 => '提现结果',
                        34 => '充值成功',
                        //31 => '当日返利',
                        31 => '当日邀请奖励',
                        39 => '投资券'
                    ),
                   2 => array(  // 活动奖励
                       // 31 => '返利到账',
                       // 30 => '红包到账',
                    ),
            );
    // key为sms_id  , 值为存储的对应的设置id
    public static $sms_id = array(
                      //  79 => 1,     // 放款计息
                        1136 => 11,    // 流标
                        1142 => 9,      // 回款
                        1143 => 18,     // 投资成功
                        1140 => 34, //充值成功
                        1131 => 6, //提现成功
                        1133 => 6, // 提现失败
                        1301 => 6, //提现成功
                        1303 => 6, // 提现失败
                        1139 => 31, // 返利到账
                        1144 => 38, // 公益标捐赠
                        1247 => 39, //投资卷
                        1178 => 39, //投资卷
                        1145 => 9, //回款
                        1146 => 18, //投资成功
                        1401 => 18, //买金
                        1402 => 9,//黄金回款
                        1403 => 11, //黄金流标
                       // 1153 => 30, // 红包到账
                       // 1156 => 30,
                       // 1157 => 30,

                        );
    // 邮件订阅
    public static $email_config = array(
                0 => array( // 项目进度
                    32 => '合同下发',
                    9 => '项目回款'

                ),
                1 => array( // 其他
                    33 => '月对账单',
                    //34 => '充值成功',
                ),
                );
    // 邮件模板的key
    public static $email_template_key = array(
                                    'TPL_DEAL_LOAD_REPAY_EMAIL_LAST' => 9,
                                    'TPL_DEAL_LOAD_REPAY_EMAIL' => 9,
                                    'TPL_SEND_CONTRACT_EMAIL' => 32,
                                   // 'TPL_MAIL_PAYMENT' => 34,
                                    'content_monthlyMail' => 33,
                                        );
    // 停发的邮件
    public static $stop_email_template_key = array(
                                         'TPL_MAIL_PAYMENT' => 34,
                                    );
    // 网信普惠允许的发送模板
    public static $p2pcn_sms_allow_tpl = array(
        'TPL_SMS_VERIFY_CODE',
        'TPL_SMS_MODIFY_FORGETPASSWORD_CODE',
        'TPL_SMS_MODIFY_OLD_PHONE_CODE',
        'TPL_SMS_MODIFY_NEW_PHONE_CODE',
        'TPL_SMS_SET_SITE_CODE',
        'TPL_SMS_MODIFY_SITE_CODE',
        'TPL_SMS_SET_PROTION_CODE',
        'TPL_SMS_MODIFY_PROTION_CODE',
        'TPL_SMS_WEB_RELOGIN_CODE',
        'TPL_SMS_CHANGE_MOBILE_NEW',
        'TPL_SMS_OPEN_BEDEV',
        'TPL_SMS_MODIFY_PASSWORD_CODE',
        'TPL_SMS_RESET_BANK',
        'TPL_SMS_LOGIN_CODE',
        'TPL_SMS_GOLD_DELIVER_VERIFY',
        'TPL_SMS_BONUS_GROUP_CODE',
        'TPL_SMS_DTB_PUBLISH',
        'TPL_SMS_RESET_PASSWORD',
        'TPL_SMS_RESERVE_CHARGE_REMIND',
        'TPL_SMS_RESERVE_DISCLOSURE',
        'TPL_SMS_RESERVE_DISCOUNT_EXCHANGE_SUCCESS',
        'TPL_SMS_RESERVE_LOAN_REPAY_MERGE',
        'TPL_SMS_RESERVE_DEAL_BID_MERGE',
        'TPL_SMS_P2P_RESERVE_DISCOUNT_EXCHANGE_SUCCESS',
        'TPL_DEAL_DUN_BORROWER',
        'TPL_DEAL_DUN_AGENCY',
        'TPL_SMS_VERIFY_CANDY_BUC_WITHDRAW',
        'TPL_SMS_PUBLISH_TRANSFER_PUSH',
        'TPL_COMPATIBLE',
        'FULL_MES_TO_OP',//满标提醒
    );

    /**
     * 获取用户订阅消息配置
     */
    public static function getAllMsgConfig() {
        return [
            'default_checked' => self::$default_checked,
            'TYPE_SMS' => self::TYPE_SMS,
            'TYPE_EMAIL' => self::TYPE_EMAIL,
            'NEW_USER_DEFAULT_STOP' => self::NEW_USER_DEFAULT_STOP,
            'sms_email_default_stop' => self::$sms_email_default_stop,
            'sms_config' => self::$sms_config,
            'sms_id' => self::$sms_id,
            'email_config' => self::$email_config,
            'email_template_key' => self::$email_template_key,
            'stop_email_template_key' => self::$stop_email_template_key,
            'p2pcn_sms_allow_tpl' => self::$p2pcn_sms_allow_tpl,
        ];
    }

    /**
     * 获取用户短信或者邮件订阅配置
     * @param int $userId
     */
    public function getUserConfig($userId, $field){
        if (empty($userId) || empty($field)){
            return false;
        }
        $user_msg_config_model = new UserMsgConfigModel();
        $info = $user_msg_config_model->getSwitches($userId, $field);
        // 整理数据新注册用户默认不发邮件和短信
        $info = $this->arrangeDataUserConfig($userId, $info, $field);
        return $info;
    }
    /**
     * 处理新注册用户默认停发邮件和短信
     * @param array $info
     * @param int $field
     * @return array
     */
    public function arrangeDataUserConfig($userId, $info, $field){
        /*不区分分站、主站，如果用户配置了消息设置，则使用消息设置 liuzhenpeng*/
        if(!empty($info)) return $info;

        switch($field){
            case 'sms_switches':
                $type = self::TYPE_SMS;
                break;
            case 'email_switches':
                $type = self::TYPE_EMAIL;
                break;
            default:
                break;
        }

        $user_model = new UserModel();
        $user_info = $user_model->find($userId,'group_id,create_time',true);
        $site_group   = $GLOBALS['sys_config']['SITE_USER_GROUP'];
        $main_site_id = $site_group['firstp2p'];
        unset($site_group['firstp2p']);
        $user_info['group_id'];
        if(!empty($user_info) && in_array($user_info['group_id'], array_values($site_group)) && $user_info['group_id'] != $main_site_id){
            return $this->chkOtherSiteSmsEmain($type);
        }

        // 不论新老用户，满标回款邮件通知默认不发送 20151110
        if (!empty($user_info) && $user_info['create_time'] < to_timespan(self::NEW_USER_DEFAULT_STOP) && $type==self::TYPE_SMS){
            return $info;
        }

        if (isset(self::$sms_email_default_stop[$type])) {
            $stop_sms_email = self::$sms_email_default_stop[$type];
            foreach ($stop_sms_email as $v) {
                $info[$v] = 0;
            }
        }
        return $info;
    }
    /**
     *  设置开关 支持 插入和更新
     * @param int $userId
     * @param array $info
     * @return bool
     */
    public function setSwitches($userId, $filed, array $switches){
        $log_info = array(__CLASS__, __FUNCTION__, APP, $userId, $filed, json_encode($switches));
        if (empty($userId) || empty($filed) || empty($switches)){
            return false;
        }
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        $user_msg_config_model = new UserMsgConfigModel();
        $ret = true;
        try {
            $ret = $user_msg_config_model->setSwitches($userId, $filed, $switches);
        } catch (\Exception $e){
            $ret = false;
        }
        Logger::info(implode(" | ", array_merge($log_info, array('end ',$ret))));
        return $ret;
    }

    /**
     * 合并数组保留索引
     * @param array   $array
     * <code>
     *   array(
     *     0 => array1,
     *     1  => array2,
     *     .....
     *  )
     * </code>
     * @param array
     *
     */
    public function mergeConfig($array){
        if (!is_array($array) || count($array) == 0){
            return array();
        }
        $ret = array();
        foreach($array as $v){
            if (!is_array($v)){
                continue;
            }
            $ret+=$v;
        }
        return $ret;
    }

    /**
     * 检查配置项
     * @param array $array 一维
     * @param type $type 1sms,2email
     */
    public function checkMsgConfig($config, $type)
    {
        if (!is_array($config) || empty($config)) {
            return false;
        }
        $ret = true;
        if ($type == self::TYPE_EMAIL) {
            $email_config_list = self::$email_config;
            $merge_array = array(
                0 => $email_config_list[0],
                1 => $email_config_list[1],
            );
         }
        if ($type == self::TYPE_SMS){
            $sms_config_list = self::$sms_config;
            $merge_array = array(
                            0 => $sms_config_list[0],
                            1 => $sms_config_list[1],
                            2 => $sms_config_list[2],
                        );
        }
        // 检查总数
        $config_list_merge = $this->mergeConfig($merge_array);
        if ( count($config) != (count($config_list_merge))){
            $ret = false;
        }
        // 检查id和value
        foreach ($config as $data_config_key => $data_config_v) {
            if (!isset($config_list_merge[$data_config_key])) {
                $ret = false;
                break;
            }

            if ($data_config_v!='0' && $data_config_v!='1'){

                $ret = false;
                break;
            }
        }

        return $ret;

    }
    /**
     * 用户订阅配置是否短信通知
     * @param int $userId
     * @param int $sms_id
     * @param bool true 的话为不发送，false为发送
     */
    public function checkIsSendSms($userId,$sms_template_id){
        $log_info = array(__CLASS__, __FUNCTION__, APP, $userId, $sms_template_id);
        if (empty($userId)){
            return false;
        }
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        $msg_config_service = new MsgConfigService();
        $sms_config = $msg_config_service->getUserConfig($userId,'sms_switches');
        if (empty($sms_config)){
            Logger::info(implode(" | ", array_merge($log_info, array('sms config data empty'))));
            return false;
        }
        $sms_ids = $msg_config_service::$sms_id;
        if (!isset($sms_ids[$sms_template_id])){
            Logger::info(implode(" | ", array_merge($log_info, array('template id '.$sms_template_id.' Undefined'))));
            return false;
        }
        if (isset($sms_config[$sms_ids[$sms_template_id]]) && $sms_config[$sms_ids[$sms_template_id]] == 0){
            Logger::info(implode(" | ", array_merge($log_info, array(json_encode($sms_ids),json_encode($sms_config), 'end true'))));
            return true;
        }
        Logger::info(implode(" | ", array_merge($log_info, array(json_encode($sms_ids), json_encode($sms_config), $sms_config[$sms_ids[$sms_template_id]], 'end false'))));
        return false;
    }

    /**
     * 网信普惠是否 发短信
     * @param int $checkOption 0只检查是否发短信，1检查是否带网信普惠签名
     * @param $siteId
     * @param $tpl
     * @return bool true 不发，
     */
    public function checkP2pcnIsSendSms($siteId,$tpl,$checkOption=0){

        $switch = app_conf('P2PCN_SMS_SWITCH');

        $log_info = array(__CLASS__, __FUNCTION__, APP, $siteId, $tpl,$switch);

        $p2pcn_site_id = $GLOBALS['sys_config']['TEMPLATE_LIST']['firstp2pcn'];
        if (empty($tpl) || $siteId != $p2pcn_site_id){
            return false;
        }

        $switch = empty($switch) ? 0 : $switch;

        $ret = false;
        // 开启网信普惠屏蔽短信
        if ($switch == 1) {
            if ($checkOption == 0) {
                if (!in_array($tpl, self::$p2pcn_sms_allow_tpl)) {
                    Logger::info(implode(" | ", array_merge($log_info, array($p2pcn_site_id, ' p2pcn sms deny '.$tpl))));
                    return true;
                }
            }

            if ($checkOption == 1) {
                if (in_array($tpl, self::$p2pcn_sms_allow_tpl)) {
                    Logger::info(implode(" | ", array_merge($log_info, array($p2pcn_site_id, ' p2pcn sms sign allow '.$tpl))));
                    return true;
                }
            }
        }
        return $ret;
    }

    /**
     * 用户订阅配置是否邮件通知
     * @param int $userId
     * @param string $template_key
     */
    public function checkIsSendEmail($userId,$template_key){
        $log_info = array(__CLASS__, __FUNCTION__, APP, $userId, $template_key);
        if (empty($userId) || empty($template_key)){
            return false;
        }
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        // 检查停发邮件
        if (isset(self::$stop_email_template_key[$template_key])){
            Logger::info(implode(" | ", array_merge($log_info, array(json_encode(self::$stop_email_template_key),$template_key.' is stop','end true'))));
            return true;
        }
        $msg_config_service = new MsgConfigService();
        $email_config = $msg_config_service->getUserConfig($userId,'email_switches');
        if (empty($email_config)){
            Logger::info(implode(" | ", array_merge($log_info, array('email config data empty'))));
            return false;
        }
        $email_template_keys = $msg_config_service::$email_template_key;
        if (isset($email_config[$email_template_keys[$template_key]]) && $email_config[$email_template_keys[$template_key]] == 0){
            Logger::info(implode(" | ", array_merge($log_info, array(json_encode($email_template_keys),json_encode($email_config),'end true'))));
            return true;
        }
        Logger::info(implode(" | ", array_merge($log_info, array(json_encode($email_template_keys),json_encode($email_config),' end false'))));
        return false;
    }

    /**
     * @分站执行操作发邮件、短信(将标示设为1)
     * @param  void
     */
    private function chkOtherSiteSmsEmain($type)
    {
        if ($type == self::TYPE_EMAIL) {
            $email_config_list = self::$email_config;
            $merge_array = array(
                0 => $email_config_list[0],
                1 => $email_config_list[1],
            );
        }
        if ($type == self::TYPE_SMS){
            $sms_config_list = self::$sms_config;
            $merge_array = array(
                0 => $sms_config_list[0],
                1 => $sms_config_list[1],
                2 => $sms_config_list[2],
            );
        }
        $config_list_merge = $this->mergeConfig($merge_array);
        foreach ($config_list_merge as $data_config_key => $data_config_v){
            $config_list_merge[$data_config_key] = 1;
        }
        return $config_list_merge;
    }
}
