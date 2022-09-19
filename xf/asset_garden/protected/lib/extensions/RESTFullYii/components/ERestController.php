<?php
class ERestController extends ItzController
{
	Const APPLICATION_ID = 'REST';
	Const C404NOTFOUND = 'HTTP/1.1 404 Not Found';
	Const C401UNAUTHORIZED = 'HTTP/1.1 401 Unauthorized';
	Const C406NOTACCEPTABLE = 'HTTP/1.1 406 Not Acceptable';
	Const C201CREATED = 'HTTP/1.1 201 Created';
	Const C200OK = 'HTTP/1.1 200 OK';
	Const C500INTERNALSERVERERROR = 'HTTP/1.1 500 Internal Server Error';

	public $HTTPStatus = 'HTTP/1.1 200 OK';
	public $restrictedProperties = array();
	public $restFilter = array(); 
	public $restSort = array();
	public $restScenario = null;
	public $restLimit = 100; // Default limit
	public $restOffset = 0; //Default Offset
	public $developmentFlag = YII_DEBUG; //When set to `false' 500 errors will not return detailed messages.
	protected $httpsOnly= FALSE; // Setting this variable to true allows the service to be used only via https
	//Auto will include all relations 
	//FALSE will include no relations in response
	//You may also pass an array of relations IE array('posts', 'comments', etc..)
	//Override $nestedModels in your controller as needed
	public $nestedModels = 'auto';

	//By supplying a scenario you can determine which fields are included in a request
	public $scenario = 'rest';

    #memcache || redis 用的 key
    public $CACHE_KEY = array(
        'session_key'=>'AppSESSION'
        );

	protected $requestReader;
	protected $model = null;

	public function __construct($id, $module = null) 
	{
		parent::__construct($id, $module);
		$this->requestReader = new ERequestReader('php://input');
	}

