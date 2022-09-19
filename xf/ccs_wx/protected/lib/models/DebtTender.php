<?php

/**
 * This is the model class for table "dw_debt_tender".
 *
 * The followings are the available columns in table 'dw_debt_tender':
 * @property integer $id
 * @property integer $debt_id
 * @property integer $user_id
 * @property integer $new_tender_id
 * @property integer $type
 * @property integer $status
 * @property string $money
 * @property string $account
 * @property string $action_money
 * @property string $money_detail
 * @property integer $addtime
 * @property string $addip
 */
class DebtTender extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $borrow_type;//产品类型
    public $borrow_name;//项目名称
    public $attorn_userid;//债权转让人
    public $take_userid;//债权认购人
    public $apr;//原收益
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return DebtTender the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
       //转让状态1新建2成功2取消
     protected $_status=array(
                                1=>'新建',
                                2=>'成功',
                                3=>'取消',
                                4=>'转让过期'
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
     
     
     public function getApr($debt_id){
         $debtInfo = Debt::model()->findByPk($debt_id);
         $borrowInfo = Borrow::model()->findByPk($debtInfo->borrow_id);
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
		return 'dw_debt_tender';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('debt_id, user_id, type, status, money, account, money_detail, addtime, addip', 'required'),
			array('debt_id, user_id, new_tender_id, type, status, addtime', 'numerical', 'integerOnly'=>true),
			array('money, account, action_money', 'length', 'max'=>11),
			array('money_detail', 'length', 'max'=>255),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('borrow_type,borrow_name,attorn_userid,take_userid,id, debt_id, user_id, new_tender_id, type, status, money, account, action_money, money_detail, addtime, addip', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
           "debtInfo"   =>  array(self::BELONGS_TO, 'Debt', 'debt_id'),
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
			'debt_id' => 'Debt',
			'user_id' => '债权认购人',
			'new_tender_id' => 'New Tender',
			'type' => 'Type',
			'status' => '转让状态',
			'money' => 'Money',
			'account' => '认购金额',
			'action_money' => 'Action Money',
			'money_detail' => 'Money Detail',
			'addtime' => 'Addtime',
			'borrow_type' => '产品类型',
			'borrow_name' => '项目名称',
			'attorn_userid' => '债权转让人',
			'take_userid' => '债权认购人',
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
		$criteria->compare('debt_id',$this->debt_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('new_tender_id',$this->new_tender_id);
		$criteria->compare('type',$this->type);
		$criteria->compare('status',$this->status);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('account',$this->account,true);
		$criteria->compare('action_money',$this->action_money,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
		    'sort'=>array(
                'defaultOrder'=>'`addtime` DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}