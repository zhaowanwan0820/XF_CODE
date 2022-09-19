<?php

/**
 * This is the model class for table "firstp2p_user_bankcard".
 *
 * The followings are the available columns in table 'firstp2p_user_bankcard':
 * @property integer $id
 * @property integer $bank_id
 * @property string $bankcard
 * @property string $bankzone
 * @property integer $user_id
 * @property integer $status
 * @property string $card_name
 * @property integer $card_type
 * @property integer $region_lv1
 * @property integer $region_lv2
 * @property integer $region_lv3
 * @property integer $region_lv4
 * @property string $image_id
 * @property integer $is_effective
 * @property string $create_time
 * @property string $update_time
 * @property integer $verify_status
 * @property string $branch_no
 * @property integer $cert_status
 * @property string $e_account
 * @property string $p_account
 * @property integer $unitebank_state
 */
class UserBankcard extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserBankcard the static model class
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
		return Yii::app()->db;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'firstp2p_user_bankcard';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('bank_id, bankzone, user_id, card_name, region_lv1, region_lv2, region_lv3, region_lv4', 'required'),
			array('bank_id, user_id, status, card_type, region_lv1, region_lv2, region_lv3, region_lv4, is_effective, verify_status, cert_status, unitebank_state', 'numerical', 'integerOnly'=>true),
			array('bankcard, bankzone, card_name, e_account, p_account', 'length', 'max'=>255),
			array('image_id', 'length', 'max'=>11),
			array('create_time, update_time', 'length', 'max'=>10),
			array('branch_no', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, bank_id, bankcard, bankzone, user_id, status, card_name, card_type, region_lv1, region_lv2, region_lv3, region_lv4, image_id, is_effective, create_time, update_time, verify_status, branch_no, cert_status, e_account, p_account, unitebank_state', 'safe', 'on'=>'search'),
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
			'bank_id' => 'Bank',
			'bankcard' => 'Bankcard',
			'bankzone' => 'Bankzone',
			'user_id' => 'User',
			'status' => 'Status',
			'card_name' => 'Card Name',
			'card_type' => 'Card Type',
			'region_lv1' => 'Region Lv1',
			'region_lv2' => 'Region Lv2',
			'region_lv3' => 'Region Lv3',
			'region_lv4' => 'Region Lv4',
			'image_id' => 'Image',
			'is_effective' => 'Is Effective',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'verify_status' => 'Verify Status',
			'branch_no' => 'Branch No',
			'cert_status' => 'Cert Status',
			'e_account' => 'E Account',
			'p_account' => 'P Account',
			'unitebank_state' => 'Unitebank State',
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
		$criteria->compare('bank_id',$this->bank_id);
		$criteria->compare('bankcard',$this->bankcard,true);
		$criteria->compare('bankzone',$this->bankzone,true);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('card_name',$this->card_name,true);
		$criteria->compare('card_type',$this->card_type);
		$criteria->compare('region_lv1',$this->region_lv1);
		$criteria->compare('region_lv2',$this->region_lv2);
		$criteria->compare('region_lv3',$this->region_lv3);
		$criteria->compare('region_lv4',$this->region_lv4);
		$criteria->compare('image_id',$this->image_id,true);
		$criteria->compare('is_effective',$this->is_effective);
		$criteria->compare('create_time',$this->create_time,true);
		$criteria->compare('update_time',$this->update_time,true);
		$criteria->compare('verify_status',$this->verify_status);
		$criteria->compare('branch_no',$this->branch_no,true);
		$criteria->compare('cert_status',$this->cert_status);
		$criteria->compare('e_account',$this->e_account,true);
		$criteria->compare('p_account',$this->p_account,true);
		$criteria->compare('unitebank_state',$this->unitebank_state);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}