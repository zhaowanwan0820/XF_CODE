<?php


class XfBorrowerBindCardInfoOnline extends CActiveRecord
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
		return 'xf_borrower_bind_card_info_online';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(

			array('id,user_id,real_name,idno,mobile,id_type,bankcard,is_set_retail,src_zz,src_ds,src_other,auto_deduct_status,request_no,status,errormsg,cardtop,cardlast,bankcode,remark,yborderid,verifyStatus,add_time,update_time,last_repay_time,l_real_name,l_idno,l_mobile,l_bankcard,s_idno,s_mobile,s_bankcard,bind_type,bank_mobile,new_bankcard,src_yr,src_other_new,sex,province,city,region,byear', 'safe', 'on'=>'search'),
		);
	}


}