<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUser extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;


    /**
     *
     * @var string
     */
    public $user_name;


    /**
     *
     * @var string
     */
    public $user_pwd;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var string
     */
    public $login_ip;


    /**
     *
     * @var integer
     */
    public $group_id;


    /**
     *
     * @var integer
     */
    public $coupon_level_id;


    /**
     *
     * @var integer
     */
    public $coupon_level_valid_end;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var string
     */
    public $email;

    /**
     *
     * @var string
     */
    public $email_sub;

    /**
     *
     * @var integer
     */
    public $id_type;


    /**
     *
     * @var string
     */
    public $idno;


    /**
     *
     * @var string
     */
    public $passport_no;


    /**
     *
     * @var string
     */
    public $military_id;


    /**
     *
     * @var string
     */
    public $h_idno;


    /**
     *
     * @var string
     */
    public $t_idno;


    /**
     *
     * @var string
     */
    public $m_idno;


    /**
     *
     * @var string
     */
    public $other_idno;


    /**
     *
     * @var integer
     */
    public $idcardpassed;


    /**
     *
     * @var integer
     */
    public $idcardpassed_time;


    /**
     *
     * @var integer
     */
    public $photo_passed;


    /**
     *
     * @var integer
     */
    public $photo_passed_time;


    /**
     *
     * @var string
     */
    public $real_name;


    /**
     *
     * @var string
     */
    public $country_code;


    /**
     *
     * @var string
     */
    public $mobile_code;


    /**
     *
     * @var string
     */
    public $mobile;


    /**
     *
     * @var integer
     */
    public $mobilepassed;


    /**
     *
     * @var integer
     */
    public $score;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var float
     */
    public $quota;


    /**
     *
     * @var float
     */
    public $lock_money;


    /**
     *
     * @var float
     */
    public $channel_pay_factor;


    /**
     *
     * @var string
     */
    public $verify;


    /**
     *
     * @var string
     */
    public $code;


    /**
     *
     * @var integer
     */
    public $pid;


    /**
     *
     * @var integer
     */
    public $login_time;


    /**
     *
     * @var integer
     */
    public $referral_count;


    /**
     *
     * @var string
     */
    public $password_verify;


    /**
     *
     * @var integer
     */
    public $integrate_id;


    /**
     *
     * @var integer
     */
    public $wx_freepayment;


    /**
     *
     * @var integer
     */
    public $supervision_user_id;


    /**
     *
     * @var integer
     */
    public $user_purpose;


    /**
     *
     * @var integer
     */
    public $user_type;


    /**
     *
     * @var string
     */
    public $bind_verify;


    /**
     *
     * @var integer
     */
    public $verify_create_time;


    /**
     *
     * @var string
     */
    public $is_dflh;


    /**
     *
     * @var string
     */
    public $referer;


    /**
     *
     * @var integer
     */
    public $login_pay_time;


    /**
     *
     * @var integer
     */
    public $focus_count;


    /**
     *
     * @var integer
     */
    public $focused_count;


    /**
     *
     * @var integer
     */
    public $n_province_id;


    /**
     *
     * @var integer
     */
    public $n_city_id;


    /**
     *
     * @var integer
     */
    public $province_id;


    /**
     *
     * @var integer
     */
    public $city_id;


    /**
     *
     * @var integer
     */
    public $sex;


    /**
     *
     * @var string
     */
    public $info;


    /**
     *
     * @var integer
     */
    public $step;


    /**
     *
     * @var integer
     */
    public $byear;


    /**
     *
     * @var integer
     */
    public $bmonth;


    /**
     *
     * @var integer
     */
    public $bday;


    /**
     *
     * @var string
     */
    public $graduation;


    /**
     *
     * @var integer
     */
    public $graduatedyear;


    /**
     *
     * @var string
     */
    public $university;


    /**
     *
     * @var string
     */
    public $edu_validcode;


    /**
     *
     * @var integer
     */
    public $has_send_video;


    /**
     *
     * @var string
     */
    public $marriage;


    /**
     *
     * @var integer
     */
    public $haschild;


    /**
     *
     * @var integer
     */
    public $hashouse;


    /**
     *
     * @var integer
     */
    public $houseloan;


    /**
     *
     * @var integer
     */
    public $hascar;


    /**
     *
     * @var integer
     */
    public $carloan;


    /**
     *
     * @var string
     */
    public $car_brand;


    /**
     *
     * @var integer
     */
    public $car_year;


    /**
     *
     * @var string
     */
    public $car_number;


    /**
     *
     * @var string
     */
    public $address;


    /**
     *
     * @var string
     */
    public $phone;


    /**
     *
     * @var string
     */
    public $postcode;


    /**
     *
     * @var integer
     */
    public $locate_time;


    /**
     *
     * @var float
     */
    public $xpoint;


    /**
     *
     * @var float
     */
    public $ypoint;


    /**
     *
     * @var integer
     */
    public $topic_count;


    /**
     *
     * @var integer
     */
    public $fav_count;


    /**
     *
     * @var integer
     */
    public $faved_count;


    /**
     *
     * @var integer
     */
    public $insite_count;


    /**
     *
     * @var integer
     */
    public $outsite_count;


    /**
     *
     * @var integer
     */
    public $level_id;


    /**
     *
     * @var integer
     */
    public $point;


    /**
     *
     * @var string
     */
    public $new_coupon_level_id;


    /**
     *
     * @var string
     */
    public $sina_app_secret;


    /**
     *
     * @var integer
     */
    public $coupon_disable;


    /**
     *
     * @var string
     */
    public $tencent_app_key;


    /**
     *
     * @var string
     */
    public $tencent_app_secret;


    /**
     *
     * @var integer
     */
    public $is_syn_tencent;


    /**
     *
     * @var string
     */
    public $t_access_token;


    /**
     *
     * @var string
     */
    public $t_openkey;


    /**
     *
     * @var string
     */
    public $t_openid;


    /**
     *
     * @var string
     */
    public $sina_token;


    /**
     *
     * @var integer
     */
    public $is_borrow_out;


    /**
     *
     * @var integer
     */
    public $is_borrow_int;


    /**
     *
     * @var integer
     */
    public $creditpassed;


    /**
     *
     * @var integer
     */
    public $creditpassed_time;


    /**
     *
     * @var integer
     */
    public $workpassed;


    /**
     *
     * @var integer
     */
    public $workpassed_time;


    /**
     *
     * @var integer
     */
    public $incomepassed;


    /**
     *
     * @var integer
     */
    public $incomepassed_time;


    /**
     *
     * @var integer
     */
    public $housepassed;


    /**
     *
     * @var integer
     */
    public $housepassed_time;


    /**
     *
     * @var integer
     */
    public $carpassed;


    /**
     *
     * @var integer
     */
    public $carpassed_time;


    /**
     *
     * @var integer
     */
    public $marrypassed;


    /**
     *
     * @var integer
     */
    public $marrypassed_time;


    /**
     *
     * @var integer
     */
    public $edupassed;


    /**
     *
     * @var integer
     */
    public $edupassed_time;


    /**
     *
     * @var integer
     */
    public $skillpassed;


    /**
     *
     * @var integer
     */
    public $skillpassed_time;


    /**
     *
     * @var integer
     */
    public $videopassed;


    /**
     *
     * @var integer
     */
    public $videopassed_time;


    /**
     *
     * @var integer
     */
    public $mobiletruepassed;


    /**
     *
     * @var integer
     */
    public $mobiletruepassed_time;


    /**
     *
     * @var integer
     */
    public $residencepassed;


    /**
     *
     * @var integer
     */
    public $residencepassed_time;


    /**
     *
     * @var string
     */
    public $alipay_id;


    /**
     *
     * @var string
     */
    public $qq_id;


    /**
     *
     * @var integer
     */
    public $passport_id;


    /**
     *
     * @var integer
     */
    public $site_id;


    /**
     *
     * @var integer
     */
    public $is_staff;


    /**
     *
     * @var integer
     */
    public $refer_user_id;


    /**
     *
     * @var string
     */
    public $invite_code;


    /**
     *
     * @var integer
     */
    public $is_rebate;


    /**
     *
     * @var integer
     */
    public $force_new_passwd;


    /**
     *
     * @var integer
     */
    public $payment_user_id;


    /**
     *
     * @var integer
     */
    public $version_id;


    /**
     *
     * @var string
     */
    public $act_name;


    /**
     *
     * @var integer
     */
    public $qwe;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->couponLevelId = '0';
        $this->couponLevelValidEnd = '0';
        $this->idType = '1';
        $this->idno = '';
        $this->mIdno = '';
        $this->photoPassed = '0';
        $this->countryCode = 'cn';
        $this->mobileCode = '86';
        $this->quota = '0';
        $this->channelPayFactor = '1.0000';
        $this->userType = '0';
        $this->isDflh = '0';
        $this->sex = '-1';
        $this->locateTime = '0';
        $this->xpoint = '0.000000';
        $this->ypoint = '0.000000';
        $this->passportId = '0';
        $this->siteId = '1';
        $this->isStaff = '0';
        $this->referUserId = '0';
        $this->inviteCode = '';
        $this->isRebate = '0';
        $this->forceNewPasswd = '0';
        $this->paymentUserId = '0';
        $this->versionId = '0';
        $this->actName = '';
        //END DEFAULT_VALUE
    }

    public function initialize()
    {
        parent::initialize();
        $this->setReadConnectionService('firstp2p_r');
        $this->setWriteConnectionService('firstp2p');
    }

    public function columnMap()
    {
        return array(
            'id' => 'id',
            'user_name' => 'userName',
            'user_pwd' => 'userPwd',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'login_ip' => 'loginIp',
            'group_id' => 'groupId',
            'coupon_level_id' => 'couponLevelId',
            'coupon_level_valid_end' => 'couponLevelValidEnd',
            'is_effect' => 'isEffect',
            'is_delete' => 'isDelete',
            'email' => 'email',
            'email_sub' => 'emailSub',
            'id_type' => 'idType',
            'idno' => 'idno',
            'passport_no' => 'passportNo',
            'military_id' => 'militaryId',
            'h_idno' => 'hIdno',
            't_idno' => 'tIdno',
            'm_idno' => 'mIdno',
            'other_idno' => 'otherIdno',
            'idcardpassed' => 'idcardpassed',
            'idcardpassed_time' => 'idcardpassedTime',
            'photo_passed' => 'photoPassed',
            'photo_passed_time' => 'photoPassedTime',
            'real_name' => 'realName',
            'country_code' => 'countryCode',
            'mobile_code' => 'mobileCode',
            'mobile' => 'mobile',
            'mobilepassed' => 'mobilepassed',
            'score' => 'score',
            'money' => 'money',
            'quota' => 'quota',
            'lock_money' => 'lockMoney',
            'channel_pay_factor' => 'channelPayFactor',
            'verify' => 'verify',
            'code' => 'code',
            'pid' => 'pid',
            'login_time' => 'loginTime',
            'referral_count' => 'referralCount',
            'password_verify' => 'passwordVerify',
            'integrate_id' => 'integrateId',
            'wx_freepayment' => 'wxFreepayment',
            'supervision_user_id' => 'supervisionUserId',
            'user_purpose' => 'userPurpose',
            'user_type' => 'userType',
            'bind_verify' => 'bindVerify',
            'verify_create_time' => 'verifyCreateTime',
            'is_dflh' => 'isDflh',
            'tencent_id' => 'tencentId',
            'referer' => 'referer',
            'login_pay_time' => 'loginPayTime',
            'focus_count' => 'focusCount',
            'focused_count' => 'focusedCount',
            'n_province_id' => 'nProvinceId',
            'n_city_id' => 'nCityId',
            'province_id' => 'provinceId',
            'city_id' => 'cityId',
            'sex' => 'sex',
            'info' => 'info',
            'step' => 'step',
            'byear' => 'byear',
            'bmonth' => 'bmonth',
            'bday' => 'bday',
            'graduation' => 'graduation',
            'graduatedyear' => 'graduatedyear',
            'university' => 'university',
            'edu_validcode' => 'eduValidcode',
            'has_send_video' => 'hasSendVideo',
            'marriage' => 'marriage',
            'haschild' => 'haschild',
            'hashouse' => 'hashouse',
            'houseloan' => 'houseloan',
            'hascar' => 'hascar',
            'carloan' => 'carloan',
            'car_brand' => 'carBrand',
            'car_year' => 'carYear',
            'car_number' => 'carNumber',
            'address' => 'address',
            'phone' => 'phone',
            'postcode' => 'postcode',
            'locate_time' => 'locateTime',
            'xpoint' => 'xpoint',
            'ypoint' => 'ypoint',
            'topic_count' => 'topicCount',
            'fav_count' => 'favCount',
            'faved_count' => 'favedCount',
            'insite_count' => 'insiteCount',
            'outsite_count' => 'outsiteCount',
            'level_id' => 'levelId',
            'point' => 'point',
            'new_coupon_level_id' => 'newCouponLevelId',
            'sina_app_secret' => 'sinaAppSecret',
            'coupon_disable' => 'couponDisable',
            'tencent_app_key' => 'tencentAppKey',
            'tencent_app_secret' => 'tencentAppSecret',
            'is_syn_tencent' => 'isSynTencent',
            't_access_token' => 'tAccessToken',
            't_openkey' => 'tOpenkey',
            't_openid' => 'tOpenid',
            'sina_token' => 'sinaToken',
            'is_borrow_out' => 'isBorrowOut',
            'is_borrow_int' => 'isBorrowInt',
            'creditpassed' => 'creditpassed',
            'creditpassed_time' => 'creditpassedTime',
            'workpassed' => 'workpassed',
            'workpassed_time' => 'workpassedTime',
            'incomepassed' => 'incomepassed',
            'incomepassed_time' => 'incomepassedTime',
            'housepassed' => 'housepassed',
            'housepassed_time' => 'housepassedTime',
            'carpassed' => 'carpassed',
            'carpassed_time' => 'carpassedTime',
            'marrypassed' => 'marrypassed',
            'marrypassed_time' => 'marrypassedTime',
            'edupassed' => 'edupassed',
            'edupassed_time' => 'edupassedTime',
            'skillpassed' => 'skillpassed',
            'skillpassed_time' => 'skillpassedTime',
            'videopassed' => 'videopassed',
            'videopassed_time' => 'videopassedTime',
            'mobiletruepassed' => 'mobiletruepassed',
            'mobiletruepassed_time' => 'mobiletruepassedTime',
            'residencepassed' => 'residencepassed',
            'residencepassed_time' => 'residencepassedTime',
            'alipay_id' => 'alipayId',
            'qq_id' => 'qqId',
            'passport_id' => 'passportId',
            'site_id' => 'siteId',
            'is_staff' => 'isStaff',
            'refer_user_id' => 'referUserId',
            'invite_code' => 'inviteCode',
            'is_rebate' => 'isRebate',
            'force_new_passwd' => 'forceNewPasswd',
            'payment_user_id' => 'paymentUserId',
            'version_id' => 'versionId',
            'act_name' => 'actName',
            'qwe' => 'qwe',
        );
    }

    public function getSource()
    {
        return "firstp2p_user";
    }
}
