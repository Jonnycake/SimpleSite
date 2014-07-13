<?php
/*
 *    SimpleSite ShowTemplate Module v0.1: ShowTemplate page for SimpleSite based website.
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

if($_SESSION['is_admin']==1)
{
	class ShowTemplate extends SimpleDisplay implements simpleModule
	{
		public function sideparse($content,$configs=array())
		{
			if($content=="")
				$content=$this->getContent($configs);
			return $content;
		}
		public function choosePage()
		{
			return -1;
		}
		public function isInstalled()
		{
			return TRUE;
		}
		public function install()
		{
			return TRUE;
		}
		public function getContent($configs=array())
		{
			if(!(isset($_GET['template'])))
				return "Error!";
			switch($_GET['func'])
			{
				case "editable":
					// Use editArray to decide which template to use and start/stop for editables
					if(!(isset($_GET['start']) && isset($_GET['length'])))
						return "Error!";
					else
						return getUnparsedEditable($_GET['template'],$_GET['start'],$_GET['length'],$configs);
				default:
					return getUnparsedTemplate($template,$configs);
			}
			
		}
		public function getUnparsedEditable($template,$start,$length,$configs=array())
		{
			return "editable";
		}
		public function getUnparsedTemplate($template,$configs=array())
		{
			return "template";
		}
	}
}
?>
