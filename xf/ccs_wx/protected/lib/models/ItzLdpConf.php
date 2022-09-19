<?php

/**
 * This is the model class for table "itz_ldp_conf".
 *
 * The followings are the available columns in table 'itz_ldp_conf':
 * @property integer $id
 * @property string $name
 * @property integer $ldp_type
 * @property string $banner_url
 * @property integer $banner_info_open
 * @property integer $banner_info_type
 * @property string $reg_btn_color
 * @property string $reg_btn_text
 * @property integer $show_login
 * @property integer $reg_suc_type
 * @property string $addip
 * @property integer $modifytime
 * @property string $modifyip
 * @property string $adduser
 * @property string $modifyuser
 * @property integer $addtime
 */
class ItzLdpConf extends DwActiveRecord
{	
	//wap注册着落页配置着落页类型
	protected $ldpType = array(
        'ldp9_mobile_banner1'  => '短月收益大图版',
        // 'ldp8_mobile_banner1'  => '短月收益标准版', //ldp8_mobile是使用的模板，_banner1表示标题区域是月收益版
        'ldp9_mobile_banner3'  => '短年收益大图版',
        // 'ldp8_mobile_banner3'  => '短年收益标准版',
        'ldp8_mobile_banner2'  => '短车挣盒子标准版',
        // 'ldplg_mobile_3'  => '长月收益+注册在上标准版',
        'ldplg_mobile_5'  => '长月收益+注册在上大图版',
        //文章类注册页模板
        'ldp_article_mobile'   => '文章类模板',
	);
	
	//wap注册着落页配置标题收益区域是否显示
	protected $bannerInfoOpen = array(
	    '1'  => '显示',
	    '0'  => '不显示',
	);
	//wap注册着落页配置标题区域类型
	protected $bannerInfoType = array(
	    '1'  => '投资总额+月收益',
	    '2'  => '投资总额+年利率',
	    '3'  => '投资总额+年收益',
	);
	
	//wap注册着落页登录入口设置
	protected $loginType = array(
	    '0'  => '无登录入口',
	    '1'  => '有登录入口',
	);
	
	//wap注册着落页注册成功页设置
	protected $regSucType = array(
	    '/wap/index/regSuc1'  => '标准版成功页',
	    '/wap/index/regSucSafeSet'  => '账户设置版成功页',
	);

	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzLdpConf the static model class
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
		return 'itz_ldp_conf';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('ldp_type,reg_btn_text', 'required','message'=>Yii::t('luben','{attribute}不能为空')),
			array('banner_info_open, banner_info_type, show_login, modifytime, addtime', 'numerical', 'integerOnly'=>true),
		    array('ldp_type', 'length', 'max'=>50),
			array('name', 'length', 'max'=>50),
		    array('reg_suc_type', 'length', 'max'=>50),
			array('banner_url', 'length', 'max'=>150),
			array('product_img', 'length', 'max'=>255),
			array('reg_btn_color, reg_area_color, addip, modifyip, adduser, modifyuser', 'length', 'max'=>20),
			array('reg_btn_text', 'length', 'max'=>100),
			array('article', 'safe'),
			array('share_title', 'length', 'max'=>100),
			array('share_desc', 'length', 'max'=>150),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, ldp_type, banner_url, banner_info_open, banner_info_type, reg_btn_color, reg_btn_text, show_login, reg_suc_type,product_img, article, share_title, share_desc, addip, modifytime, modifyip, adduser, modifyuser, addtime', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => '着落页名称',
			'ldp_type' => '着落页模板',
			'banner_url' => '头图设置',
			'banner_info_open' => '是否显示标题收益区域',
			'banner_info_type' => '标题收益区域',
			'reg_btn_color' => '按钮颜色',
			'reg_btn_text' => '注册按钮',
			'reg_area_color' => '注册区域颜色',
			'show_login' => '是否显示登陆入口',
			'reg_suc_type' => '注册成功页设置',
			'product_img' => '产品介绍图',
			'article' => '文章内容',
			'share_title' => '微信分享标题',
			'share_desc' => '微信分享描述',
			'addip' => 'Addip',
			'modifytime' => 'Modifytime',
			'modifyip' => 'Modifyip',
			'adduser' => 'Adduser',
			'modifyuser' => 'Modifyuser',
			'addtime' => 'Addtime',
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
		$criteria->compare('name',$this->name,true);
		$criteria->compare('ldp_type',$this->ldp_type);
		$criteria->compare('banner_url',$this->banner_url,true);
		$criteria->compare('banner_info_open',$this->banner_info_open);
		$criteria->compare('banner_info_type',$this->banner_info_type);
		$criteria->compare('reg_btn_color',$this->reg_btn_color,true);
		$criteria->compare('reg_btn_text',$this->reg_btn_text,true);
		$criteria->compare('reg_area_color',$this->reg_area_color,true);
		$criteria->compare('show_login',$this->show_login);
		$criteria->compare('reg_suc_type',$this->reg_suc_type);
		$criteria->compare('product_img',$this->product_img);
		$criteria->compare('article',$this->article,true);
		$criteria->compare('share_title',$this->share_title,true);
		$criteria->compare('share_desc',$this->share_desc,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	
	//返回着落页类型
	public function getLdpType() {
	    return $this->ldpType;
	}
	
	//返回标题区域是否显示下拉框数据
    public function getBannerInfoOpen(){
        return $this->bannerInfoOpen;
    }
    
    //返回标题区域类型下拉框数据
    public function getBannerInfoType(){
        return $this->bannerInfoType;
    }
    
    //返回登录入口下拉框数据
    public function getLoginType(){
        return $this->loginType;
    }
    
    //返回登录入口下拉框数据
    public function getRegSucType(){
        return $this->regSucType;
    }
}