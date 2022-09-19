<?php

/**
 * This is the model class for table "firstp2p_user".
 *
 * The followings are the available columns in table 'firstp2p_user':
 * @property integer $id
 * @property string $user_name
 * @property string $user_pwd
 * @property string $passport_id
 * @property integer $create_time
 * @property integer $update_time
 * @property string $login_ip
 * @property integer $group_id
 * @property integer $coupon_level_id
 * @property integer $coupon_level_valid_end
 * @property integer $is_effect
 * @property integer $is_delete
 * @property string $email
 * @property integer $id_type
 * @property string $idno
 * @property string $passport_no
 * @property string $military_id
 * @property string $h_idno
 * @property string $t_idno
 * @property string $m_idno
 * @property string $other_idno
 * @property integer $idcardpassed
 * @property integer $idcardpassed_time
 * @property integer $photo_passed
 * @property integer $photo_passed_time
 * @property string $real_name
 * @property string $country_code
 * @property string $mobile_code
 * @property string $mobile
 * @property integer $mobilepassed
 * @property integer $score
 * @property string $money
 * @property double $quota
 * @property string $lock_money
 * @property string $channel_pay_factor
 * @property string $verify
 * @property string $code
 * @property integer $pid
 * @property integer $login_time
 * @property integer $referral_count
 * @property string $password_verify
 * @property integer $integrate_id
 * @property integer $wx_freepayment
 * @property integer $supervision_user_id
 * @property integer $user_purpose
 * @property integer $user_type
 * @property string $bind_verify
 * @property integer $verify_create_time
 * @property string $is_dflh
 * @property string $referer
 * @property integer $login_pay_time
 * @property integer $focus_count
 * @property integer $focused_count
 * @property integer $n_province_id
 * @property integer $n_city_id
 * @property integer $province_id
 * @property integer $city_id
 * @property integer $sex
 * @property string $info
 * @property integer $step
 * @property integer $byear
 * @property integer $bmonth
 * @property integer $bday
 * @property string $graduation
 * @property integer $graduatedyear
 * @property string $university
 * @property string $edu_validcode
 * @property integer $has_send_video
 * @property string $marriage
 * @property integer $haschild
 * @property integer $hashouse
 * @property integer $houseloan
 * @property integer $hascar
 * @property integer $carloan
 * @property string $car_brand
 * @property integer $car_year
 * @property string $car_number
 * @property string $address
 * @property string $phone
 * @property string $postcode
 * @property integer $locate_time
 * @property double $xpoint
 * @property double $ypoint
 * @property integer $topic_count
 * @property integer $fav_count
 * @property integer $faved_count
 * @property integer $insite_count
 * @property integer $outsite_count
 * @property integer $level_id
 * @property integer $point
 * @property string $new_coupon_level_id
 * @property string $sina_app_secret
 * @property integer $coupon_disable
 * @property string $tencent_app_key
 * @property string $tencent_app_secret
 * @property integer $is_syn_tencent
 * @property string $email_sub
 * @property string $t_openkey
 * @property string $t_openid
 * @property string $sina_token
 * @property integer $is_borrow_out
 * @property integer $is_borrow_int
 * @property integer $creditpassed
 * @property integer $creditpassed_time
 * @property integer $workpassed
 * @property integer $workpassed_time
 * @property integer $incomepassed
 * @property integer $incomepassed_time
 * @property integer $housepassed
 * @property integer $housepassed_time
 * @property integer $carpassed
 * @property integer $carpassed_time
 * @property integer $marrypassed
 * @property integer $marrypassed_time
 * @property integer $edupassed
 * @property integer $edupassed_time
 * @property integer $skillpassed
 * @property integer $skillpassed_time
 * @property integer $videopassed
 * @property integer $videopassed_time
 * @property integer $mobiletruepassed
 * @property integer $mobiletruepassed_time
 * @property integer $residencepassed
 * @property integer $residencepassed_time
 * @property string $alipay_id
 * @property string $qq_id
 * @property integer $site_id
 * @property integer $is_staff
 * @property integer $refer_user_id
 * @property string $invite_code
 * @property integer $is_rebate
 * @property integer $force_new_passwd
 * @property string $payment_user_id
 * @property string $version_id
 * @property string $act_name
 */
