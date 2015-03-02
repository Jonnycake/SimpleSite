<?php
/**
 * SimpleFile Helper Classes
 */

/**
 * SimpleFileInfo
 *
 * Extends SplFileInfo to provide a multiton pattern for handling file objects.
 */
class SimpleFileInfo extends SplFileInfo implements simpleFileI
{
	/**
	 * An array of file objects with the absolute path being the key
	 *
	 * @var array $fileObjects
	 */
	private static $fileObjects=null;

	/**
	 * The class to use for file objects
	 *
	 * @var string $objectClass
	 */
	private static $objectClass="SimpleFileObject";

	/**
	 * Initializes the file object cache
	 *
	 * @return void
	 */
	private static function initFileObjectCache()
	{
		if(is_null(self::$fileObjects))
		{
			self::$fileObjects=array();
		}
	}

	/**
	 * Retrieve the object class (fileClass) to be used
	 *
	 * @return string The object class to be used.
	 */
	public static function getObjectClass()
	{
		return self::$objectClass;
	}

	/**
	 * Set the object class to be used.
	 *
	 * @param string $class The object class to be used.
	 * @return void
	 */
	public static function setObjectClass($class)
	{
		self::$objectClass=$class;
	}

	/**
	 * Check if the file actually exists
	 *
	 * @param string $filename The filename to check, if it's not passed it checks the file the info object was opened for.
	 *
	 * @return bool Whether or not the file exists
	 */
	public function exists($filename=null)
	{
		if(is_null($filename))
			return file_exists($this->getRealPath());
		else
			return file_exists($filename);
	}

	/**
	 * Opens the file using the specified file class
	 *
	 * Any implementations must take into account that null can't be passed to splFileInfo::openFile() for $context
	 *
	 * @param string $open_mode The mode to open the file with (see fopen() in the PHP manual)
	 * @param bool $use_include_path When set to TRUE the filename is also searched for within the include_path
	 * @param resource $context Refer to the context section of the PHP manual for a description of contexts
	 * @param string $fileClass The class to use within openFile() - default is SimpleFileObject
	 * @return object The opened file as a $fileClass object
	 */
	public function openFile($open_mode="r", $use_include_path=false, $context=null, $reopen=false, $fileClass="SimpleFileObject")
	{
		self::initFileObjectCache();
		if(!is_null($fileClass))
			$this->setFileClass($fileClass);
		$absPath=$this->getRealPath();

		if(!(isset(self::$fileObjects[$absPath])) || ($reopen))
		{
			// If null is passed for $context a RuntimeException occurs, it must not be passed at all for it to work correctly
			if(!is_null($context))
				self::$fileObjects[$absPath]=parent::openFile($open_mode, $use_include_path, $context);
			else
				self::$fileObjects[$absPath]=parent::openFile($open_mode, $use_include_path);
		}

		return self::$fileObjects[$absPath];
	}
}

/**
 * SimpleFileObject
 *
 * Contains more functions involving the manipulation of files after they're opened
 */
class SimpleFileObject extends SplFileObject
{
	/**
	 * Retrieve the current size of the file
	 *
	 * Takes into account outside changes affecting the size
	 *
	 * @return int The size of the file in bytes.
	 */
	public function getSize()
	{
		$stats=$this->fstat();
		return $stats['size'];
	}

	/**
	 * Change the permissions on the file
	 *
	 * @param int $mode The octal version of the file mode to be used.
	 *
	 * @return bool TRUE on success and FALSE on failure
	 */
	public function chmod($mode)
	{
		return chmod($this->getRealPath(), octdec($mode));
	}

	/**
	 * Change the owner of the file (only the superuser may do so)
	 *
	 * @param mixed $user The user to change the ownership to
	 *
	 * @return bool TRUE on success and FALSE on failure
	 */
	public function chown($user)
	{
		chown($this->getRealPath(), $user);
	}

	/**
	 * Read the given number of bytes from the file
	 *
	 * Put in for compatability reasons - SplFileObject::fread() only exists in PHP5 >= 5.5.11
	 *
	 * @return string The string from the file
	 */
	public function fread($length)
	{
		$ret="";

		$x=0;
		while($x<$length)
		{
			$ret.=$this->fgetc();
			$x++;
		}

		return $ret;
	}

	/**
	 * Read the entire file
	 *
	 * @todo Protect against extremely large files
	 *
	 * @return string The content of the entire file
	 */
	public function readAll()
	{
		$this->rewind();
		return $this->fread($this->getSize());
	}

	/**
	 * Read the rest of the file from the current file pointer
	 *
	 * @return string The rest of the file content
	 */
	public function readRest()
	{
		return $this->fread(($this->getSize())-($this->ftell()));
	}

