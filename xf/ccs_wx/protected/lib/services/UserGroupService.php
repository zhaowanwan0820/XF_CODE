<?php
/**
* dsphper
*/
class UserGroupService extends ItzInstanceService
{

	public function find($condition = [], $field = []) {
		return UserGroupMongo::model()->find($condition, $field);
	}
	public function findAllTask($condition, $field = []) {
		return $this->ObjectToArray($this->find($condition, $field));
	}
	public function findByPk($_id, $field = []) {
		return $this->ObjectToArray($this->find(['_id' => new MongoId($_id)], $field));
	}
	public function count($condition) {
		return UserGroupMongo::model()->count($condition);
	}
	private function ObjectToArray($object) {
		$list = [];
		foreach ($object as $key => $value) {
			$list[$key] = $value->getAttributes();
		}
		return $list;
	}
	public function uploadFileToOss($file) {
		return Yii::app()->oss->putObject('itztest1', basename($field, '.zip'), fopen($file, 'r'));
	}
	public function dowloadOssFileToLocal($filename, $time = null) {
		try {
			$buffer = Yii::app()->oss->getObject(Yii::app()->oss->bucket, 'UserGroup/' . date('Y/m/d/', $time) . $filename);
		} catch (Exception $e) {
			$buffer = Yii::app()->oss->getObject(Yii::app()->oss->bucket, $filename);
		}
		return $buffer;
	}
	public function getTaskFileDir($id) {
        Yii::import('itzlib.plugins.LinuxZip.*');
		$fileName = '';
		$result = current($this->findByPk($id, ['_id', 'password', 'status', 'addtime']));
		$filename = current((Array)$result['_id']);
		$password = $result['password'];
		$addtime = $result['addtime'];
		if($result['status'] != 3) {
			return false;
		}
		$fileBuffer = $this->dowloadOssFileToLocal($filename, $addtime);
		$filePath = '/tmp/UserGroup/' . $filename . DIRECTORY_SEPARATOR . $filename . '.zip';
		if(!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0777, true);
		if($fileBuffer) {
			$flag = file_put_contents($filePath, $fileBuffer);
			$LinuxZip = new LinuxZip();
			$LinuxZip->setExportPath(dirname($filePath));
			$LinuxZip->setInputFile($filePath);
			$LinuxZip->setExportPassword($password);
			$LinuxZip->release();
			return dirname($filePath);
		} else {
			return false;
		}
	}
	public function putFileToOss($file, $key) {
		return Yii::app()->oss->putObject(Yii::app()->oss->bucket, $key, file_get_contents($file));
	}
}