<?php

/**
 * This is the model class for table "ag_wx_contract_record".
 *
 * The followings are the available columns in table 'ag_wx_contract_record':
 * @property integer $id
 * @property integer $status
 * @property integer $successtime
 * @property string $contract_addr
 * @property integer $addtime
 * @property integer $user_id
 * @property integer $type
 * @property integer $loan_id
 * @property integer $repay_way
 * @property integer $deal_id
 * @property string $borrower_fdd_id
 * @property string $agency_fdd_id
 * @property string $land_fdd_id
 */
class AgWxContractRecord extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgWxContractRecord the static model class
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
		return 'ag_wx_contract_record';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('status, user_id', 'required'),
			array('status, successtime, addtime, user_id, type, loan_id, repay_way, deal_id', 'numerical', 'integerOnly'=>true),
			array('contract_addr', 'length', 'max'=>2048),
			array('borrower_fdd_id, agency_fdd_id, land_fdd_id', 'length', 'max'=>127),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, status, handletime,download,successtime, contract_addr, addtime, user_id, type, loan_id, repay_way, deal_id, borrower_fdd_id, agency_fdd_id, land_fdd_id', 'safe', 'on'=>'search'),
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
			'status' => 'Status',
			'successtime' => 'Successtime',
			'contract_addr' => 'Contract Addr',
			'addtime' => 'Addtime',
			'user_id' => 'User',
			'type' => 'Type',
			'loan_id' => 'Loan',
			'repay_way' => 'Repay Way',
			'deal_id' => 'Deal',
			'borrower_fdd_id' => 'Borrower Fdd',
			'agency_fdd_id' => 'Agency Fdd',
			'land_fdd_id' => 'Land Fdd',
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
		$criteria->compare('status',$this->status);
		$criteria->compare('successtime',$this->successtime);
		$criteria->compare('contract_addr',$this->contract_addr,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('type',$this->type);
		$criteria->compare('loan_id',$this->loan_id);
		$criteria->compare('repay_way',$this->repay_way);
		$criteria->compare('deal_id',$this->deal_id);
		$criteria->compare('borrower_fdd_id',$this->borrower_fdd_id,true);
		$criteria->compare('agency_fdd_id',$this->agency_fdd_id,true);
		$criteria->compare('land_fdd_id',$this->land_fdd_id,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}