<?php

/**
 * This is the model class for table "dw_userinfo".
 *
 * The followings are the available columns in table 'dw_userinfo':
 * @property string $id
 * @property integer $site_id
 * @property integer $user_id
 * @property string $name
 * @property integer $status
 * @property integer $order
 * @property integer $hits
 * @property string $litpic
 * @property string $flag
 * @property string $source
 * @property string $publish
 * @property string $marry
 * @property string $child
 * @property string $education
 * @property string $income
 * @property string $shebao
 * @property string $shebaoid
 * @property string $housing
 * @property string $car
 * @property string $late
 * @property string $house_address
 * @property string $house_area
 * @property string $house_year
 * @property string $house_status
 * @property string $house_holder1
 * @property string $house_holder2
 * @property string $house_right1
 * @property string $house_right2
 * @property string $house_loanyear
 * @property string $house_loanprice
 * @property string $house_balance
 * @property string $house_bank
 * @property string $company_name
 * @property integer $company_credit
 * @property string $company_type
 * @property string $company_industry
 * @property string $company_office
 * @property string $company_jibie
 * @property string $company_worktime1
 * @property string $company_worktime2
 * @property string $company_workyear
 * @property string $company_tel
 * @property string $company_address
 * @property string $company_weburl
 * @property string $company_reamrk
 * @property string $company_business_scope
 * @property string $company_describe
 * @property string $company_property
 * @property string $company_date
 * @property string $company_regestered_capital
 * @property string $company_regestered_address
 * @property string $private_type
 * @property string $private_date
 * @property string $private_place
 * @property string $private_rent
 * @property string $private_term
 * @property string $private_taxid
 * @property string $private_commerceid
 * @property string $private_income
 * @property string $private_employee
 * @property string $finance_repayment
 * @property string $finance_property
 * @property string $finance_amount
 * @property string $finance_car
 * @property string $finance_caramount
 * @property string $finance_creditcard
 * @property string $mate_name
 * @property string $mate_salary
 * @property string $mate_phone
 * @property string $mate_tel
 * @property string $mate_type
 * @property string $mate_office
 * @property string $mate_address
 * @property string $mate_income
 * @property string $education_record
 * @property string $education_school
 * @property string $education_study
 * @property string $education_time1
 * @property string $education_time2
 * @property string $tel
 * @property string $phone
 * @property string $post
 * @property string $address
 * @property string $province
 * @property string $city
 * @property string $area
 * @property string $linkman1
 * @property string $relation1
 * @property integer $stock_right
 * @property string $tel1
 * @property string $phone1
 * @property string $linkman2
 * @property string $relation2
 * @property string $tel2
 * @property string $phone2
 * @property string $linkman3
 * @property string $relation3
 * @property string $tel3
 * @property string $phone3
 * @property string $msn
 * @property string $qq
 * @property string $wangwang
 * @property string $ability
 * @property string $interest
 * @property string $others
 * @property string $experience
 * @property string $addtime
 * @property string $addip
 * @property string $updatetime
 * @property string $updateip
 */
