<?php


class XfDebtAssigneeInfo extends CActiveRecord
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
	 * @return CDbConnection database connection
	 */
	public function getDbConnection()
	{
		return Yii::app()->phdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'xf_debt_assignee_info';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(

			array('id,user_id,area_id,transferability_limit,transferred_amount,agreement_url,status,add_user_id,add_ip,add_time,update_user_id,update_ip,update_time', 'safe', 'on'=>'search'),
		);
	}


}