<?php


class XfPlanPurchase extends CActiveRecord
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
		return 'xf_plan_purchase';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(

			array('id,area_id,scale,discount,trading_amount,total_amount,purchased_amount,traded_num,trading_num,user_id,status,starttime,endtime,add_user_id,add_ip,add_time', 'safe', 'on'=>'search'),
		);
	}


}