<?php

/**
 * This is the model class for table "itz_channel_ups".
 *
 * The followings are the available columns in table 'itz_channel_ups':
 * @property string $id
 * @property string $name
 * @property integer $status
 * @property integer $addtime
 * @property integer $expire_time
 */
class ChannelUps extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ChannelUps the static model class
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
		return 'itz_channel_ups';
	}
    //状态
     protected $_Status=array('停用','推广','屏蔽');
    
     public function getStatus(){
         return $this->_Status;
     }
     public function StrStatus(){
         if($this->status)
            return $this->_Status[$this->status];
     }
     public function getStrStatus($key){
         return (array_key_exists($key, $this->_Status))?$this->_Status[$key]:"选择类型";
     }
     
     //渠道
     public function getWaytypeName(){
         return Yii::app()->params['waytype'];
         $res = ItzChannelWaytype::model()->findAll();
         $arr = array(0=>'请选择');
         foreach ($res as $key => $value) {
             $arr[$value->id] = $value->title;
         }
         return $arr;
     }

     public function strWaytype(){
     	$res=Yii::app()->params['waytype'];
     	foreach ($res as $key => $value) {
     		# code...
     		if($key==$this->waytype){
     			return $value;
     		}
     	}
     }
     
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name,status,waytype, expire_time', 'required','message'=>Yii::t('luben','{attribute}不能为空')),
			array('name', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id,waytype, name, status, addtime, expire_time', 'safe', 'on'=>'search'),
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
			'id' => '渠道ID',
			'name' => '渠道名称',
			'status' => '状态',
            'waytype' => '推广方式',
			'addtime' => '添加时间',
			'expire_time' => '到期时间',
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
		$criteria->compare('name',$this->name);
		$criteria->compare('status',$this->status);
		$criteria->compare('waytype',$this->waytype);
		if(!empty($this->addtime))
             $criteria->addBetweenCondition('addtime',strtotime($this->addtime),(strtotime($this->addtime)+86400));
        else
            $criteria->compare('addtime',$this->addtime);
        if(!empty($this->expire_time))
             $criteria->addBetweenCondition('expire_time',strtotime($this->expire_time),(strtotime($this->expire_time)+86400));
        else
            $criteria->compare('expire_time',$this->expire_time);

		return new CActiveDataProvider($this, array(
	       'sort'=>array(
                'defaultOrder'=>'addtime DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}