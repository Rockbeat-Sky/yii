<?php
namespace sky\yii\helpers;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 * @property integer $size
 * @property boolean $isFile
 * @property boolean $isDir
 * @property boolean $isWriteable
 * @property boolean $isReadable
 * @property boolean $isAccesable
 * @property integer $total
 * @property integer $totalFiles
 * @property integer $totalFolders
 * @property number $permission
 * @property FileExplorer $up go to up directory
 * @property array $scan action scan directory
 * @property mix $content
 * @property string $mimeType
 */

class FileExplorer extends \yii\base\Component
{
    public $path;
    
    public $class;
    
    private $_info;
    
    private $_files = [];
    
    public function init() {
        parent::init();
        $this->path = realpath($this->path);

        if (!$this->path) {
            throw new InvalidConfigException(Yii::t('app', 'File or Folder Not Found'));
        }
        if (!$this->class) {
            $this->class = self::className();
        }
        $info = [
            'size' => filesize($this->path),
            'changed' => filectime($this->path),
            'modification' => filemtime($this->path),
            'accesed' => fileatime($this->path),
            'isFile' => is_file($this->path),
            'isDir' => is_dir($this->path),
            'isWritable' => is_writable($this->path),
            'isReadable' => is_readable($this->path),
            'isAccesable' => is_executable($this->path),
        ];
        $this->_info = array_merge(pathinfo($this->path), $info);
    }
    
    public function __GET($key)
    {
        if (isset($this->_info[$key])) {
            return $this->_info[$key];
        }
        return parent::__GET($key);
    }
    
    public function getUp()
    {
        return $this->createObject($this->dirname);
    }
    
    public function getScan($sorting_order = SCANDIR_SORT_ASCENDING)
    {
        $ignore = ['.', '..'];
        foreach (scandir($this->currentFolderPath, $sorting_order) as $file) {
            if (!in_array($file, $ignore)) {
                $this->_files[$file] = $this->createObject($this->currentFolderPath . DIRECTORY_SEPARATOR . $file);
            }
        }
        return $this->_files;
    }
    
    public function delete()
    {
        if ($this->isFile) {
            return unlink($this->path);
        }
        return rmdir($this->path);
    }
    
    public function newFolder($fileName, $mode = 0777, $recursive = true)
    {
        mkdir($this->currentFolderPath . '/' . $fileName, $mode, $recursive);
    }
    
    public function copy(FileExplorer $file = null, $surfixName = '-copy')
    {
        if (!$this->isWritable) {
            return false;
        }
        if (!$file) {
            return copy($this->path, $this->currentFolderPath . $this->basename . $surfixName . '.' . $this->extension);
        }
    }
    
    public function chmod($mode)
    {
        return chmod($this->path, $mode);
    }
    
    public function getPermission()
    {
        return fileperms($this->path);
    }

    public function getFile($file)
    {
        if (!$this->_files) {
            $this->scan;
        }
        if (isset($this->_files[$file])) {
            return $this->_files[$file];
        }
    }
    
    public function readFile($use_include_path = false, $context = null)
    {
        readfile($this->path, $use_include_path, $context);
    }
    
    public function getContent()
    {
        return file_get_contents($this->path);
    }
    
    public function getContentArray($flags = 0)
    {
        return file($this->path, $flags);
    }
    
    public function getCurrentFolderPath()
    {
        if ($this->isFile) {
            return $this->dirname;
        }
        return $this->path;
    }
    
    public function getMimeType($magicFile = null)
    {
        return FileHelper::getMimeTypeByExtension($this->basename, $magicFile);
    }
    
    /**
     * creating object file or folder
     * 
     * @param string $path path location
     * @param string $class nameclass
     * @return \sky\yii\helpers\FileExplorer | mix | object
     */
    private function createObject($path, $class = null)
    {
        if ($class == null) {
            $class = $this->class;
        }
        return new $this->class(['path' => $path, 'class' => $class]);
    }
    
    /**
     * count total files
     * 
     * @return int
     */
    public function getTotalFiles()
    {
        if (!$this->_files) {
            $this->scan;
        }
        $i = 0;
        foreach ($this->_files as $file) {
            if ($file->isFile) {
                $i++;
            }
        }
        return $i;
    }
    
    /**
     * count Total Folders
     * 
     * @return int
     */
    public function getTotalFolders()
    {
        return $this->total - $this->totalFiles;
    }
    
    /**
     * count total Folders and Files
     * 
     * @return int
     */
    public function getTotal()
    {
        if (!$this->_files) {
            $this->scan;
        }
        return count($this->_files);
    }
}