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
class CUser extends DwActiveRecord
{
     public $dbname = 'dwdb';
     public $uprealname;//用户真实姓名
     public $upusername;//用户名
     public $type;//企业类型
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
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'dw_user';
	}

    
    
     public function getType(){
         $_type= Yii::app()->params["company_type"];
         return $_type;
     }
     public function StrType(){
         $_type= $this->getType();
         return $_type[$model->companyInfo->type];
     }
     public function getStrType($key){
         $_type= $this->getType();
         return (array_key_exists($key, $_type))?$_type[$key]:"未知";
     } 
    //企业状态
    protected $_company_status=array(1=>'审核通过','待审核','审核不通过');
    
     public function getCompanyStatus(){
         return $this->_company_status;
     }
     public function StrCompanyStatus(){
         return $this->_company_status[$this->company_status];
     }
     public function getStrCompanyStatus($key){
         return (array_key_exists($key, $this->_company_status))?$this->_company_status[$key]:"未知";
     } 
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type_id, order, islock, invite_userid, invite_money, id5_num, real_type, real_apply_time, real_auth_time, status, avatar_status, video_status, scene_status, logintime, authorize_cash, qn_score, isinvested, addtime', 'numerical', 'integerOnly'=>true),
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
			array('company_status,uprealname,upusername,user_id, type_id, order, purview, username, password, paypassword, islock, invite_userid, invite_money, id5_num, real_type, real_status, real_apply_time, real_auth_time, audit_content, card_type, card_id, card_pic1, card_pic2, nation, realname, integral, status, avatar_status, email_status, phone_status, video_status, scene_status, email, sex, litpic, tel, phone, qq, wangwang, question, answer, birthday, province, city, area, address, postcode, remind, privacy, logintime, addtime, addip, uptime, upip, lasttime, lastip, others, user_src, tuiguang_keywords, salesman_userid, openid, access_token, open_type, authorize_cash, phone_audit_content, reg_device, phone_vcode, qn_score, isinvested', 'safe'),
			array('type,company_status,uprealname,upusername,user_id, type_id, order, purview, username, password, paypassword, islock, invite_userid, invite_money, id5_num, real_type, real_status, real_apply_time, real_auth_time, audit_content, card_type, card_id, card_pic1, card_pic2, nation, realname, integral, status, avatar_status, email_status, phone_status, video_status, scene_status, email, sex, litpic, tel, phone, qq, wangwang, question, answer, birthday, province, city, area, address, postcode, remind, privacy, logintime, addtime, addip, uptime, upip, lasttime, lastip, others, user_src, tuiguang_keywords, salesman_userid, openid, access_token, open_type, authorize_cash, phone_audit_content, reg_device, phone_vcode, qn_score, isinvested', 'safe', 'on'=>'search'),
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
		    'companyInfo'=>array(self::HAS_ONE,'Userinfo','','on'=>'t.user_id=companyInfo.user_id'),
            "borrowTenderInfo" =>  array(self::HAS_MANY, 'BorrowTender','user_id')
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'user_id' => '企业ID',
			'type_id' => 'Type',
			'order' => 'Order',
			'purview' => 'Purview',
			'username' => '企业名称',
			'password' => '密码',
			'paypassword' => '支付密码',
			'islock' => 'Islock',
			'invite_userid' => '推荐人',
			'invite_money' => '优惠券金额(元)',
			'id5_num' => 'Id5 Num',
			'real_type' => 'Real Type',
			'real_status' => '状态',
			'real_apply_time' => '申请时间',
			'real_auth_time' => '审核时间',
			'audit_content' => '实名认证备注',
			'card_type' => '正反面',
			'card_id' => '身份证号',
			'card_pic1' => '照片上传',
			'card_pic2' => 'Card Pic2',
			'nation' => 'Nation',
			'realname' => '联系人',
			'integral' => 'Integral',
			'status' => 'Status',
			'avatar_status' => 'Avatar Status',
			'email_status' => 'Email Status',
			'phone_status' => 'Phone Status',
			'video_status' => 'Video Status',
			'scene_status' => 'Scene Status',
			'email' => '联系邮箱',
			'sex' => '性别',
			'litpic' => 'Litpic',
			'tel' => '家庭电话',
			'phone' => '联系手机',
			'qq' => 'QQ',
			'wangwang' => 'Wangwang',
			'question' => 'Question',
			'answer' => 'Answer',
			'birthday' => '生日',
			'company_status' => '企业状态',
			'province' => 'Province',
			'city' => 'City',
			'area' => '所在地',
			'address' => '联系地址',
			'postcode' => '邮编',
			'remind' => 'Remind',
			'privacy' => 'Privacy',
			'logintime' => 'Logintime',
			'addtime' => '添加时间',
			'addip' => '注册IP',
			'uptime' => 'Uptime',
			'upip' => 'Upip',
			'lasttime' => 'Lasttime',
			'lastip' => 'Lastip',
			'others' => 'Others',
			'user_src' => '用户来源',
			'tuiguang_keywords' => '凤巢关键词',
			'salesman_userid' => 'Salesman Userid',
			'openid' => 'Openid',
			'access_token' => 'Access Token',
			'open_type' => 'Open Type',
			'authorize_cash' => 'Authorize Cash',
			'phone_audit_content' => 'Phone Audit Content',
			'reg_device' => 'Reg Device',
			'phone_vcode' => 'Phone Vcode',
			'qn_score' => 'Qn Score',
			'isinvested' => '是否投资',
			'uprealname' => '上线姓名',
            'upusername' => '上线用户名',
            'isvalidpromotion'=>'是否生效',
            'type'=>'企业类型',
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
        $criteria->with = 'companyInfo';
		$criteria->compare('t.user_id',$this->user_id);
		$criteria->compare('t.type_id',$this->type_id);
		$criteria->compare('t.order',$this->order);
		$criteria->compare('t.purview',$this->purview);
		$criteria->compare('t.username',$this->username,TRUE);
		$criteria->compare('t.password',$this->password);
		$criteria->compare('t.paypassword',$this->paypassword);
		$criteria->compare('t.islock',$this->islock);
        $criteria->compare('companyInfo.type',$this->type);
        $criteria->compare('t.invite_userid',$this->invite_userid);
		$criteria->compare('t.invite_money',$this->invite_money);
		$criteria->compare('t.id5_num',$this->id5_num);
		$criteria->compare('t.real_type',$this->real_type);
		$criteria->compare('t.real_status',$this->real_status);
		$criteria->compare('t.real_apply_time',$this->real_apply_time);
        $criteria->compare('t.real_auth_time',$this->real_auth_time);
		$criteria->compare('t.audit_content',$this->audit_content);
		$criteria->compare('t.card_type',$this->card_type);
		$criteria->compare('t.card_id',$this->card_id);
		$criteria->compare('t.card_pic1',$this->card_pic1);
		$criteria->compare('t.card_pic2',$this->card_pic2);
		$criteria->compare('t.nation',$this->nation);
		$criteria->compare('t.realname',$this->realname);
		$criteria->compare('t.integral',$this->integral);
		$criteria->compare('t.status',$this->status);
		$criteria->compare('t.avatar_status',$this->avatar_status);
		$criteria->compare('t.email_status',$this->email_status);
		$criteria->compare('t.phone_status',$this->phone_status);
		$criteria->compare('t.video_status',$this->video_status);
		$criteria->compare('t.scene_status',$this->scene_status);
		$criteria->compare('t.email',$this->email);
		$criteria->compare('t.sex',$this->sex);
		$criteria->compare('t.litpic',$this->litpic);
		$criteria->compare('t.tel',$this->tel);
		$criteria->compare('t.phone',$this->phone);
		$criteria->compare('t.qq',$this->qq);
		$criteria->compare('t.wangwang',$this->wangwang);
		$criteria->compare('t.question',$this->question);
		$criteria->compare('t.answer',$this->answer);
		$criteria->compare('t.birthday',$this->birthday);
        $criteria->compare('t.company_status',$this->company_status);
		$criteria->compare('t.province',$this->province);
		$criteria->compare('t.city',$this->city);
		$criteria->compare('t.area',$this->area);
		$criteria->compare('t.address',$this->address);
		$criteria->compare('t.postcode',$this->postcode);
		$criteria->compare('t.remind',$this->remind);
		$criteria->compare('t.privacy',$this->privacy);
		$criteria->compare('t.logintime',$this->logintime);
		if(!empty($this->addtime))
             $criteria->addBetweenCondition('t.addtime',strtotime($this->addtime),(strtotime($this->addtime)+86400));
        else
            $criteria->compare('t.addtime',$this->addtime);
		$criteria->compare('t.addip',$this->addip);
		$criteria->compare('t.uptime',$this->uptime);
		$criteria->compare('t.upip',$this->upip);
		$criteria->compare('t.lasttime',$this->lasttime);
		$criteria->compare('t.lastip',$this->lastip);
		$criteria->compare('t.others',$this->others);
		$criteria->compare('t.user_src',$this->user_src);
		$criteria->compare('t.tuiguang_keywords',$this->tuiguang_keywords);
		$criteria->compare('t.salesman_userid',$this->salesman_userid);
		$criteria->compare('t.access_token',$this->access_token);
		$criteria->compare('t.open_type',$this->open_type);
		$criteria->compare('t.authorize_cash',$this->authorize_cash);
		$criteria->compare('t.phone_audit_content',$this->phone_audit_content);
		$criteria->compare('t.reg_device',$this->reg_device);
		$criteria->compare('t.phone_vcode',$this->phone_vcode);
		$criteria->compare('t.qn_score',$this->qn_score);
		$criteria->compare('t.isinvested',$this->isinvested);
        $criteria->compare('t.isvalidpromotion',$this->isvalidpromotion);
        return new CActiveDataProvider($this, array(
            'sort'=>array(
                'defaultOrder'=>'t.user_id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}

    
    public function behaviors(){
        return array(
            'ItzLogBehavior'=>array(
              'class'               => 'ItzLogBehavior',
              'tableName'           => 'itz_log',   //日志表名, 如果不指定则默认为'log',
              'autoCreateLogTable'  => true,         //是否自动创建日志表,如果为真,第一次保存日志是如果没有这个表,则自动新建, 默认为true
              'attributeConvertion' => array(        //表中的某些字段需要记录成可读形式,必须进行转换, 支持以下两种格式
               )
           )
        );
    }
}
