<?php
/**
 * Vfs 接口
 */

namespace libs\vfs;

interface IVfsEngine {
	public function read($filePath, $filename);
	public function write($filePath, $filename, $content);
}