	/**
	 * Replace all occurances of the search string within the file with the replacement string
	 *
	 * Can be also save the result to the file
	 *
	 * @param mixed $search The string(s) to search for.
	 * @param mixed $replacement The replacement text(s).
	 * @param int $count The variable to store the number of replacements in.
	 * @param bool $write Whether or not to write the output to the file.
	 * @return string The string with the replaced values.
	 */
	public function str_replace($search, $replacement, &$count=null, $write=false)
	{
		$content=$this->readAll();
		$content=str_replace($search, $replacement, $content, $count);

		if($write)
		{
			$this->fwrite($content);
		}

		return $content;
	}

	/**
	 * Searches the file for matches to pattern and replaces them with replacement
	 *
	 * @param mixed $pattern The pattern to search for.  It can be a string or an array of strings.
	 * @param mixed $replacement The string or array with strings to replace.
	 * @param int The maximum possible replacements for each search pattern.  Defaults to -1 (no limit).
	 * @param int $count Will be filled with the number of replacements done.
	 8 @return string The content with the replaced values.
	 */
	public function preg_replace($pattern, $replacement, $limit=-1, &$count=null, $write=false)
	{
		$content=$this->readAll();
		$content=preg_replace($pattern, $replacement, $content, $limit, $count);

		if($write)
		{
			$this->fwrite($content);
		}

		return $content;
	}

	/**
	 * Write a string to the file
	 *
	 * @param string $str The string to write to the file.
	 * @param int $length The maximum number of bytes to write to the file.
	 * @return int The number of bytes written or false on error.
	 */
	public function fwrite($str, $length=null)
	{
		if(is_null($length))
		{
			return parent::fwrite($str);
		}
		else
		{
			return parent::fwrite($str, $length);
		}
	}

	/**
	 * Retrieve the first X lines of a file.
	 *
	 * @param int $lines The number of lines to retrieve.
	 * @return string The first X lines of the file.
	 */
	public function head($lines=10)
	{
		$curPos=$this->ftell();
		$this->rewind();

		$x=0;
		$ret="";
		for($x;$x<$lines && !($this->eof());$x++)
			$ret.=$this->fgets();

		$this->fseek($curPos); // Return to the position we were in when the function was called
		return $ret;
	}

	/**
	 * Retrieve the last X lines of a file.
	 *
	 * @param int $lines The number of lines to retrieve.
	 * @return string The last X lines of the file.
	 */
	public function tail($lines=10)
	{
		$ret="";
		$curPos=$this->ftell();
		$begin=$this->lineCount()-($lines+1);

		// We can't seek to a negative line number
		if($begin<0)
		{
			$begin=0;
		}
		$this->seek($begin);

		while(!$this->eof())
		{
			$ret.=$this->fgets();
		}

		$this->fseek($curPos); // Return to the position we were in when the function was called
		return $ret;
	}

	/**
	 * Get the line count in a file
	 *
	 * @return int The number of lines in the file
	 */
	public function lineCount()
	{
		$curPos=$this->ftell();

		$x=0;
		while(!($this->eof()))
		{
			$this->current();
			$x++;
			$this->next();
		}

		$this->fseek($curPos); // Return to the position we were in when the function was called
		return $x-1;
	}

	/**
	 * Move the file to a new location
	 *
	 * @param string $newPath The destination path
	 * @param bool $overwrite Whether or not to force an overwrite of the destination path
	 * @param SplFileObject $obj The object to update with the new location
	 * @return bool Whether or not the move succeeded
	 */
	public function move($newPath, $overwrite=false, &$obj=null)
	{
		// Protect against overwrites
		if(file_exists($newPath) && !$overwrite)
		{
			SimpleFile::addSuffix($newPath);
		}

		// It should then verify if the file has been moved and if it has update its path
		if(rename($this->getRealPath(), $newPath))
		{
			$success=true;
			$obj=new self($newPath);
		}
		else
		{
			$success=false;
		}

		return $success;
	}

	/**
	 * Copy a file to a separate location
	 *
	 * @param string $newPath The destination path
	 * @param bool $overwrite Whether or not to force an overwrite of the destination path
	 * @param SplFileObject $obj The object to update with the new location
	 * @return bool Whether the copy succeeded or not.
	 */
	public function copy($newPath=null, $overwrite=false, &$obj=null)
	{
		$fullPath=$this->getRealPath();

		if(is_null($newPath))
		{
			$newPath=$fullPath;
		}

		// If realpath() is supplied a non-existent filename it returns NULL so to avoid this we strip the filename
		$lastDelim=strrpos($newPath, "/", -1);
		$endDelim=($lastDelim===false) ? "" : "/";
		$fileName=substr($newPath, $lastDelim);
		$newPath=realpath(substr($newPath, 0, $lastDelim).$endDelim)."/${fileName}";

		// If we don't want to overwrite the destination file, we need to add a suffix
		if(!($newPath!=$fullPath && ($overwrite || !(file_exists("${newPath}")))))
		{
			SimpleFile::addSuffix($newPath);
		}

		if(copy($fullPath, $newPath))
		{
			$obj=new self($newPath);
			return true;
		}

		return false;
	}

