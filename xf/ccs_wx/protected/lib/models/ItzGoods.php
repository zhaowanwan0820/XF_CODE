<?php

/**
 * This is the model class for table "itz_goods".
 *
 * The followings are the available columns in table 'itz_goods':
 * @property integer $id
 * @property integer $type
 * @property string $name
 * @property integer $status
 * @property integer $total
 * @property integer $is_hot
 * @property string $coupon_src
 * @property integer $market_price
 * @property integer $need_price
 * @property integer $price
 * @property integer $upper_time
 * @property integer $down_time
 * @property integer $daily_time
 * @property integer $limit_status
 * @property integer $single_num
 * @property integer $single_daily_num
 * @property integer $daily_num
 * @property string $desc
 * @property string $pc_list_img
 * @property string $pc_detail_img
 * @property string $app_list_img
 * @property string $app_detail_img
 * @property integer $sort
 * @property string $content
 * @property integer $addtime
 * @property integer $updatetime
 */
class ItzGoods extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzGoods the static model class
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
		return Yii::app()->dwdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_goods';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, status, total, is_hot, market_price, need_price, price, upper_time, down_time, daily_time, limit_status, single_num, single_daily_num, daily_num, sort, addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>100),
			array('coupon_src', 'length', 'max'=>50),
			array('desc, pc_list_img, pc_detail_img, app_list_img, app_detail_img', 'length', 'max'=>200),
			array('content', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, type, name, status, total, is_hot, coupon_src, market_price, need_price, price, upper_time, down_time, daily_time, limit_status, single_num, single_daily_num, daily_num, desc, pc_list_img, pc_detail_img, app_list_img, app_detail_img, sort, content, addtime, updatetime', 'safe', 'on'=>'search'),
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
			'type' => 'Type',
			'name' => 'Name',
			'status' => 'Status',
			'total' => 'Total',
			'is_hot' => 'Is Hot',
			'coupon_src' => 'Coupon Src',
			'market_price' => 'Market Price',
			'need_price' => 'Need Price',
			'price' => 'Price',
			'upper_time' => 'Upper Time',
			'down_time' => 'Down Time',
			'daily_time' => 'Daily Time',
			'limit_status' => 'Limit Status',
			'single_num' => 'Single Num',
			'single_daily_num' => 'Single Daily Num',
			'daily_num' => 'Daily Num',
			'desc' => 'Desc',
			'pc_list_img' => 'Pc List Img',
			'pc_detail_img' => 'Pc Detail Img',
			'app_list_img' => 'App List Img',
			'app_detail_img' => 'App Detail Img',
			'sort' => 'Sort',
			'content' => 'Content',
			'addtime' => 'Addtime',
			'updatetime' => 'Updatetime',
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
		$criteria->compare('type',$this->type);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('total',$this->total);
		$criteria->compare('is_hot',$this->is_hot);
		$criteria->compare('coupon_src',$this->coupon_src,true);
		$criteria->compare('market_price',$this->market_price);
		$criteria->compare('need_price',$this->need_price);
		$criteria->compare('price',$this->price);
		$criteria->compare('upper_time',$this->upper_time);
		$criteria->compare('down_time',$this->down_time);
		$criteria->compare('daily_time',$this->daily_time);
		$criteria->compare('limit_status',$this->limit_status);
		$criteria->compare('single_num',$this->single_num);
		$criteria->compare('single_daily_num',$this->single_daily_num);
		$criteria->compare('daily_num',$this->daily_num);
		$criteria->compare('desc',$this->desc,true);
		$criteria->compare('pc_list_img',$this->pc_list_img,true);
		$criteria->compare('pc_detail_img',$this->pc_detail_img,true);
		$criteria->compare('app_list_img',$this->app_list_img,true);
		$criteria->compare('app_detail_img',$this->app_detail_img,true);
		$criteria->compare('sort',$this->sort);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}