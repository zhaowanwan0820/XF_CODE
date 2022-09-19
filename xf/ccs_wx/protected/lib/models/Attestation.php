<?php

/**
 * This is the model class for table "dw_attestation".
 *
 * The followings are the available columns in table 'dw_attestation':
 * @property string $id
 * @property integer $user_id
 * @property integer $type_id
 * @property string $name
 * @property integer $status
 * @property integer $is_visible
 * @property string $litpic
 * @property string $thumb_url
 * @property string $content
 * @property integer $order
 * @property integer $jifen
 * @property string $pic
 * @property string $pic2
 * @property string $pic3
 * @property string $verify_time
 * @property integer $verify_user
 * @property string $verify_remark
 * @property string $addtime
 * @property string $addip
 */
class Attestation extends DwActiveRecord
{
    public $dbname = 'dwdb';
    public $realname;//用户真实姓名
    public $username;//用户名
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Attestation the static model class
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
		return 'dw_attestation';
	}
     //附件类型
     public function getType(){
         $usersrcs = AttestationType::model()->findAllByAttributes(array('use'=>2));
         foreach($usersrcs as $v){
             $res[$v->type_id] = $v->name;
         }
         return $res;
     }
     public function StrType(){
         $usersrcs = $this->getType();
         if(isset($usersrcs[$this->type_id]))
            return $usersrcs[$this->type_id];
     }
     public function getStrType($key){
         $res  = $this->getType();
         return (array_key_exists($key, $res?$res[$key]:""));
     }
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, type_id, status, is_visible, order, jifen, verify_user', 'numerical', 'integerOnly'=>true),
			array('name, litpic, thumb_url, content', 'length', 'max'=>255),
			array('pic2, pic3', 'length', 'max'=>100),
			array('verify_time', 'length', 'max'=>32),
			array('verify_remark', 'length', 'max'=>250),
			array('addtime, addip', 'length', 'max'=>50),
			array('pic', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('realname,username,id, user_id, type_id, name, status, is_visible, litpic, thumb_url, content, order, jifen, pic, pic2, pic3, verify_time, verify_user, verify_remark, addtime, addip', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array(
              "userInfo"   =>  array(self::BELONGS_TO, 'User', 'user_id'),
        );
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'user_id' => 'User',
			'type_id' => '附件类型',
			'name' => 'Name',
			'status' => 'Status',
			'is_visible' => '前台是否可见',
			'litpic' => '上传图片',
			'thumb_url' => '缩略图',
			'content' => '说明信息',
			'order' => '排序(数字越大越靠前)',
			'jifen' => 'Jifen',
			'pic' => 'Pic',
			'pic2' => 'Pic2',
			'pic3' => 'Pic3',
			'verify_time' => 'Verify Time',
			'verify_user' => 'Verify User',
			'verify_remark' => 'Verify Remark',
			'addtime' => '上传时间',
			'addip' => 'Addip',
			'realname' => '联系人',
            'username' => '企业名称',
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
		$criteria->compare('type_id',$this->type_id);
		$criteria->compare('name',$this->name);
		$criteria->compare('status',$this->status);
		$criteria->compare('is_visible',$this->is_visible);
		$criteria->compare('litpic',$this->litpic);
		$criteria->compare('thumb_url',$this->thumb_url);
		$criteria->compare('content',$this->content);
		$criteria->compare('order',$this->order);
		$criteria->compare('jifen',$this->jifen);
		$criteria->compare('pic',$this->pic);
		$criteria->compare('pic2',$this->pic2);
		$criteria->compare('pic3',$this->pic3);
		$criteria->compare('verify_time',$this->verify_time);
		$criteria->compare('verify_user',$this->verify_user);
		$criteria->compare('verify_remark',$this->verify_remark);
		if(!empty($this->addtime))
             $criteria->addBetweenCondition('addtime',strtotime($this->addtime),(strtotime($this->addtime)+86400));
        else
            $criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip);

		return new CActiveDataProvider($this, array(
		   'sort'=>array(
                'defaultOrder'=>'`order` DESC,id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
}