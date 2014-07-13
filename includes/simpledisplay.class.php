<?php
/*
 *    SimpleSite Display Class v1.5: Create a user interface from templates.
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

if(SIMPLESITE!=1)
	die("Can't access this file directly.");

$funcsperformed=0;
class SimpleDisplay extends SimpleUtils
{
	public $templateLengths=array();
	public $editables=0;
	public $editArray=array();
	protected $db=NULL;

	// Parse bbcodes
	protected function bbencode($post,$getcodes=0,$codetop="<table style='font-size:75%;margin-bottom:2%;'>",$codeformat="<tr><td>{CODE}</td><td>{RESULT}</td></tr>",$codebottom="</table>")
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: bbencode(\$post,$getcodes)\n";

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

	// Template display
	protected function readTemplate($template, $mod)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: readtemplate(\"".str_replace($_SERVER['DOCUMENT_ROOT'],"",$template)."\",\"$mod\")\n";
		$f=fopen($template,"r");
		$content="";
		while(($line=fgets($f)))
			$content.=$line;
		if(!(array_key_exists(str_replace($_SERVER['DOCUMENT_ROOT'],"",$template))))
			$this->templateLengths[str_replace($_SERVER['DOCUMENT_ROOT'],"",$template)]=strlen($content);
		if(@($_GET['noparse']==1))
			return $content;
		return $this->parseTemplate($content, $mod, $template);
	}
	protected function parseTemplate($content, $mod, $templateName="")
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: parsetemplate(\$content,\"$mod\")\n";
		if(!isset($this->configs))
		{
			if(@($_GET['debug'])==1)
				echo "Dbg: Loading module's configurations from config file...\n";
			include("config.inc.php");
			$this->configs=$configs;
		}
		// Template variables from function call
		$funcconsts=array();
		if($mod!="")
		{
			$obj=new $mod();
			$obj->db=$this->db;
			if(@($_GET['debug'])==1)
				echo "Dbg: \$obj->isInstalled()\n";
			if($obj->isInstalled($this->configs))
			{
				if(@($_GET['debug'])==1)
					echo "Dbg: \$obj->sideparse()\n";
				$content=$obj->sideparse($content,$this->configs);
			}
			else
			{
				if(@($_GET['debug'])==1)
					echo "Dbg: \$obj->install()\n";
				$obj->install($this->configs);
				if($obj->isInstalled($this->configs))
				{
					if(@($_GET['debug'])==1)
						echo "Dbg: \$obj->sideparse(\$content,\$this->configs)\n";
					$content=$obj->sideparse($content,$this->configs);
				}
				else
					if(@($_GET['debug'])==1)
						echo "Dbg: Mod unable to be installed...\n";
			}
		}

		// Overall constants
		if((preg_match("/{HEADER}/si",$content,$match)))
		{
			while((preg_match("/{HEADER}/si",$content,$match)))
				$content=@str_replace("{HEADER}",$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$this->configs["path"]["root"].$this->configs["path"]["templates"]."/header.template"),$content);
		}
		if((preg_match("/{CONTENT}/si",$content,$match)))
		{
			if(isset($obj))
			{
				while((preg_match("/{CONTENT}/si",$content,$match)))
					if((@$obj->isInstalled($this->configs))?TRUE:@$obj->install($this->configs))
					{
						if(@($_GET['debug'])==1)
							echo "Dbg: \$obj->getContent(\$this->configs)\n";
						$content=@str_replace("{CONTENT}",$obj->getContent($this->configs),$content);
					}
			}
			else
				$content=$content=str_replace($match[0],$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$this->configs["path"]["root"].$this->configs["path"]["templates"]."/index.template",$mod),$content);
		}
		if((preg_match("/{FOOTER}/si",$content,$match)))
		{
			if(@$_GET['debug']==1)
				echo "Dbg: Replacing {FOOTER}\n";
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
			$replacement=(($_SESSION['is_admin']==1)?"<span id=\"editable_".$this->editables."\"><span id=\"origContent".$this->editables."\">".$matches[1][$elementNum][0]."</span><span id=\"toolbar_".$this->editables."\"><img src=\"{CONFIGS_path_root}images/edit.jpg\" style=\"width:20px;\" onclick=\"edit(".$this->editables.");\" alt=\"Edit\" title=\"Edit\"/></span></span>":$matches[1][$elementNum][0]);
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
				if(@$_GET['debug']==1)
					echo "Dbg: Replacing &#123;${match[1]}&#125;\n";
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
			$widget=$match[1];
			if(is_file($_SERVER['DOCUMENT_ROOT'].$this->configs['path']['root']."includes/widgets/$widget.widget.php"))
			{
				include($_SERVER['DOCUMENT_ROOT'].$this->configs['path']['root']."includes/widgets/$widget.widget.php");
				$content=str_replace($match[0],((@($widgetTemp=$this->$widget($this->configs))!="")?$widgetTemp:"Widget Failed: $widget"),$content);
			}
			$content=str_replace($match[0],"",$content);
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
				$content=str_replace($match[0],$this->simpleFilter($_GET[$match[1]],0),$content);
			$content=str_replace($match[0],"",$content);
		}

		// Post Variables
		while((preg_match("/{POST_(.*?)}/si",$content,$match)) && $match)
		{
			if(isset($_POST[$match[1]]))
				$content=str_replace($match[0],$this->simpleFilter($_POST[$match[1]],0),$content);
			$content=str_replace($match[0],"",$content);
		}
		$this->templateLengths[str_replace($_SERVER['DOCUMENT_ROOT'],"",$templateName)]=strlen($content);

		// Conditional Statements
		while((preg_match("/{IF \"(.*?)\" (eq|ne|gt|lt|gte|lte) \"(.*?)\"}(.*?){\/IF}/si",$content,$match)) && $match)
		{
			$content=str_replace($match[0],$this->tempConditional($match),$content);
			$content=str_replace($match[0],"",$content);
		}
		return $content;
	}
	protected function showSite($mod)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: showsite(\"$mod\")\n";
		if($mod!="")
			$obj=new $mod();
		if(@($_GET['debug'])==1)
			echo "Dbg: \$obj->choosePage(\$this->configs)\n";
		if(@($_POST['ajax']!=1))
			echo @$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$this->configs["path"]["root"].$this->configs["path"]["templates"]."/".((isset($obj))?((($page=$obj->choosePage($this->configs)))?(($page==-1)?"":$page):"overall"):"overall").".template",$mod);
		else
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
}
?>