	/**
	 * Delete the file
	 *
	 * @param SplFileObject $fileObject To allow use from a static context to delete a specific file
	 * @return Whether or not the deletion succeeded
	 */
	public function delete(&$fileObject=null)
	{
		//$something=null;
		return (!is_null($fileObject)) ? unlink($fileObject->getRealPath()) : unlink($this->getRealPath());
	}

	/**
	 * Calculate the md5 sum of the file
	 *
	 * @return string The md5-encoded result of the file content
	 */
	public function md5sum()
	{
		var_dump($this);
		return md5($this->readAll());
	}

	/**
	 * Get the base64 encoded version of the file
	 *
	 * @return string The base64 encoded result of the file content
	 */
	public function base64_encode()
	{
		return base64_encode($this->readAll());
	}

	/**
	 * Create the file content from a base64 string
	 *
	 * @param string $base64_string The base64 encoded version of the content you wish to write
	 * @return void
	 */
	public function base64_decode($base64_string)
	{
		$this->fwrite(base64_decode($base64_string));
	}
}

/**
 * SimpleFile
 *
 * Static class designed to make OOP-based file-access easier and quicker.
 */
class SimpleFile
{
	/**
	 * The class to use for retrieving a file info object
	 *
	 * @var string $infoClass
	 */
	private static $infoClass="SimpleFileInfo";

	/**
	 * The class to use for manipulating files
	 *
	 * @var string $objectClass
	 */
	private static $objectClass="SimpleFileObject";

	/**
	 * The storage location for file info objects
	 *
	 * @var array $files
	 */
	private static $files=null;

	/**
	 * Files to avoid opening - mainly created so that files which cause parse errors can be blocked
	 *
	 * @var array $blacklist
	 */
	private static $blacklist=null;

	/**
	 * Initialize the blacklist
	 *
	 * @return void
	 */
	private static function initBlacklist()
	{
		if(is_null(self::$blacklist))
		{
			self::$blacklist=array();
		}
	}

	/**
	 * Add a suffix to a pathname
	 *
	 * @param string $path The pathname to add a suffix to
	 * @return void
	 */
	public static function addSuffix(&$path)
	{
		$x=1;
		for($x;file_exists($path.".${x}");++$x) { }
		$path.=".${x}";
	}

	/**
	 * Safely include files - checks blacklist first
	 *
	 * @param string $filename The filename to include
	 * @return void
	 */
	public static function safe_include($filename)
	{
		self::initBlacklist();
		$absPath=realpath($filename);
		if(!in_array($absPath, self::$blacklist) && !is_null($absPath))
		{
			include($absPath);
		}
	}

	/**
	 * Initialize files array
	 *
	 * @return void
	 */
	private static function initFiles()
	{
		if(is_null(self::$files))
		{
			self::$files=array();
		}
	}

	/**
	 * Retrieve the name of the file info class
	 *
	 * @return string The name of the file info class
	 */
	public static function getInfoClass()
	{
		return self::$infoClass;
	}

	/**
	 * Set the name of the file info class
	 *
	 * @param string $class The name of the class to use for file info objects
	 * @return void
	 */
	public static function setInfoClass($class)
	{
		self::$infoClass=$class;
	}

	/**
	 * Get the name of the file object class
	 *
	 * @return string The name of the file object class
	 */
	public static function getObjectClass()
	{
		return self::$objectClass;
	}

	/**
	 * Set the name of the file object class
	 *
	 * @param string $class The name of the class to use for file objects
	 * @return void
	 */
	public static function setObjectClass($class)
	{
		self::$objectClass=$class;
	}

	/**
	 * Get a file info object for $file
	 *
	 * @param string $file The name of the file to get an info object for
	 * @param bool $reopen Whether or not to force SimpleFile to reopen the file
	 * @return SplFileInfo The file info object
	 */
	public static function getFile($file, $reopen=false)
	{
		self::initFiles();
		$absPath=realpath($file);

		if(!isset(self::$files[$absPath]) || $reopen)
		{
			$infoClass=self::$infoClass;
			self::$files[$absPath]=new $infoClass($file);
		}

		return self::$files[$absPath];
	}

	/**
	 * Open a file for manipulation
	 *
	 * @param string $file The path to the file to open
	 * @param string $open_mode The mode for opening the file. See the fopen() documentation for descriptions of possible modes. The default is read only. 
	 * @param bool $use_include_path When set to TRUE, the filename is also searched for within the include_path.
	 * @param resource $context Refer to the context section of the manual for a description of contexts. 
	 * @para bool $reopen When set to true it creates a new file info object regardless of if one already exists for the file
	 * @param string $fileClass The class to use to create the file object
	 * @return SplFileObject An object created from the file object class
	 */
	public static function openFile($file, $open_mode="r", $use_include_path=false, $context=null, $reopen=false, $fileClass=null)
	{
		if(is_null($fileClass))
		{
			$fileClass=self::$objectClass;
		}

		$fileInfo=self::getFile($file, $reopen);
		return $fileInfo->openFile($open_mode, $use_include_path, $context, $reopen, $fileClass); // Assumes that the info class set handles the null $context problem
	}
}
?>
