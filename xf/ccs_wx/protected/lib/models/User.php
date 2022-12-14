<?php

/**
 * This is the model class for table "dw_user".
 *
 * The followings are the available columns in table 'dw_user':
 * @property string $user_id
 * @property integer $type_id
 * @property integer $order
 * @property string $purview
 * @property string $username
 * @property string $password
 * @property string $paypassword
 * @property integer $islock
 * @property integer $invite_userid
 * @property integer $invite_money
 * @property integer $id5_num
 * @property integer $real_type
 * @property string $real_status
 * @property integer $real_apply_time
 * @property integer $real_auth_time
 * @property string $audit_content
 * @property string $card_type
 * @property string $card_id
 * @property string $card_pic1
 * @property string $card_pic2
 * @property string $nation
 * @property string $realname
 * @property string $integral
 * @property integer $status
 * @property integer $avatar_status
 * @property string $email_status
 * @property string $phone_status
 * @property integer $video_status
 * @property integer $scene_status
 * @property string $email
 * @property string $sex
 * @property string $litpic
 * @property string $tel
 * @property string $phone
 * @property string $qq
 * @property string $wangwang
 * @property string $question
 * @property string $answer
 * @property string $birthday
 * @property string $province
 * @property string $city
 * @property string $area
 * @property string $address
 * @property string $postcode
 * @property string $remind
 * @property string $privacy
 * @property integer $logintime
 * @property string $addtime
 * @property string $addip
 * @property string $uptime
 * @property string $upip
 * @property string $lasttime
 * @property string $lastip
 * @property string $others
 * @property string $user_src
 * @property string $tuiguang_keywords
 * @property string $salesman_userid
 * @property string $openid
 * @property string $access_token
 * @property string $open_type
 * @property integer $authorize_cash
 * @property string $phone_audit_content
 * @property string $reg_device
 * @property string $phone_vcode
 * @property integer $qn_score
 * @property integer $isinvested
 */
class User extends DwActiveRecord
{
     public $dbname = 'dwdb';
     public $uprealname;//??????????????????
     public $upusername;//?????????
	 public $phone;//?????????
     public $bbs_nick;//????????????
     public $debt_times;//??????????????????
     public $addtime_start;
     public $addtime_end;
     public $isrecharge;//????????????
     public $norecharge;//???????????????
     
     
     //????????????
     protected $_emailStatus=array(0=>'?????????',1=>'?????????');
     public function getEmailStatus1(){
         return $this->_emailStatus;
     }
     public function getEmailStatus(){
         return $this->_emailStatus[$this->email_status];        
    }
    
    //????????????
    protected  $_phoneStatu=array(0=>'?????????',1=>'?????????',2=>'?????????');
    //??????????????????????????????
    public function getPhoneStatusA(){
        return $this->_phoneStatu;
    }
    //????????????????????????
     public function getPhoneStatus(){
         if(!empty($this->phone)){
             if($this->phone_status == 1 ){
                 return "?????????";
             }else if($this->phone_status ==0){
                 return "?????????";
             }else{
                 return "?????????";
             }
         }else{
             return "?????????";
         }
     }
     
     //??????????????????
     public function getDebtTimes($user_id){
         $num = 0;
         if($user_id){
             $debtRes = DebtTender::model()->findAll('status=2 and user_id='.$user_id);
             if($debtRes){
                 $num = count($debtRes);
             }
         }
         return $num;
     }
     
     //??????
     protected $_status=array('?????????','?????????',9=>'?????????');
    
