<?php

/**
 * This is the model class for table "itz_stat_repayment".
 *
 * The followings are the available columns in table 'itz_stat_repayment':
 * @property string $id
 * @property string $repay_interest_count
 * @property string $repay_interest_amount
 * @property string $repay_capital_count
 * @property string $repay_capital_amount
 * @property string $repay_interest_maturity_count
 * @property string $repay_interest_maturity_amount
 * @property string $repay_capital_maturity_count
 * @property string $repay_capital_maturity_amount
 * @property string $repay_interest_matching_count
 * @property string $repay_interest_matching_amount
 * @property string $repay_capital_matching_count
 * @property string $repay_capital_matching_amount
 * @property integer $timestamp
 */
class ItzStatRepayment extends DwActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzStatRepayment the static model class
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
		return Yii::app()->datadb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_stat_repayment';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('timestamp', 'numerical', 'integerOnly'=>true),
			array('repay_interest_count, repay_capital_count, repay_interest_maturity_count, repay_capital_maturity_count, repay_interest_matching_count, repay_capital_matching_count', 'length', 'max'=>10),
			array('repay_interest_amount, repay_capital_amount, repay_interest_maturity_amount, repay_capital_maturity_amount, repay_interest_matching_amount, repay_capital_matching_amount', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, repay_interest_count, repay_interest_amount, repay_capital_count, repay_capital_amount, repay_interest_maturity_count, repay_interest_maturity_amount, repay_capital_maturity_count, repay_capital_maturity_amount, repay_interest_matching_count, repay_interest_matching_amount, repay_capital_matching_count, repay_capital_matching_amount, timestamp', 'safe', 'on'=>'search'),
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
			'repay_interest_count' => 'Repay Interest Count',
			'repay_interest_amount' => 'Repay Interest Amount',
			'repay_capital_count' => 'Repay Capital Count',
			'repay_capital_amount' => 'Repay Capital Amount',
			'repay_interest_maturity_count' => 'Repay Interest Maturity Count',
			'repay_interest_maturity_amount' => 'Repay Interest Maturity Amount',
			'repay_capital_maturity_count' => 'Repay Capital Maturity Count',
			'repay_capital_maturity_amount' => 'Repay Capital Maturity Amount',
			'repay_interest_matching_count' => 'Repay Interest Matching Count',
			'repay_interest_matching_amount' => 'Repay Interest Matching Amount',
			'repay_capital_matching_count' => 'Repay Capital Matching Count',
			'repay_capital_matching_amount' => 'Repay Capital Matching Amount',
			'timestamp' => 'Timestamp',
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
		$criteria->compare('repay_interest_count',$this->repay_interest_count,true);
		$criteria->compare('repay_interest_amount',$this->repay_interest_amount,true);
		$criteria->compare('repay_capital_count',$this->repay_capital_count,true);
		$criteria->compare('repay_capital_amount',$this->repay_capital_amount,true);
		$criteria->compare('repay_interest_maturity_count',$this->repay_interest_maturity_count,true);
		$criteria->compare('repay_interest_maturity_amount',$this->repay_interest_maturity_amount,true);
		$criteria->compare('repay_capital_maturity_count',$this->repay_capital_maturity_count,true);
		$criteria->compare('repay_capital_maturity_amount',$this->repay_capital_maturity_amount,true);
		$criteria->compare('repay_interest_matching_count',$this->repay_interest_matching_count,true);
		$criteria->compare('repay_interest_matching_amount',$this->repay_interest_matching_amount,true);
		$criteria->compare('repay_capital_matching_count',$this->repay_capital_matching_count,true);
		$criteria->compare('repay_capital_matching_amount',$this->repay_capital_matching_amount,true);
		$criteria->compare('timestamp',$this->timestamp);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}