<?php

/**
 * This is the model class for table "firstp2p_deal_load".
 *
 * The followings are the available columns in table 'firstp2p_deal_load':
 * @property integer $id
 * @property integer $deal_id
 * @property integer $user_id
 * @property string $user_name
 * @property string $user_deal_name
 * @property double $money
 * @property string $wait_capital
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $is_repay
 * @property integer $from_deal_id
 * @property integer $deal_parent_id
 * @property integer $site_id
 * @property integer $source_type
 * @property string $ip
 * @property string $short_alias
 * @property integer $deal_type
 * @property integer $debt_status
 * @property integer $debt_type
 */
class PHDealLoad extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return DealLoad the static model class
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
		return 'firstp2p_deal_load';
	}

    public function getDbConnection()
    {
        return Yii::app()->phdb;
    }

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('deal_id, user_id, user_name, user_deal_name, money, create_time, is_repay', 'required'),
			array('deal_id, user_id, create_time, update_time, is_repay, from_deal_id, deal_parent_id, site_id, source_type, deal_type, debt_status, debt_type', 'numerical', 'integerOnly'=>true),
			array('money', 'numerical'),
			array('user_name, user_deal_name, ip', 'length', 'max'=>50),
			array('wait_capital, short_alias', 'length', 'max'=>20),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('displace_id,from_displace_id,displace_amount,province_name,card_address,yes_interest,wait_interest,status,debt_batch_number,is_debt_confirm,debt_confirm_time,update_black_time,black_status,id, deal_id, user_id, user_name, user_deal_name, money, wait_capital, create_time, update_time, is_repay, from_deal_id, deal_parent_id, site_id, source_type, ip, short_alias, deal_type, debt_status, debt_type', 'safe', 'on'=>'search'),
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
			'user_id' => 'User',
			'user_name' => 'User Name',
			'user_deal_name' => 'User Deal Name',
			'money' => 'Money',
			'wait_capital' => 'Wait Capital',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'is_repay' => 'Is Repay',
			'from_deal_id' => 'From Deal',
			'deal_parent_id' => 'Deal Parent',
			'site_id' => 'Site',
			'source_type' => 'Source Type',
			'ip' => 'Ip',
			'short_alias' => 'Short Alias',
			'deal_type' => 'Deal Type',
			'debt_status' => 'Debt Status',
			'debt_type' => 'Debt Type',
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
		$criteria->compare('deal_id',$this->deal_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('user_name',$this->user_name,true);
		$criteria->compare('user_deal_name',$this->user_deal_name,true);
		$criteria->compare('money',$this->money);
		$criteria->compare('wait_capital',$this->wait_capital,true);
		$criteria->compare('create_time',$this->create_time);
		$criteria->compare('update_time',$this->update_time);
		$criteria->compare('is_repay',$this->is_repay);
		$criteria->compare('from_deal_id',$this->from_deal_id);
		$criteria->compare('deal_parent_id',$this->deal_parent_id);
		$criteria->compare('site_id',$this->site_id);
		$criteria->compare('source_type',$this->source_type);
		$criteria->compare('ip',$this->ip,true);
		$criteria->compare('short_alias',$this->short_alias,true);
		$criteria->compare('deal_type',$this->deal_type);
		$criteria->compare('debt_status',$this->debt_status);
		$criteria->compare('debt_type',$this->debt_type);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}