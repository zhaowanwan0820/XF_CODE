<?php

/**
 * This is the model class for table "itz_dispose_borrow".
 *
 * The followings are the available columns in table 'itz_dispose_borrow':
 * @property string $id
 * @property integer $type
 * @property integer $dispose_type
 * @property integer $borrow_id
 * @property string $wise_borrow_id
 * @property string $style
 * @property string $apr
 * @property string $dispose_capital
 * @property integer $value_date
 * @property integer $repay_time
 * @property integer $status
 * @property integer $addtime
 * @property integer $cycle
 * @property integer $start_time
 * @property string $content
 * @property integer $next_value_date
 * @property integer $process_time
 * @property integer $repay_num
 * @property integer $s_id
 * @property string $loan_contract_number
 * @property integer $repay_deal_status
 */
class ItzDisposeBorrow extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzDisposeBorrow the static model class
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
		return Yii::app()->dwdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_dispose_borrow';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type', 'required'),
			array('type, dispose_type, borrow_id, value_date, repay_time, status, addtime, cycle, start_time, next_value_date, process_time, repay_num, s_id, repay_deal_status', 'numerical', 'integerOnly'=>true),
			array('wise_borrow_id, style', 'length', 'max'=>50),
			array('apr', 'length', 'max'=>18),
			array('dispose_capital', 'length', 'max'=>20),
			array('content,time_limit', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, time_limit,type, dispose_type, borrow_id, wise_borrow_id, style, apr, dispose_capital, value_date, repay_time, status, addtime, cycle, start_time, content, next_value_date, process_time, repay_num, s_id, loan_contract_number, repay_deal_status', 'safe', 'on'=>'search'),
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
			'type' => 'Type',
			'dispose_type' => 'Dispose Type',
			'borrow_id' => 'Borrow',
			'wise_borrow_id' => 'Wise Borrow',
			'style' => 'Style',
			'apr' => 'Apr',
			'dispose_capital' => 'Dispose Capital',
			'value_date' => 'Value Date',
			'repay_time' => 'Repay Time',
			'status' => 'Status',
			'addtime' => 'Addtime',
			'cycle' => 'Cycle',
			'start_time' => 'Start Time',
			'content' => 'Content',
			'next_value_date' => 'Next Value Date',
			'process_time' => 'Process Time',
			'repay_num' => 'Repay Num',
			's_id' => 'S',
			'loan_contract_number' => 'Loan Contract Number',
			'repay_deal_status' => 'Repay Deal Status',
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
		$criteria->compare('type',$this->type);
		$criteria->compare('dispose_type',$this->dispose_type);
		$criteria->compare('borrow_id',$this->borrow_id);
		$criteria->compare('wise_borrow_id',$this->wise_borrow_id,true);
		$criteria->compare('style',$this->style,true);
		$criteria->compare('apr',$this->apr,true);
		$criteria->compare('dispose_capital',$this->dispose_capital,true);
		$criteria->compare('value_date',$this->value_date);
		$criteria->compare('repay_time',$this->repay_time);
		$criteria->compare('status',$this->status);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('cycle',$this->cycle);
		$criteria->compare('start_time',$this->start_time);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('next_value_date',$this->next_value_date);
		$criteria->compare('process_time',$this->process_time);
		$criteria->compare('repay_num',$this->repay_num);
		$criteria->compare('s_id',$this->s_id);
		$criteria->compare('loan_contract_number',$this->loan_contract_number,true);
		$criteria->compare('repay_deal_status',$this->repay_deal_status);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}