class User extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return User the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return CDbConnection database connection
	 */
	public function getDbConnection()
	{
		return Yii::app()->phdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'firstp2p_user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_name, user_pwd, passport_id, create_time, update_time, login_ip, group_id, is_effect, is_delete, passport_no, military_id, h_idno, t_idno, m_idno, other_idno, idcardpassed, idcardpassed_time, real_name, mobile, mobilepassed, score, money, lock_money, code, login_time, focus_count, focused_count, n_province_id, n_city_id, province_id, city_id, graduation, graduatedyear, university, edu_validcode, marriage, haschild, hashouse, houseloan, hascar, carloan, car_brand, car_year, car_number, address, phone, point, creditpassed, creditpassed_time, workpassed, workpassed_time, incomepassed, incomepassed_time, housepassed, housepassed_time, carpassed, carpassed_time, marrypassed, marrypassed_time, edupassed, edupassed_time, skillpassed, skillpassed_time, videopassed, videopassed_time, mobiletruepassed, mobiletruepassed_time, residencepassed, residencepassed_time', 'required'),
			array('create_time, update_time, group_id, coupon_level_id, coupon_level_valid_end, is_effect, is_delete, id_type, idcardpassed, idcardpassed_time, photo_passed, photo_passed_time, mobilepassed, score, pid, login_time, referral_count, integrate_id, wx_freepayment, supervision_user_id, user_purpose, user_type, verify_create_time, login_pay_time, focus_count, focused_count, n_province_id, n_city_id, province_id, city_id, sex, step, byear, bmonth, bday, graduatedyear, has_send_video, haschild, hashouse, houseloan, hascar, carloan, car_year, locate_time, topic_count, fav_count, faved_count, insite_count, outsite_count, level_id, point, coupon_disable, is_syn_tencent, is_borrow_out, is_borrow_int, creditpassed, creditpassed_time, workpassed, workpassed_time, incomepassed, incomepassed_time, housepassed, housepassed_time, carpassed, carpassed_time, marrypassed, marrypassed_time, edupassed, edupassed_time, skillpassed, skillpassed_time, videopassed, videopassed_time, mobiletruepassed, mobiletruepassed_time, residencepassed, residencepassed_time, site_id, is_staff, refer_user_id, is_rebate, force_new_passwd', 'numerical', 'integerOnly'=>true),
			array('quota, xpoint, ypoint', 'numerical'),
			array('user_name, user_pwd, login_ip, email, idno, mobile, verify, code, password_verify, bind_verify, is_dflh, referer, new_coupon_level_id, sina_app_secret, tencent_app_key, tencent_app_secret, alipay_id, qq_id', 'length', 'max'=>255),
			array('passport_id, h_idno, t_idno, m_idno, money, lock_money, edu_validcode, postcode, invite_code', 'length', 'max'=>20),
			array('passport_no, military_id, other_idno', 'length', 'max'=>64),
			array('real_name, car_brand, car_number, phone, act_name', 'length', 'max'=>50),
			array('country_code', 'length', 'max'=>10),
			array('mobile_code', 'length', 'max'=>3),
			array('channel_pay_factor', 'length', 'max'=>8),
			array('graduation, marriage', 'length', 'max'=>15),
			array('university', 'length', 'max'=>100),
			array('address', 'length', 'max'=>150),
			array('email_sub, t_openkey, t_openid, sina_token', 'length', 'max'=>250),
			array('payment_user_id, version_id', 'length', 'max'=>11),
			array('debt_email,info', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('province_name,card_address,is_displace,user_loan_type,debt_email,id, user_name, user_pwd, passport_id, create_time, update_time, login_ip, group_id, coupon_level_id, coupon_level_valid_end, is_effect, is_delete, email, id_type, idno, passport_no, military_id, h_idno, t_idno, m_idno, other_idno, idcardpassed, idcardpassed_time, photo_passed, photo_passed_time, real_name, country_code, mobile_code, mobile, mobilepassed, score, money, quota, lock_money, channel_pay_factor, verify, code, pid, login_time, referral_count, password_verify, integrate_id, wx_freepayment, supervision_user_id, user_purpose, user_type, bind_verify, verify_create_time, is_dflh, referer, login_pay_time, focus_count, focused_count, n_province_id, n_city_id, province_id, city_id, sex, info, step, byear, bmonth, bday, graduation, graduatedyear, university, edu_validcode, has_send_video, marriage, haschild, hashouse, houseloan, hascar, carloan, car_brand, car_year, car_number, address, phone, postcode, locate_time, xpoint, ypoint, topic_count, fav_count, faved_count, insite_count, outsite_count, level_id, point, new_coupon_level_id, sina_app_secret, coupon_disable, tencent_app_key, tencent_app_secret, is_syn_tencent, email_sub, t_openkey, t_openid, sina_token, is_borrow_out, is_borrow_int, creditpassed, creditpassed_time, workpassed, workpassed_time, incomepassed, incomepassed_time, housepassed, housepassed_time, carpassed, carpassed_time, marrypassed, marrypassed_time, edupassed, edupassed_time, skillpassed, skillpassed_time, videopassed, videopassed_time, mobiletruepassed, mobiletruepassed_time, residencepassed, residencepassed_time, alipay_id, qq_id, site_id, is_staff, refer_user_id, invite_code, is_rebate, force_new_passwd, payment_user_id, version_id, act_name, fdd_customer_id,yj_fdd_customer_id', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_name' => 'User Name',
			'user_pwd' => 'User Pwd',
			'passport_id' => 'Passport',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'login_ip' => 'Login Ip',
			'group_id' => 'Group',
			'coupon_level_id' => 'Coupon Level',
			'coupon_level_valid_end' => 'Coupon Level Valid End',
			'is_effect' => 'Is Effect',
			'is_delete' => 'Is Delete',
			'email' => 'Email',
			'id_type' => 'Id Type',
			'idno' => 'Idno',
			'passport_no' => 'Passport No',
			'military_id' => 'Military',
			'h_idno' => 'H Idno',
			't_idno' => 'T Idno',
			'm_idno' => 'M Idno',
			'other_idno' => 'Other Idno',
			'idcardpassed' => 'Idcardpassed',
			'idcardpassed_time' => 'Idcardpassed Time',
			'photo_passed' => 'Photo Passed',
			'photo_passed_time' => 'Photo Passed Time',
			'real_name' => 'Real Name',
			'country_code' => 'Country Code',
			'mobile_code' => 'Mobile Code',
			'mobile' => 'Mobile',
			'mobilepassed' => 'Mobilepassed',
			'score' => 'Score',
			'money' => 'Money',
			'quota' => 'Quota',
			'lock_money' => 'Lock Money',
			'channel_pay_factor' => 'Channel Pay Factor',
			'verify' => 'Verify',
			'code' => 'Code',
			'pid' => 'Pid',
			'login_time' => 'Login Time',
			'referral_count' => 'Referral Count',
			'password_verify' => 'Password Verify',
			'integrate_id' => 'Integrate',
			'wx_freepayment' => 'Wx Freepayment',
			'supervision_user_id' => 'Supervision User',
			'user_purpose' => 'User Purpose',
			'user_type' => 'User Type',
			'bind_verify' => 'Bind Verify',
			'verify_create_time' => 'Verify Create Time',
			'is_dflh' => 'Is Dflh',
			'referer' => 'Referer',
			'login_pay_time' => 'Login Pay Time',
			'focus_count' => 'Focus Count',
			'focused_count' => 'Focused Count',
			'n_province_id' => 'N Province',
			'n_city_id' => 'N City',
			'province_id' => 'Province',
			'city_id' => 'City',
			'sex' => 'Sex',
			'info' => 'Info',
			'step' => 'Step',
			'byear' => 'Byear',
			'bmonth' => 'Bmonth',
			'bday' => 'Bday',
			'graduation' => 'Graduation',
			'graduatedyear' => 'Graduatedyear',
			'university' => 'University',
			'edu_validcode' => 'Edu Validcode',
			'has_send_video' => 'Has Send Video',
			'marriage' => 'Marriage',
			'haschild' => 'Haschild',
			'hashouse' => 'Hashouse',
			'houseloan' => 'Houseloan',
			'hascar' => 'Hascar',
			'carloan' => 'Carloan',
			'car_brand' => 'Car Brand',
			'car_year' => 'Car Year',
			'car_number' => 'Car Number',
			'address' => 'Address',
			'phone' => 'Phone',
			'postcode' => 'Postcode',
			'locate_time' => 'Locate Time',
			'xpoint' => 'Xpoint',
			'ypoint' => 'Ypoint',
			'topic_count' => 'Topic Count',
			'fav_count' => 'Fav Count',
			'faved_count' => 'Faved Count',
			'insite_count' => 'Insite Count',
			'outsite_count' => 'Outsite Count',
			'level_id' => 'Level',
			'point' => 'Point',
			'new_coupon_level_id' => 'New Coupon Level',
			'sina_app_secret' => 'Sina App Secret',
			'coupon_disable' => 'Coupon Disable',
			'tencent_app_key' => 'Tencent App Key',
			'tencent_app_secret' => 'Tencent App Secret',
			'is_syn_tencent' => 'Is Syn Tencent',
			'email_sub' => 'Email Sub',
			't_openkey' => 'T Openkey',
			't_openid' => 'T Openid',
			'sina_token' => 'Sina Token',
			'is_borrow_out' => 'Is Borrow Out',
			'is_borrow_int' => 'Is Borrow Int',
			'creditpassed' => 'Creditpassed',
			'creditpassed_time' => 'Creditpassed Time',
			'workpassed' => 'Workpassed',
			'workpassed_time' => 'Workpassed Time',
			'incomepassed' => 'Incomepassed',
			'incomepassed_time' => 'Incomepassed Time',
			'housepassed' => 'Housepassed',
			'housepassed_time' => 'Housepassed Time',
			'carpassed' => 'Carpassed',
			'carpassed_time' => 'Carpassed Time',
			'marrypassed' => 'Marrypassed',
			'marrypassed_time' => 'Marrypassed Time',
			'edupassed' => 'Edupassed',
			'edupassed_time' => 'Edupassed Time',
			'skillpassed' => 'Skillpassed',
			'skillpassed_time' => 'Skillpassed Time',
			'videopassed' => 'Videopassed',
			'videopassed_time' => 'Videopassed Time',
			'mobiletruepassed' => 'Mobiletruepassed',
			'mobiletruepassed_time' => 'Mobiletruepassed Time',
			'residencepassed' => 'Residencepassed',
			'residencepassed_time' => 'Residencepassed Time',
			'alipay_id' => 'Alipay',
			'qq_id' => 'Qq',
			'site_id' => 'Site',
			'is_staff' => 'Is Staff',
			'refer_user_id' => 'Refer User',
			'invite_code' => 'Invite Code',
			'is_rebate' => 'Is Rebate',
			'force_new_passwd' => 'Force New Passwd',
			'payment_user_id' => 'Payment User',
			'version_id' => 'Version',
			'act_name' => 'Act Name',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('user_name',$this->user_name,true);
		$criteria->compare('user_pwd',$this->user_pwd,true);
		$criteria->compare('passport_id',$this->passport_id,true);
		$criteria->compare('create_time',$this->create_time);
		$criteria->compare('update_time',$this->update_time);
		$criteria->compare('login_ip',$this->login_ip,true);
		$criteria->compare('group_id',$this->group_id);
		$criteria->compare('coupon_level_id',$this->coupon_level_id);
		$criteria->compare('coupon_level_valid_end',$this->coupon_level_valid_end);
		$criteria->compare('is_effect',$this->is_effect);
		$criteria->compare('is_delete',$this->is_delete);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('id_type',$this->id_type);
		$criteria->compare('idno',$this->idno,true);
		$criteria->compare('passport_no',$this->passport_no,true);
		$criteria->compare('military_id',$this->military_id,true);
		$criteria->compare('h_idno',$this->h_idno,true);
		$criteria->compare('t_idno',$this->t_idno,true);
		$criteria->compare('m_idno',$this->m_idno,true);
		$criteria->compare('other_idno',$this->other_idno,true);
		$criteria->compare('idcardpassed',$this->idcardpassed);
		$criteria->compare('idcardpassed_time',$this->idcardpassed_time);
		$criteria->compare('photo_passed',$this->photo_passed);
		$criteria->compare('photo_passed_time',$this->photo_passed_time);
		$criteria->compare('real_name',$this->real_name,true);
		$criteria->compare('country_code',$this->country_code,true);
		$criteria->compare('mobile_code',$this->mobile_code,true);
		$criteria->compare('mobile',$this->mobile,true);
		$criteria->compare('mobilepassed',$this->mobilepassed);
		$criteria->compare('score',$this->score);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('quota',$this->quota);
		$criteria->compare('lock_money',$this->lock_money,true);
		$criteria->compare('channel_pay_factor',$this->channel_pay_factor,true);
		$criteria->compare('verify',$this->verify,true);
		$criteria->compare('code',$this->code,true);
		$criteria->compare('pid',$this->pid);
		$criteria->compare('login_time',$this->login_time);
		$criteria->compare('referral_count',$this->referral_count);
		$criteria->compare('password_verify',$this->password_verify,true);
		$criteria->compare('integrate_id',$this->integrate_id);
		$criteria->compare('wx_freepayment',$this->wx_freepayment);
		$criteria->compare('supervision_user_id',$this->supervision_user_id);
		$criteria->compare('user_purpose',$this->user_purpose);
		$criteria->compare('user_type',$this->user_type);
		$criteria->compare('bind_verify',$this->bind_verify,true);
		$criteria->compare('verify_create_time',$this->verify_create_time);
		$criteria->compare('is_dflh',$this->is_dflh,true);
		$criteria->compare('referer',$this->referer,true);
		$criteria->compare('login_pay_time',$this->login_pay_time);
		$criteria->compare('focus_count',$this->focus_count);
		$criteria->compare('focused_count',$this->focused_count);
		$criteria->compare('n_province_id',$this->n_province_id);
		$criteria->compare('n_city_id',$this->n_city_id);
		$criteria->compare('province_id',$this->province_id);
		$criteria->compare('city_id',$this->city_id);
		$criteria->compare('sex',$this->sex);
		$criteria->compare('info',$this->info,true);
		$criteria->compare('step',$this->step);
		$criteria->compare('byear',$this->byear);
		$criteria->compare('bmonth',$this->bmonth);
		$criteria->compare('bday',$this->bday);
		$criteria->compare('graduation',$this->graduation,true);
		$criteria->compare('graduatedyear',$this->graduatedyear);
		$criteria->compare('university',$this->university,true);
		$criteria->compare('edu_validcode',$this->edu_validcode,true);
		$criteria->compare('has_send_video',$this->has_send_video);
		$criteria->compare('marriage',$this->marriage,true);
		$criteria->compare('haschild',$this->haschild);
		$criteria->compare('hashouse',$this->hashouse);
		$criteria->compare('houseloan',$this->houseloan);
		$criteria->compare('hascar',$this->hascar);
		$criteria->compare('carloan',$this->carloan);
		$criteria->compare('car_brand',$this->car_brand,true);
		$criteria->compare('car_year',$this->car_year);
		$criteria->compare('car_number',$this->car_number,true);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('postcode',$this->postcode,true);
		$criteria->compare('locate_time',$this->locate_time);
		$criteria->compare('xpoint',$this->xpoint);
		$criteria->compare('ypoint',$this->ypoint);
		$criteria->compare('topic_count',$this->topic_count);
		$criteria->compare('fav_count',$this->fav_count);
		$criteria->compare('faved_count',$this->faved_count);
		$criteria->compare('insite_count',$this->insite_count);
		$criteria->compare('outsite_count',$this->outsite_count);
		$criteria->compare('level_id',$this->level_id);
		$criteria->compare('point',$this->point);
		$criteria->compare('new_coupon_level_id',$this->new_coupon_level_id,true);
		$criteria->compare('sina_app_secret',$this->sina_app_secret,true);
		$criteria->compare('coupon_disable',$this->coupon_disable);
		$criteria->compare('tencent_app_key',$this->tencent_app_key,true);
		$criteria->compare('tencent_app_secret',$this->tencent_app_secret,true);
		$criteria->compare('is_syn_tencent',$this->is_syn_tencent);
		$criteria->compare('email_sub',$this->email_sub,true);
		$criteria->compare('t_openkey',$this->t_openkey,true);
		$criteria->compare('t_openid',$this->t_openid,true);
		$criteria->compare('sina_token',$this->sina_token,true);
		$criteria->compare('is_borrow_out',$this->is_borrow_out);
		$criteria->compare('is_borrow_int',$this->is_borrow_int);
		$criteria->compare('creditpassed',$this->creditpassed);
		$criteria->compare('creditpassed_time',$this->creditpassed_time);
		$criteria->compare('workpassed',$this->workpassed);
		$criteria->compare('workpassed_time',$this->workpassed_time);
		$criteria->compare('incomepassed',$this->incomepassed);
		$criteria->compare('incomepassed_time',$this->incomepassed_time);
		$criteria->compare('housepassed',$this->housepassed);
		$criteria->compare('housepassed_time',$this->housepassed_time);
		$criteria->compare('carpassed',$this->carpassed);
		$criteria->compare('carpassed_time',$this->carpassed_time);
		$criteria->compare('marrypassed',$this->marrypassed);
		$criteria->compare('marrypassed_time',$this->marrypassed_time);
		$criteria->compare('edupassed',$this->edupassed);
		$criteria->compare('edupassed_time',$this->edupassed_time);
		$criteria->compare('skillpassed',$this->skillpassed);
		$criteria->compare('skillpassed_time',$this->skillpassed_time);
		$criteria->compare('videopassed',$this->videopassed);
		$criteria->compare('videopassed_time',$this->videopassed_time);
		$criteria->compare('mobiletruepassed',$this->mobiletruepassed);
		$criteria->compare('mobiletruepassed_time',$this->mobiletruepassed_time);
		$criteria->compare('residencepassed',$this->residencepassed);
		$criteria->compare('residencepassed_time',$this->residencepassed_time);
		$criteria->compare('alipay_id',$this->alipay_id,true);
		$criteria->compare('qq_id',$this->qq_id,true);
		$criteria->compare('site_id',$this->site_id);
		$criteria->compare('is_staff',$this->is_staff);
		$criteria->compare('refer_user_id',$this->refer_user_id);
		$criteria->compare('invite_code',$this->invite_code,true);
		$criteria->compare('is_rebate',$this->is_rebate);
		$criteria->compare('force_new_passwd',$this->force_new_passwd);
		$criteria->compare('payment_user_id',$this->payment_user_id,true);
		$criteria->compare('version_id',$this->version_id,true);
		$criteria->compare('act_name',$this->act_name,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}