<?php

/**
 * This is the model class for table "dw_links".
 *
 * The followings are the available columns in table 'dw_links':
 * @property integer $id
 * @property integer $site_id
 * @property integer $status
 * @property integer $order
 * @property integer $flag
 * @property integer $type_id
 * @property string $url
 * @property string $webname
 * @property string $summary
 * @property string $linkman
 * @property string $email
 * @property string $logo
 * @property string $logoimg
 * @property string $province
 * @property string $city
 * @property string $area
 * @property integer $hits
 * @property integer $addtime
 * @property string $addip
 */
class Links extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Links the static model class
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
		return 'dw_links';
	}
     
     protected $_type_id=array(1=>'综合网站',2=>'财经媒体',3=>'网贷垂直',4=>'投资理财');
    
     public function getTypeId(){
         return $this->_type_id;
     }
     public function StrTypeId(){
         return $this->_type_id[$this->type_id];
     }
     public function getStrTypeId($key){
         return (array_key_exists($key, $this->_type_id))?$this->_type_id[$key]:"-";
     }

	/**
	 * @return array validation rules for model attributes.
	 */
	 public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('status, updatetime', 'required'),
            array('site_id, status, order, flag, type_id, hits, addtime, updatetime', 'numerical', 'integerOnly'=>true),
            array('url', 'length', 'max'=>60),
            array('webname', 'length', 'max'=>30),
            array('summary', 'length', 'max'=>200),
            array('linkman, email', 'length', 'max'=>50),
            array('logo, logoimg', 'length', 'max'=>100),
            array('province, city, area', 'length', 'max'=>10),
            array('addip', 'length', 'max'=>20),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, site_id, status, order, flag, type_id, url, webname, summary, linkman, email, logo, logoimg, province, city, area, hits, addtime, addip, updatetime', 'safe', 'on'=>'search'),
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
		  "linkTypeInfo"  => array(self::BELONGS_TO, 'LinksType', 'id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'site_id' => 'Site',
			'status' => 'Status',
			'order' => 'Order',
			'flag' => 'Flag',
			'type_id' => '分类',
			'url' => '链接地址',
			'webname' => '网站',
			'summary' => 'Summary',
			'linkman' => 'Linkman',
			'email' => 'Email',
			'logo' => 'Logo',
			'logoimg' => 'Logoimg',
			'province' => 'Province',
			'city' => 'City',
			'area' => 'Area',
			'hits' => 'Hits',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
			'updatetime' => '修改时间',
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
		$criteria->compare('site_id',$this->site_id);
		$criteria->compare('status',$this->status);
		$criteria->compare('order',$this->order);
		$criteria->compare('flag',$this->flag);
		$criteria->compare('type_id',$this->type_id);
		$criteria->compare('url',$this->url,true);
		$criteria->compare('webname',$this->webname,true);
		$criteria->compare('summary',$this->summary,true);
		$criteria->compare('linkman',$this->linkman,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('logo',$this->logo,true);
		$criteria->compare('logoimg',$this->logoimg,true);
		$criteria->compare('province',$this->province,true);
		$criteria->compare('city',$this->city,true);
		$criteria->compare('area',$this->area,true);
		$criteria->compare('hits',$this->hits);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('updatetime',$this->updatetime);

		return new CActiveDataProvider($this, array(
		    'sort'=>array(
                'defaultOrder'=>'`updatetime` asc', 
            ),
			'criteria'=>$criteria,
		));
	}
}