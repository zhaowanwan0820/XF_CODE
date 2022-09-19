<?php

/**
 * 自定义sql查询
 * @author   zhaowanwan<zhaowanwan@itouzi.com>
 * @since    1.0
 */
class SqlQueryController extends \iauth\components\IAuthController
{
    public $layout = '//layouts/main';

    public function allowActions()
    {
        return ['strEncrypt'];
    }


    /**
     * 自定义查询sql接口
     * @param $db 指定数据库 0:尊享1:普惠 2：合同库
     * @param $sql  自定义sql
     * @return json
     */
    public function actionIndex()
    {
        $db = Yii::app()->request->getParam( 'db' );
        $sql = Yii::app()->request->getParam( 'sql' );
        //日志审计参数
        $this->AuditLog['status'] = true;
        $this->AuditLog['action'] = 'query';
        $this->AuditLog['resource'] = 'default/sqlQuery/index';
        //相应时间
        ini_set('max_execution_time', '0');
        ini_set("memory_limit", "-1");
        //SQL判断
        if (!isset($sql) || $sql == '') {
            $this->echoJsonAuditLog('', 4000, '请填写要查询的SQL！');
        }
        //sql长度限制
        if (strlen($sql) > 15000) {
            $this->echoJsonAuditLog('', 4001, 'sql过长，请限制在15000字节！');
        }
        //语句中禁止出现的关键字不区分大小写限制

        if (preg_match('/\b(update|insert|drop|create|alter|delete)\b/i', $sql)) {
            $this->echoJsonAuditLog('', 4003, '此sql包含禁止执行的关键字！');
        }
        //针对加密解密字段转换sql（mobile、idno）
        $sql = $this->findThePhoneNumbers($sql);
        //过滤的关键字段后面禁止用as
        $sql_array = explode(' ', $sql);
        if (count($sql_array) == 0) {
            $this->echoJsonAuditLog('', 4008, '此sql不合法！');
        }
        foreach ($sql_array as $kk => $vv) {
            //匹配 忽略大小写的as
            if (preg_match('/\b(as)\b/i', $vv)) {
                if(strripos($sql_array[$kk - 1],",")){
                    $sql_array[$kk - 1] = substr($sql_array[$kk - 1],strripos($sql_array[$kk - 1],",")+1);
                }
                $filter_field = array_merge(Yii::app()->c->params_borrow['keep_one'], Yii::app()->c->params_borrow['keep_four']);
                if (in_array($sql_array[$kk - 1], $filter_field)) {
                    $this->echoJsonAuditLog('', 4009, '脱敏字段不能使用as别名！');
                }
            }
        }
        //DB判断
        if (!isset($db) || $db === '') {
            $this->echoJsonAuditLog('', 4004, '请选择要查询的DB！');
        }
        //目前只支持主站，论坛的查询，暂未写成配置，如以后支持多个数据库查询，可写成配置文件
        if (!in_array($db, array_keys(Yii::app()->c->params_borrow['sql_query_db']))) {
            $this->echoJsonAuditLog('', 4005, '您选择的DB不存在！');
        }
        //如果语句中没有 limit 强限制500
        if (!preg_match('/\b(limit|LIMIT)\b/i', $sql)) {
            $sql .= ' limit 500 ';
        }
        //执行sql
        try {
            $dbname = Yii::app()->c->params_borrow['sql_query_db'][$db];
            $seachResult = Yii::app()->$dbname->createCommand($sql)->queryAll();
            if (count($seachResult) == 0) {
                $this->echoJsonAuditLog('', 0, '未查询到数据！');
            }
        } catch (Exception $ee) {
            Yii::log('sql query Exception are ' . print_r($ee->getMessage(), true), 'error', 'sqlQueryLog');
            $this->echoJsonAuditLog('', 4007, 'sql有语法错误无法执行，请检查！<br/>' . print_r($ee->getMessage(), true));
        }

        //对姓名保留最后1位、card_id后4位、银行卡号后四位、phone后四位 其它脱敏
        $returnResult = array();
        foreach ($seachResult as $key => $row) {
            if ($key == 0) {
                $array_key = array_keys($row);
            }
            foreach ($array_key as $K => $v) {
                if (in_array($v, Yii::app()->c->params_borrow['keep_one'])) {
                    $returnResult[$key][$v] = $this->strEncrypt($row[$v], 1);
                } elseif (in_array($v, Yii::app()->c->params_borrow['keep_four'])) {
                    $returnResult[$key][$v] = $this->strEncrypt($row[$v], 4);
                } elseif (in_array($v, array('content'))) {    //content字段显示部分 20180716
                    //$res = mb_substr($row[$v], 0,30).'*****';
                    $res = $this->strEncrypt($row[$v], 10);
                    $returnResult[$key][$v] = $res;
                } elseif (in_array($v, Yii::app()->c->params_borrow['keep_enc'])) {
                    $returnResult[$key][$v] = GibberishAESUtil::dec($row[$v], Yii::app()->c->idno_key);//手机号身份证号解密
                } else {
                    $returnResult[$key][$v] = $row[$v];
                }
            }
        }
        $this->echoJsonAuditLog($returnResult, 0, '查询成功！');
    }

    /**对指定字符串进行加密
     * @param $str 字符串
     * @param $l 保留后几位
     * @return string
     */
    public function strEncrypt($str, $l = 1)
    {
        if (!isset($str) || $str == '') {
            return $str;
        }
        $str_length = iconv_strlen($str, "UTF-8");
        if ($str_length <= $l) {
            return $str;
        }
        $returnStr = '';
        for ($x = 0; $x < ($str_length - $l); $x++) {
            $returnStr .= '*';
        }

        $returnStr .= mb_substr($str, $x, $str_length - 1, 'utf-8');
        return $returnStr;
    }

    public function findThePhoneNumbers($oldStr = "")
    {
        // 检测字符串是否为空
        $oldStr = trim($oldStr);
        $numbers = array();
        if (empty($oldStr)) {
            return $numbers;
        }
        // 手机号的获取
        $reg = '/\D(?:86)?(\d{11})\D/is';//匹配数字的正则表达式
        preg_match_all($reg, $oldStr, $result);
        //针对手机号的替换
;       foreach ($result[1] as $key => $value) {
            $oldStr = str_replace($value,GibberishAESUtil::enc($value, Yii::app()->c->idno_key),$oldStr);
        }
        // 返回最终数组
        return $oldStr;

    }
}

