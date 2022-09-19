<?php
/**
 * author:
 * 注意本插件只支持以下系统环境：
 * Linux、Uinux
 */
class LinuxZip {
	private $savePath = null;
	private $saveName = null;
	private $saveType = null;
	private $savePassword  = null;
	private $target = null;
	private $exportPath = null;
	private $inputFile = null;
	private $exportPassword = null;
	public $path = '';
	public function setSavePath($path) {
		$this->savePath = $path;
		return $this;
	}
	public function setSaveName($name) {
		$this->saveName = $name;
		return $this;
	}
	public function setSaveType($type) {
		$this->saveType = $type;
		return $this;
	}
	public function setSavePassword($password) {
		$this->savePassword = $password;
		return $this;
	}
	public function setTarget($target) {
		$this->target = $target;
		return $this;
	}
	public function setExportPath($exportPath) {
		$this->exportPath = $exportPath;
		return $this;
	}
	public function setInputFile($inputFile) {
		$this->inputFile = $inputFile;
		return $this;
	}
	public function setExportPassword($exportPassword) {
		$this->exportPassword = $exportPassword;
		return $this;
	}
	public function release() {
		$params = [];
		$params[] = 'unzip';
		$params[] = '-q';
		$params[] = '-n';
		if($this->exportPath == null) throw new Exception("exportPath cannot be empty");
		if($this->inputFile == null) throw new Exception("inputFile cannot be empty");
		if($this->exportPassword != null) {
			$params[] = '-P';
			$params[] = $this->exportPassword;
		}
		if($this->inputFile != null) {
			$params[] = $this->inputFile;
		}
		if($this->exportPath != null) {
			$params[] = '-d';
			$params[] = $this->exportPath;
		}
		$cmd = implode(' ', $params);
		exec($cmd, $result);
		return $result;
	}
	public function make() {
		$params = [0 => ''];
		$params[] = 'zip';
		$params[] = '-q';
		$params[] = '-r';
		if($this->savePath == null) throw new Exception("Path cannot be empty");
		if($this->saveName == null) throw new Exception("Name cannot be empty");
		if($this->target == null) throw new Exception("Target cannot be empty");
		if($this->savePassword != null) {
			$params[] = '-P';
			$params[] = $this->savePassword;
		}
		if($this->saveName != null) {
			if(!$this->saveType) throw new Exception("Type cannot be empty");
			$params[] = $this->path = $this->savePath . $this->saveName . $this->saveType;
		}
		if($this->target != null) {
			$params[0] = 'cd ' . dirname($this->target) . ';';
			$params[] = basename($this->target);
		}
		$cmd = implode(' ', $params);
		$cmd .= ';';
		exec($cmd, $result);
		return $result;
	}
}