	public function beforeAction($event)
	{   
		 if($this->versionControl('1.6.1',false)){
            $this->echoJson(array(),100,"为了您的正常使用，请于官网或者应用市场升级至最新版本APP");Yii::app()->end();
        }
        #App 客户端维护时 使用的代码
        #$this->echoJson(array(),3,"爱亲，APP在今晚22点到24点进行服务器维护，如需使用，建议明早再来哦！");Yii::app()->end();

        $module = $event->controller->module->id;
        $controller = $event->controller->id;
        $action = $event->controller->action->id;
        $request_uri = $module.'/'.$controller.'/'.$action;
        Yii::app()->onException = array($this, 'onException'); //Register Custom Exception
        //接口维护使用的代码
        // $deny_action = Yii::app()->c->upgrade_action['app'];
        // if(time() <= strtotime('2017-07-05 09:00:00') && in_array($request_uri,$deny_action)){
        //     $this->echoJson(array(),100,"爱亲，APP在今天凌晨0点到9点进行服务器维护，如需使用，建议早上再来哦！");Yii::app()->end();
        // }
        if($this->versionControl('3.0.0') == false && !in_array($request_uri,['api/user/board','api/index/update','api/index/mainPopInfo','api/index/statement'])){ // 快捷支付协议是一个h5页面
            $this->echoJson(array(),100,'为了您的正常使用，请升级至最新版本APP');
            Yii::app()->end();
        }

		if(isset($_GET['filter']))
			$this->restFilter = $_GET['filter'];

		if(isset($_GET['sort']))
			$this->restSort = $_GET['sort'];

		if(isset($_GET['limit']))
			$this->restLimit = $_GET['limit'];

		if(isset($_GET['offset']))
			$this->restOffset = $_GET['offset'];

		if(isset($_GET['scenario']))
			$this->restScenario = $_GET['scenario'];

        if(!empty($_POST))
        {
            $_POST['check_sign_str'] = $_POST;
        }
        Yii::import('application.modules.api.classes.AppCommon');

        #密码解密处理
        if(!empty($_REQUEST['security']))
        {
            if(!empty($_POST['password']))
            {
                $_POST['password'] = DesUtil::decrypt($_POST['password'],Yii::app()->c->AppDesKey);
            }

            if(!empty($_POST['oldPassword']))
            {
                $_POST['oldPassword'] = DesUtil::decrypt($_POST['oldPassword'],Yii::app()->c->AppDesKey);
            }

            if(!empty($_POST['oldpassword']))
            {
                $_POST['oldpassword'] = DesUtil::decrypt($_POST['oldpassword'],Yii::app()->c->AppDesKey);
            }

            if(!empty($_POST['newPassword']))
            {
                $_POST['newPassword'] = DesUtil::decrypt($_POST['newPassword'],Yii::app()->c->AppDesKey);
            }

            if(!empty($_POST['newpassword']))
            {
                $_POST['newpassword'] = DesUtil::decrypt($_POST['newpassword'],Yii::app()->c->AppDesKey);
            }

            if(!empty($_POST['paypassword']))
            {
                $_POST['paypassword'] = DesUtil::decrypt($_POST['paypassword'],Yii::app()->c->AppDesKey);
            }

            if(!empty($_REQUEST['paypassword']))
            {
                $pay_tmp = $_REQUEST['paypassword'];
                $_REQUEST['paypassword'] = DesUtil::decrypt($_REQUEST['paypassword'],Yii::app()->c->AppDesKey);
                if(empty($_REQUEST['paypassword']))
                {
                    $_REQUEST['paypassword'] = DesUtil::decrypt(strtr($pay_tmp,array(' '=>'+')),Yii::app()->c->AppDesKey);
                }
            }
        }

        //中文支持
        if(!empty($_SERVER['HTTP_DEVICE_NAME']))
        {
            $_SERVER['HTTP_DEVICE_NAME'] = urldecode($_SERVER['HTTP_DEVICE_NAME']);
        }
        if(!empty($_SERVER['HTTP_SYSTEM_NAME']))
        {
            $_SERVER['HTTP_SYSTEM_NAME'] = urldecode($_SERVER['HTTP_SYSTEM_NAME']);
        }

        //补全ios header 信息
        if($this->getDeviceOs()=='ios' && $_SERVER["HTTP_ITZ_CHANNEL"] == '')
        {
            $_SERVER["HTTP_ITZ_CHANNEL"] = 'AppStore_iOS';
        }

        
        //判断非法客户端
        if($_SESSION['wapapi'] !== 'wapapi2394' && $this->action->id != 'statement'){
            if(!(strripos($_SERVER['HTTP_USER_AGENT'],'Volley') || strripos($_SERVER['HTTP_USER_AGENT'],'CFNetwork'))){
                throw new CHttpException(401, 'You are not authorized to preform this action.');
            }
        }
        $version = Yii::app()->params['app_safe_version'];
        //加post验签
        if( $_POST && $this->versionControl($version) ){
            if(Yii::app()->request->getIsPostRequest() || Yii::app()->request->getIsDeleteRequest() || Yii::app()->request->getIsPutRequest()){
                $this->_checkSign(true);
            }
        }

        //加header解密
        if( $this->versionControl($version) && !in_array($request_uri,['mt/v5/user/registerOrLogin']) ){
        	// if(!empty($_SERVER['HTTP_TOKEN']))
	        // {
	        //     $_SERVER['HTTP_TOKEN'] = DesUtil::decrypt($_SERVER['HTTP_TOKEN'],Yii::app()->c->AppDesKey);
	        // }
//            if(!empty($_SERVER['HTTP_ITZ_LOCATION']))
//            {
//                $tmp = DesUtil::decrypt($_SERVER['HTTP_ITZ_LOCATION'],Yii::app()->c->AppDesKey);
//                $_SERVER['HTTP_ITZ_LOCATION'] = $tmp ? $tmp : $_SERVER['HTTP_ITZ_LOCATION'];
//            }
	        if(!empty($_SERVER['HTTP_ANDROIDID']))
	        {
	        	$tmp = DesUtil::decrypt($_SERVER['HTTP_ANDROIDID'],Yii::app()->c->AppDesKey);
	            $_SERVER['HTTP_ANDROIDID'] = $tmp ? $tmp : $_SERVER['HTTP_ANDROIDID'];
	        }
	        if(!empty($_SERVER['HTTP_IMEI']))
	        {
	        	$tmp = DesUtil::decrypt($_SERVER['HTTP_IMEI'],Yii::app()->c->AppDesKey);
	            $_SERVER['HTTP_IMEI'] = $tmp ? $tmp : $_SERVER['HTTP_IMEI'];

	        }
	        if(!empty($_SERVER['HTTP_MAC']))
	        {
	        	$tmp = DesUtil::decrypt($_SERVER['HTTP_MAC'],Yii::app()->c->AppDesKey);
	            $_SERVER['HTTP_MAC'] = $tmp ? $tmp : $_SERVER['HTTP_MAC'];
	        }
	        if(!empty($_SERVER['HTTP_IDFA']))
	        {
	        	$tmp = DesUtil::decrypt($_SERVER['HTTP_IDFA'],Yii::app()->c->AppDesKey);
	            $_SERVER['HTTP_IDFA'] = $tmp ? $tmp : $_SERVER['HTTP_IDFA'];
	        }
	        if(!empty($_SERVER['HTTP_OPEN_UDID']))
	        {
	        	$tmp = DesUtil::decrypt($_SERVER['HTTP_OPEN_UDID'],Yii::app()->c->AppDesKey);
	            $_SERVER['HTTP_OPEN_UDID'] = $tmp ? $tmp : $_SERVER['HTTP_OPEN_UDID'];
	        }
        }

        //统计 单独header
        if( $this->versionControl($version) ){
            if(!empty($_SERVER['HTTP_ITZ_LOCATION']))
            {
                $tmp = DesUtil::decrypt($_SERVER['HTTP_ITZ_LOCATION'],Yii::app()->c->AppDesKey);
                $_SERVER['HTTP_ITZ_LOCATION'] = $tmp ? $tmp : $_SERVER['HTTP_ITZ_LOCATION'];
            }
        }
        //监听APP所有请求
        Yii::trace(Yii::app()->request->url.':'.Yii::app()->request->getRawBody(),'apprequest');
		return parent::beforeAction($event);
	}

	public function onException($event)
	{
		if(!$this->developmentFlag && ($event->exception->statusCode == 500 || is_null($event->exception->statusCode)))
			$message = "系统繁忙，请稍后重试！";
		else
		{
			$message = $event->exception->getMessage();
			if($tempMessage = CJSON::decode($message))
				$message = $tempMessage;
		}

		$errorCode = (!isset($event->exception->statusCode) || is_null($event->exception->statusCode))? 500: $event->exception->statusCode;
		if ($errorCode == 401) {
			header('HTTP/1.0 401 Unauthorized', true);
            header('WWW-Authenticate: Basic realm="fake"');
            $this->echoJson(array(),$errorCode,$message);
		}elseif($errorCode == 403){
            header('HTTP/1.1 403 Forbidden', true);
            $this->echoJson(array(),$errorCode,$message);
        }else {
			$this->renderJson(array('code' => 100, 'info' => $message, 'data' => array('errorCode'=>$errorCode)));
		}
		
		$event->handled = true;
	}
 
	public function filters() 
	{
		$restFilters = array('HttpsOnly','restAccessRules+ restList restView restCreate restUpdate restDelete','blockCC');
		if(method_exists($this, '_filters'))
			return CMap::mergeArray($restFilters, $this->_filters());
		else
			return $restFilters;
	} 

