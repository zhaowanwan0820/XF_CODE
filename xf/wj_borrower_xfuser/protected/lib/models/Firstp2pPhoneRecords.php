<?php


class Firstp2pPhoneRecords extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return WxStatRepay the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function getDbConnection()
	{
		return Yii::app()->cmsdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'firstp2p_phone_records';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(

			array('id,record_time,addtime,file_path,company_id,company_name,tax_number,op_user_id,op_user_name,record_num', 'safe', 'on'=>'search'),
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