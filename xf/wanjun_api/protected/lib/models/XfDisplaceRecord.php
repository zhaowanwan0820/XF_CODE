<?php


class XfDisplaceRecord extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Deal the static model class
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
		return 'xf_displace_record';
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
			array('move_time,debt_type,id,user_id,real_name,mobile_phone,idno,bank_card,province_name,card_address,displace_capital,status,add_ip,add_time,add_device,add_browser,user_sign_time,assignee_sign_time,debt_time,displace_time,displace_type,contract_url,contract_id,contract_transaction_id,oss_contract_url', 'safe', 'on'=>'search'),
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


}