class Userinfo extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Userinfo the static model class
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
		return 'dw_userinfo';
	}

     //企业性质
     public function getTypeName($type_id,$id){
         $usersrcs = Linkage::model()->findAllByAttributes(array('type_id'=>$type_id));
         $types = array('请选择');
         foreach($usersrcs as $vals){
             $types[$vals->id] = $vals->name;
         }
         return $types[$id];
     }
     //企业性质
     public function getCompanyType($type_id){
         $usersrcs = Linkage::model()->findAllByAttributes(array('type_id'=>$type_id));
         $types = array('请选择');
         foreach($usersrcs as $vals){
             $types[$vals->id] = $vals->name;
         }
         return $types;
     }
     //联动名称
     public function getType($type=NULL){
         $company_industry_list = Yii::app()->params['company_industry'];
         return $company_industry_list;
     }
     //联动名称
     public function getSubcategoryType($type_id){
         if($type_id == ''){
             $usersrcs[''] = '请选择';
         }else{
             $usersrcs = Yii::app()->params['company_industry_subcategory'][$type_id];
         }
         return $usersrcs;
     }
     

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
		    array('company_industry_subcategory,company_name,company_credit','required','message'=>Yii::t('luben','{attribute}不能为空')),
			array('stock_right', 'numerical', 'integerOnly'=>false,'message'=>Yii::t('luben','{attribute}必须为数字'),'min'=>0,'tooSmall'=>Yii::t('luben','{attribute}不能小于0'),'max'=>100,'tooBig'=>Yii::t('luben','{attribute}不能大于100')),
			array('private_employee', 'numerical', 'integerOnly'=>false,'message'=>Yii::t('luben','{attribute}必须为数字'),'min'=>1,'tooSmall'=>Yii::t('luben','{attribute}必须大于0')),
            array('company_regestered_capital', 'numerical', 'integerOnly'=>false,'message'=>Yii::t('luben','{attribute}必须为数字')),
            array('linkman1,linkman2', 'match', 'allowEmpty'=>true, 'pattern'=>'/^[\x{4e00}-\x{9fa5}]+$/u','message'=>'只能填写汉字'),
            array('site_id, user_id, status, order, hits, company_credit', 'numerical', 'integerOnly'=>true),
			array('name, litpic', 'length', 'max'=>255),
			array('flag, source, publish, marry, shebaoid, company_tel, company_regestered_capital, private_commerceid, tel, phone, post, address, linkman1, relation1, tel1, phone1, linkman2, relation2, tel2, phone2, linkman3, relation3, tel3, phone3, msn, qq, wangwang, addtime, addip, updatetime, updateip', 'length', 'max'=>50),
			array('child, education, income, shebao, housing, car, late, house_address, house_area, house_year, house_status, house_holder1, house_holder2, house_right1, house_right2, house_loanyear, house_loanprice, house_balance, house_bank, company_type, company_industry, company_office, company_jibie, company_worktime1, company_worktime2, company_workyear, company_date, private_type, private_date, private_place, private_rent, private_term, finance_car, mate_salary, mate_type, mate_office', 'length', 'max'=>10),
			array('company_name, education_school, education_study', 'length', 'max'=>200),
			array('company_address, company_weburl, company_regestered_address, private_income, private_employee, finance_repayment, finance_property, finance_amount, finance_caramount, finance_creditcard, mate_name, mate_phone, mate_tel, mate_income, education_record', 'length', 'max'=>100),
			array('company_reamrk, mate_address, ability, interest, others', 'length', 'max'=>250),
			array('private_taxid', 'length', 'max'=>30),
			array('education_time1, education_time2, province, city, area', 'length', 'max'=>20),
			array('company_business_scope, company_describe, company_property, experience', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
		    array('borrower_desc,company_industry_subcategory,abl_company_logo,abl_company_slogan,abl_company_link,type,stamp,crt,id, site_id, user_id, name, status, order, hits, litpic, flag, source, publish, marry, child, education, income, shebao, shebaoid, housing, car, late, house_address, house_area, house_year, house_status, house_holder1, house_holder2, house_right1, house_right2, house_loanyear, house_loanprice, house_balance, house_bank, company_name, company_credit, company_type, company_industry, company_office, company_jibie, company_worktime1, company_worktime2, company_workyear, company_tel, company_address, company_weburl, company_reamrk, company_business_scope, company_describe, company_property, company_date, company_regestered_capital, company_regestered_address, private_type, private_date, private_place, private_rent, private_term, private_taxid, private_commerceid, private_income, private_employee, finance_repayment, finance_property, finance_amount, finance_car, finance_caramount, finance_creditcard, mate_name, mate_salary, mate_phone, mate_tel, mate_type, mate_office, mate_address, mate_income, education_record, education_school, education_study, education_time1, education_time2, tel, phone, post, address, province, city, area, linkman1, relation1, stock_right, tel1, phone1, linkman2, relation2, tel2, phone2, linkman3, relation3, tel3, phone3, msn, qq, wangwang, ability, interest, others, experience, addtime, addip, updatetime, updateip', 'safe'),
        	array('borrower_desc,company_industry_subcategory,abl_company_logo,abl_company_slogan,abl_company_link,type,stamp,crt,id, site_id, user_id, name, status, order, hits, litpic, flag, source, publish, marry, child, education, income, shebao, shebaoid, housing, car, late, house_address, house_area, house_year, house_status, house_holder1, house_holder2, house_right1, house_right2, house_loanyear, house_loanprice, house_balance, house_bank, company_name, company_credit, company_type, company_industry, company_office, company_jibie, company_worktime1, company_worktime2, company_workyear, company_tel, company_address, company_weburl, company_reamrk, company_business_scope, company_describe, company_property, company_date, company_regestered_capital, company_regestered_address, private_type, private_date, private_place, private_rent, private_term, private_taxid, private_commerceid, private_income, private_employee, finance_repayment, finance_property, finance_amount, finance_car, finance_caramount, finance_creditcard, mate_name, mate_salary, mate_phone, mate_tel, mate_type, mate_office, mate_address, mate_income, education_record, education_school, education_study, education_time1, education_time2, tel, phone, post, address, province, city, area, linkman1, relation1, stock_right, tel1, phone1, linkman2, relation2, tel2, phone2, linkman3, relation3, tel3, phone3, msn, qq, wangwang, ability, interest, others, experience, addtime, addip, updatetime, updateip', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
		   "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'site_id' => 'Site',
			'user_id' => 'User',
			'name' => 'Name',
			'status' => 'Status',
			'order' => 'Order',
			'hits' => 'Hits',
			'litpic' => 'Litpic',
			'flag' => 'Flag',
			'source' => 'Source',
			'publish' => 'Publish',
			'marry' => 'Marry',
			'child' => 'Child',
			'education' => 'Education',
			'income' => 'Income',
			'shebao' => 'Shebao',
			'shebaoid' => 'Shebaoid',
			'housing' => 'Housing',
			'car' => 'Car',
			'late' => 'Late',
			'house_address' => 'House Address',
			'house_area' => 'House Area',
			'house_year' => 'House Year',
			'house_status' => 'House Status',
			'house_holder1' => 'House Holder1',
			'house_holder2' => 'House Holder2',
			'house_right1' => 'House Right1',
			'house_right2' => 'House Right2',
			'house_loanyear' => 'House Loanyear',
			'house_loanprice' => 'House Loanprice',
			'house_balance' => 'House Balance',
			'house_bank' => 'House Bank',
			'company_name' => '企业名称',
			'company_credit' => '信用等级',
			'company_type' => '企业性质',
			'company_industry' => '行业大类',
			'company_office' => '职 位',
			'company_jibie' => '工作级别',
			'company_worktime1' => '服务时间',
			'company_worktime2' => 'Company Worktime2',
			'company_workyear' => '工作年限',
			'company_tel' => '企业电话',
			'company_address' => '企业地址',
			'company_weburl' => '企业网站',
			'company_reamrk' => '其他备注信息',
			'company_business_scope' => '企业营业范围',
			'company_describe' => '企业背景资料基本信息描述(或原债企业介绍)',
			'company_property' => '企业经营状况,资产实力描述',
			'company_date' => '成立日期',
			'company_regestered_capital' => '注册资本',
			'company_regestered_address' => '注册地址',
			'private_type' => '私营企业类型',
			'private_date' => '成立日期',
			'private_place' => '经营场所',
			'private_rent' => '租金(元)',
			'private_term' => '租期(月)',
			'private_taxid' => '税务编号',
			'private_commerceid' => '工商登记号',
			'private_income' => '全年盈利/亏损额',
			'private_employee' => '雇员人数(人)',
			'company_industry_subcategory' => '行业中类',
			'finance_repayment' => 'Finance Repayment',
			'finance_property' => 'Finance Property',
			'finance_amount' => 'Finance Amount',
			'finance_car' => 'Finance Car',
			'finance_caramount' => 'Finance Caramount',
			'finance_creditcard' => 'Finance Creditcard',
			'mate_name' => 'Mate Name',
			'mate_salary' => 'Mate Salary',
			'mate_phone' => 'Mate Phone',
			'mate_tel' => 'Mate Tel',
			'mate_type' => 'Mate Type',
			'mate_office' => 'Mate Office',
			'mate_address' => 'Mate Address',
			'mate_income' => 'Mate Income',
			'education_record' => 'Education Record',
			'education_school' => 'Education School',
			'education_study' => 'Education Study',
			'education_time1' => 'Education Time1',
			'education_time2' => 'Education Time2',
			'tel' => '固定电话',
			'phone' => '手机号码',
			'post' => '联系邮编',
			'address' => '联系地址',
			'province' => '企业所在省',
			'city' => '企业所在省市',
			'area' => '企业所在区',
			'linkman1' => '第二联系人姓名',
			'relation1' => '第二联系人关系',
			'stock_right' => '股权比例(%)',
			'tel1' => '第二联系人电话',
			'phone1' => '第二联系人联系住址',
			'linkman2' => '第三联系人姓名',
			'relation2' => '第三联系人关系',
			'tel2' => '第三联系人联系电话',
			'phone2' => '第三联系人联系住址',
			'linkman3' => 'Linkman3',
			'relation3' => 'Relation3',
			'tel3' => 'Tel3',
			'phone3' => 'Phone3',
			'msn' => 'MSN',
			'qq' => 'QQ',
			'wangwang' => 'Wangwang',
			'ability' => 'Ability',
			'interest' => 'Interest',
			'others' => '内部备注',
			'experience' => 'Experience',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
			'updatetime' => 'Updatetime',
			'updateip' => 'Updateip',
			'stamp' => '合同章路径',
			'crt' => 'CFCA证书路径',
			'type' =>'企业类型',
			'abl_company_logo' => '题图标识',
            'abl_company_slogan' => '企业标语图',
            'abl_company_link' =>'链接地址'
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

		$criteria->compare('id',$this->id,true);
		$criteria->compare('site_id',$this->site_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('status',$this->status);
        $criteria->compare('stamp',$this->stamp);
        $criteria->compare('crt',$this->crt);
		$criteria->compare('order',$this->order);
		$criteria->compare('hits',$this->hits);
		$criteria->compare('litpic',$this->litpic,true);
		$criteria->compare('flag',$this->flag,true);
		$criteria->compare('source',$this->source,true);
		$criteria->compare('publish',$this->publish,true);
		$criteria->compare('marry',$this->marry,true);
		$criteria->compare('child',$this->child,true);
		$criteria->compare('education',$this->education,true);
		$criteria->compare('income',$this->income,true);
		$criteria->compare('shebao',$this->shebao,true);
		$criteria->compare('shebaoid',$this->shebaoid,true);
		$criteria->compare('housing',$this->housing,true);
		$criteria->compare('car',$this->car,true);
		$criteria->compare('late',$this->late,true);
		$criteria->compare('house_address',$this->house_address,true);
		$criteria->compare('house_area',$this->house_area,true);
		$criteria->compare('house_year',$this->house_year,true);
		$criteria->compare('house_status',$this->house_status,true);
		$criteria->compare('house_holder1',$this->house_holder1,true);
		$criteria->compare('house_holder2',$this->house_holder2,true);
		$criteria->compare('house_right1',$this->house_right1,true);
		$criteria->compare('house_right2',$this->house_right2,true);
		$criteria->compare('house_loanyear',$this->house_loanyear,true);
		$criteria->compare('house_loanprice',$this->house_loanprice,true);
		$criteria->compare('house_balance',$this->house_balance,true);
		$criteria->compare('house_bank',$this->house_bank,true);
		$criteria->compare('company_name',$this->company_name,true);
		$criteria->compare('company_credit',$this->company_credit);
		$criteria->compare('company_type',$this->company_type,true);
		$criteria->compare('company_industry',$this->company_industry,true);
		$criteria->compare('company_office',$this->company_office,true);
		$criteria->compare('company_jibie',$this->company_jibie,true);
		$criteria->compare('company_worktime1',$this->company_worktime1,true);
		$criteria->compare('company_worktime2',$this->company_worktime2,true);
		$criteria->compare('company_workyear',$this->company_workyear,true);
		$criteria->compare('company_tel',$this->company_tel,true);
		$criteria->compare('company_address',$this->company_address,true);
		$criteria->compare('company_weburl',$this->company_weburl,true);
		$criteria->compare('company_reamrk',$this->company_reamrk,true);
		$criteria->compare('company_business_scope',$this->company_business_scope,true);
		$criteria->compare('company_describe',$this->company_describe,true);
		$criteria->compare('company_property',$this->company_property,true);
		$criteria->compare('company_date',$this->company_date,true);
		$criteria->compare('company_regestered_capital',$this->company_regestered_capital,true);
		$criteria->compare('company_regestered_address',$this->company_regestered_address,true);
		$criteria->compare('private_type',$this->private_type,true);
		$criteria->compare('private_date',$this->private_date,true);
		$criteria->compare('private_place',$this->private_place,true);
		$criteria->compare('private_rent',$this->private_rent,true);
		$criteria->compare('private_term',$this->private_term,true);
		$criteria->compare('private_taxid',$this->private_taxid,true);
		$criteria->compare('private_commerceid',$this->private_commerceid,true);
		$criteria->compare('private_income',$this->private_income,true);
		$criteria->compare('private_employee',$this->private_employee,true);
		$criteria->compare('finance_repayment',$this->finance_repayment,true);
		$criteria->compare('finance_property',$this->finance_property,true);
		$criteria->compare('finance_amount',$this->finance_amount,true);
		$criteria->compare('finance_car',$this->finance_car,true);
		$criteria->compare('finance_caramount',$this->finance_caramount,true);
		$criteria->compare('finance_creditcard',$this->finance_creditcard,true);
		$criteria->compare('mate_name',$this->mate_name,true);
		$criteria->compare('mate_salary',$this->mate_salary,true);
		$criteria->compare('mate_phone',$this->mate_phone,true);
		$criteria->compare('mate_tel',$this->mate_tel,true);
		$criteria->compare('mate_type',$this->mate_type,true);
		$criteria->compare('mate_office',$this->mate_office,true);
		$criteria->compare('mate_address',$this->mate_address,true);
		$criteria->compare('mate_income',$this->mate_income,true);
		$criteria->compare('education_record',$this->education_record,true);
		$criteria->compare('education_school',$this->education_school,true);
		$criteria->compare('education_study',$this->education_study,true);
		$criteria->compare('education_time1',$this->education_time1,true);
		$criteria->compare('education_time2',$this->education_time2,true);
		$criteria->compare('tel',$this->tel,true);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('post',$this->post,true);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('province',$this->province,true);
		$criteria->compare('city',$this->city,true);
		$criteria->compare('area',$this->area,true);
		$criteria->compare('linkman1',$this->linkman1,true);
		$criteria->compare('relation1',$this->relation1,true);
		$criteria->compare('stock_right',$this->stock_right);
		$criteria->compare('tel1',$this->tel1,true);
		$criteria->compare('phone1',$this->phone1,true);
		$criteria->compare('linkman2',$this->linkman2,true);
		$criteria->compare('relation2',$this->relation2,true);
		$criteria->compare('tel2',$this->tel2,true);
		$criteria->compare('phone2',$this->phone2,true);
		$criteria->compare('linkman3',$this->linkman3,true);
		$criteria->compare('relation3',$this->relation3,true);
		$criteria->compare('tel3',$this->tel3,true);
		$criteria->compare('phone3',$this->phone3,true);
		$criteria->compare('msn',$this->msn,true);
		$criteria->compare('qq',$this->qq,true);
		$criteria->compare('wangwang',$this->wangwang,true);
		$criteria->compare('ability',$this->ability,true);
		$criteria->compare('interest',$this->interest,true);
		$criteria->compare('others',$this->others,true);
		$criteria->compare('experience',$this->experience,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('updatetime',$this->updatetime,true);
		$criteria->compare('updateip',$this->updateip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}