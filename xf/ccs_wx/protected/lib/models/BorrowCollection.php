<?php

/**
 * This is the model class for table "dw_borrow_collection".
 *
 * The followings are the available columns in table 'dw_borrow_collection':
 * @property string $id
 * @property integer $site_id
 * @property integer $status
 * @property integer $order
 * @property integer $tender_id
 * @property string $value_date
 * @property string $repay_time
 * @property string $repay_yestime
 * @property string $repay_account
 * @property string $repay_yesaccount
 * @property string $interest
 * @property string $capital
 * @property integer $late_days
 * @property string $late_interest
 * @property string $addtime
 * @property string $addip
 */
class BorrowCollection extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $realname;//用户真实姓名
    public $username;//用户名
    public $borrowname;//用户名
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return BorrowCollection the static model class
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
		return 'dw_borrow_collection';
	}


    /**
     * 状态
     */
     protected $_status=array('未还',"已还");
    
     public function getStatus(){
         return $this->_status;
     }
     public function StrStatus(){
         if(isset($this->_status[$this->status]))
            return $this->_status[$this->status];
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_status))?$this->_status[$key]:"";
     }  
     public function getEncryptRealName($str='')
     {
        $strlen = mb_strlen($str, 'UTF-8');
        if ($strlen>0) {
            return $this->substr_replace_cn($str,'*',1,mb_strlen($str, 'UTF-8')-1);
        }
        return '-';
     }
     public function substr_replace_cn($string, $repalce = '*',$start = 0,$len = 0) {
        $count = mb_strlen($string, 'UTF-8'); //此处传入编码，建议使用utf-8。此处编码要与下面mb_substr()所使用的一致
        if(!$count) { 
            return $string; 
        }
        if($len == 0){
            $end = $count;  //传入0则替换到最后
        }else{
            $end = $start + $len;       //传入指定长度则为开始长度+指定长度
        }
        $i = 0;
        $returnString = '';
        while ($i < $count) {        //循环该字符串
            $tmpString = mb_substr($string, $i, 1, 'UTF-8'); // 与mb_strlen编码一致
            if ($start <= $i && $i < $end) {
                $returnString .= $repalce;
            } else {
                $returnString .= $tmpString;
            }
            $i ++;
        }
        return $returnString;
    }

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('site_id, status, order, tender_id, late_days, value_date, repay_time, repay_yestime, addtime', 'numerical', 'integerOnly'=>true),
			array('addip', 'length', 'max'=>50),
			array('repay_account, repay_yesaccount, interest, capital, late_interest', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('borrowname,realname,username,id, site_id, status, order, tender_id, value_date, repay_time, repay_yestime, repay_account, repay_yesaccount, interest, capital, late_days, late_interest, addtime, addip', 'safe', 'on'=>'search'),
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
		   "borrowTenderInfo" =>  array(self::BELONGS_TO, 'BorrowTender', 'tender_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'site_id' => '合同ID',
			'status' => '状态',
			'order' => 'Order',
			'tender_id' => '投资记录ID',
			'value_date' => '计息时间',
			'repay_time' => '预还时间',
			'repay_yestime' => '实还时间',
			'repay_account' => '预还金额',
			'repay_yesaccount' => '实还金额',
			'interest' => '利息',
			'capital' => '本金',
			'late_days' => '用户名',
			'late_interest' => '真实姓名',
			'addtime' => '项目名称',
			'addip' => 'IP',
			'realname' => '真实姓名',
            'username' => '用户名',
            'borrowname' =>'项目名称',
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
		$criteria->compare('site_id',$this->site_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('order',$this->order);
		$criteria->compare('tender_id',$this->tender_id);
        if(!empty($this->repay_yestime))
             $criteria->addBetweenCondition('repay_yestime',strtotime($this->repay_yestime),(strtotime($this->repay_yestime)+86399));
        else
            $criteria->compare('repay_yestime',$this->repay_yestime);
        
        if(!empty($this->value_date))
             $criteria->addBetweenCondition('value_date',strtotime($this->value_date),(strtotime($this->value_date)+86399));
        else
            $criteria->compare('value_date',$this->value_date);
        
        if(!empty($this->repay_time))
             $criteria->addBetweenCondition('repay_time',strtotime($this->repay_time),(strtotime($this->repay_time)+86399));
        else
            $criteria->compare('repay_time',$this->repay_time);
		
		$criteria->compare('repay_account',$this->repay_account);
		$criteria->compare('repay_yesaccount',$this->repay_yesaccount);
		$criteria->compare('interest',$this->interest);
		$criteria->compare('capital',$this->capital);
		$criteria->compare('late_days',$this->late_days);
		$criteria->compare('late_interest',$this->late_interest);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip);
		return new CActiveDataProvider($this, array(
		    'sort'=>array(
                'defaultOrder'=>'id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}
