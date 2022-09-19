<?php

/**
 * This is the model class for table "offline_import_content".
 *
 * The followings are the available columns in table 'offline_import_content':
 * @property string $id
 * @property string $file_id
 * @property integer $platform_id
 * @property string $old_user_id
 * @property string $user_name
 * @property integer $user_type
 * @property integer $idno_type
 * @property string $idno
 * @property integer $bank_id
 * @property string $bank_number
 * @property string $bankzone
 * @property string $cardholder
 * @property string $mobile_phone
 * @property string $p_name
 * @property string $p_type
 * @property integer $p_limit_type
 * @property integer $p_limit_num
 * @property string $rate
 * @property integer $loantype
 * @property integer $value_date
 * @property integer $repayment_time
 * @property string $raise_money
 * @property string $p_number
 * @property string $p_desc
 * @property string $rg_amount
 * @property integer $rg_time
 * @property string $contract_number
 * @property string $download
 * @property string $danbao_download
 * @property string $zixun_fuwu_download
 * @property integer $wait_date
 * @property string $wait_capital
 * @property string $wait_interest
 * @property integer $end_repay_time
 * @property string $borrower_name
 * @property integer $b_type
 * @property integer $b_idno_type
 * @property string $b_idno
 * @property string $b_address
 * @property string $b_mobile_phone
 * @property string $b_desc
 * @property string $b_bankzone
 * @property string $b_bank_number
 * @property string $b_legal_person
 * @property string $guarantee_name
 * @property string $g_license
 * @property string $g_address
 * @property string $g_mobile_phone
 * @property string $g_desc
 * @property string $g_bankzone
 * @property string $g_bank_number
 * @property string $g_legal_person
 * @property string $order_sn
 * @property string $object_sn
 * @property integer $status
 * @property string $addtime
 * @property integer $deal_status
 * @property string $update_time
 * @property string $remark
 */
