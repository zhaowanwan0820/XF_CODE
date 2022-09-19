<?php

/**
 * This is the model class for table "ag_wx_stat_repay".
 *
 * The followings are the available columns in table 'ag_wx_stat_repay':
 * @property string $id
 * @property string $deal_id
 * @property integer $deal_type
 * @property string $project_id
 * @property string $project_product_class
 * @property string $deal_name
 * @property string $jys_record_number
 * @property string $project_name
 * @property double $borrow_amount
 * @property string $deal_rate
 * @property integer $deal_repay_time
 * @property integer $deal_loantype
 * @property integer $deal_repay_start_time
 * @property integer $deal_advisory_id
 * @property string $deal_advisory_name
 * @property integer $deal_user_id
 * @property string $deal_user_real_name
 * @property integer $loan_repay_time
 * @property string $repay_amount
 * @property integer $repay_yestime
 * @property string $repaid_amount
 * @property integer $repay_type
 * @property integer $repay_status
 * @property string $addtime
 */
class OfflineStatRepay extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return PHWxStatRepay the static model class
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
		return Yii::app()->offlinedb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'offline_stat_repay';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('borrow_amount, deal_rate, deal_repay_time, deal_loantype, deal_repay_start_time, deal_advisory_id, deal_user_id', 'required'),
			array('deal_type, deal_repay_time, deal_loantype, deal_repay_start_time, deal_advisory_id, deal_user_id, loan_repay_time, repay_yestime, repay_type, repay_status', 'numerical', 'integerOnly'=>true),
			array('borrow_amount', 'numerical'),
			array('deal_id, project_id, addtime', 'length', 'max'=>11),
			array('project_product_class', 'length', 'max'=>25),
			array('deal_name, deal_advisory_name, deal_user_real_name', 'length', 'max'=>127),
			array('jys_record_number, project_name', 'length', 'max'=>255),
			array('deal_rate', 'length', 'max'=>8),
			array('repay_amount, repaid_amount', 'length', 'max'=>20),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, platform_id,deal_id, deal_type, project_id, project_product_class, deal_name, jys_record_number, project_name, borrow_amount, deal_rate, deal_repay_time, deal_loantype, deal_repay_start_time, deal_advisory_id, deal_advisory_name, deal_user_id, deal_user_real_name, loan_repay_time, repay_amount, repay_yestime, repaid_amount, repay_type, repay_status, addtime', 'safe', 'on'=>'search'),
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
			'deal_id' => 'Deal',
			'deal_type' => 'Deal Type',
			'project_id' => 'Project',
			'project_product_class' => 'Project Product Class',
			'deal_name' => 'Deal Name',
			'jys_record_number' => 'Approve Number',
			'project_name' => 'Project Name',
			'borrow_amount' => 'Borrow Amount',
			'deal_rate' => 'Deal Rate',
			'deal_repay_time' => 'Deal Repay Time',
			'deal_loantype' => 'Deal Loantype',
			'deal_repay_start_time' => 'Deal Repay Start Time',
			'deal_advisory_id' => 'Deal Agency',
			'deal_advisory_name' => 'Deal Agency Name',
			'deal_user_id' => 'Deal User',
			'deal_user_real_name' => 'Deal User Real Name',
			'loan_repay_time' => 'Loan Repay Time',
			'repay_amount' => 'Repay Amount',
			'repay_yestime' => 'Repay Yestime',
			'repaid_amount' => 'Repaid Amount',
			'repay_type' => 'Repay Type',
			'repay_status' => 'Repay Status',
			'addtime' => 'Addtime',
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
		$criteria->compare('deal_id',$this->deal_id,true);
		$criteria->compare('deal_type',$this->deal_type);
		$criteria->compare('project_id',$this->project_id,true);
		$criteria->compare('project_product_class',$this->project_product_class,true);
		$criteria->compare('deal_name',$this->deal_name,true);
		$criteria->compare('jys_record_number',$this->jys_record_number,true);
		$criteria->compare('project_name',$this->project_name,true);
		$criteria->compare('borrow_amount',$this->borrow_amount);
		$criteria->compare('deal_rate',$this->deal_rate,true);
		$criteria->compare('deal_repay_time',$this->deal_repay_time);
		$criteria->compare('deal_loantype',$this->deal_loantype);
		$criteria->compare('deal_repay_start_time',$this->deal_repay_start_time);
		$criteria->compare('deal_advisory_id',$this->deal_advisory_id);
		$criteria->compare('deal_advisory_name',$this->deal_advisory_name,true);
		$criteria->compare('deal_user_id',$this->deal_user_id);
		$criteria->compare('deal_user_real_name',$this->deal_user_real_name,true);
		$criteria->compare('loan_repay_time',$this->loan_repay_time);
		$criteria->compare('repay_amount',$this->repay_amount,true);
		$criteria->compare('repay_yestime',$this->repay_yestime);
		$criteria->compare('repaid_amount',$this->repaid_amount,true);
		$criteria->compare('repay_type',$this->repay_type);
		$criteria->compare('repay_status',$this->repay_status);
		$criteria->compare('addtime',$this->addtime,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}