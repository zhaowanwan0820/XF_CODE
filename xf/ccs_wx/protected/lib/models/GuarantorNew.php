<?php

/**
 * This is the model class for table "dw_guarantor_new".
 *
 * The followings are the available columns in table 'dw_guarantor_new':
 * @property integer $gid
 * @property string $abbr
 * @property integer $type
 * @property integer $status
 * @property string $name
 * @property string $sname
 * @property string $username
 * @property string $password
 * @property string $paypassword
 * @property string $contactperson
 * @property string $card_id
 * @property string $phone
 * @property string $email
 * @property integer $city
 * @property integer $province
 * @property string $regcapital
 * @property string $credited
 * @property string $guaranteeing
 * @property string $guaranteed
 * @property string $compensated
 * @property integer $interestrepaydays
 * @property integer $capitalrepaydays
 * @property string $logo
 * @property string $desc
 * @property string $summary
 * @property integer $weight
 * @property string $license1
 * @property string $license2
 * @property string $license3
 * @property string $license4
 * @property string $agreement1
 * @property string $agreement2
 * @property string $stamp
 * @property string $crt
 * @property string $addtime
 * @property string $updatetime
 * @property string $addip
 */
class GuarantorNew extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return GuarantorNew the static model class
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
		return 'dw_guarantor_new';
	}
	
     //合作状态
     protected $_cooperation_status=array(1=>'合作中','终止合作','暂停合作');
    
     public function getCooperationStatus(){
         return $this->_cooperation_status;
     }
     public function StrCooperationStatus(){
         if(isset($this->_cooperation_status[$this->cooperation_status]))
            return $this->_cooperation_status[$this->cooperation_status];
         else 
            return '-';
     }
    //审核状态
     protected $_status=array(1=>'审核通过','待审核','审核不通过');
    
     public function getStatus(){
         return $this->_status;
     }
     public function StrStatus(){
         if(isset($this->_status[$this->status]))
            return $this->_status[$this->status];
         else 
            return '未知';
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_status))?$this->_status[$key]:"";
     } 
     
    //分类
     public function getType(){
         $types = array('请选择','融资性担保公司','融资租赁公司','商业保理公司','保险公司','拍卖公司',6=>'小贷公司',7=>'典当公司',8=>'资管公司',9=>'影视公司',10=>'资管回购公司',11=>'零钱计划保障机构');
         return $types;
     }
     public function StrType(){
         $types = $this->getType();
         if(isset($types[$this->type]))
            return $types[$this->type];
     }
     public function getStrType($key){
         $res  = $this->getType();
         return (array_key_exists($key, $res?$res[$key]:""));
     }
     
    public function getProvince(){
         $areaMosel = new Area;
         $provinces = $areaMosel->findAllByAttributes(array('pid'=>0));
         foreach ($provinces as $areaMosel) {
             $res = $areaMosel->attributes;
             $provincesName[$res['id']] = $res['name'];
         }
         return $provincesName;
     }
     public function StrProvince(){
         $provinces = $this->getProvince();
         if(isset($provinces[$this->province]))
            return $provinces[$this->province];
     }
     public function getStrProvince($key){
         $res  = $this->getProvince();
         return (array_key_exists($key, $res?$res[$key]:""));
     }

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('credited,contactperson,card_id,phone,email,business_entity_card_id,business_entity,license1,agreement1,agreement2,type,name, sname,city, province, regcapital,summary, desc, weight,logotext,logo', 'required','message'=>Yii::t('luben','{attribute}不能为空')),
			array('type, status, city, province, interestrepaydays, capitalrepaydays, weight', 'numerical', 'integerOnly'=>true),
			array('regcapital, credited', 'numerical', 'integerOnly'=>false,'message'=>Yii::t('luben','{attribute}必须为整数'),'min'=>0,'tooSmall'=>Yii::t('luben','{attribute}必须大于等于0')),
			array('phone', 'numerical', 'integerOnly'=>false,'message'=>Yii::t('luben','{attribute}必须为数字'),'min'=>0,'tooSmall'=>Yii::t('luben','{attribute}不能为负数')),
			array('sname, phone', 'length', 'max'=>20),
			array('username, contactperson, card_id, addtime, updatetime, addip', 'length', 'max'=>50),
			array('password, paypassword', 'length', 'max'=>32),
			array('email', 'length', 'max'=>100),
			array('regcapital, abbr,credited', 'length', 'max'=>11),
			array('organization_code,tax_registration_certificate,item_class,cooperation_status,company_phone,license2,license3,license4,logo_slogan_name,business_entity_card_id,entity_crt,entity_stamp,business_entity,address,web_url,tel,enterprise_character,logo_full_name,license1_alt,license2_alt,license3_alt,license4_alt,agreement1_alt,agreement2_alt,desc, summary,business_license,startcorptime,abbr,bankbranch,bankcardid,foundtime,logotext,stamp, crt', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('organization_code,tax_registration_certificate,item_class,company_phone,cooperation_status,logo_slogan_name,business_entity_card_id,entity_crt,entity_stamp,business_entity,address,web_url,tel,enterprise_character,logo_full_name,license1_alt,license2_alt,license3_alt,license4_alt,agreement1_alt,agreement2_alt,gid,business_license,startcorptime,foundtime,abbr,bankbranch,bankcardid, type, status, name, sname, username, password, paypassword, contactperson, card_id, phone, email, city, province, regcapital, credited,interestrepaydays, capitalrepaydays, logo, desc, summary, weight, license1, license2, license3, license4, agreement1, agreement2, stamp, crt, addtime, updatetime, addip', 'safe', 'on'=>'search'),
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
			'gid' => 'ID',
			'abbr' => '字母缩写',
			'type' => '合作保障机构类别',
			'status' => 'Status',
			'name' => '名称',
			'sname' => '中文缩写',
			'username' => 'Username',
			'password' => 'Password',
			'paypassword' => 'Paypassword',
			'contactperson' => '联系人',
			'card_id' => '身份证号',
			'phone' => '手机号',
			//'bankbranch'=>'共管账户开户行',
			//'bankcardid'=>'共管账户账号',
			'email' => '邮件地址',
			'city' => '所在市',
			'province' => '所在省份',
			'regcapital' => '注册资本(万元)',
			'credited' => '授信额度(万元)',
			'interestrepaydays' => '利息赔付时间(天)',
			'capitalrepaydays' => '本金赔付时间(天)',
			'logo' => '机构LOGO',
			'logotext' => '文字LOGO',
			'desc' => '描述内容',
			'summary' => '机构简介',
			'weight' => '排序',
			'license1' => '金融资质牌照1',
			'license2' => '金融资质牌照2',
			'license3' => '其他类型照片1',
			'license4' => '其他类型照片2',
			'agreement1' => '战略合作协议首页',
			'agreement2' => '战略合作协议尾页',
			'stamp' => '合同章',
			'crt' => '合同证书',
			'addtime' => '添加时间',
			'updatetime' => '修改时间',
			'addip' => '添加IP',
			'bankbranch'=>'共管账户所属银行',
			'bankcardid'=>'共管账户卡号',
			'foundtime'=>'融资租赁公司成立时间',
			'startcorptime'=>'开始合作时间',
			'status'=>'审核状态',
			'address'=>'公司地址',
			'web_url'=>'公司网址',
			'tel'=>'联系电话',
			'business_license'=>'营业执照号码',
			'business_entity'=>'企业法人(或授权委托人)',
			'entity_stamp' => '企业法人合同章',
            'entity_crt' => '企业法人合同证书',
            'business_entity_card_id' => '法人身份证号',
            'cooperation_status'=>'合作状态',
            'company_phone'=>'企业电话',
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

		$criteria->compare('gid',$this->gid);
		$criteria->compare('abbr',$this->abbr,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('status',$this->status);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('sname',$this->sname,true);
		$criteria->compare('username',$this->username,true);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('paypassword',$this->paypassword,true);
		$criteria->compare('contactperson',$this->contactperson,true);
		$criteria->compare('card_id',$this->card_id,true);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('city',$this->city);
		$criteria->compare('province',$this->province);
		$criteria->compare('regcapital',$this->regcapital,true);
		$criteria->compare('credited',$this->credited,true);
		$criteria->compare('interestrepaydays',$this->interestrepaydays);
		$criteria->compare('capitalrepaydays',$this->capitalrepaydays);
		$criteria->compare('logo',$this->logo,true);
		$criteria->compare('logotext',$this->logotext,true);
		$criteria->compare('desc',$this->desc,true);
		$criteria->compare('summary',$this->summary,true);
		$criteria->compare('weight',$this->weight);
		$criteria->compare('license1',$this->license1,true);
		$criteria->compare('license2',$this->license2,true);
		$criteria->compare('license3',$this->license3,true);
		$criteria->compare('license4',$this->license4,true);
		$criteria->compare('agreement1',$this->agreement1,true);
		$criteria->compare('agreement2',$this->agreement2,true);
		$criteria->compare('stamp',$this->stamp,true);
		$criteria->compare('crt',$this->crt,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('updatetime',$this->updatetime,true);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('business_entity',$this->business_entity,true);
        $criteria->compare('cooperation_status',$this->cooperation_status);

		return new CActiveDataProvider($this, array(
		  'sort'=>array(
                'defaultOrder'=>'weight DESC,gid DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}