class OfflineImportContent extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return OfflineImportContent the static model class
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
		return 'offline_import_content';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('platform_id, user_type, idno_type, bank_id, p_limit_type, p_limit_num, loantype, value_date, repayment_time, rg_time, wait_date, end_repay_time, b_type, b_idno_type, status, deal_status', 'numerical', 'integerOnly'=>true),
			array('file_id, old_user_id, addtime, update_time', 'length', 'max'=>11),
			array('user_name, cardholder, mobile_phone, raise_money, rg_amount, wait_capital, wait_interest, b_mobile_phone, g_mobile_phone', 'length', 'max'=>20),
			array('idno, p_name, p_type, b_idno, b_legal_person, g_legal_person, order_sn, object_sn', 'length', 'max'=>50),
			array('bank_number, borrower_name, b_bank_number, guarantee_name, g_bank_number', 'length', 'max'=>30),
			array('bankzone, p_number, contract_number, b_bankzone, g_license, g_bankzone', 'length', 'max'=>255),
			array('rate', 'length', 'max'=>8),
			array('b_address, g_address', 'length', 'max'=>200),
			array('p_desc, download, danbao_download, zixun_fuwu_download, b_desc, g_desc, remark', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('receivable_interest, max_rate, id, file_id, platform_id, old_user_id, user_name, user_type, idno_type, idno, bank_id, bank_number, bankzone, cardholder, mobile_phone, p_name, p_type, p_limit_type, p_limit_num, rate, loantype, value_date, repayment_time, raise_money, p_number, p_desc, rg_amount, rg_time, contract_number, download, danbao_download, zixun_fuwu_download, wait_date, wait_capital, wait_interest, end_repay_time, borrower_name, b_type, b_idno_type, b_idno, b_address, b_mobile_phone, b_desc, b_bankzone, b_bank_number, b_legal_person, guarantee_name, g_license, g_address, g_mobile_phone, g_desc, g_bankzone, g_bank_number, g_legal_person, order_sn, object_sn, status, addtime, deal_status, update_time, remark', 'safe', 'on'=>'search'),
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
			'file_id' => 'File',
			'platform_id' => 'Platform',
			'old_user_id' => 'Old User',
			'user_name' => 'User Name',
			'user_type' => 'User Type',
			'idno_type' => 'Idno Type',
			'idno' => 'Idno',
			'bank_id' => 'Bank',
			'bank_number' => 'Bank Number',
			'bankzone' => 'Bankzone',
			'cardholder' => 'Cardholder',
			'mobile_phone' => 'Mobile Phone',
			'p_name' => 'P Name',
			'p_type' => 'P Type',
			'p_limit_type' => 'P Limit Type',
			'p_limit_num' => 'P Limit Num',
			'rate' => 'Rate',
			'loantype' => 'Loantype',
			'value_date' => 'Value Date',
			'repayment_time' => 'Repayment Time',
			'raise_money' => 'Raise Money',
			'p_number' => 'P Number',
			'p_desc' => 'P Desc',
			'rg_amount' => 'Rg Amount',
			'rg_time' => 'Rg Time',
			'contract_number' => 'Contract Number',
			'download' => 'Download',
			'danbao_download' => 'Danbao Download',
			'zixun_fuwu_download' => 'Zixun Fuwu Download',
			'wait_date' => 'Wait Date',
			'wait_capital' => 'Wait Capital',
			'wait_interest' => 'Wait Interest',
			'end_repay_time' => 'End Repay Time',
			'borrower_name' => 'Borrower Name',
			'b_type' => 'B Type',
			'b_idno_type' => 'B Idno Type',
			'b_idno' => 'B Idno',
			'b_address' => 'B Address',
			'b_mobile_phone' => 'B Mobile Phone',
			'b_desc' => 'B Desc',
			'b_bankzone' => 'B Bankzone',
			'b_bank_number' => 'B Bank Number',
			'b_legal_person' => 'B Legal Person',
			'guarantee_name' => 'Guarantee Name',
			'g_license' => 'G License',
			'g_address' => 'G Address',
			'g_mobile_phone' => 'G Mobile Phone',
			'g_desc' => 'G Desc',
			'g_bankzone' => 'G Bankzone',
			'g_bank_number' => 'G Bank Number',
			'g_legal_person' => 'G Legal Person',
			'order_sn' => 'Order Sn',
			'object_sn' => 'Object Sn',
			'status' => 'Status',
			'addtime' => 'Addtime',
			'deal_status' => 'Deal Status',
			'update_time' => 'Update Time',
			'remark' => 'Remark',
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
		$criteria->compare('file_id',$this->file_id,true);
		$criteria->compare('platform_id',$this->platform_id);
		$criteria->compare('old_user_id',$this->old_user_id,true);
		$criteria->compare('user_name',$this->user_name,true);
		$criteria->compare('user_type',$this->user_type);
		$criteria->compare('idno_type',$this->idno_type);
		$criteria->compare('idno',$this->idno,true);
		$criteria->compare('bank_id',$this->bank_id);
		$criteria->compare('bank_number',$this->bank_number,true);
		$criteria->compare('bankzone',$this->bankzone,true);
		$criteria->compare('cardholder',$this->cardholder,true);
		$criteria->compare('mobile_phone',$this->mobile_phone,true);
		$criteria->compare('p_name',$this->p_name,true);
		$criteria->compare('p_type',$this->p_type,true);
		$criteria->compare('p_limit_type',$this->p_limit_type);
		$criteria->compare('p_limit_num',$this->p_limit_num);
		$criteria->compare('rate',$this->rate,true);
		$criteria->compare('loantype',$this->loantype);
		$criteria->compare('value_date',$this->value_date);
		$criteria->compare('repayment_time',$this->repayment_time);
		$criteria->compare('raise_money',$this->raise_money,true);
		$criteria->compare('p_number',$this->p_number,true);
		$criteria->compare('p_desc',$this->p_desc,true);
		$criteria->compare('rg_amount',$this->rg_amount,true);
		$criteria->compare('rg_time',$this->rg_time);
		$criteria->compare('contract_number',$this->contract_number,true);
		$criteria->compare('download',$this->download,true);
		$criteria->compare('danbao_download',$this->danbao_download,true);
		$criteria->compare('zixun_fuwu_download',$this->zixun_fuwu_download,true);
		$criteria->compare('wait_date',$this->wait_date);
		$criteria->compare('wait_capital',$this->wait_capital,true);
		$criteria->compare('wait_interest',$this->wait_interest,true);
		$criteria->compare('end_repay_time',$this->end_repay_time);
		$criteria->compare('borrower_name',$this->borrower_name,true);
		$criteria->compare('b_type',$this->b_type);
		$criteria->compare('b_idno_type',$this->b_idno_type);
		$criteria->compare('b_idno',$this->b_idno,true);
		$criteria->compare('b_address',$this->b_address,true);
		$criteria->compare('b_mobile_phone',$this->b_mobile_phone,true);
		$criteria->compare('b_desc',$this->b_desc,true);
		$criteria->compare('b_bankzone',$this->b_bankzone,true);
		$criteria->compare('b_bank_number',$this->b_bank_number,true);
		$criteria->compare('b_legal_person',$this->b_legal_person,true);
		$criteria->compare('guarantee_name',$this->guarantee_name,true);
		$criteria->compare('g_license',$this->g_license,true);
		$criteria->compare('g_address',$this->g_address,true);
		$criteria->compare('g_mobile_phone',$this->g_mobile_phone,true);
		$criteria->compare('g_desc',$this->g_desc,true);
		$criteria->compare('g_bankzone',$this->g_bankzone,true);
		$criteria->compare('g_bank_number',$this->g_bank_number,true);
		$criteria->compare('g_legal_person',$this->g_legal_person,true);
		$criteria->compare('order_sn',$this->order_sn,true);
		$criteria->compare('object_sn',$this->object_sn,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('deal_status',$this->deal_status);
		$criteria->compare('update_time',$this->update_time,true);
		$criteria->compare('remark',$this->remark,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}