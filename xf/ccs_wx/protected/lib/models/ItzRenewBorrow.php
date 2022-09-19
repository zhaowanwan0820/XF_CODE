<?php

/**
 * This is the model class for table "itz_renew_borrow".
 *
 * The followings are the available columns in table 'itz_renew_borrow':
 * @property string $id
 * @property integer $type
 * @property string $borrow_id
 * @property string $wise_borrow_id
 * @property string $style
 * @property string $apr
 * @property string $renew_capital
 * @property integer $value_date
 * @property integer $repay_time
 * @property string $deferred_interest
 * @property integer $status
 * @property integer $borrow_type
 * @property integer $process_time
 * @property integer $time_limit
 * @property integer $month_limit
 * @property integer $addtime
 */
class ItzRenewBorrow extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzRenewBorrow the static model class
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
		return 'itz_renew_borrow';
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
			array('type, value_date, repay_time,start_time, status, borrow_type, process_time, time_limit, month_limit, addtime', 'numerical', 'integerOnly'=>true),
			array('borrow_id, wise_borrow_id, style', 'length', 'max'=>50),
			array('apr', 'length', 'max'=>18),
			array('renew_capital, deferred_interest', 'length', 'max'=>20),
			array('content', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, type, borrow_id, wise_borrow_id, style, apr, renew_capital, value_date, repay_time, start_time, deferred_interest, status, borrow_type, process_time, time_limit, month_limit, addtime,content', 'safe', 'on'=>'search'),
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
			'borrow_id' => 'Borrow',
			'wise_borrow_id' => 'Wise Borrow',
			'style' => 'Style',
			'apr' => 'Apr',
			'renew_capital' => 'Renew Capital',
			'value_date' => 'Value Date',
			'repay_time' => 'Repay Time',
			'start_time' => 'Start Time',
			'deferred_interest' => 'Deferred Interest',
			'status' => 'Status',
			'borrow_type' => 'Borrow Type',
			'process_time' => 'Process Time',
			'time_limit' => 'Time Limit',
			'month_limit' => 'Month Limit',
			'addtime' => 'Addtime',
			'content' => 'Content',
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
		$criteria->compare('borrow_id',$this->borrow_id,true);
		$criteria->compare('wise_borrow_id',$this->wise_borrow_id,true);
		$criteria->compare('style',$this->style,true);
		$criteria->compare('apr',$this->apr,true);
		$criteria->compare('renew_capital',$this->renew_capital,true);
		$criteria->compare('value_date',$this->value_date);
		$criteria->compare('repay_time',$this->repay_time);
		$criteria->compare('start_time',$this->start_time);
		$criteria->compare('deferred_interest',$this->deferred_interest,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('borrow_type',$this->borrow_type);
		$criteria->compare('process_time',$this->process_time);
		$criteria->compare('time_limit',$this->time_limit);
		$criteria->compare('month_limit',$this->month_limit);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('content',$this->content,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}