     public function getStatus(){
         return $this->_status;
     }
     public function StrStatus(){
         if(isset($this->_status[$this->safecard_status]))
            return $this->_status[$this->safecard_status];
         else 
            return '??????';
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_status))?$this->_status[$key]:"";
     } 
     
     //??????????????????
     protected $_realNameA1=array(0=>'?????????',1=>'?????????',2=>'?????????',3=>'???????????????');
     public function getRealStatusA(){
         return $this->_realNameA1;
     }
     public function getRealNameStatus(){
         return $this->_realNameA1[$this->real_status];
     }
     
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'dw_user';
	}

    //????????????
    public function getBbsNick($ucenter_uid){
        $bbsInfo = CommonMemberFieldForum::model()->find('uid='.$ucenter_uid);
        return $bbsInfo->customstatus;
    }
    
     //????????????
     protected $_sexType=array(1=>'???',2=>'???');
    
     public function getSexType(){
         return $this->_sexType;
     }
     

     public function StrSexType(){
         if($this->sex)
            return $this->_sexType[$this->sex];
     }
     public function getStrSexType($key){
         return (array_key_exists($key, $this->_sexType))?$this->_sexType[$key]:"????????????";
     }
     
     protected $_cardType=array('??????','?????????','?????????','?????????');
    
     public function getCardType(){
         return $this->_cardType;
     }
     public function StrCardType(){
         if($this->card_type)
            return $this->_cardType[$this->card_type];
     }
     public function getStrCardType($key){
         return (array_key_exists($key, $this->_cardType))?$this->_cardType[$key]:"????????????";
     }
	 
	 protected $_marryType=array('?????????','??????','??????');
    
     public function getMarryType(){
         return $this->_marryType;
     }
     public function StrMarryType(){
         return $this->_marryType[$this->merriage_status];
     }
     public function getStrMarryType($key){
         return (array_key_exists($key, $this->_marryType))?$this->_marryType[$key]:"";
     }
     /**
     * [??????]??????
     */
     protected $_real_status=array(1=>'[??????]??????',2=>'??????[??????]',3=>'[??????]?????????');
    
     public function getRealStatus(){
         return $this->_real_status;
     }
     public function StrRealStatus(){
         if($this->real_status)
            return $this->_real_status[$this->real_status];
     }
     public function getStrRealStatus($key){
         return (array_key_exists($key, $this->_real_status))?$this->_real_status[$key]:"????????????";
     }
     
     protected $_real_type=array(1=>'????????????',2=>'????????????',3=>'????????????');
    
     public function getRealType(){
         return $this->_real_type;
     }
     public function StrRealType(){
         if($this->real_type)
            return $this->_real_type[$this->real_type];
     }
     public function getStrRealType($key){
         return (array_key_exists($key, $this->_real_type))?$this->_real_type[$key]:"-";
     }
    /**
     * ????????????
     */
     protected $_isinvestedType=array('?????????','?????????');
    
     public function getIsinvestedType(){
         return $this->_isinvestedType;
     }
     public function StrIsinvestedType(){
         return $this->_isinvestedType[$this->isinvested];
     }
     public function getStrIsinvestedType($key){
         return (array_key_exists($key, $this->_isinvestedType))?$this->_isinvestedType[$key]:"";
     }
     
     //????????????
     protected $_isrechargeType=array(1=>'?????????',2=>'?????????');
    
     public function getIsrechargeType(){
         return $this->_isrechargeType;
     }
     public function StrIsrechargeType(){
         return $this->_isrechargeType[$this->isrecharge];
     }
     public function getStrIsrechargeType($key){
         return (array_key_exists($key, $this->_isrechargeType))?$this->_isrechargeType[$key]:"";
     }
    /**
     * ??????????????????
     */
    protected $_reg_deviceType=array('pc'=>'pc','wap'=>'wap','ios'=>'ios','android'=>'android','pc_forum'=>'pc??????');
    
     public function getRegDeviceType(){
         return $this->_reg_deviceType;
     }
     public function StrRegDeviceType(){
         if(!empty($this->reg_device)){
             return $this->_reg_deviceType[$this->reg_device];
         }else{
             return '';
         }
         
     }
     public function getStrRegDeviceType($key){
         return (array_key_exists($key, $this->_reg_deviceType))?$this->_reg_deviceType[$key]:"";
     }
     
	 
	 
     public function getUsersrcType(){
         $usersrcs = Yii::app()->params['user_src'];
         $itzChannelList = ItzChannel::model()->findAll();
         foreach ($itzChannelList as $key => $value) {
             $usersrcs[$value->id] = $value->name;
         }
         return $usersrcs;
     }
     public function StrUsersrcType(){
         $usersrcs = $this->getUsersrcType();
         if(isset($usersrcs[$this->user_src]))
            return $usersrcs[$this->user_src];
     }
     public function getStrUsersrcType($key){
         $res  = $this->getUsersrcType();
         return (array_key_exists($key, $res?$res[$key]:""));
     }
     
     protected $_userStatus=array('??????','?????????','??????');
    
     public function getUserStatus(){
         return $this->_userStatus;
     }
     public function StrUserStatus(){
         return $this->_userStatus[$this->status];
     }
     public function getStrUserStatus($key){
         return (array_key_exists($key, $this->_userStatus))?$this->_userStatus[$key]:"";
     }
     //?????????????????????????????????????????????
     public function getBorrowRepaymentTime($user_id){
         $returnMsg = '-';
         $userSql = 'select `repayment_time` from dw_borrow WHERE id in (select DISTINCT(borrow_id) from  dw_borrow_tender WHERE user_id='.$user_id.') ORDER BY repayment_time DESC LIMIT 1';
         $timeInfo = Yii::app()->dwdb->createCommand($userSql)->queryAll();
         if(count($timeInfo)>0){
             $returnMsg = date("Y-m-d",$timeInfo[0]['repayment_time']);
         }
         return $returnMsg;
     }
     
     //??????????????????????????????
     protected $_birthday=array(1=>"????????????",2=>"???7???????????????");
    
     public function getBirthday(){
         return $this->_birthday;
     }
     public function StrBirthday(){
         if(isset($this->birthday) && isset($this->_birthday[$this->birthday]))
            return $this->_birthday[$this->birthday];
     }
     public function getStrBirthday($key){
         return (array_key_exists($key, $this->_birthday))?$this->_birthday[$key]:"-";
     }
     
     
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
		    array( 'username', 'required','message'=>Yii::t('luben','{attribute}????????????')),
			array('type_id, order, islock, invite_userid, invite_money, id5_num, real_type, real_apply_time, real_auth_time, status, avatar_status, video_status, scene_status, logintime, authorize_cash, qn_score, isinvested, addtime,user_grade_code', 'numerical', 'integerOnly'=>true),
			array('purview, wangwang, answer', 'length', 'max'=>100),
			array('username, address', 'length', 'max'=>200),
			array('password, paypassword, card_id, email_status, phone_status, email, tel, phone, qq, addip, uptime, upip, lasttime', 'length', 'max'=>50),
			array('real_status', 'length', 'max'=>2),
			array('audit_content, litpic, others', 'length', 'max'=>250),
			array('card_type, nation, integral, sex, question', 'length', 'max'=>10),
			array('card_pic1, card_pic2', 'length', 'max'=>150),
			array('realname, province, city, area, postcode, lastip, open_type, phone_vcode', 'length', 'max'=>20),
			array('birthday, salesman_userid', 'length', 'max'=>11),
			array('user_src, reg_device', 'length', 'max'=>32),
			array('tuiguang_keywords, openid, access_token', 'length', 'max'=>255),
            array('phone_audit_content', 'length', 'max'=>500),
			array('safecard_status,remind, privacy,status,user_grade_code,grade_expire_time', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('norecharge,isrecharge,addtime_start,addtime_end,debt_times,bbs_nick,safecard_status,phone,merriage_status,month_income,user_position,business_scale,school,high_edu,reg_device,invest_times,ucenter_uid,uprealname,upusername,user_id, type_id, order, purview, username, password, paypassword, islock, invite_userid, invite_money, id5_num, real_type, real_status, real_apply_time, real_auth_time, audit_content, card_type, card_id, card_pic1, card_pic2, nation, realname, integral, status, avatar_status, email_status, phone_status, video_status, scene_status, email, sex, litpic, tel, phone, qq, wangwang, question, answer, birthday, province, city, area, address, postcode, remind, privacy, logintime, addtime, addip, uptime, upip, lasttime, lastip, others, user_src, tuiguang_keywords, salesman_userid, openid, access_token, open_type, authorize_cash, phone_audit_content, reg_device, phone_vcode, qn_score, isinvested,user_grade_code,grade_expire_time', 'safe', 'on'=>'search'),
		    array('user_id,username,phone,realname,sex,birthday,card_type,card_id,real_apply_time,real_status,real_auth_time,user_grade_code', 'safe', 'on'=>'searchForRealType'),
        );
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
		    "accountInfo" =>  array(self::HAS_ONE, 'Account','user_id'),
		    "userInfo" =>  array(self::BELONGS_TO, 'User','invite_userid'),
            "borrowTenderInfo" =>  array(self::HAS_MANY, 'BorrowTender','user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'user_id' => '??????ID',
			'ucenter_uid'=>'????????????ID',
			'type_id' => 'Type',
			'order' => 'Order',
			'purview' => 'Purview',
			'username' => '?????????',
			'password' => '??????',
			'paypassword' => '????????????',
			'islock' => '????????????',
			'invite_userid' => '?????????',
			'invite_money' => '???????????????(???)',
			'id5_num' => 'Id5 Num',
			'real_type' => '??????????????????',
			'real_status' => '??????',
			'real_apply_time' => '????????????',
			'real_auth_time' => '[??????]??????',
			'audit_content' => '??????????????????',
			'card_type' => '????????????',
			'card_id' => '????????????',
			'card_pic1' => '???????????????',
			'card_pic2' => '???????????????',
			'nation' => 'Nation',
			'realname' => '????????????',
			'integral' => 'Integral',
			'status' => '????????????',
			'avatar_status' => 'Avatar Status',
			'email_status' => 'Email Status',
			'phone_status' => 'Phone Status',
			'video_status' => 'Video Status',
			'scene_status' => 'Scene Status',
			'email' => '????????????',
			'sex' => '??????',
			'litpic' => 'Litpic',
			'tel' => '????????????',
			'phone' => '????????????',
			'qq' => 'QQ',
			'wangwang' => 'MSN',
			'question' => 'Question',
			'answer' => 'Answer',
			'birthday' => '??????',
			'province' => 'Province',
			'city' => 'City',
			'area' => '?????????',
            'email_status'=>'??????????????????',
            'phone_status'=>'??????????????????',
            'real_status'=>'??????????????????',
			'address' => '????????????',
			'postcode' => '??????',
			'remind' => 'Remind',
			'privacy' => 'Privacy',
			'logintime' => 'Logintime',
			'addtime' => '????????????',
			'addip' => '??????IP',
			'uptime' => 'Uptime',
			'upip' => 'Upip',
			'lasttime' => 'Lasttime',
			'lastip' => 'Lastip',
			'others' => '????????????',
			'user_src' => '????????????',
			'invest_times' => '??????????????????',
			'debt_times'=>'??????????????????',
			'tuiguang_keywords' => '???????????????',
			'salesman_userid' => 'Salesman Userid',
			'openid' => 'Openid',
			'access_token' => 'Access Token',
			'open_type' => 'Open Type',
			'authorize_cash' => 'Authorize Cash',
			'phone_audit_content' => 'Phone Audit Content',
			'reg_device' => 'Reg Device',
			'phone_vcode' => 'Phone Vcode',
			'qn_score' => 'Qn Score',
			'isinvested' => '????????????',
			'reg_device' => '????????????',
			'uprealname' => '????????????',
            'upusername' => '???????????????',
            'isvalidpromotion'=>'????????????',
            'high_edu'=>'????????????',
            'school'=>'????????????',
            'merriage_status'=>'????????????',
            'business_type'=>'????????????',
	        'business_scale'=>'????????????',
	        'user_position'=>'??????',
	        'month_income'=>'?????????',
	        'safecard_status'=>'?????????????????????',
	        'bbs_nick' => '????????????',
            'isrecharge'=>'????????????',
            'user_grade_code'=> 'VIP??????',
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

        //$order = 'user_id DESC';
        //if(!empty($this->username)){
        //    $criteria->select = "* ,case when username like '%".$this->username."%' then (length(username)-length('".$this->username."')) end as rn ";
        //    $order      = ' rn ';
        //}
        $criteria->compare('username',$this->username);
		$criteria->compare('user_id',$this->user_id);
        $criteria->compare('ucenter_uid',$this->ucenter_uid);
        //???????????????
        if(!empty($this->norecharge)){
            $criteria->addNotInCondition('user_id',$this->norecharge);
        }
        //$criteria->compare('isrecharge',$this->isrecharge);
		$criteria->compare('type_id',$this->type_id);
		$criteria->compare('order',$this->order);
		$criteria->compare('purview',$this->purview);
		$criteria->compare('password',$this->password);
		$criteria->compare('paypassword',$this->paypassword);
        $criteria->compare('safecard_status',$this->safecard_status);
		$criteria->compare('islock',$this->islock);
        if($this->openid === 1 && $this->invite_userid == '')
             $criteria->addCondition("invite_userid != '' ");
        else
            $criteria->compare('invite_userid',$this->invite_userid);
		$criteria->compare('invite_money',$this->invite_money);
		$criteria->compare('id5_num',$this->id5_num);
		$criteria->compare('real_type',$this->real_type);
		$criteria->compare('real_status',$this->real_status);
		 if(!empty($this->real_apply_time))
             $criteria->addBetweenCondition('real_apply_time',strtotime($this->real_apply_time),(strtotime($this->real_apply_time)+86399));
        else
            $criteria->compare('real_apply_time',$this->real_apply_time);
        if(!empty($this->real_auth_time))
             $criteria->addBetweenCondition('real_auth_time',strtotime($this->real_auth_time),(strtotime($this->real_auth_time)+86399));
        else
            $criteria->compare('real_auth_time',$this->real_auth_time);
		$criteria->compare('audit_content',$this->audit_content);
		$criteria->compare('card_type',$this->card_type);
		$criteria->compare('card_id',$this->card_id);
		$criteria->compare('card_pic1',$this->card_pic1);
		$criteria->compare('card_pic2',$this->card_pic2);
		$criteria->compare('nation',$this->nation);
		$criteria->compare('realname',$this->realname);
		$criteria->compare('integral',$this->integral);
		$criteria->compare('status',$this->status);
		$criteria->compare('avatar_status',$this->avatar_status);
		$criteria->compare('email_status',$this->email_status);
		// $criteria->compare('phone_status',$this->phone_status);
        // 0=>'?????????',1=>'?????????',2=>'?????????',3=>'???????????????'
        if(isset($this->phone_status) && $this->phone_status != ''){
            switch($this->phone_status){
                case 0 : 
                   // $criteria->addCondition("phone = ''");
                    $criteria->addInCondition(phone_status,array(0,2,3));
                    break;
                case 1:
//                    $criteria->compare('phone_status',$this->phone_status);
                    $criteria->addCondition("phone_status = :phone_status AND phone != ''");
                    $criteria->params[':phone_status'] = 1;
                    break;
                case 2: 
                    $criteria->addBetweenCondition('phone_status',99999, 1000000); 
                   // $criteria->addCondition("phone != ''");
                    break;
            }              
        } 
		$criteria->compare('video_status',$this->video_status);
		$criteria->compare('scene_status',$this->scene_status);
		$criteria->compare('email',$this->email);
		$criteria->compare('sex',$this->sex);
		$criteria->compare('litpic',$this->litpic);
		$criteria->compare('tel',$this->tel);
		$criteria->compare('phone',$this->phone);
		$criteria->compare('qq',$this->qq);
		$criteria->compare('wangwang',$this->wangwang);
		$criteria->compare('question',$this->question);
		$criteria->compare('reg_device',$this->reg_device);
		$criteria->compare('answer',$this->answer);
        if(!empty($this->birthday) && ($this->birthday)==2){
            $criteria->condition="DATE_FORMAT(from_unixtime(birthday),'%m-%d') >= DATE_FORMAT(now(),'%m-%d')  
         and DATE_FORMAT(from_unixtime(birthday),'%m-%d') <= DATE_FORMAT(date_add(now(),  interval 7 day),'%m-%d')";
        }else{
            $this->birthday = NULL;
            $criteria->compare('birthday',$this->birthday);
        }
		$criteria->compare('province',$this->province);
		$criteria->compare('city',$this->city);
		$criteria->compare('area',$this->area);
		$criteria->compare('address',$this->address);
		$criteria->compare('postcode',$this->postcode);
		$criteria->compare('remind',$this->remind);
		$criteria->compare('privacy',$this->privacy);
		$criteria->compare('logintime',$this->logintime);
		
        //????????????????????????????????????
        if(!empty($this->addtime_start)){
            $criteria->addCondition("addtime >= " . strtotime($this->addtime_start));
            
        }
        //????????????????????????????????????
        if(!empty($this->addtime_end)){
            $criteria->addCondition("addtime <= " . (strtotime($this->addtime_end) + 86399));
        }
        //?????????????????????????????????
        if(!empty($this->addtime)){
            $criteria->addCondition("addtime >= " . strtotime($this->addtime) . ' and addtime <= ' . (strtotime($this->addtime) + 86399));
        }
		$criteria->compare('addip',$this->addip);
		$criteria->compare('uptime',$this->uptime);
		$criteria->compare('upip',$this->upip);
		$criteria->compare('lasttime',$this->lasttime);
		$criteria->compare('lastip',$this->lastip);
		$criteria->compare('others',$this->others);
		$criteria->compare('user_src',$this->user_src);
		$criteria->compare('invest_times',$this->invest_times);
		$criteria->compare('tuiguang_keywords',$this->tuiguang_keywords);
		$criteria->compare('salesman_userid',$this->salesman_userid);
		//$criteria->compare('openid',$this->openid);
		$criteria->compare('access_token',$this->access_token);
		$criteria->compare('open_type',$this->open_type);
		$criteria->compare('authorize_cash',$this->authorize_cash);
		$criteria->compare('phone_audit_content',$this->phone_audit_content);
		$criteria->compare('phone_vcode',$this->phone_vcode);
		$criteria->compare('qn_score',$this->qn_score);
		$criteria->compare('isinvested',$this->isinvested);
        $criteria->compare('isvalidpromotion',$this->isvalidpromotion);
        
        return new CActiveDataProvider($this, array(
            'sort'=>array(
                'defaultOrder'=>'user_id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}

    public function searchForRealType()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria=new CDbCriteria;

        $order = 'real_apply_time DESC';
        if(!empty($this->username)){
            $criteria->select = "* ,case when username like '%".$this->username."%' then (length(username)-length('".$this->username."')) end as rn ";
            $order      = ' rn ';
        }
        $criteria->compare('username',$this->username);
        $criteria->compare('user_id',$this->user_id);
        $criteria->compare('ucenter_uid',$this->ucenter_uid);
        $criteria->compare('type_id',$this->type_id);
        $criteria->compare('order',$this->order);
        $criteria->compare('purview',$this->purview);
        $criteria->compare('password',$this->password);
        $criteria->compare('paypassword',$this->paypassword);
        $criteria->compare('islock',$this->islock);
        if(!empty($this->openid))
             $criteria->addCondition("invite_userid!=''");
        else
            $criteria->compare('invite_userid',$this->invite_userid);
        $criteria->compare('invite_money',$this->invite_money);
        $criteria->compare('id5_num',$this->id5_num);
        $criteria->compare('real_type',$this->real_type);
        $criteria->compare('real_status',$this->real_status);
         if(!empty($this->real_apply_time))
             $criteria->addBetweenCondition('real_apply_time',strtotime($this->real_apply_time),(strtotime($this->real_apply_time)+86399));
        else
            $criteria->compare('real_apply_time',$this->real_apply_time);
        if(!empty($this->real_auth_time))
             $criteria->addBetweenCondition('real_auth_time',strtotime($this->real_auth_time),(strtotime($this->real_auth_time)+86399));
        else
            $criteria->compare('real_auth_time',$this->real_auth_time);
        $criteria->compare('audit_content',$this->audit_content);
        $criteria->compare('card_type',$this->card_type);
        $criteria->compare('card_id',$this->card_id);
        $criteria->compare('card_pic1',$this->card_pic1);
        $criteria->compare('card_pic2',$this->card_pic2);
        $criteria->compare('nation',$this->nation);
        $criteria->compare('realname',$this->realname);
        $criteria->compare('integral',$this->integral);
        $criteria->compare('status',$this->status);
		$criteria->compare('reg_device',$this->reg_device);
		$criteria->compare('reg_device',$this->reg_device);
        $criteria->compare('avatar_status',$this->avatar_status);
        $criteria->compare('email_status',$this->email_status);
        $criteria->compare('video_status',$this->video_status);
        $criteria->compare('scene_status',$this->scene_status);
        $criteria->compare('email',$this->email);
        $criteria->compare('sex',$this->sex);
        $criteria->compare('litpic',$this->litpic);
        $criteria->compare('tel',$this->tel);
        $criteria->compare('phone',$this->phone);
       

        $criteria->compare('qq',$this->qq);
        $criteria->compare('wangwang',$this->wangwang);
        $criteria->compare('question',$this->question);
        $criteria->compare('answer',$this->answer);
		$criteria->compare('school',$this->school);
		$criteria->compare('high_edu',$this->high_edu);
		$criteria->compare('business_type',$this->business_type);
		$criteria->compare('business_scale',$this->business_scale);
		$criteria->compare('user_position',$this->user_position);
		$criteria->compare('month_income',$this->month_income);
        if(!empty($this->birthday))
             $criteria->addBetweenCondition('birthday',strtotime($this->birthday),(strtotime($this->birthday)+86400));
        else
            $criteria->compare('birthday',$this->birthday);
        $criteria->compare('province',$this->province);
        $criteria->compare('city',$this->city);
        $criteria->compare('area',$this->area);
        $criteria->compare('address',$this->address);
        $criteria->compare('postcode',$this->postcode);
        $criteria->compare('remind',$this->remind);
        $criteria->compare('privacy',$this->privacy);
        $criteria->compare('logintime',$this->logintime);
        if(!empty($this->addtime))
             $criteria->addBetweenCondition('addtime',strtotime($this->addtime),(strtotime($this->addtime)+86400));
        else
            $criteria->compare('addtime',$this->addtime);
        $criteria->compare('addip',$this->addip);
        $criteria->compare('uptime',$this->uptime);
        $criteria->compare('upip',$this->upip);
        $criteria->compare('lasttime',$this->lasttime);
        $criteria->compare('lastip',$this->lastip);
        $criteria->compare('others',$this->others);
        $criteria->compare('user_src',$this->user_src);
        $criteria->compare('tuiguang_keywords',$this->tuiguang_keywords);
        $criteria->compare('salesman_userid',$this->salesman_userid);
        //$criteria->compare('openid',$this->openid);
        $criteria->compare('access_token',$this->access_token);
        $criteria->compare('open_type',$this->open_type);
        $criteria->compare('authorize_cash',$this->authorize_cash);
        $criteria->compare('phone_audit_content',$this->phone_audit_content);
        $criteria->compare('phone_vcode',$this->phone_vcode);
        $criteria->compare('qn_score',$this->qn_score);
        $criteria->compare('isinvested',$this->isinvested);
        $criteria->compare('isvalidpromotion',$this->isvalidpromotion);
        return new CActiveDataProvider($this, array(
            'sort'=>array(
                'defaultOrder'=>$order, 
            ),
            'criteria'=>$criteria,
        ));
    }
    public function behaviors(){
        return array(
            'ItzLogBehavior'=>array(
              'class'               => 'ItzLogBehavior',
              'tableName'           => 'itz_log',   //????????????, ???????????????????????????'log',
              'autoCreateLogTable'  => true,         //???????????????????????????,????????????,?????????????????????????????????????????????,???????????????, ?????????true
              'attributeConvertion' => array(        //????????????????????????????????????????????????,??????????????????, ????????????????????????
               )
           )
        );
    }

    public function isValidatedPhone($phone)
    {
        return $this->count('phone_status = 1 and phone = :phone', [':phone' => $phone]);
    }

    public function getUserId(){
        return $this->user_id;
    }
    public function getBankList(){
        //return '1212';
         $arr = AccountBank::model()->findAllByAttributes(array('user_id' =>$this->user_id,'bank_status' => 1));
         $str = '';
         if($arr){
            foreach ($arr as $v){ 
                $arr1 =ItzBank::model()->findByAttributes(array('withdraw_link_id' => $v->bank));
                $str .= "<div style='width:580px;height:25px;'>
                    <label style='width : 120px; margin-left :30px;float:left;'>".$arr1['bank_name']."</label>
                    <a style='color:red;margin-right:100px;float:right;' id =\"aDiv{$v->id}\" href='javascript::void(0)' onclick =delbank(\"{$v->id}\")>[???????????????]</a>
                    <label style='width : 100px; margin-right :100px;float:right;'> {$v->account}</label>
                    </div>
                     ";
              }    
          }

        return $str;
    }
}