 	/**
	 * @author Romina Suarez
	 *
	 * allows users to block any nonHttps request if they want their service to be safe
	 * If the attribute $httpsOnly is set in one of the controllers that extend ERestController,
	 * you can avoid a specific model from being using without a secure connection.
	 */
	public function filterHttpsOnly($c){			
		if ($this->httpsOnly){
			if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS']!='on'){
				Yii::app()->errorHandler->errorAction = '/' . $this->uniqueid . '/error';	
				throw new CHttpException(401, "You must use a secure connection");						
			}	
		}
		$c->run();
	}
	public function accessRules()
	{
		$restAccessRules = array(
			array(
				'allow',	// allow all users to perform 'index' and 'view' actions
				'actions'=>array('restList', 'restView', 'restCreate', 'restUpdate', 'restDelete', 'error'),
				'users'=>array('*'),
			)
		);

		if(method_exists($this, '_accessRules'))
			return CMap::mergeArray($restAccessRules, $this->_accessRules());
		else
			return $restAccessRules;
	}	

	/**
	 * Controls access to restfull requests
	 */ 
	public function filterRestAccessRules( $c )
	{
		Yii::app()->clientScript->reset(); //Remove any scripts registered by Controller Class
		Yii::app()->onException = array($this, 'onException'); //Register Custom Exception
		//For requests from JS check that a user is logged in and call validateUser
		//validateUser can/should be overridden in your controller.
		if( (!Yii::app()->user->isGuest && $this->validateAjaxUser($this->action->id))
            || ($_SESSION['wapapi'] == 'wapapi2394' && !empty($this->user_id)) ){
			$c->run(); 
        }else{
			Yii::app()->errorHandler->errorAction = '/' . $this->uniqueid . '/error';
			if(!(isset($_SERVER['HTTP_TOKEN'])) ) {
				// Error: Unauthorized
				throw new CHttpException(401, 'You are not authorized to preform this action.');
			}
            $token = $_SERVER['HTTP_TOKEN'];

			if ($_SERVER["HTTP_APIVERSION"] == "1.1") {

                #获得 用户 id
                $user_id = $this->getCacheDevices($token);

                #设置读取主库
                #Yii::app()->db->switchToMaster();
                #$devices = BaseCrudService::getInstance()->get("Devices", "`token`='".$token."'",0,1);
                #Yii::app()->db->switchToSlave();

				if (!empty($user_id)) {
                    $this->user_id = $user_id;
                }else{

                    $msg = '登录已失效，请重新登录！';
                    if($this->versionControl('1.8.0'))
                    {
                        //获取用户信息
                        $devices = BaseCrudService::getInstance()->get("Devices","",0,1,"",array("token"=>$token));
                        if($devices[0]['user_id'])
                        {
                            //获取正在登录此帐号的用户
                            $devices = BaseCrudService::getInstance()->get("Devices","",0,1,"",array(
                                "user_id"   => $devices[0]['user_id'],
                                "is_invalid"=>0,
                            ));
                            if(!empty($devices[0]))
                            {

                                $login_date = date('Y-m-d H:i:s',$devices[0]['expire_time']);
                                
                                $login_device_name = '其他设备';
                                $device_name = trim($devices[0]['device_name']);
                                if($device_name && mb_strlen($device_name, 'UTF-8')<30)
                                {
                                    $login_device_name = '设备“'.$device_name.'”';
                                }
                                header ( "Account-On-Other-Device: true" );
                                $msg = '您的账户于'.$login_date.'在'.$login_device_name.'登录。您可以进行如下操作：';
                            }
                        }
                    }
                    throw new CHttpException(401, $msg);
                }
			} else {
				$token = McryptUtil::decrypt($_SERVER['HTTP_TOKEN']);
	            $decode_token =  explode("@@",$token );
				if(count($decode_token)!= 2) {
					throw new CHttpException(401, '登录已失效，请重新登录！');
				}
	            $this->user_id = $decode_token[0];
				
			}
			
           	$c->run();
		}
	}	

    #会话 获取 用户 id
    public function getCacheDevices($token){
        $user_id = 0;

        if(empty($token))
        {
            return $user_id;
        }
        
        $user_id = RedisService::getInstance()->get('AppToken_'.$token);
        if($user_id)
        {
            return $user_id;
        }


        #设置读取主库
        Yii::app()->db->switchToMaster();
        $devices = BaseCrudService::getInstance()->get("Devices", "`token`='".$token."' and `is_invalid`='0'",0,1);
        Yii::app()->db->switchToSlave();
        if(!empty($devices[0]['user_id']))
        {
            $user_id = $devices[0]['user_id'];
            $this->setCacheDevices($token,$user_id);
        }
        return $user_id;
    }

    #会话 设置 用户 id
    public function setCacheDevices($token,$user_id='',$endtime=''){
        if(!empty($token))
        {
            #默认有效期
            if($endtime=='')
            {
                $endtime = 86400;
            }

            RedisService::getInstance()->set( 'AppToken_'.$token, $user_id, $endtime );
        }
    }


	/**
	 * Custom error handler for restfull Errors
	 */ 
	public function actionError()
	{
		if($error=Yii::app()->errorHandler->error)
		{
			//print_r($error); exit();
			if(!Yii::app()->request->isAjaxRequest)
				$this->HTTPStatus = $this->getHttpStatus($error['code'], 'C500INTERNALSERVERERROR');
			else if(!$this->developmentFlag)
			{
				if($error['code'] == 500)
					$error['message'] = '系统繁忙，请稍后重试！';
			}
			$this->renderJson(array('code' => 100, 'info' => $error['message'], 'data' => array('errorCode'=>$error['code'])));
		}
	}

	/**
	 * Get HTTP Status Headers From code
	 */ 
	public function getHttpStatus($statusCode, $default='C200OK')
	{
		$httpStatus = new EHttpStatus($statusCode);
		if ($httpStatus->message) {
			return $httpStatus->__toString();
		} else { //Backward compatibility.
			switch ($statusCode)
			{
				case '200':
					return self::C200OK;
					break;
				case '201':
					return self::C201CREATED;
					break;
				case '401':
					return self::C401UNAUTHORIZED;
					break;
				case '404':
					return self::C404NOTFOUND;
					break;
				case '406':
					return self::C406NOTACCEPTABLE;
					break;
				case '500':
					return self::C500INTERNALSERVERERROR;
					break;
				default:
					return self::$default;
			}
		}
	}

	protected function getNestedRelations()
	{
		$nestedRelations = array();
		if(!is_array($this->nestedModels) && $this->nestedModels == 'auto')
		{
			foreach($this->model->metadata->relations as $rel=>$val)
			{
				$className = $val->className;
				if(!is_array($className::model()->tableSchema->primaryKey))
					$nestedRelations[] = $rel;
			}
			return $nestedRelations;
		}
		else if(!is_array($this->nestedModels) && $this->nestedModels === false)
			return $nestedRelations;
		else if(is_array($this->nestedModels))
			return $this->nestedModels;
			
		return $nestedRelations;
	} 

 /**
	****************************************************************************************** 
	******************************************************************************************
	* Actions that are triggered by RESTFull requests
	* To change their default behavior 
	* you should override "doRest..." Methods in the controller 
	* and leave these actions as is
	******************************************************************************************
	******************************************************************************************
	 */

	/**
	 * Renders list of data associated with controller as json
	 */
	public function actionRestList() 
	{
		$this->doRestList();
	}
	
	/**
	 * Renders View of record as json
	 * Or Custom method
	 */ 
	public function actionRestView($id, $var=null, $var2=null)
	{
		if($this->isPk($id) && is_null($var))
			$this->doRestView($id);
		else
		{
			if($this->isPk($id) && !is_null($var) && is_null($var2))
			{
				if($this->validateSubResource($var))
					$this->doRestViewSubResource($id, $var);
				else
					$this->triggerCustomRestGet(ucFirst($var), array($id));
			}
			else if($this->isPk($id) && !is_null($var) && !is_null($var2))
			{
				if($this->validateSubResource($var))
					$this->doRestViewSubResource($id, $var, $var2);
				else
					$this->triggerCustomRestGet(ucFirst($var), array($id, $var2));
			}
			else
			{
				//if the $id is not numeric and var + var2 are not set
				//we are assume that the client is attempting to call a custom method
				//There may optionaly be a second param `$var` passed in the url
				$this->triggerCustomRestGet(ucFirst($id), array($var, $var2));
			}
		}
	}

	/**
	 * Updated record
	 */ 
	public function actionRestUpdate($id, $var=null, $var2=null)
	{
		$this->HTTPStatus = $this->getHttpStatus('200');
			
		if($this->isPk($id))
		{
			if(is_null($var))
				$this->doRestUpdate($id, $this->data());
			else if (is_null($var2))
				$this->triggerCustomRestPut($var, array($id));
			else if(!is_null($var2))
			{
				if($this->validateSubResource($var))
					$this->doRestUpdateSubResource($id, $var, $var2);
				else
					$this->triggerCustomRestPut($var, array($id, $var2));
			} 
		}
		else
			$this->triggerCustomRestPut($id, array($var, $var2));
	}
	

	/**
	 * Creates new record
	 */ 
	public function actionRestCreate($id=null, $var=null) 
	{
		$this->HTTPStatus = $this->getHttpStatus('201');

		if(!$id) 
		{
			$this->doRestCreate($this->data());
		}
		else
		{
			//we can assume if $id is set and var is not a subresource
			//then the user is trying to call a custom method
			$var = 'doCustomRestPost' . ucfirst($id);
			if(method_exists($this, $var))
				$this->$var($this->data());
			else if($this->isPk($var))
				$this->doRestCreate($this->data());
			else
				throw new CHttpException(500, 'Method or Sub-Resource does not exist 1.');
		}
	}

	/**
	 * Deletes record
	 */ 
	public function actionRestDelete($id, $var=null, $var2=null)
	{
		if($this->isPk($id))
		{
			if(is_null($var))
				$this->doRestDelete($id);
			else if(!is_null($var2))
			{
				if($this->validateSubResource($var, $var2))
					$this->doRestDeleteSubResource($id, $var, $var2); //Looks like we are trying to delete a subResource
				else
					$this->triggerCustomDelete($var, array($id, $var2));
			}
			else 
				$this->triggerCustomDelete($var, array($id));
		}
		else
		{
			$this->triggerCustomDelete($id, array($var, $var2));
		}
	}

	 /**
	****************************************************************************************** 
	******************************************************************************************
	* Helper functions for processing Rest data 
	******************************************************************************************
	******************************************************************************************
	 */
	
	/**
	 * Takes array and renders Json String
	 */ 
	protected function renderJson($data) {
		$this->layout = 'application.lib.extensions.RESTFullYii.views.layouts.json';
		$this->render('application.lib.extensions.RESTFullYii.views.api.output', array('data'=>$data));
	}


	/**
	 * Get data submitted by the client
	 */ 
	public function data() 
	{
		$request = $this->requestReader->getContents();
		if ($request) {
			return CJSON::decode($request);
		}
		return false;
	}

	/**
	 * Returns the model associated with this controller.
	 * The assumption is that the model name matches your controller name
	 * If this is not the case you should override this method in your controller
	 */ 
	public function getModel() 
	{
		if ($this->model === null) 
		{
			$modelName = str_replace('Controller', '', get_class($this)); 
			$this->model = new $modelName;
		}
		$this->_attachBehaviors($this->model);

		if(!is_null($this->restScenario)) {
			$this->model->scenario = $this->restScenario;
		}

		return $this->model;
	}

	/**
	* Helper for loading a single model
	*/
	protected function loadOneModel($id, $nested=true) 
	{
		if($nested)
			return $this->getModel()->with($this->nestedRelations)->findByPk($id);
		else
			return $this->getModel()->findByPk($id);
	}

	
	//Updated setModelAttributes to allow for related data to be set.
	private function setModelAttributes($model, $data)
	{
		foreach($data as $var=>$value) 
		{
			if(($model->hasAttribute($var) || isset($model->metadata->relations[$var])) && !in_array($var, $this->restrictedProperties)) {
				$model->$var = $value;
			}
			else {
				throw new CHttpException(406, 'Parameter \'' . $var . '\' is not allowed for model (' . get_class($model) . ')');
				
			}
		}
		
		return $model;
	}
	
	/**
	 * Helper for saving single/multiple models 
	 */ 
	protected function saveModel($model, $data) {
		$return_array = true;
		if (empty($data)) {
				$this->HTTPStatus = $this->getHttpStatus(406);
				throw new CHttpException(406, 'Model could not be saved as empty data.');
		}

		if (!isset($data[0])) {
			$models[] = $this->setModelAttributes($model, $data);
			$return_array = false;
		}
		else {
				for ($i = 0; $i < count($data); $i++) {
						$models[$i] = $this->setModelAttributes($this->getModel(), $data[$i]);
						$this->model = null;
				}
		}

		for ($cnt = 0; $cnt < count($models); $cnt++) {
				$this->_attachBehaviors($models[$cnt]);
				if ($models[$cnt]->validate()) {
						if (!$models[$cnt]->save()) {
								$this->HTTPStatus = $this->getHttpStatus(406);
								throw new CHttpException(406, 'Model could not be saved');
						}
						else {
							$ids[] = $models[$cnt]->{$models[$cnt]->tableSchema->primaryKey};
						}
				}else {
						$message = CJSON::encode(array('error' => 'Model could not be saved as validation failed.',
												'validation' => $models[$cnt]->getErrors()));

						$this->HTTPStatus = $this->getHttpStatus(406);
						throw new CHttpException(406, $message);
				}
		}
		if($return_array) {
			return $this->getModel()->with($this->getNestedRelations())->findAllByPk($ids);
		}
		else
			return $this->getModel()->with($this->getNestedRelations())->findAllByPk($ids[0]);
	}

    //Attach helper behaviors
	public function _attachBehaviors($model)
	{
		//Attach this behavior to help saving nested models
		if(!array_key_exists('EActiveRecordRelationBehavior', $model->behaviors()))
			$model->attachBehavior('EActiveRecordRelationBehavior', new EActiveRecordRelationBehavior());

		//Attach this behavior to help outputting models and their relations as arrays
		if(!array_key_exists('MorrayBehavior', $model->behaviors()))
			$model->attachBehavior('MorrayBehavior', new MorrayBehavior());

		if(!array_key_exists('ERestHelperScopes', $model->behaviors()))
			$model->attachBehavior('ERestHelperScopes', new ERestHelperScopes());

		return true;
	}


	/**
	 *  Convert list of models or single model to array
	 */ 
	public function allToArray($models)
	{
		$options = array('scenario' => $this->scenario);
		
		if(is_array($models))
		{
			$results = array();
			foreach($models as $model)
			{
				$this->_attachBehaviors($model);
				$results[] = $model->toArray($options);
			}
				return $results;
		}
		else if($models != null)
		{
			$this->_attachBehaviors($models);
			return $models->toArray($options);
		}
		else
			return array();
	}

	public function triggerCustomRestGet($id, $vars=array())
	{
		$method = 'doCustomRestGet' . ucfirst($id);
		if(method_exists($this, $method))
			$this->$method($vars);
		else
			throw new CHttpException(500, 'Method or Sub-Resource does not exist 2.');
	}

	public function triggerCustomRestPut($method, $vars=array())
	{
		$method = 'doCustomRestPut' . ucfirst($method);
		
		if(method_exists($this, $method))
		{
			if(count($vars) > 0)
				$this->$method($this->data(), $vars);
			else
				$this->$method($this->data());
		}
		else
    		{
        		throw new CHttpException(500, 'Method or Sub-Resource does not exist 3.');
    		}	
	}

	public function triggerCustomDelete($methodName, $vars=array())
	{
		$method = 'doCustomRestDelete' . ucfirst($methodName);
		if(method_exists($this, $method))
			$this->$method($vars);
		else
			throw new CHttpException(500, 'Method or Sub-Resource does not exist 4.');
	}

	public function validateSubResource($subResourceName, $subResourceID=null)
	{
		if(is_null($relations = $this->getModel()->relations()))
			return false;
		if(!isset($relations[$subResourceName]))
			return false;
		if($relations[$subResourceName][0] != CActiveRecord::MANY_MANY)
			return false;
		if(!is_null($subResourceID))
			return filter_var($subResourceID, FILTER_VALIDATE_INT) !== false;

		return true;
	}

	public function getSubResource($subResourceName)
	{
		$relations = $this->getModel()->relations();
		return $this->getModel()->parseManyManyFk($subResourceName, $relations[$subResourceName]);
	}

	/**
	****************************************************************************************** 
	******************************************************************************************
	* OVERRIDE THE METHODS BELOW IN YOUR CONTROLLER TO REMOVE/ALTER DEFAULT FUNCTIONALITY
	******************************************************************************************
	******************************************************************************************
	 */
	
	/**
	 * Override this function if your model uses a non Numeric PK.
	 */
	public function isPk($pk) 
	{
		return filter_var($pk, FILTER_VALIDATE_INT) !== false;
	} 

	/**
	 * You should override this method to provide stronger access control 
	 * to specific restfull actions via AJAX
	 */ 
	public function validateAjaxUser($action)
	{
		return false;
	}

	public function outputHelper($message, $results, $totalCount=0, $model=null)
	{
		if(is_null($model))
			$model = lcfirst(get_class($this->model));
		else
			$model = lcfirst($model);	

		$this->renderJson(array(
			'code'=>true, 
			'info'=>$message, 
			'data'=>array(
				'totalCount'=>$totalCount, 
				$model=>$this->allToArray($results)
			)
		));
	}

	/**
	 * This is broken out as a separate method from actionRestList 
	 * To allow for easy overriding in the controller
	 * and to allow for easy unit testing
	 */ 
	public function doRestList()
	{
		$this->outputHelper( 
			'Records Retrieved Successfully', 
			$this->getModel()->with($this->nestedRelations)
				->filter($this->restFilter)->orderBy($this->restSort)
				->limit($this->restLimit)->offset($this->restOffset)
			->findAll(),
			$this->getModel()
				->with($this->nestedRelations)
				->filter($this->restFilter)
			->count()
		);
	}
	
	/**
	 * This is broken out as a separate method from actionRestView
	 * To allow for easy overriding in the controller
	 * adn to allow for easy unit testing
	 */ 
	public function doRestViewSubResource($id, $subResource, $subResourceID=null)
	{
		$subResourceRelation = $this->getModel()->getActiveRelation($subResource);
		$subResourceModel = new $subResourceRelation->className;
		$this->_attachBehaviors($subResourceModel);

		if(is_null($subResourceID))
		{
			$modelName = get_class($this->model);
			$newRelationName = "_" . $subResourceRelation->className . "Count";
			$this->getModel()->metaData->addRelation($newRelationName, array(
        $modelName::STAT, $subResourceRelation->className, $subResourceRelation->foreignKey
			));

			$model = $this->getModel()->with($newRelationName)->findByPk($id);
			$count = $model->$newRelationName;

			$results = $this->getModel()
				->with($subResource)
				->limit($this->restLimit)
				->offset($this->restOffset)
			->findByPk($id, array('together'=>true));

			$results = $results->$subResource;

			$this->outputHelper(
				'Records Retrieved Successfully', 
				$results,
				$count,
				$subResourceRelation->className
			);
		}
		else
		{		
			$results = $this->getModel()
				->with($subResource)
				->findByPk($id, array('condition'=>"$subResource.id=$subResourceID"));

			if(is_null($results))
			{
				$this->HTTPStatus = 404;
				throw new CHttpException('404', 'Record Not Found');
			}

			$this->outputHelper(
				'Record Retrieved Successfully', 
				$results->$subResource,
				1,
				$subResourceRelation->className
			);
		}
	}

	 /**
	 * This is broken out as a separate method from actionRestView
	 * To allow for easy overriding in the controller
	 * adn to allow for easy unit testing
	 */ 
	public function doRestView($id)
	{
		$model = $this->loadOneModel($id);
		
		if(is_null($model))
		{
			$this->HTTPStatus = 404;
				throw new CHttpException('404', 'Record Not Found');
		}
	
		$this->outputHelper(
			'Record Retrieved Successfully', 
			$model,
			1
		);
	}

	/**
	 * This is broken out as a separate method from actionResUpdate 
	 * To allow for easy overriding in the controller
	 * and to allow for easy unit testing
	 */ 
    public function doRestUpdate($id, $data) {
			$model = $this->loadOneModel($id, false);
			if (is_null($model)) {
					$this->HTTPStatus = $this->getHttpStatus(404);
					throw new CHttpException(404, 'Record Not Found');
			} else {
					$model = $this->saveModel($this->loadOneModel($id,false), $data);
					$this->outputHelper(
						'Record Updated', $this->loadOneModel($id), 1
					);
			}
    }
	
	/**
	 * This is broken out as a separate method from actionRestCreate 
	 * To allow for easy overriding in the controller
	 * and to allow for easy unit testing
	 */ 
	public function doRestCreate($data) 
	{
		$models = $this->saveModel($this->getModel(), $data);
		//$this->renderJson(array('success'=>true, 'message'=>'Record(s) Created', 'data'=>array($models)));
		$this->outputHelper(
			'Record(s) Created',
			$models,
			count($models)
		);
	}

	/**
	 * This is broken out as a separate method from actionRestCreate 
	 * To allow for easy overriding in the controller
	 * and to allow for easy unit testing
	 */
	public function doRestUpdateSubResource($id, $subResource, $subResourceID)
	{
		list($relationTable, $fks) = $this->getSubResource($subResource);
		if($this->saveSubResource($id, $subResourceID, $relationTable, $fks) > 0)
		{
			$this->renderJson(
				array('code'=> 0 , 'info'=>'Sub-Resource Added', 'data'=>array(
					$fks[0] => $id,
					$fks[1] => $subResourceID,
				))
			);
		}
		else
			throw new CHttpException('500', 'Could not save Sub-Resource');
		
	}

	public function saveSubResource($pk, $fk, $relationTable, $fks)
	{
		return $this->getModel()->dbConnection->commandBuilder->createInsertCommand($relationTable, array(
			$fks[0] => $pk,
			$fks[1] => $fk,
		))->execute();
	}
	
	/**
	 * This is broken out as a separate method from actionRestDelete 
	 * To allow for easy overriding in the controller
	 * and to allow for easy unit testing
	 */ 
	public function doRestDelete($id) {
        $model = $this->loadOneModel($id);
        if (is_null($model)) {
            $this->HTTPStatus = $this->getHttpStatus(404);
            throw new CHttpException(404, 'Record Not Found');
        } else {
            if ($model->delete())
                $data = array('code' => 0, 'info' => 'Record Deleted', 'data' => array('id' => $id));
            else {
                $this->HTTPStatus = $this->getHttpStatus(406);
                throw new CHttpException(406, 'Could not delete model with ID: ' . $id);
            }
            $this->renderJson($data);
        }
    }
	
	/**
	 * This is broken out as a separate method from actionRestDelete 
	 * To allow for easy overriding in the controller
	 * and to allow for easy unit testing
	 */ 
	public function doRestDeleteSubResource($id, $subResource, $subResourceID)
	{
		list($relationTable, $fks) = $this->getSubResource($subResource);
		$criteria=new CDbCriteria();
		$criteria->addColumnCondition(array(
			$fks[0]=>$id,
			$fks[1]=>$subResourceID
		));
		if($this->getModel()->dbConnection->commandBuilder->createDeleteCommand($relationTable, $criteria)->execute())
		{
			$data = array('code'=>0, 'info'=>'Record Deleted', 'data'=>array(
				$fks[0]=>$id,
				$fks[1]=>$subResourceID
			));
		}
		else
		{
			throw new CHttpException(406, 'Could not delete model with ID: ' . array(
				$fks[0]=>$id,
				$fks[1]=>$subResourceID
			));
		}
		
		$this->renderJson($data);
	}

	public function setRequestReader($requestReader) 
	{
		$this->requestReader = $requestReader;
	}

	public function setModel($model) 
	{
		$this->model = $model;
	}
    
    //获得签名key
    protected function _getSignSalt(){

        $iosSignSalt = 'c86bae7c1edf5ed29fb0';
        $androidSignSalt = 'a2a3690ead8760b58927d734f5637eb7';

        //使用新的 key
        if($this->versionControl('2.1.0'))
        {
            $iosSignSalt = 'ae304190075788b591e1957cea3f285d';//echo md5('iositZ123$%^2.1.0_sadfl8');
            $androidSignSalt = 'f847f0f417b3b1b8a06b3e00da6d0102';//echo md5('androiditZ123$%^2.1.0_sd6d7fa*^');
        }
        elseif($this->versionControl('2.0.0'))
        {
            $iosSignSalt = '5ce57c21b4ff95ceb1d6d8839cdeeefd';//echo md5('iositZ123$%^2.0.0_df7(d');
            $androidSignSalt = 'eca45c0d277ee89679a9f2b32bdb6439';//echo md5('androiditZ123$%^2.0.0_asd8ffak');
        }
        elseif($this->versionControl('1.8.0'))
        {
            $iosSignSalt = '89b9c733e7e2a2bb0743849b59f1d864';//echo md5('iositZ123$%^1.8.0_dikd6d8');exit;
            $androidSignSalt = '40a2683706f962a205951b6e19068b15';//echo md5('androiditZ123$%^1.8.0_d8d6d8');exit;
        }
        elseif($this->versionControl('1.7.1'))
        {
            $iosSignSalt = 'ea58e8d25da9339af924c5dbcc00e9eb';//echo md5('iositZ123$%^1.7.1_ds7678d');exit;
            $androidSignSalt = '4f8a4d2194200e63bd63731fcd45fb4b';//echo md5('androiditZ123$%^1.7.1_ds7678d');exit;
        }
        elseif($this->versionControl('1.7.0'))
        {
            
            $iosSignSalt = '339a9353c352040a58120935dca2c4c7';//echo md5('iositZ123$%^1.7.0');exit;
            $androidSignSalt = 'b392fe42ab402e70873705b5265011a9';//echo md5('androiditZ123$%^1.7.0');exit;
        }
        elseif($this->versionControl('1.6.3'))
        {
            
            $iosSignSalt = 'fef0f6e6b8503961ec6e4cde9d71fa7b';//echo md5('iositZ123$%^1.6.3');exit;
            $androidSignSalt = 'ff6de1559bb94aa27f67f851d080a6e0';//echo md5('androiditZ123$%^1.6.3');exit;
        }

        $key = $this->getDeviceOs()=='ios' ? $iosSignSalt : $androidSignSalt;
        return $key;
    }
    
    //APP接口数据验证签名
    protected function _checkSign($forced = false) {
		if ($_SERVER['HTTP_APIVERSION'] == '1.1' || $forced) {
			$signSalt = $this->_getSignSalt();
            
            $arr = $_POST['check_sign_str'];
            unset($_POST['check_sign_str']);
            $str = array();
            foreach ($arr as $key => $value) {
            	if ($key == 'sign') {
                    continue;
                }
                if(is_array($value)){
                    foreach($value as $k=>$v){
                        $childkey = $key.'['.$k.']';
                        $str[$childkey] = $childkey . '=' . $v;
                    }
                }else{
                    $str[$key] = $key . '=' . $value;
                }
            }

            ksort($str);
            $str = implode('&', $str);
            $sign = strtoupper(md5($str . $signSalt));

            if ($sign !== strtoupper($arr['sign'])) {
                Yii::log("checksignError:str:{$str}--sign:{$sign}--appsign:{$arr['sign']}", CLogger::LEVEL_WARNING, __METHOD__ );
            	throw new CHttpException(401, 'Sign error');
            }
		}
	}

    //获取客户端系统 转换成数字
    protected function getDeviceOsId(){

        $result = 0;
        
        $arr = array(
            'pc'        =>6,
            'android'   =>5,
            'ios'       =>4,
            'wap'       =>3
            );

        $os = $this->getDeviceOs();
        if($os)
        {
            if(isset($arr[$os]))
            {
                $result = $arr[$os];
            }
        }

        return $result;
    }

    //通过head 获取设备唯一ID用来判断唯一设备（设备锁功能上线时添加）
    public function getDeviceUniqueId(){
        $result = array(
            'device_id'     =>'',//主
            'device_id_ext' =>''//辅
            );

        //设备管理 防止传输过程中加密未解密 导致设备管理记录问题
        if(!empty($_SERVER['HTTP_ANDROIDID']))
        {
            $tmp = DesUtil::decrypt($_SERVER['HTTP_ANDROIDID'],Yii::app()->c->AppDesKey);
            $_SERVER['HTTP_ANDROIDID'] = $tmp ? $tmp : $_SERVER['HTTP_ANDROIDID'];
        }
        if(!empty($_SERVER['HTTP_IMEI']))
        {
            $tmp = DesUtil::decrypt($_SERVER['HTTP_IMEI'],Yii::app()->c->AppDesKey);
            $_SERVER['HTTP_IMEI'] = $tmp ? $tmp : $_SERVER['HTTP_IMEI'];

        }
        if(!empty($_SERVER['HTTP_IDFA']))
        {
            $tmp = DesUtil::decrypt($_SERVER['HTTP_IDFA'],Yii::app()->c->AppDesKey);
            $_SERVER['HTTP_IDFA'] = $tmp ? $tmp : $_SERVER['HTTP_IDFA'];
        }
        if(!empty($_SERVER['HTTP_OPEN_UDID']))
        {
            $tmp = DesUtil::decrypt($_SERVER['HTTP_OPEN_UDID'],Yii::app()->c->AppDesKey);
            $_SERVER['HTTP_OPEN_UDID'] = $tmp ? $tmp : $_SERVER['HTTP_OPEN_UDID'];
        }

        if($this->getDeviceOs()=='ios')
        {
            $result['device_id']        = (string)$_SERVER["HTTP_IDFA"];
            $result['device_id_ext']    = (string)$_SERVER["HTTP_OPEN_UDID"];
        }
        else
        {
            $result['device_id']        = (string)$_SERVER["HTTP_ANDROIDID"];
            $result['device_id_ext']    = (string)$_SERVER["HTTP_IMEI"];
        }
        return $result;
    }

    #版本控制
    public function versionControl( $version, $type=true )
    {
        if(empty($_SERVER["HTTP_CLIENT_VERSION"]) || empty($version))
        {
            return false;
        }

        #将版本号 处理成整数
        $v          = $this->formatVersion($_SERVER["HTTP_CLIENT_VERSION"]);
        $version    = $this->formatVersion($version);

        if($type)
        {
            if($v>=$version)
            {
                return true;
            }
        }
        else
        {
            if($v<=$version)
            {
                return true;
            }
        }
        return false;
    }

    #格式化版本号
    public function formatVersion($varsion){
        $arr = explode('.',$varsion);
        if(empty($arr[0])) $arr[0] = 0;
        if(empty($arr[1])) $arr[1] = 0;
        if(empty($arr[2])) $arr[2] = 0;

        $new_version  = intval($arr[0]);
        $new_version .= substr(str_pad(intval($arr[1]), 3, 0,STR_PAD_LEFT),0,3);
        $new_version .= substr(str_pad(intval($arr[2]), 3, 0,STR_PAD_LEFT),0,3);
        return intval($new_version);
    }

    #获得客户端 APP 版本号
    public function getAppVersion(){
        $var = $_SERVER["HTTP_CLIENT_VERSION"];
        $arr = explode('.',$var);
        if(empty($arr[0])) $arr[0] = 0;
        if(empty($arr[1])) $arr[1] = 0;
        if(empty($arr[2])) $arr[2] = 0;

        return $arr[0].'.'.$arr[1].'.'.$arr[2];
    }

    #判断用户是否登录
    public function getUserId(){

        $user_id = 0;
        if(isset($_SERVER['HTTP_TOKEN'])){
            $token = $_SERVER['HTTP_TOKEN'];
            if ($_SERVER["HTTP_APIVERSION"] == "1.1") {
                #$devices = BaseCrudService::getInstance()->get("Devices", "", 0, 1, "", array("token"=> $token));
                #$user_id = $devices[0]["user_id"];
                $user_id = $this->getCacheDevices($token);
            } else {
                $token = McryptUtil::decrypt($token);
                $decode_token =  explode("@@",$token );
                $user_id = $decode_token[0];
            }
        }

        return $user_id;
    }
    public function isLicaiApp(){
        if($this->getDeviceOs()=='ios' && isset($_SERVER['HTTP_IOS_BUFFER']) && $_SERVER['HTTP_IOS_BUFFER'] == "BUFFER-1" && isset($_SERVER['HTTP_ITZ_CHANNEL']) && ($_SERVER['HTTP_ITZ_CHANNEL'] == "iOS_licaiban" || $_SERVER['HTTP_ITZ_CHANNEL'] == "iOS-licaiban") ){
            return true;
        }
        return false;
    }

    public function echoJson($data=array(),$code=0,$info="",$plain_flag=false,$type="json"){

        #处理审计日志
        $this->auditLogAdd($code,$info);

        if(isset($_REQUEST['data_type']) && $_REQUEST['data_type'] =="jsonp" ){
            $this->echoJsonp($data,$code,$info);return;
        }
        if($type=="jsonp"){
            $this->echoJsonp($data,$code,$info);return;
        }
        if($plain_flag){
            if(strpos(isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '', 'application/json') !== false){
                header('Content-type:text/plain; charset=utf-8');
            }
        }else{
            header ( "Content-type:application/json; charset=utf-8" );
        }

        // 跨域设置
        $this->addAccessControlAllow();

        $data = ArrayUtil::getArray($data);
        $res["data"] = $data ? $data : (object)$data;
        $res['code'] = intval($code);
        $res['info'] = $info;

        echo json_encode ( $res );
    }
}