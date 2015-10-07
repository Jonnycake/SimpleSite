<?php
/*
 *    SimpleSite Display Class v2.1: Create a user interface from templates.
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

/**
 * Default template parser for SimpleSite.
 *
 * @package SimpleSite Core
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/**
 * File can not be accessed directly.
 */
if(SIMPLESITE!=1)
	die("Can't access this file directly.");

/**
 * Number of functions performed so far
 *
 * @var int
 */
$funcsperformed=0;

/**
 * SimpleDisplay Class
 *
 * The SimpleDisplay class is the default template parser that is used
 * when running SimpleSite.
 */
class SimpleDisplay extends SimpleUtils implements SimpleDisplayI
{
	/**
	 * Array of template lengths (for use in editables)
	 *
	 * @var array
	 */
	public $templateLengths=array();

	/**
	 * Number of editables found in the content.
	 *
	 * @var int
	 */
	public $editables=0;

	/**
	 * Array of editables and their content
	 *
	 * @var array
	 */
	public $editArray=array();

	/**
	 * The database connection.
	 *
	 * @var resource
	 */
	protected $db=NULL;

	/**
	 * Should check that the display has all of the dependencies required
	 *
	 * @return bool
	 */
	public function displayIsInstalled()
	{
		return true;
	}

	/**
	 * Should attempt to install all of the dependencies required.
	 *
	 * @return bool
	 */
	public function displayInstall()
	{
		return true;
	}

	/**
	 * Should uninstall any database tables or extra files.
	 *
	 * @return bool
	 */
	public function displayUninstall()
	{
		return true;
	}

	/**
	 * Parse BBCodes
	 *
	 * Parses bbcodes based on a built-in set as well as a database table
	 *
	 * @param string $post The content which needs to be parsed.
	 * @param bool $getcodes Whether or not a table listing coulds should be displayed.
	 * @param string $codetop Template for the top of the BBCode table
	 * @param string $codeformat Template for the BBCodes to come out in in the table
	 * @param string $codebottom Template for the bottom of the BBCode table
	 * @return string The bbencoded version of the output.
	 *
	 * @todo Move to separate plugin.
	 */
	protected function bbencode($post,$getcodes=false,$codetop=null,$codeformat=null,$codebottom=null)
	{
		SimpleDebug::logInfo("bbencode(\$post,$getcodes)");

		if(is_null($codetop))
		{
			$codetop="<table style='font-size:75%;margin-bottom:2%;'>";
			$codeformat="<tr><td>{CODE}</td><td>{RESULT}</td></tr>";
			$codebottom="</table>";
		}
		// Built-in bbcodes
		$search=array(  "/\[b\](.*?)\[\/b\]/si",
						"/\[i\](.*?)\[\/i\]/si",
						"/\[u\](.*?)\[\/u\]/si",
						"/\[color=(#[A-Fa-f0-9]+)\](.*?)\[\/color\]/si",
						"/\[url\]([ ]+)?(?![Jj][Aa][Vv][Aa][Ss][Cc][Rr][Ii][Pp][Tt])([A-Za-z0-9-\/.?=%:_;#\[\]@!$&'()*+,~]+)\[\/url\]/si",
						"/\[url=(?![Jj][Aa][Vv][Aa][Ss][Cc][Rr][Ii][Pp][Tt])([A-Za-z0-9-\/.?=%:]+)\](.*?)\[\/url\]/si",
				);
		$replace=array( "<b>$1</b>",
						"<i>$1</i>",
						"<u>$1</u>",
						"<font style=\"color:$1\">$2</font>",
						"<a href=\"$2\">$2</a>",
						"<a href=\"$1\">$2</a>",
				);

		// Custom bbcodes
		$dbconf=$this->configs['database'];
		$bbcodeTbl=$this->db->openTable("bbcodes");
		$bbcodeTbl->select('*');
		foreach($bbcodeTbl->sdbGetRows() as $row)
		{
			$search[]=$row['search'];
			$replace[]=$row['replace'];
		}

		// Generating BBCode table
		if($getcodes==1)
		{
			$codes=$codetop;
			$x=0;
			foreach($search as $code)
				$codes.=str_replace("{CODE}",$code,str_replace("{RESULT}",htmlspecialchars($replace[$x++]),$codeformat));
			$codes.=$codebottom;
			return $codes;
		}
        	return preg_replace($search,$replace,$post);
	}

