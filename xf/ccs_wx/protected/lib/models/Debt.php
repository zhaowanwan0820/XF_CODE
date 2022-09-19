<?php

/**
 * This is the model class for table "dw_debt".
 *
 * The followings are the available columns in table 'dw_debt':
 * @property integer $id
 * @property integer $user_id
 * @property integer $tender_id
 * @property integer $borrow_id
 * @property integer $type
 * @property integer $status
 * @property string $money
 * @property string $sold_money
 * @property string $discount_money
 * @property string $scale
 * @property string $real_apr_s
 * @property string $real_apr_b
 * @property integer $starttime
 * @property integer $endtime
 * @property integer $next_repay_time
 * @property integer $successtime
 * @property integer $addtime
 * @property string $addip
 * @property string $request_no
 */
class Debt extends DwActiveRecord
{
	public $dbname = 'dwdb';
	public $type;//产品类型
	public $borrow_name;//项目名称
	public $attorn_userid;//债权转让人
	public $take_userid;//债权认购人
	public $request_no;//订单号
	public $phone;
	public $user_id;
	public $realname;
	public $username;
	public $starttime;
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Debt the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
	public $_status=array(
			1=>'发起转让成功',
			2=>'转让成功',
			3=>'取消转让',
			4=>'已过期',
			6=>'发起转让',
			7=>'发起转让失败',
			8=>'发起转让未成功'
	);
	public function getStatus(){
		return $this->_status;
	}
	public function StrStatus(){
		return $this->_status[$this->status];
	}
	public function getStrStatus($key){
		return (array_key_exists($key, $this->_status))?$this->_status[$key]:"";
	}

	public function getApr($id){
		$borrowInfo = Borrow::model()->findByPk($id);
		$apr = isset($borrowInfo) ? $borrowInfo->apr : '0%';
		return $apr;
	}
	//产品类型

	public function getBorrowType(){
		$_borrow_type = Yii::app()->params['borrow_type_online'];
		unset($_borrow_type[0]);
		return $_borrow_type;
	}
	public function StrBorrowType($type){
		$_borrow_type = $this->getBorrowType();
		return $_borrow_type[$type];
	}
	public function getStrBorrowType($key){
		$_borrow_type = $this->getBorrowType();
		return (array_key_exists($key, $_borrow_type))?$_borrow_type[$key]:"";
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'dw_debt';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, tender_id, borrow_id, type, status, money, sold_money, discount_money, real_apr_s, real_apr_b, starttime, endtime, successtime, addtime, addip', 'required'),
			array('user_id, tender_id, borrow_id, type, status, starttime, endtime, next_repay_time, successtime, addtime', 'numerical', 'integerOnly'=>true),
			array('money, sold_money, discount_money, scale, real_apr_s, real_apr_b', 'length', 'max'=>11),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id,username,phone,realname,request_no,starttime, user_id, tender_id, borrow_id, type, status, money, sold_money, discount_money, scale, real_apr_s, real_apr_b, starttime, endtime, next_repay_time, successtime, addtime, addip', 'safe', 'on'=>'search'),
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
		      "borrowInfo"   =>  array(self::BELONGS_TO, 'Borrow', 'borrow_id'),
		      "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => '订单ID',
			'user_id' => '用户ID',
			'tender_id' => 'Tender',
			'request_no'=>'订单号',
			'phone'=>'手机号',
			'realname'=>'用户实名',
			'type'=>'产品类型',
			'borrow_name'=>'项目名称',
			'username'=>'债权转让人',
			'money'=>'转让金额',
			'starttime'=>'发起时间',
			'borrow_id' => 'Borrow',
			'status' => '转让状态',
			'sold_money' => 'Sold Money',
			'discount_money' => 'Discount Money',
			'scale' => 'Scale',
			'real_apr_s' => 'Real Apr S',
			'real_apr_b' => 'Real Apr B',
			'endtime' => 'Endtime',
			'next_repay_time' => 'Next Repay Time',
			'successtime' => 'Successtime',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('tender_id',$this->tender_id);
		$criteria->compare('request_no',$this->request_no);
		$criteria->compare('borrow_id',$this->borrow_id);
		$criteria->compare('type',$this->type);
		$criteria->compare('status',$this->status);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('sold_money',$this->sold_money,true);
		$criteria->compare('discount_money',$this->discount_money,true);
		$criteria->compare('scale',$this->scale,true);
		$criteria->compare('real_apr_s',$this->real_apr_s,true);
		$criteria->compare('real_apr_b',$this->real_apr_b,true);
		//$criteria->compare('starttime',$this->starttime,true);
		$criteria->compare('endtime',$this->endtime);
		$criteria->compare('next_repay_time',$this->next_repay_time);
		$criteria->compare('successtime',$this->successtime);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}