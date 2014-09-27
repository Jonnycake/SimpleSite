<?php
/*
 *    SimpleFile 0.1: Basic file access functions.
 *    Copyright (C) 2014 Jon Stockton
 * 
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class SimpleFile
{
	private $rfd=null;
	private $wfd=null;
	private $content=null;
	private $filename=null;
	private $directory=null;
	private $path=null;
	private $delim=null;
	private $debug=false;
	private $url=null;

	public function __construct($filepath, $toWrite=false, $delim="/", $debug=false)
	{
		if(preg_match("/((http)|(ftp))s?:\/\//si",$filepath,$match) && $match)
		{
			$this->url=$filepath;
		}
		else
		{
			$filepath=$this->getFullPath($filepath);
			$pathArr=explode($delim,$filepath);
			$this->filename=$pathArr[count($pathArr)-1];
			unset($pathArr[count($pathArr)-1]);
			$this->directory=implode($delim,$pathArr);
			$this->delim=$delim;
		}
		$this->debug=$debug;
	}
	public function __destruct()
	{
		$this->close();
	}

	// General accessors
	public function getDebug()
	{
		return $this->debug;
	}
	public function setDebug($debug)
	{
		$this->debug=$debug;
	}

	// File descriptor functions
	public function open()
	{
		if(is_null($this->url))
		{
			$fullPath=$this->getFullPath();
			if($this->debug)
				echo "Dbg: Attempting to open $fullPath...";
			if(file_exists($fullPath) && !is_dir($fullPath))
			{
				try
				{
					$this->rfd=(($f=fopen($fullPath,"r"))?$f:null);
					if(is_resource($this->rfd))
					{
						echo "Success.\n";
						while(!(feof($this->rfd)))
							$this->content.=fread($this->rfd, 250);
					}
					else if($this->debug)
						echo "Failed.\n";
				}
				catch (Exception $e)
				{
					if($this->debug)
						echo "\nDbg: ".$e->getMessage()."\n";
				}
			}
			else if($this->debug)
			{
				echo "File not found.\n";
			}
		}
		else
		{
			if(preg_match("/^[a-z]*:\/\/([^\/]*).*/si",$this->url,$matches) && $matches)
			{
				$host=$matches[1];
				if(!(preg_match("/^[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}$/si",$host,$matches2) && $matches2))
				{
					$hostip=gethostbyname($host);
					if($host==$hostip)
					{
						if($this->debug)
							echo "Dbg: Could not resolve hostname...\n";
						return;
					}
				}
				try
				{
					$this->content=file_get_contents($this->url); // I want to avoid connection refused warnings...but apparently gethostbyname() isn't going to fail unless the name's too long -_-
				}
				catch (Exception $e)
				{
					if($this->debug)
						echo "Dbg: ".$e->getMessage()."\n";
				}
			}
			else
			{
				if($this->debug)
					echo "Dbg: Invalid URL.\n";
			}
		}
	}
	public function close()
	{
		if($this->debug)
			echo "Dbg: Closing file descriptor.";
		if(is_resource($this->rfd))
		{
			fclose($this->rfd);
		}
	}
	public function reload()
	{
		if($this->debug)
			echo "Dbg: Reloading file...";
		$this->close();
		$this->content="";
		$this->open();
		if($this->isOpen())
			echo "Sucess.\n";
		else
			echo "Failure.\n";
	}

	// Access information
	public function isOpen()
	{
		if($this->wfd)
			$fd=$this->wfd;
		else
			$fd=$this->rfd;
		return is_resource($fd);
	}
	public function isWritable()
	{
		return is_writable($this->getFullPath());
	}
	public function getOwner()
	{
		return fileowner($this->getFullPath());
	}
	public function setOwner($owner)
	{
		return chown($this->getFullPath(),$owner);
	}
	public function getGroup()
	{
		return posix_getgrgid(filegroup($this->getFullPath()));
	}
	public function setGroup($group)
	{
		return chgrp($this->getFullPath(),$group);
	}
	public function getFullPath($pathname=null)
	{
		return ($pathname!=null) ? realpath($pathname) : $this->directory."/".$this->filename;
	}
	public function getDirPath()
	{
		return $this->directory;
	}
	public function getFileName()
	{
		return $this->filename;
	}
	public function getFileType()
	{
		return filetype($this->getFullPath());
	}
	public function getModified()
	{
		return filemtime($this->getFullPath());
	}
	public function getCreated()
	{
		return filemtime($this->getFullPath());
	}
	public function getSize()
	{
		return strlen($this->content);
	}

	// File location functions
	public function copy($newPath=null)
	{
		$fullPath=$this->getFullPath();
		return copy($fullPath, (is_null($newPath)) ? "${fullPath} Copy" : $newPath); // We need some file validation done so we don't overwrite
	}
	public function move($newPath)
	{
		return rename($this->getFullPath(), $newPath);// nope clearly not right
	}
	public function delete()
	{
		unlink($this->getFullPath());
	}

	// File content functions
	public function getContent()
	{
		return $this->content;
	}
	public function setContent($content)
	{
		if(!$this->isWritable())
			return false;

		$this->content=$content;
		return true;
	}
	public function head()
	{
		$ret="";
		$contentArr=explode("\n",$this->content);

		$x=0;
		for($x;$x<10 && isset($contentArr[$x]);$x++)
			$ret.=$contentArr[$x]."\n";

		return $ret;
	}
	public function tail()
	{
		$ret="";
		$contentArr=explode("\n",$this->content);

		$x=count($contentArr)-11;
		$x=($x<0)?0:$x;
		for($x;isset($contentArr[$x]);$x++)
			$ret.=$contentArr[$x]."\n";

		return $ret;
	}
	public function getSection($offset, $length=false)
	{
		return ($length === false) ? substr($this->content,$offset) : substr($this->content,$offset,$length);
	}
	public function append($string)
	{
		$this->content.=$string;
		$this->write();
		return true;
	}
	public function write()
	{
		if(!$this->isWritable())
			return false;

		$this->close();

		$this->wfd=fopen($this->getFullPath(), "w");
		fwrite($this->wfd, $this->getContent());
		fclose($this->wfd);

		$this->reload();
		return true;
	}
	public function regReplace($regex, $replacement="", $handler=null, $limit=-1)
	{
		if(!$this->isWritable())
			return false;

		$matches=array();
		if(preg_match($regex,$this->content,$match) && $match)
		{
	                preg_match_all($regex,$this->content,$matches);
			foreach($matches as $match)
			{
				$count=1;
				str_replace($match[0], (is_null($handler)) ? $replacement : $handler($match), $this->content, $count);
			}
		}
		else
		{
			$this->content=preg_replace($regex, $replacement, $this->content);
		}
	}
	public function strReplace($search, $replace, $permanent=false)
	{
		if(!$this->isWritable())
			return false;

		$this->content=str_replace($search,$replace,$this->content);

		if($permanent)
		{
			if($this->write())
				return true;
			else 
				return false;
		}
	}
}
?>