	/**
	 * Read the template.
	 *
	 * @param string $template The path to the template to read.
	 * @param string $mod The name of the module to use to parse the template.
	 * @return string The parsed version of the template
	 */
	public function readTemplate($template, $mod)
	{
		SimpleDebug::logInfo("readtemplate(\"".str_replace($_SERVER['DOCUMENT_ROOT'],"",$template)."\",\"$mod\")");

		// This will be replaced by SimpleFile
		$f=fopen($template,"r");
		$content="";
		while(($line=fgets($f)))
			$content.=$line;

		// Editable stuff
		if(!(array_key_exists(str_replace($_SERVER['DOCUMENT_ROOT'],"",$template), $this->templateLengths)))
			$this->templateLengths[str_replace($_SERVER['DOCUMENT_ROOT'],"",$template)]=strlen($content);

		if(@($_GET['noparse']==1)) // Yeah...no? o.O
			return $content;

		return $this->parseTemplate($content, $mod, $template);
	}

	// This entire function will probably be cleaned up with SimpleFile
	/**
	 * Parse the template content.
	 *
	 * @param string $content The content to parse.
	 * @param string $mod The module to use to parse the template
	 * @param string $templateName The name to use for keeping track of editables (pretty much unused).
	 * @return string The parsed version of $content.
	 */
	public function parseTemplate($content, $mod, $templateName="")
	{
		SimpleDebug::logInfo("parsetemplate(\$content,\"$mod\")");

		// Should replace with a util function loadConfigs()
		if(!isset($this->configs))
		{
			SimpleDebug::logInfo("Loading module's configurations from config file...");
			include("config.inc.php");
			$this->configs=$configs;
		}

		// Template variables from function call - see below <--- wtf does this mean? lmfao don't think it applies anymore
		$funcconsts=array();
		if($mod!="")
		{
			try
			{
				$obj=new $mod($this->configs,$this->db);
				if(@$obj->isInstalled($this->configs))
				{
					$content=$obj->sideparse($content,$this->configs); // I wish we could move this toward the end so we could take advantage of tail recursion
				}
			}
			catch(Exception $e)
			{
				SimpleDebug::logException($e);
			}
		}

		// Overall constants - maybe put these into a separate function - mainparse?
		if((preg_match("/{HEADER}/si",$content,$match)))
		{
			while((preg_match("/{HEADER}/si",$content,$match)))
				$content=@str_replace("{HEADER}",$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$this->configs["path"]["root"].$this->configs["path"]["templates"]."/header.template",$mod),$content);
		}
		if((preg_match("/{CONTENT}/si",$content,$match)))
		{
			if(isset($obj))
			{
				try
				{
					while((preg_match("/{CONTENT}/si",$content,$match)))
						if((@$obj->isInstalled($this->configs))?TRUE:@$obj->install($this->configs))
						{
							SimpleDebug::logInfo("\$obj->getContent(\$this->configs)");
							try
							{
								$content_replacement=$obj->getContent($this->configs);
								if(!is_null($content_replacement))
									$content=str_replace("{CONTENT}", $content_replacement, $content);
								else
									$content="";
							}
							catch(Exception $e)
							{
								$content=str_replace("{CONTENT}", "&#123;CONTENT&#125;", $content);
								SimpleDebug::logException($e);
							}
							$content=@str_replace("{CONTENT}",(!is_null($replacement=$obj->getContent($this->configs))?$replacement:($content="")),$content);
							$this->editArray=array_merge($this->editArray, $obj->editArray);
							$this->templateLengths=array_merge($this->templateLengths, $obj->templateLengths);
						}
				}
				catch(Exception $e)
				{
					SimpleDebug::logException($e);
				}
			}
			else
				$content=$content=str_replace($match[0],$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$this->configs["path"]["root"].$this->configs["path"]["templates"]."/index.template",$mod),$content);
		}
		if((preg_match("/{FOOTER}/si",$content,$match)))
		{
			SimpleDebug::logInfo("Replacing {FOOTER}".time());
			while((preg_match("/{FOOTER}/si",$content,$match)))
				$content=@str_replace("{FOOTER}",$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$this->configs["path"]["root"].$this->configs["path"]["templates"]."/footer.template",$mod),$content);
		}

		// Administrator only content
		while((preg_match("/{ISADMIN}(.*?){\/ISADMIN}/si",$content,$match)) && $match)
		{
			$content=str_replace($match[0],(($_SESSION['is_admin']==1)?$match[1]:""),$content);
		}
		$lengthDif=0; // Without this the replacement will go in the wrong place when displaying editables
		preg_match_all("/{EDITABLE}(.*?){\/EDITABLE}/si",$content,$matches,PREG_OFFSET_CAPTURE);
		foreach($matches[0] as $match)
		{
			preg_match("/{EDITABLE}(.*?){\/EDITABLE}/si",$match[0],$unparsedMatch);
			$unparsedMatch[1]=str_replace("{","&#123;",str_replace("}","&#125;",$unparsedMatch[1]));
			$elementNum=array_search($match,$matches[0]);
			$editTemplateName=str_replace($_SERVER['DOCUMENT_ROOT'],"",$templateName);
			if($_SESSION['is_admin']==1)
				$this->editArray[]=array("template" => $editTemplateName, "start" => $match[1], "length" => strlen($match[0]), "unparsed" => $unparsedMatch[1]);
			$this->editables++;
			$replacement=(($_SESSION['is_admin']==1)?"<span id=\"editable_".$this->editables."\"><span id=\"origContent".$this->editables."\">".$matches[1][$elementNum][0]."</span><span id=\"toolbar_".$this->editables."\"><img src=\"{CONFIGS_path_img_assets}edit.jpg\" style=\"width:20px;\" onclick=\"edit(".$this->editables.");\" alt=\"Edit\" title=\"Edit\"/></span></span>":$matches[1][$elementNum][0]);
			$content=substr_replace($content,$replacement,$match[1]+$lengthDif,strlen($match[0]));
			$lengthDif+=strlen($replacement)-strlen($match[0]);
		}
		while((preg_match("/{EDITARRAY}/si",$content,$match)))
		{
			$content=str_replace($match[0],base64_encode(json_encode($this->editArray)),$content);
		}

		// Hard-coded constants
		while((preg_match("/{((?!ELSE})[A-Z]*)}/si",$content,$match)) && $match)
			if(defined($match[1]))
				$content=str_replace($match[0],constant($match[1]),$content);
			else if(array_key_exists($match[1],$funcconsts))
				$content=str_replace($match[0],$this->$funcconsts[$match[1]](),$content);
			else
			{
				SimpleDebug::logInfo("Replacing &#123;${match[1]}&#125;");
				$content=str_replace($match[0],"&#123;".$match[1]."&#125;",$content);
			}

		// Configuration file variables
		while((preg_match("/{CONFIGS_([A-Za-z0-9]*)_([A-Za-z0-9_]*)}/si",$content,$match)) && $match)
		{
			if(isset($this->configs[$match[1]]))
				if(is_array($this->configs[$match[1]]))
				{
					if(isset($this->configs[$match[1]][$match[2]]))
						$content=str_replace($match[0],$this->configs[$match[1]][$match[2]],$content);
				}
				else
					$content=str_replace($match[0],$this->configs[$match[1]],$content);
			$content=str_replace($match[0],"&#123;CONFIGS_".$match[1]."_".$match[2]."&#125;",$content);
		}

		// Include Templates
		while((preg_match("/{TEMPLATE_(.*)}/si",$content,$match)) && $match)
		{
			$content=str_replace($match[0],$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$this->configs['path']['root'].$match[1],$mod),$content);
		}

		// Widgets
		while((preg_match("/{WIDGET_([([A-Za-z0-9]*)}/si",$content,$match)) && $match)
		{
			try
			{
				$widget=$match[1];
				if(is_file($_SERVER['DOCUMENT_ROOT'].$this->configs['path']['root']."includes/widgets/$widget.widget.php"))
				{
					include($_SERVER['DOCUMENT_ROOT'].$this->configs['path']['root']."includes/widgets/$widget.widget.php");
					$content=str_replace($match[0],((@($widgetTemp=$this->$widget($this->configs))!="")?$widgetTemp:"Widget Failed: $widget"),$content);
				}
				$content=str_replace($match[0],"",$content);
			}
			catch(Exception $e)
			{
				SimpleDebug::logException($e);
				$content=str_replace($match[0], "", $content);
			}
		}

		// Module info variables
		while((preg_match("/{MODINFO_([([A-Za-z0-9]*)}/si",$content,$match)) && $match)
		{
			if(@(isset($obj->info[$match[1]])))
				$content=str_replace($match[0],$obj->info[$match[1]],$content);
			$content=str_replace($match[0],"",$content);
		}

		// Session Variables
		while((preg_match("/{SESSION_(.*?)_(.*?)}/si",$content,$match)) && $match)
		{
			if(isset($_SESSION[$match[1]]))
				if(is_array($_SESSION[$match[1]]))
				{
					if(isset($_SESSION[$match[1]][$match[2]]))
						$content=str_replace($match[0],$_SESSION[$match[1]][$match[2]],$content);
				}
				else
					$content=str_replace($match[0],$_SESSION[$match[1]],$content);
			$content=str_replace($match[0],"",$content);
		}

		// Get Variables
		while((preg_match("/{GET_(.*?)}/si",$content,$match)) && $match)
		{
			if(isset($_GET[$match[1]]))
				$content=str_replace($match[0],$this->simpleFilter($_GET[$match[1]],false),$content);
			$content=str_replace($match[0],"",$content);
		}

		// Post Variables
		while((preg_match("/{POST_(.*?)}/si",$content,$match)) && $match)
		{
			if(isset($_POST[$match[1]]))
				$content=str_replace($match[0],$this->simpleFilter($_POST[$match[1]],false),$content);
			$content=str_replace($match[0],"",$content);
		}
		$this->templateLengths[str_replace($_SERVER['DOCUMENT_ROOT'],"",$templateName)]=strlen($content); // Uhmm...this probably isn't too good for editables o.O

		// Conditional Statements
		while((preg_match("/{IF \"(.*?)\" (eq|ne|gt|lt|gte|lte) \"(.*?)\"}(.*?){\/IF}/si",$content,$match)) && $match)
		{
			$content=str_replace($match[0],$this->tempConditional($match),$content);
			$content=str_replace($match[0],"",$content);
		}
		return $content;
	}

	/**
	 * Show the website.
	 *
	 * @param string $mod The name of the module to use.
	 * @return void
	 */
	public function showSite($mod)
	{
		try
		{
			SimpleDebug::logInfo("showsite(\"$mod\")");

			// We need a multiton type pattern to use here so we don't eat up so much memory creating duplicate objects
			if($mod!="")
			{
				try
				{
					$obj=new $mod($this->configs,$this->db);
				}
				catch(Exception $e)
				{
					SimpleDebug::logException($e);
				}
			}

			SimpleDebug::logInfo("\$obj->choosePage(\$this->configs)");

			if(@($_POST['ajax']!=1))
			{
				try
				{
					$chosenPage=(isset($obj)) ? $obj->choosePage($this->configs) : "";
				}
				catch(Exception $e)
				{
					$chosenPage="";
					SimpleDebug::logException($e);
				}

				if(is_int($chosenPage)) {
					switch($chosenPage)
					{
						case SimpleDisplay::FORMAT_JSON:
							header("Content-Type: text/json");
							break;
						case SimpleDisplay::FORMAT_BASE64:
						case SimpleDisplay::FORMAT_SERIALIZED:
						case SimpleDisplay::FORMAT_XML:
						case SimpleDisplay::FORMAT_ARRAY:
						default:
							header("Content-Type: text/plain");
							break;
					}
				}
				else if(is_string($chosenPage))
				{
					$path=$this->configs["path"]["templates"]."/".$chosenPage;
				}
				else if(is_array($chosenPage) && count($chosenPage)>=2) // Can't remember what I was doing here - something to do with commponents customizing their display I know that much
				{
					$path="";
					switch($chosenPage[0])
					{
						case "mod":
							break;
						case "theme":
							break;
						case "custom":
							break;
						case "widget":
							break;
					}
				}

				// This should probably be cleaned up...
				echo $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$this->configs['path']['root'].$this->configs['path']['templates']."/".(($chosenPage=="")?"overall":$chosenPage).".template", $mod);
			}
			else // This will get cleaned up by SimpleFile
			{
				switch($_POST['ajaxFunc'])
				{
					case "liveEdit":
						if($_SESSION['is_admin']!=1)
							die("You don't have privileges for this.");
						$oldContent="";
						$editArray=json_decode(base64_decode($_POST['editArray']));
						$editableInfo=$editArray[$_POST['eid']-1];
						$f=@fopen($_SERVER['DOCUMENT_ROOT'].$editableInfo->template,"r");
						while(@($line=fgets($f)))
							$oldContent.=$line;
						@fclose($f);
						$f=@fopen($_SERVER['DOCUMENT_ROOT'].$editableInfo->template,"w");
						$newContent=@substr_replace($oldContent,"{EDITABLE}".$_POST['updateContent']."{/EDITABLE}",$editableInfo->start,$editableInfo->length);
						fwrite($f,$newContent);
						fclose($f);

						// Update editArray
						$x=$_POST['eid'];
							$editArray[$x-1]->length+=strlen($newContent)-strlen($oldContent);
							$arrCount=0;
							foreach($editArray as $editable)
								$arrCount++;
							while($x++<$arrCount)
								if($editArray[$x-1]->template==$editableInfo->template)
									$editArray[$x-1]->start+=strlen($newContent)-strlen($oldContent);
							echo base64_encode(json_encode($editArray));
							break;
					case "parseText":
						if($_SESSION['is_admin']==1)
							echo $this->parseTemplate($_POST['content'],$mod);
						break;
					default:
						echo "something went wrong o.O";
						break;
				}
			}
		}
		catch (Exception $e)
		{
			SimpleDebug::logException($e);
		}
	}
}
?>
