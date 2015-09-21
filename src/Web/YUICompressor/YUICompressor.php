<?php
namespace Pecee\Web\YUICompressor;
class YUICompressor {
	
	/**
	 * @author Simon Sessingø
	 * @version 1.0
	 * 
	 * For information about options - or download of the jarFile, please refer to the 
	 * YUICompressor documentation here:
	 * 
	 * http://developer.yahoo.com/yui/compressor/
	 * http://yuilibrary.com/projects/yuicompressor/
	 */
	
	const TYPE_JAVASCRIPT = 'js';
	const TYPE_CSS = 'css';

    protected $jarFile;
    protected $tempDir;
    protected $javaExecutable;
    private $types = array(self::TYPE_JAVASCRIPT, self::TYPE_CSS);
    protected $items;
    
    public function __construct() {
    	$this->jarFile = null;
    	$this->tempDir = sys_get_temp_dir();
    	$this->javaExecutable = 'java';
    	$this->items = array();
    	return $this;
    }
     
    public function addFile($type,$file,$options=array()) {
    	$this->validateType($type);
    	if(!file_exists($file)) {
    		throw new \Pecee\Web\YUICompressor\YUICompressorException('File does not exist: ' . $file);
    	}
    	$this->addItem($type,file_get_contents($file),$options, substr($file, 
    					strrpos($file, DIRECTORY_SEPARATOR)+1, strlen($file)),
    					substr($file, 0, strrpos($file, DIRECTORY_SEPARATOR)));
    }
    
    public function addContent($type,$content,$options=array() ) {
    	$this->validateType($type);
    	$this->addItem($type, $content, $options);
    }
    
    private function addItem($type,$content,$options,$filename=null,$filepath=null) {
    	$item = new YUICompressorItem();
    	$item->type=$type;
    	$item->content=$content;
    	$item->options=$options;
    	$item->filename=$filename;
    	$item->filepath=$filepath;
    	$this->items[] = $item;
    }
    
	public function minify($single=false) {
    	if (!is_file($this->jarFile) || !is_dir($this->tempDir) || !is_writable($this->tempDir)) {
            throw new \Pecee\Web\YUICompressor\YUICompressorException('Minify_YUICompressor : $jarFile must be set or is not a valid ressource.');
        }
        if (!($tmpFile = tempnam($this->tempDir, 'yuic_'))) {
            throw new \Pecee\Web\YUICompressor\YUICompressorException('Minify_YUICompressor : could not create temp file.');
        }
        if(count($this->items) > 0) {
        	/* @var $item YUICompressorItem */
	        foreach( $this->items as $item ) {
	        	file_put_contents($tmpFile, $item->content);
	        	$output = array();
		        exec($this->getCmd($item->options, $item->type, $tmpFile), $output);
		        unlink($tmpFile);
		        $item->minified = $output[0];
		        $item->sizeKB = round(strlen($item->content)/1024, 2);
		        $item->minifiedKB = $item->sizeKB - round(strlen($output[0])/1024, 2);
		        $item->minifiedRatio = round(($item->minifiedKB / $item->sizeKB) * 100);
	        }
        }
        return ($single) ? $this->items[count($this->items)-1] : $this->items;
    }
	private function validateType($type) {
    	if(!in_array($type, $this->types)) {
    		throw new \Pecee\Web\YUICompressor\YUICompressorException('Unknown type: '.$type.'. Type must be one of the following: ' . join($this->types, ', '));
    	}
    }
   
    private function getCmd($userOptions, $type, $tmpFile) {
        $o = array_merge(
            array(
                'charset' => ''
                ,'line-break' => 5000
                ,'type' => $type
                ,'nomunge' => false
                ,'preserve-semi' => false
                ,'disable-optimizations' => false
            ),$userOptions
        );
        $cmd = $this->javaExecutable . ' -jar ' . escapeshellarg($this->jarFile) . " --type {$type}"
             . (preg_match('/^[a-zA-Z\\-]+$/', $o['charset']) ? " --charset {$o['charset']}" : '')
             . (is_numeric($o['line-break']) && $o['line-break'] >= 0 ? ' --line-break ' . (int)$o['line-break'] : '');
        if ($type === 'js') {
            foreach (array('nomunge', 'preserve-semi', 'disable-optimizations') as $opt) {
                $cmd .= $o[$opt] ? " --{$opt}" : '';
            }
        }
        return $cmd . ' ' . escapeshellarg($tmpFile);
    }
    
	public function getJarFile() {
		return $this->jarFile;
	}
	public function getTempDir() {
		return $this->tempDir;
	}
	public function getJavaExecutable() {
		return $this->javaExecutable;
	}
	public function setJarFile($jarFile) {
		$this->jarFile = $jarFile;
	}
	public function setTempDir($tempDir) {
		$this->tempDir = $tempDir;
	}
	public function setJavaExecutable($javaExecutable) {
		$this->javaExecutable = $javaExecutable;
	}
	public function getItems() {
		return $this->items;
	}
}