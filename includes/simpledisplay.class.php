<?php
/*
 *    SimpleSite Display Class v1.0: Create a user interface from templates.
 *    Copyright (C) 2012 Jon Stockton
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
	// Parse bbcodes
	function bbencode($post,$getcodes=0,$codetop="<table style='font-size:75%;margin-bottom:2%;'>",$codeformat="<tr><td>{CODE}</td><td>{RESULT}</td></tr>",$codebottom="</table>")
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
		include("config.inc.php");
		$dbconf=$configs['database'];
		$conn=@mysql_connect($dbconf['host'],$dbconf['username'],$dbconf['password']);
		mysql_select_db($dbconf['database'],$conn);
		$query="SELECT * FROM ${dbconf['tbl_prefix']}bbcodes;";
		$results=mysql_query($query,$conn);
		while(($row=@mysql_fetch_array($results)))
		{
			$search[]=$row['search'];
			$replace[]=$row['replace'];
		}
		mysql_close($conn);
		
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
	function readTemplate($template, $mod)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: readtemplate(\"$template\",\"$mod\")\n";
		$f=fopen($template,"r");
		$content="";
		while(($line=fgets($f)))
			$content.=$line;
		return $this->parseTemplate($content, $mod);
	}
	function parseTemplate($content, $mod)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: parsetemplate(\$content,\"$mod\")\n";
		include("config.inc.php");
		
		// Template variables from function call
		$funcconsts=array();
		if($mod!="")
		{
			$obj=new $mod();
			if(@($_GET['debug'])==1)
				echo "Dbg: \$obj->isInstalled()\n";
			if($obj->isInstalled())
			{
				if(@($_GET['debug'])==1)
					echo "Dbg: \$obj->sideparse()\n";
				$content=$obj->sideparse($content,$configs);
			}
			else
			{
				if(@($_GET['debug'])==1)
					echo "Dbg: \$obj->install()\n";
				if($obj->install())
				{
					if(@($_GET['debug'])==1)
						echo "Dbg: \$obj->sideparse(\$content,\$configs)\n";
					$content=$obj->sideparse($content,$configs);
				}
				else
					if(@($_GET['debug'])==1)
						echo "Dbg: Mod unable to be installed...\n";
			}
		}
		
		// Overall constants
		if((preg_match("/{CONTENT}/si",$content,$match)))
		{
			if(isset($obj))
			{
				while((preg_match("/{CONTENT}/si",$content,$match)))
					if((@$obj->isInstalled($configs))?TRUE:@$obj->install())
					{
						if(@($_GET['debug'])==1)
							echo "Dbg: \$obj->getContent(\$configs)\n";
						$content=@str_replace("{CONTENT}",$obj->getContent($configs),$content);
					}
			}	
			else
				$content=$content=str_replace($match[0],$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["templates"]."/index.template",$mod),$content);
		}
		if((preg_match("/{HEADER}/si",$content,$match)))
		{
			while((preg_match("/{HEADER}/si",$content,$match)))
				$content=@str_replace("{HEADER}",$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["templates"]."/header.template"),$content);
		}
		if((preg_match("/{FOOTER}/si",$content,$match)))
		{
			while((preg_match("/{FOOTER}/si",$content,$match)))
				$content=@str_replace("{FOOTER}",$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["templates"]."/footer.template"),$content);
		}

		// Administrator only content
		while((preg_match("/{ISADMIN}(.*?){\/ISADMIN}/si",$content,$match)) && $match)
		{
			$content=str_replace($match[0],(($_SESSION['is_admin']==1)?$match[1]:""),$content);
		}

		// Hard-coded constants
		while((preg_match("/{([A-Z]*)}/si",$content,$match)) && $match)
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
		while((preg_match("/{CONFIGS_([A-Za-z0-9]*)_([A-Za-z0-9]*)}/si",$content,$match)) && $match)
		{
			if(isset($configs[$match[1]]))
				if(is_array($configs[$match[1]]))
				{
					if(isset($configs[$match[1]][$match[2]]))
						$content=str_replace($match[0],$configs[$match[1]][$match[2]],$content);
				}
				else
					$content=str_replace($match[0],$configs[$match[1]],$content);
			$content=str_replace($match[0],"&#123;CONFIGS_".$match[1]."_".$match[2]."&#125;",$content);
		}
		
		// Include Templates
		while((preg_match("/{TEMPLATE_(.*)}/si",$content,$match)) && $match)
		{
			$content=str_replace($match[0],$this->readTemplate($match[1],$mod),$content);
		}
		
		// Widgets
		while((preg_match("/{WIDGET_([([A-Za-z0-9]*)}/si",$content,$match)) && $match)
		{
			$widget=$match[1];
			if(is_file($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/widgets/$widget.widget.php"))
			{
				include($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/widgets/$widget.widget.php");
				if(function_exists($widget))
					$content=str_replace($match[0],$match[1]($this,$configs),$content);
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
		while((preg_match("/{SESSION_(.*)_(.*?)}/si",$content,$match)) && $match)
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

		return $content;
	}
	function showSite($mod)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: showsite(\"$mod\")\n";
		include("config.inc.php");
		if($mod!="")
			$obj=new $mod();
		if(@($_GET['debug'])==1)
			echo "Dbg: \$obj->choosePage(\$configs)\n";
		echo @$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["templates"]."/".((isset($obj))?((($page=$obj->choosePage($configs)))?(($page==-1)?"":$page):"overall"):"overall").".template",$mod);
	}
}
?>
