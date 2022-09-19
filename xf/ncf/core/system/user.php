<?php
use core\service\user\BOBase;
use libs\utils\PaymentApi;
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

define("EMPTY_ERROR",1);  //未填写的错误
define("FORMAT_ERROR",2); //格式错误
define("EXIST_ERROR",3); //已存在的错误
define("IDNO_ERROR", 4);  // 身份号认证失败
define("IDNO_LINK_ERROR", 5); // 连接失败或其他错误

define("ACCOUNT_NO_EXIST_ERROR",1); //帐户不存在
define("ACCOUNT_PASSWORD_ERROR",2); //帐户密码错误
define("ACCOUNT_NO_VERIFY_ERROR",3); //帐户未激活

    /**
     * 生成会员数据
     * @param $user_data  提交[post或get]的会员数据
     * @param $mode  处理的方式，注册或保存
     * 返回：data中返回出错的字段信息，包括field_name, 可能存在的field_show_name 以及 error 错误常量
     * @param bool $modifyMobile 默认false，true的话，强制修改手机号，不受mobilepassed 字段影响  by xiaoan 2014-06-17
     * 不会更新保存的字段为：score,money,verify,pid
     */
    function save_user($user_data, $mode='INSERT', $check_idno = 1, $modifyMobile=false)
    {
        if (isset($user_data['idno'])) {
            $user_data['idno'] = strtoupper(trim($user_data['idno']));
        }

        if (isset($user_data['real_name'])) {
            $user_data['real_name'] = trim($user_data['real_name']);
        }
        //开始数据验证
        $res = array('status'=>1,'info'=>'','data'=>''); //用于返回的数据

        if($mode=="INSERT" || isset($user_data['user_name']))
        {
            if(trim($user_data['user_name'])=='')
            {
                $field_item['field_name'] = 'user_name';
                $field_item['error'] = EMPTY_ERROR;
                $res['status'] = 0;
                $res['data'] = $field_item;
                return $res;
            }
            if(isset($user_data['passport_id']) && !empty($user_data['passport_id']))
            {
                $ret = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where passport_id = '".trim($user_data['passport_id'])."' and id <> ".intval($user_data['id']));
            }
            else
            {
                $ret = $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where user_name = '".trim($user_data['user_name'])."' and id <> ".intval($user_data['id']));
            }
            if($ret>0)
            {
                if(!$user_data['oauth'])
                {
                    $field_item['field_name'] = 'user_name';
                    $field_item['error'] = EXIST_ERROR;
                    $res['status'] = 0;
                    $res['data'] = $field_item;
                    return $res;
                }
                else
                {
                    $user_data['id']=$GLOBALS['db']->getOne("select id from ".DB_PREFIX."user where passport_id = '"
                            .trim($user_data['passport_id'])."'");
                    $mode='UPDATE';
                }
            }
            else
            {
                if($user_data['oauth'] && $GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where user_name = '".trim($user_data['user_name'])."' and id <> ".intval($user_data['id']))>0)
                {
                    $field_item['field_name'] = 'user_name';
                    $field_item['error'] = EXIST_ERROR;
                    $res['status'] = 0;
                    $res['data'] = $field_item;
                    return $res;
                }
            }
        }

        // oauth数据不做邮箱认证
        if( !$user_data['oauth'] && trim($user_data['email']) !='')
        {
            // Edit By guofeng At 20161212 15:25(JIRA#FIRSTPTOP-4269)
            if (!check_email(trim($user_data['email'])))
            {
               $field_item['field_name'] = 'email';
               $field_item['error'] = FORMAT_ERROR;
               $res['status'] = 0;
               $res['data'] = $field_item;
               return $res;
            }
            if ($mode === 'UPDATE')
            {
                $where = sprintf('`email` = \'%s\' AND `id` <> %d', trim($user_data['email']), intval($user_data['id']));
            } else {
                $where = sprintf('`email` = \'%s\'', trim($user_data['email']));
            }
            // 检查邮箱是否有重复
            if ($GLOBALS['db']->getOne('SELECT COUNT(*) FROM '.DB_PREFIX.'user WHERE ' . $where) > 0)
            {
                $field_item['field_name'] = 'email';
                $field_item['error'] = EXIST_ERROR;
                $res['status'] = 0;
                $res['data'] = $field_item;
                return $res;
            }
        }

        // oauth数据不做手机认证检查 Edit By guofeng At 20151215 18:45
        $user_data['oauth'] = (isset($user_data['user_type']) && $user_data['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE) ? $user_data['oauth'] : false;
        if(!$user_data['oauth'])
        {
            if( !isset($user_data['mobilepassed']) || $user_data['mobilepassed']=="false" ){
                if((intval(app_conf("MOBILE_MUST"))==1&&trim($user_data['mobile'])==''))
                {
                    $field_item['field_name'] = 'mobile';
                    $field_item['error'] = EMPTY_ERROR;
                    $res['status'] = 0;
                    $res['data'] = $field_item;
                    return $res;
                }

                /* if(!check_mobile(trim($user_data['mobile'])))
                {
                    $field_item['field_name'] = 'mobile';
                    $field_item['error']	=	FORMAT_ERROR;
                    $res['status'] = 0;
                    $res['data'] = $field_item;
                    return $res;
                } */
            }
            // 后台20140626 jira988 手机号为验证数字即可
            $reg = "/\d+$/";
            if (!preg_match($reg, $user_data['mobile'])) {
                $field_item['field_name'] = 'mobile';
                $field_item['error'] = FORMAT_ERROR;
                $res['status'] = 0;
                $res['data'] = $field_item;
                return $res;
            }

            // 只验证认证过的手机号
            if($user_data['mobile']!=''&&$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where mobile = '".trim($user_data['mobile'])."' and mobilepassed = 1 and id <> ".intval($user_data['id']))>0)
            {
                $field_item['field_name'] = 'mobile';
                $field_item['error'] = EXIST_ERROR;
                $res['status'] = 0;
                $res['data'] = $field_item;
                $res['msg'] = "该手机号已经注册认证";
                return $res;
            }

        }
        //验证扩展字段
        $user_field = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_field");
        foreach($user_field as $field_item)
        {
            if($field_item['is_must']==1&&trim($user_data[$field_item['field_name']])=='')
            {
                $field_item['error'] = EMPTY_ERROR;
                $res['status'] = 0;
                $res['data'] = $field_item;
                return $res;
            }
        }


        // 验证身份证号,如果身份证没有验证通过
        if($check_idno && $user_data['idno'] != "" && $GLOBALS['db']->getOne("select idcardpassed from ".DB_PREFIX."user where  id = ".intval($user_data['id'])) != 1 )
        {
            $len = strlen($user_data['idno']);
            if($len != 15 && $len != 18)
            {
                $field_item['field_name'] = 'idno';
                $field_item['error'] = FORMAT_ERROR;
                $res['status'] = 0;
                $res['data'] = $field_item;
                return $res;
            }
            else
            {
                require_once APP_ROOT_PATH."libs/idno/CommonIdnoVerify.php";
                $id5 = new \libs\idno\CommonIdnoVerify();

                $reinfo = $id5->checkIdno($user_data['real_name'],  $user_data['idno']);

                // 记录日志文件
                require_once APP_ROOT_PATH."system/utils/logger.php";

                // 如果信息验证不一致
                if($reinfo['code'] != 1)
                {
                    // 如果 姓名与身份证号不一致, 姓名与身份证号库中无此号, 姓名与身份证号 未查到数据
                    if($reinfo['code'] == -200 || $reinfo['code'] == -300 || $reinfo['code'] == -111)
                    {
                        $field_item['field_name'] = 'idno';
                        $field_item['error'] = IDNO_ERROR;
                        $res['status'] = 0;
                        $res['data'] = $field_item;
                        return $res;
                    }
                    else
                    {
                        $field_item['field_name'] = 'idno';
                        $field_item['error'] = IDNO_LINK_ERROR;
                        $res['status'] = 0;
                        $res['data'] = $field_item;
                        return $res;
                    }

                    $log = array(
                        'type' => 'idno',
                        'real_name' => $user_data['real_name'],
                        'user_id' => $user_data['id'],
                        'indo' => $user_data['idno'],
                        'path' =>  __FILE__,
                        'function' => 'save_user',
                        'msg' => '身份证认证失败.',
                        'time' => date('Y-m-d H:i:s'),
                    );
                    logger::wLog($log);
                }
                else
                {
                    // 设定身份证已被认证
                    $user['idcardpassed'] = 1;
                    $user['idcardpassed_time'] = get_gmtime();

                    // 判断是否港澳台其他证件用户，如果是则不更新用户性别和出生年月日

                    $hasPassport = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM firstp2p_user_passport WHERE user_id = '{$user_data['id']}'");
                    if (!$hasPassport && $user_data['id_type'] == 1) {
                        // 获取用户性别
                        $user['sex'] = $id5->getSex($user_data['idno']);
                        //设置出生日期
                        $birth = $id5->getBirthDay($user_data['idno']);
                        $user['byear'] = $birth['year'];
                        $user['bmonth'] = $birth['month'];
                        $user['bday'] = $birth['day'];

                        $log = array(
                            'type' => 'idno',
                            'user_name' => $user_data['real_name'],
                            'user_id' => $user_data['id'],
                            'indo' => $user_data['idno'],
                            'path' =>  __FILE__,
                            'function' => 'save_user',
                            'msg' => '身份证认证成功.',
                            'time' => date('Y-m-d H:i:s'),
                        );
                        logger::wLog($log);
                   }
                }
            }
        }

        //验证结束开始插入数据
        $user['info'] = $user_data['info'];
        if($mode=="INSERT"){
            $user['create_time'] = get_gmtime();
            $user['user_name'] = $user_data['user_name'];
        }

        $user['update_time'] = get_gmtime();
        $user['pid'] = $user_data['pid'];

        if(isset($user_data['real_name']))
            $user['real_name'] = $user_data['real_name'];
        if(isset($user_data['idno']))
            $user['idno'] = $user_data['idno'];

        // jira 1050
        $user['id_type'] = $user_data['id_type'];
        // 用户类型 Add by guofeng At 20151215 14:25
        isset($user_data['user_type']) && $user['user_type'] = (int)$user_data['user_type'];
        // 是否强制修改密码 Add by guofeng At 20151217 15:31
        isset($user_data['force_new_passwd']) && $user['force_new_passwd'] = (int)$user_data['force_new_passwd'];
        // 用户的账户类型 Add by guofeng At 20170313 16:06
        isset($user_data['user_purpose']) && $user['user_purpose'] = (int)$user_data['user_purpose'];

        // 如果是后台修改
        if(!$check_idno)
        {
            if(isset($user_data['byear']))
                $user['byear'] = $user_data['byear'];
            if(isset($user_data['bmonth']))
                $user['bmonth'] = $user_data['bmonth'];
            if(isset($user_data['bday']))
                $user['bday'] = $user_data['bday'];
            if(isset($user_data['sex']))
                $user['sex'] = $user_data['sex'];
        }

        if(isset($user_data['passport_id']))
            $user['passport_id'] = $user_data['passport_id'];
        if(isset($user_data['graduation']))
            $user['graduation'] = $user_data['graduation'];
        if(isset($user_data['graduatedyear']))
            $user['graduatedyear'] = intval($user_data['graduatedyear']);
        if(isset($user_data['university']))
            $user['university'] = $user_data['university'];
        if(isset($user_data['marriage']))
            $user['marriage'] = $user_data['marriage'];
        if(isset($user_data['haschild']))
            $user['haschild'] = intval($user_data['haschild']);
        if(isset($user_data['hashouse']))
            $user['hashouse'] = intval($user_data['hashouse']);
        if(isset($user_data['houseloan']))
            $user['houseloan'] = intval($user_data['houseloan']);
        else
            $user['houseloan'] = 0;
        if(isset($user_data['hascar']))
            $user['hascar'] = intval($user_data['hascar']);
        if(isset($user_data['carloan']))
            $user['carloan'] = intval($user_data['carloan']);
        else
            $user['carloan'] = 0;
        if(isset($user_data['address']))
            $user['address'] = $user_data['address'];
        if(isset($user_data['phone']))
            $user['phone'] = $user_data['phone'];
        if(isset($user_data['postcode']))
            $user['postcode'] = $user_data['postcode'];
        if(isset($user_data['n_province_id']))
        $user['n_province_id'] = intval($user_data['n_province_id']);
        if(isset($user_data['n_city_id']))
        $user['n_city_id'] = intval($user_data['n_city_id']);

        if(isset($user_data['province_id']))
        $user['province_id'] = intval($user_data['province_id']);
        if(isset($user_data['city_id']))
        $user['city_id'] = intval($user_data['city_id']);

        if(isset($user_data['is_staff'])){
            $user['is_staff'] = intval($user_data['is_staff']);
        }

        // 没有做身份证认证则使用用户提交
        if($user['idcardpassed'] != 1 && $GLOBALS['db']->getOne("select idcardpassed from ".DB_PREFIX."user where  id = ".intval($user_data['id'])) != 1)
        {
            if(isset($user_data['sex']))
            $user['sex'] = intval($user_data['sex']);
            if(isset($user_data['byear']))
            $user['byear'] = intval($user_data['byear']);
            if(isset($user_data['bmonth']))
            $user['bmonth'] = intval($user_data['bmonth']);
            if(isset($user_data['bday']))
            $user['bday'] = intval($user_data['bday']);
        }

        // 如果是oauth且手机号不为空的话则该手机号必定是验证通过的
        if( $user_data['oauth'] && strlen($user_data['mobile']) == 11 )
        {
            $user['mobilepassed'] = 1;
        }
        // 后台添加修改
        if ($user_data['mobilepassed']===true){
            $user['mobilepassed'] = 1;
        }
        if(isset($user_data['is_merchant']))
        {
            $user['is_merchant'] = intval($user_data['is_merchant']);
            $user['merchant_name'] = $user_data['merchant_name'];
        }
        if(isset($user_data['is_daren']))
        {
            $user['is_daren'] = intval($user_data['is_daren']);
            $user['daren_title'] = $user_data['daren_title'];
        }

        //自动获取会员分组
        if(intval($user_data['group_id'])!=0) {
            $user['group_id'] = $user_data['group_id'];
        } else {
            if($mode=='INSERT')
            {
                $group_id = $GLOBALS['sys_config']['SITE_USER_GROUP'][$GLOBALS['sys_config']['APP_SITE']];
                if(intval($group_id)==0)
                {
                    //获取默认会员组, 即升级积分最小的会员组
                    $user['group_id'] = $GLOBALS['db']->getOne("select id from ".DB_PREFIX."user_group order by score asc limit 1");
                }
                else
                {
                    $user['group_id'] = $group_id;
                }
            }
        }

        //会员等级
        if (intval($user_data['coupon_level_id']) != 0) {
            $user['coupon_level_id'] = $user_data['coupon_level_id'];
        } else {
            //todo 注册
        }
        if(intval($user_data['new_coupon_level_id']) != 0){
            $user['new_coupon_level_id'] = $user_data['new_coupon_level_id'];
        }
        //会员等级失效时间
        if (isset($user_data['coupon_level_valid_end'])) {
            $user['coupon_level_valid_end'] = to_timespan($user_data['coupon_level_valid_end']);
        }

        //返利系数，会员个人系数值合法时，取个人系数值，否则取/*所属群组的合法系数值，群组系数无效则默认复制*/1.0000
        if (!empty($user_data['channel_pay_factor']) && is_numeric($user_data['channel_pay_factor']) && $user_data['channel_pay_factor'] > 0) {
            $user['channel_pay_factor'] = $user_data['channel_pay_factor'];
        //} else if (!empty($user['group_id'])) { // 前台修改不传group_id
        //    $group_factor = $GLOBALS['db']->getOne("select channel_pay_factor from ".DB_PREFIX."user_group where id=" . $user['group_id']);
        //    $user['channel_pay_factor'] = (!empty($group_factor) && $group_factor > 0) ? $group_factor : '1.0000';
        } else {
            $user['channel_pay_factor'] = '1.0000';
        }

        //会员状态 Edit By guofeng At 20151225 15:05
        if($mode == 'INSERT') {
            $user['is_effect'] = isset($user_data['is_effect']) ? (int)$user_data['is_effect'] : app_conf('USER_VERIFY');
        }else if (isset($user_data['is_effect'])) {
            $user['is_effect'] = (int)$user_data['is_effect'];
        }

        if($mode=="INSERT" || isset($user_data['email']))
            $user['email'] = $user_data['email'];
        if(!isset($user_data['mobilepassed'])|| $user_data['mobilepassed']=="false"){
            $user['mobile'] = $user_data['mobile'];
            $user['mobile_code'] = $user_data['mobile_code'];
            $user['country_code'] = $user_data['country_code'];
        }
        // 后台强制修改手机号
        if ($modifyMobile === true){
            $user['mobile'] = $user_data['mobile'];
            $user['mobile_code'] = $user_data['mobile_code'];
            $user['country_code'] = $user_data['country_code'];
        }
        if($mode == 'INSERT')
        {
            $user['code'] = ''; //默认不使用code, 该值用于其他系统导入时的初次认证
        }
        else
        {
            $user['code'] = $GLOBALS['db']->getOne("select code from ".DB_PREFIX."user where id =".$user_data['id']);
        }

        //$user_data['user_pwd']=='';//屏蔽掉用户密码修改，走oauth
        //if(isset($user_data['user_pwd'])&&$user_data['user_pwd']!='')

        //$user['user_pwd'] = md5($user_data['user_pwd'].$user['code']);
        if(isset($user_data['user_pwd'])&&$user_data['user_pwd']!=''){
            require_once APP_ROOT_PATH.'core/service/user/BOBase.php';
            $boBase = new BOBase();
            $user['user_pwd'] = $boBase->compilePassword($user_data['user_pwd']);
        }
        //载入会员整合
        $integrate_code = trim(app_conf("INTEGRATE_CODE"));
        if($integrate_code!='')
        {
            $integrate_file = APP_ROOT_PATH."system/integrate/".$integrate_code."_integrate.php";
            if(file_exists($integrate_file))
            {
                require_once $integrate_file;
                $integrate_class = $integrate_code."_integrate";
                $integrate_obj = new $integrate_class;
            }
        }
        //同步整合
        if($integrate_obj)
        {
            if($mode == 'INSERT')
            {
                $res = $integrate_obj->add_user($user_data['user_name'],$user_data['user_pwd'],$user_data['email']);
                $user['integrate_id'] = intval($res['data']);
            }
            else
            {
                $add_res = $integrate_obj->add_user($user_data['user_name'],$user_data['user_pwd'],$user_data['email']);
                if(intval($add_res['status']))
                {
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set integrate_id = ".intval($add_res['data'])." where id = ".intval($user_data['id']));
                }
                else
                {
                    if(isset($user_data['user_pwd'])&&$user_data['user_pwd']!='') //有新密码
                    {
                        $status = $integrate_obj->edit_user($user,$user_data['user_pwd']);
                        if($status<=0)
                        {
                            //修改密码失败
                            $res['status'] = 0;
                        }
                    }
                }
            }
            if(intval($res['status'])==0) //整合注册失败
            {
                return $res;
            }
        }

        if($mode == 'INSERT')
        {
            $user['site_id'] = $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];
            $user['referer'] = NCFGroup\Protos\Ptp\Enum\DeviceEnum::DEVICE_WEB;//标记注册来源
            $s_api_user_info = es_session::get("api_user_info");
            $user[$s_api_user_info['field']] = $s_api_user_info['id'];
            es_session::delete("api_user_info");
            $where = '';
        }
        else
        {
            unset($user['pid']);
            $where = "id=".intval($user_data['id']);
        }
        if($GLOBALS['db']->autoExecute(DB_PREFIX."user",$user,$mode,$where))
        {
            if($mode == 'INSERT')
            {
                $user_id = $GLOBALS['db']->insert_id();
                $register_money = doubleval(app_conf("USER_REGISTER_MONEY"));
                $register_score = intval(app_conf("USER_REGISTER_SCORE"));
                $register_point = intval(app_conf("USER_REGISTER_POINT"));
                if($register_money>0||$register_score>0)
                {
                    $user_get['score'] = $register_score;
                    $user_get['money'] = $register_money;
                    $user_get['point'] = $register_point;
                    modify_account($user_get,intval($user_id),'注册成功',0,"在".to_date(get_gmtime())."注册成功");
                }
                //更新用户等级
                $coupon_level_service = new \core\service\CouponLevelService();
                $coupon_level_service->updateUserLevel($user_id);

                // $bonus_service = new \core\service\BonusService;
                // $bonus_service->bind($user_id, $user['mobile']);
            }
            else
            {
                $user_id = $user_data['id'];
            }
        }
        $res['data'] = $user_id;

        //开始更新处理扩展字段
        if($mode == 'INSERT')
        {
            foreach($user_field as $field_item)
            {
                $extend = array();
                $extend['user_id'] = $user_id;
                $extend['field_id'] = $field_item['id'];
                $extend['value'] = $user_data[$field_item['field_name']];
                $GLOBALS['db']->autoExecute(DB_PREFIX."user_extend",$extend,$mode);
            }

        }
        else
        {
            foreach($user_field as $field_item)
            {
                $extend = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_extend where user_id=".$user_id." and field_id =".$field_item['id']);
                if($extend)
                {
                    $extend['value'] = $user_data[$field_item['field_name']];
                    $where = 'id='.$extend['id'];
                    $GLOBALS['db']->autoExecute(DB_PREFIX."user_extend",$extend,$mode,$where);
                }
                else
                {
                    $extend = array();
                    $extend['user_id'] = $user_id;
                    $extend['field_id'] = $field_item['id'];
                    $extend['value'] = $user_data[$field_item['field_name']];
                    $GLOBALS['db']->autoExecute(DB_PREFIX."user_extend",$extend,"INSERT");
                }

            }
        }

        return $res;
    }

    /**
     * 删除会员以及相关数据
     * @param integer $id
     */
    function delete_user($id)
    {

        $result = 1;
        //载入会员整合
        $integrate_code = trim(app_conf("INTEGRATE_CODE"));
        if($integrate_code!='')
        {
            $integrate_file = APP_ROOT_PATH."system/integrate/".$integrate_code."_integrate.php";
            if(file_exists($integrate_file))
            {
                require_once $integrate_file;
                $integrate_class = $integrate_code."_integrate";
                $integrate_obj = new $integrate_class;
            }
        }
        if($integrate_obj)
        {
            $user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".$id);
            $result = $integrate_obj->delete_user($user_info);
        }

        if($result>0)
        {
            $now = get_gmtime();
            $GLOBALS['db']->query("delete from ".DB_PREFIX."user where id =".$id); //删除会员

            //以上数据不删除，只更新字段内容
            $GLOBALS['db']->query("update ".DB_PREFIX."user set pid = 0 where pid = ".$id); //更新推荐人数据为0
            $GLOBALS['db']->query("update ".DB_PREFIX."referrals set rel_user_id = 0 where rel_user_id=".$id);  //更新返利记录的推荐人为0
            $GLOBALS['db']->query("update ".DB_PREFIX."user_log set log_user_id = 0 where log_user_id=".$id);  //更新记录会员ID为0
            $GLOBALS['db']->query("update ".DB_PREFIX."delivery_notice set user_id = 0 where user_id=".$id);
            $GLOBALS['db']->query("update ".DB_PREFIX."payment_notice set user_id = 0, update_time = $now where user_id=".$id);    //收款单
            $GLOBALS['db']->query("update ".DB_PREFIX."deal_order set user_id= 0, update_time = $now where user_id=".$id);  //订单

            //开始删除关联数据
            $GLOBALS['db']->query("delete from ".DB_PREFIX."user_auth where user_id=".$id);  //权限
            $GLOBALS['db']->query("delete from ".DB_PREFIX."user_extend where user_id=".$id);  //扩展字段
            $GLOBALS['db']->query("delete from ".DB_PREFIX."user_log where user_id=".$id);  //会员日志
            $GLOBALS['db']->query("delete from ".DB_PREFIX."ecv where user_id=".$id);  //代金券
            $GLOBALS['db']->query("delete from ".DB_PREFIX."user_consignee where user_id=".$id);  //配送地址
            $GLOBALS['db']->query("delete from ".DB_PREFIX."promote_msg_list where user_id=".$id);  //推广队列
            $GLOBALS['db']->query("delete from ".DB_PREFIX."deal_msg_list where user_id=".$id);  //业务队列
            $GLOBALS['db']->query("delete from ".DB_PREFIX."user_cate_link where user_id=".$id);
            $GLOBALS['db']->query("delete from ".DB_PREFIX."msg_conf where user_id=".$id);//通知配置
            $GLOBALS['db']->query("delete from ".DB_PREFIX."user_carry where user_id=".$id); //提现
            $GLOBALS['db']->query("delete from ".DB_PREFIX."user_credit_file where user_id=".$id); //认证
            $GLOBALS['db']->query("delete from ".DB_PREFIX."user_autobid where user_id=".$id); //自动投标
            $GLOBALS['db']->query("delete from ".DB_PREFIX."user_work where user_id=".$id); //工作信息
            $GLOBALS['db']->query("delete from ".DB_PREFIX."user_sta where user_id=".$id); //用户统计

            //删除会员相关的关注
            //取出被删除会员ID关注的会员IDS,即我的关注
            $focus_user_ids = $GLOBALS['db']->getOne("select group_concat(focused_user_id) from ".DB_PREFIX."user_focus where focus_user_id = ".$id);
            if($focus_user_ids)
            $GLOBALS['db']->query("update ".DB_PREFIX."user set focused_count = focused_count - 1 where id in (".$focus_user_ids.")"); //减去相应会员的被关注数，即粉丝数


            //关注我的粉丝ID
            $fans_user_ids = $GLOBALS['db']->getOne("select group_concat(focus_user_id) from ".DB_PREFIX."user_focus where focused_user_id = ".$id);
            if($fans_user_ids)
            $GLOBALS['db']->query("update ".DB_PREFIX."user set focus_count = focus_count - 1 where id in (".$fans_user_ids.")"); //减去相应会员的关注数


            $GLOBALS['db']->query("delete from ".DB_PREFIX."user_focus where focus_user_id = ".$id." or focused_user_id = ".$id);

        }
    }

    /**
     * 弃用，请直接使用AccountService::changeMoney
     * 修改用户余额
     */
    function modify_account($data, $user_id, $log_msg = '', $allow = true, $log_note = '', $allowNagativeValue = true)
    {
    }

    /**
     * 处理cookie的自动登录
     * @param $user_name_or_email  用户名或邮箱
     * @param $user_md5_pwd  md5加密过的密码
     */
    function auto_do_login_user($user_name_or_email,$user_md5_pwd)
    {
        $user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where (user_name='".$user_name_or_email."' or email = '".$user_name_or_email."') and is_delete = 0");

        if($user_data)
        {
            if(md5($user_data['user_pwd']."_EASE_COOKIE")==$user_md5_pwd)
            {
                //成功
                es_session::set("user_info",$user_data);
                $GLOBALS['user_info'] = $user_data;
                $GLOBALS['db']->query("update ".DB_PREFIX."user set login_ip = '".get_client_ip()."',login_time= ".get_gmtime().",group_id=".intval($user_data['group_id'])." where id =".$user_data['id']);

                /*
                //登录成功自动检测关于会员等级
                $user_current_group = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_group where id = ".intval($user_data['group_id']));
                $user_group = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_group where score <=".intval($user_data['score'])." order by score desc");
                if($user_current_group['score']<$user_group['score'])
                {
                    $user_data['group_id'] = intval($user_group['id']);
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set level_id = ".$user_data['group_id']." where id = ".$user_data['id']);
                    $pm_title = "您已经成为".$user_group['name']."";
                    $pm_content = "恭喜您，您已经成为".$user_group['name']."。";
                    if($user_group['discount']<1)
                    {
                        $pm_content.="您将享有".($user_group['discount']*10)."折的购物优惠";
                    }
                    send_user_msg($pm_title,$pm_content,0,$user_data['id'],get_gmtime(),0,true,true);
                }

                $user_current_level = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_level where id = ".intval($user_data['level_id']));
                $user_level = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_level where point <=".intval($user_data['point'])." order by point desc");
                if($user_current_level['point']<$user_level['point'])
                {
                    $user_data['level_id'] = intval($user_level['id']);
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set level_id = ".$user_data['level_id']." where id = ".$user_data['id']);
                    $pm_title = "您已经成为".$user_level['name']."";
                    $pm_content = "恭喜您，您已经成为".$user_level['name']."。";
                    send_user_msg($pm_title,$pm_content,0,$user_data['id'],get_gmtime(),0,true,true);
                }

                if($user_current_level['point']>$user_level['point'])
                {
                    $user_data['level_id'] = intval($user_level['id']);
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set level_id = ".$user_data['level_id']." where id = ".$user_data['id']);
                    $pm_title = "您已经降为".$user_level['name']."";
                    $pm_content = "很报歉，您已经降为".$user_level['name']."。";
                    send_user_msg($pm_title,$pm_content,0,$user_data['id'],get_gmtime(),0,true,true);
                }

                //检测勋章
                $medal_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."medal where is_effect = 1 and allow_check = 1");
                foreach($medal_list as $medal)
                {
                    $file = APP_ROOT_PATH."system/medal/".$medal['class_name']."_medal.php";
                    $cls = $medal['class_name']."_medal";
                    if(file_exists($file))
                    {
                        require_once $file;
                        if(class_exists($cls))
                        {
                            $o = new $cls;
                            $check_result = $o->check_medal();
                            if($check_result['status']==0)
                            {
                                send_user_msg($check_result['info'],$check_result['info'],0,$user_data['id'],get_gmtime(),0,true,true);
                            }
                        }
                    }
                }
                */

            }
        }
    }
    /**
     * 处理会员登录
     * @param $passportid 用户名或邮箱地址
     * @param $user_pwd 密码
     *
     */
    function do_login_user($passportid,$user_pwd, $oauth = 0)
    {
        $user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where (passport_id='".$passportid."') and is_delete = 0");

        //载入会员整合
//        $integrate_code = trim(app_conf("INTEGRATE_CODE"));
//        if($integrate_code!='')
//        {
//            $integrate_file = APP_ROOT_PATH."system/integrate/".$integrate_code."_integrate.php";
//            if(file_exists($integrate_file))
//            {
//                require_once $integrate_file;
//                $integrate_class = $integrate_code."_integrate";
//                $integrate_obj = new $integrate_class;
//            }
//        }
//        if($integrate_obj)
//        {
//            $result = $integrate_obj->login($user_name_or_email,$user_pwd);
//
//        }
//
//        $user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where (user_name='".$user_name_or_email."' or email = '".$user_name_or_email."') and is_delete = 0");
        if(!$user_data)
        {
            $result['status'] = 0;
            $result['data'] = ACCOUNT_NO_EXIST_ERROR;
            return $result;
        }
        else
        {
            $result['user'] = $user_data;
            if( !$oauth && $user_data['user_pwd'] != md5($user_pwd.$user_data['code']))
            {
                $result['status'] = 0;
                $result['data'] = ACCOUNT_PASSWORD_ERROR;
                return $result;
            }
            elseif($user_data['is_effect'] != 1)
            {
                $result['status'] = 0;
                $result['data'] = ACCOUNT_NO_VERIFY_ERROR;
                return $result;
            }
            else
            {

                if(intval($result['status'])==0) //未整合，则直接成功
                {
                    $result['status'] = 1;
                }

                /*
                $user_current_group = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_group where id = ".intval($user_data['group_id']));
                $user_group = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_group where score <=".intval($user_data['score'])." order by score desc");
                if($user_current_group['score']<$user_group['score'])
                {
                    $user_data['group_id'] = intval($user_group['id']);
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set group_id = ".$user_data['group_id']." where id = ".$user_data['id']);
                    $pm_title = "您已经成为".$user_group['name']."";
                    $pm_content = "恭喜您，您的会有组升级为".$user_group['name']."。";
                    if($user_group['discount']<1)
                    {
                        $pm_content.="您将享有".($user_group['discount']*10)."折的购物优惠";
                    }
                    send_user_msg($pm_title,$pm_content,0,$user_data['id'],get_gmtime(),0,true,true);
                }



                $user_current_level = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_level where id = ".intval($user_data['level_id']));
                $user_level = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_level where point <=".intval($user_data['point'])." order by point desc");
                if($user_current_level['point']<$user_level['point'])
                {
                    $user_data['level_id'] = intval($user_level['id']);
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set level_id = ".$user_data['level_id']." where id = ".$user_data['id']);
                    $pm_title = "您信用等级升级为：".$user_level['name']."";
                    $pm_content = "恭喜您，您的信用等级升级到".$user_level['name']."。";
                    send_user_msg($pm_title,$pm_content,0,$user_data['id'],get_gmtime(),0,true,true);
                }

                if($user_current_level['point']>$user_level['point'])
                {
                    $user_data['level_id'] = intval($user_level['id']);
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set level_id = ".$user_data['level_id']." where id = ".$user_data['id']);
                    $pm_title = "您已经降为".$user_level['name']."";
                    $pm_content = "很报歉，您的信用等级降为".$user_level['name']."。";
                    send_user_msg($pm_title,$pm_content,0,$user_data['id'],get_gmtime(),0,true,true);
                }

                //检测勋章
                $medal_list = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."medal where is_effect = 1 and allow_check = 1");
                foreach($medal_list as $medal)
                {
                    $file = APP_ROOT_PATH."system/medal/".$medal['class_name']."_medal.php";
                    $cls = $medal['class_name']."_medal";
                    if(file_exists($file))
                    {
                        require_once $file;
                        if(class_exists($cls))
                        {
                            $o = new $cls;
                            $check_result = $o->check_medal();
                            if($check_result['status']==0)
                            {
                                send_user_msg($check_result['info'],$check_result['info'],0,$user_data['id'],get_gmtime(),0,true,true);
                            }
                        }
                    }
                }
                */
                es_session::set("user_info",$user_data);
                $GLOBALS['user_info'] = $user_data;
                $GLOBALS['db']->query("update ".DB_PREFIX."user set login_ip = '".get_client_ip()."',login_time= ".get_gmtime().",group_id=".intval($user_data['group_id'])." where id =".$user_data['id']);

                $s_api_user_info = es_session::get("api_user_info");

                if($s_api_user_info)
                {
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set ".$s_api_user_info['field']." = '".$s_api_user_info['id']."' where id = ".$user_data['id']." and (".$s_api_user_info['field']." = 0 or ".$s_api_user_info['field']."='')");
                    es_session::delete("api_user_info");
                }

                $result['step'] = intval($user_data["step"]);

                return $result;
            }
        }
    }

    /**
     * 登出,返回 array('status'=>'',data=>'',msg=>'') msg存放整合接口返回的字符串
     */
    function loginout_user()
    {
        $user_info = es_session::get("user_info");
        if(!$user_info)
        {
            return false;
        }
        else
        {
            //载入会员整合
            $integrate_code = trim(app_conf("INTEGRATE_CODE"));
            if($integrate_code!='')
            {
                $integrate_file = APP_ROOT_PATH."system/integrate/".$integrate_code."_integrate.php";
                if(file_exists($integrate_file))
                {
                    require_once $integrate_file;
                    $integrate_class = $integrate_code."_integrate";
                    $integrate_obj = new $integrate_class;
                }
            }
            if($integrate_obj)
            {
                $result = $integrate_obj->logout();
            }
            if(intval($result['status'])==0)
            {
                $result['status'] = 1;
            }

            es_session::delete("user_info");
            return $result;
        }
    }





    /**
     * 验证会员数据
     */
    function check_user($field_name,$field_data)
    {
        //开始数据验证
        $user_data[$field_name] = $field_data;
        $res = array('status'=>1,'info'=>'','data'=>''); //用于返回的数据
        if(trim($user_data['user_name'])==''&&$field_name=='user_name')
        {
            $field_item['field_name'] = 'user_name';
            $field_item['error']    =    EMPTY_ERROR;
            $res['status'] = 0;
            $res['data'] = $field_item;
            return $res;
        }
        if($field_name=='user_name'&&$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where user_name = '".trim($user_data['user_name'])."' and id <> ".intval($user_data['id']))>0)
        {
            $field_item['field_name'] = 'user_name';
            $field_item['error']    =    EXIST_ERROR;
            $res['status'] = 0;
            $res['data'] = $field_item;
            return $res;
        }
        if($field_name=='email'&&$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where email = '".trim($user_data['email'])."' and id <> ".intval($user_data['id']))>0)
        {
            $field_item['field_name'] = 'email';
            $field_item['error']    =    EXIST_ERROR;
            $res['status'] = 0;
            $res['data'] = $field_item;
            return $res;
        }
        if($field_name=='email'&&trim($user_data['email'])=='')
        {
            $field_item['field_name'] = 'email';
            $field_item['error']    =    EMPTY_ERROR;
            $res['status'] = 0;
            $res['data'] = $field_item;
            return $res;
        }
        if($field_name=='email'&&!check_email(trim($user_data['email'])))
        {
            $field_item['field_name'] = 'email';
            $field_item['error']    =    FORMAT_ERROR;
            $res['status'] = 0;
            $res['data'] = $field_item;
            return $res;
        }
        if($field_name=='mobile'&&intval(app_conf("MOBILE_MUST"))==1&&trim($user_data['mobile'])=='')
        {
            $field_item['field_name'] = 'mobile';
            $field_item['error']    =    EMPTY_ERROR;
            $res['status'] = 0;
            $res['data'] = $field_item;
            return $res;
        }

        if($field_name=='mobile'&&!check_mobile(trim($user_data['mobile'])))
        {
            $field_item['field_name'] = 'mobile';
            $field_item['error']    =    FORMAT_ERROR;
            $res['status'] = 0;
            $res['data'] = $field_item;
            return $res;
        }
        if($field_name=='mobile'&&$user_data['mobile']!=''&&$GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where mobile = '".trim($user_data['mobile'])."' and id <> ".intval($user_data['id']))>0)
        {
            $field_item['field_name'] = 'mobile';
            $field_item['error']    =    EXIST_ERROR;
            $res['status'] = 0;
            $res['data'] = $field_item;
            return $res;
        }
        //验证扩展字段
        $field_item = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user_field where field_name = '".$field_name."'");

        if($field_item['is_must']==1&&trim($user_data[$field_item['field_name']])=='')
        {
                $field_item['error']    =    EMPTY_ERROR;
                $res['status'] = 0;
                $res['data'] = $field_item;
                return $res;
        }



        return $res;
    }
?>
