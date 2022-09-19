<?php

/**
 * This is the model class for table "ccs_activity_member".
 *
 * The followings are the available columns in table 'ccs_activity_member':
 * @property integer $id
 * @property integer $user_id
 * @property integer $activity_id
 * @property integer $last_tender_time
 * @property integer $last_login_time
 * @property integer $max_vip
 * @property integer $max_invest
 * @property integer $addtime
 * @property integer $awardtime
 * @property integer $invest_times
 * @property integer $invest_amount
 * @property integer $coupon_times
 * @property integer $coupon_amount
 * @property integer $admin_id 
 * @property integer $tag 
 * @property integer $extra 
 * @property integer $status 
 * @property integer $updatetime
 */
class CcsActivityMember extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CcsActivityMember the static model class
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
		return Yii::app()->ccsdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ccs_activity_member';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id', 'required'),
			array('user_id, activity_id, last_tender_time, last_login_time, max_vip, addtime, awardtime', 'numerical', 'integerOnly'=>true),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, activity_id, last_tender_time, last_login_time, max_vip, addtime, awardtime, max_invest,invest_times,invest_amount,coupon_times,coupon_amount,admin_id,tag,extra,status,updatetime', 'safe', 'on'=>'search'),
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
			'user_id' => 'User ID',
			'activity_id' => 'Activity ID',
			'last_tender_time' => 'Last Tender Time',
			'last_login_time' => 'Last Login Time',
			'max_vip' => 'Max VIP',
			'max_invest' => 'Max Invest',
			'addtime' => 'Addtime',
			'awardtime' => 'Awardtime',
			'invest_times' => '投资笔数',
			'invest_amount' => '投资金额',
			'coupon_times' => '优惠券张数',
			'coupon_amount' => '优惠券收益',
			'admin_id' => '客维编号',
			'tag' => '标签',
			'extra' => '备注',
			'status' => '状态',
			'updatetime' => '最后更新时间',

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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('activity_id',$this->activity_id);
		$criteria->compare('last_tender_time',$this->last_tender_time);
		$criteria->compare('last_login_time',$this->last_login_time);
		$criteria->compare('max_vip',$this->max_vip);
		$criteria->compare('max_invest',$this->max_invest);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('awardtime',$this->awardtime);
		$criteria->compare('invest_times',$this->invest_times);
		$criteria->compare('invest_amount',$this->invest_amount);
		$criteria->compare('coupon_times',$this->coupon_times);
		$criteria->compare('coupon_amount',$this->coupon_amount);
		$criteria->compare('admin_id',$this->admin_id);
		$criteria->compare('tag',$this->tag);
		$criteria->compare('extra',$this->extra);
		$criteria->compare('status',$this->status);
		$criteria->compare('updatetime',$this->updatetime);
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}