<?php
/**
 * FileLogger class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace libs\logging;

use libs\base\IComponent;

/**
 * 使用文本文件存储的日志
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class FileLogger extends AbstractLogger implements IComponent
{
    /**
     * 当前日志文件的路径
     *
     * @var string
     **/
    public $file;

    /**
     * 日志文件的最大大小(KB), 超过此大小自动切分
     *
     * @var integer
     **/
    public $max_size = 2048; //kb

    /**
     * 日志格式
     *
     * @var string
     **/
    public $format = "{time} {level}: {message}\n";

    /**
     * 时间格式
     *
     * @var string
     **/
    public $time_format = '[ c ]';

    /**
     * 日志目录权限, 仅当指定的日志目录不存在第一次自动创建时生效
     *
     * @var integer
     **/
    public $dir_mode = 0775;

    /**
     * 最大切分日志文件个数，超过此数量的旧日志将被丢弃
     *
     * @var integer
     **/
    public $max_files = 10;

    /**
     * 初始化日志目录以及文件
     *
     * @return void
     **/
    public function init(){
        if ($this->file === null) {
            throw new \Exception('the log file path does not set');
        }
        $path = dirname($this->file);
        if (!is_dir($path)) {
            @mkdir($path, $this->dir_mode, true);
        }
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if (@filesize($this->file) > $this->max_size * 1024) {
            $this->rotateFiles();
        }
        $message = $this->interpolate($message, $context);
        $message = strtr($this->format, array(
            '{time}'    => date($this->time_format),
            '{level}'   => $level,
            '{message}' => $message,
        ));
        error_log($message, 3, $this->file);
    }

    /**
     * 进行日志切分
     *
     * @return void
     **/
    protected function rotateFiles()
    {
        $file = $this->file;
        for ($i = $this->max_files; $i > 0; --$i) {
            $rotateFile = $file . '.' . $i;
            if (is_file($rotateFile)) {
                // suppress errors because it's possible multiple processes enter into this section
                if ($i === $this->max_files) {
                    @unlink($rotateFile);
                } else {
                    @rename($rotateFile, $file . '.' . ($i + 1));
                }
            }
        }
        if (is_file($file)) {
            @rename($file, $file . '.1'); // suppress errors because it's possible multiple processes enter into this section
        }
    }

}
