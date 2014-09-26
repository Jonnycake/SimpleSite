<?php
/*
 *    SimpleSite AdminCP Module v1.5: Admin page for a SimpleSite-based website.
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
 
if(@SIMPLESITE!=1)
	die("Can't access this file directly.");
class adminCP extends SimpleModule
{
	public static $info=array( "author"  => "Jon Stockton",
						"name"    => "SimpleAdmin",
						"version" => "2.0",
						"date"    => "November ??, 2014"
					 );
	public function sideparse($content,$configs=array())
	{
		if($_SESSION['is_admin'])
		{
			switch($_GET['act'])
			{
				case "backup":
					$content=$this->backupAdm($content,$configs);
					break;
				case "blocked":
					$content=$this->blockingAdm($content,$configs);
					break;
				case "dbAdmin":
					$content=$this->dbAdmin($content,$configs);
					$content=str_replace("{QUERYSUCCESS}","",$content);
					break;
				case "modAdmin":
					$content=$this->modAdmin($content,$configs);
					break;
				case "configAdmin":
					$content=$this->configAdmin($content,$configs);
					break;
				case "templateAdmin":
					$content=$this->templateAdmin($content,$configs);
					break;
				case "themeMgr":
					$content=$this->themeMgr($content,$configs);
					break;
				case "testEnv":
					$content=$this->testEnvironment($content,$configs);
					break;
				case "widgetAdmin":
					$content=$this->widgetAdmin($content,$configs);
					break;
				case "toggleDbg":
					global $funcsperformed;
					if(!$funcsperformed)
						$_SESSION['debug']=($_SESSION['debug'])?0:1;
					$funcsperformed++;
					break;
			}
		}
		else
		{
			if($_GET['act']=="login")
				$content=str_replace("{LOGINSUCCESS}","Login failed, please try again.",$content);
			else
				$content=str_replace("{LOGINSUCCESS}","",$content);
		}
			
		if((preg_match("/{ACTIONPAGE}/si",$content,$match)))
			$content=str_replace("{ACTIONPAGE}",$this->getActionPage($configs),$content);
		return $content;
	}
	public function choosePage($configs=array())
	{
		if(!$this->db)
		{
			$this->db=new SimpleDB($configs['database']);
		}

		if($_GET['act']=="login")
		{
			if($this->db->connected())
			{
				$this->db->openTable("admins");
				$adminTbl=$this->db->sdbGetTable("admins");
				$count=$adminTbl->select('id',array('AND' => array('username' => array('op' => '=', 'val' => $_POST['username']), 'passwd' => array('op' => '=', 'val' => md5($_POST['password'])))));
				if($count==1)
				{
					$_SESSION['is_admin']=1;
				}
			}
			else
			{
				if($_POST['username']==$configs['database']['username'] && $_POST['password']==$configs['database']['password'])
					$_SESSION['is_admin']=1;
			}
		}
		else if($_GET['act']=="logout")
		{
			$_SESSION['is_admin']=0;
		}
		return "";
	}
	public function isInstalled($configs=array())
	{
		$reqFiles=array(
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_backup.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_backup_BACKUPSAVAIL.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_blocked.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_blocked_BLOCKTBL.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_configAdmin.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_dbAdmin.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_dbAdmin_table.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_login.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_modAdmin.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_modAdmin_modules.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_templateAdmin.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_testEnv.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_themeMgr.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_welcome.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_widgetAdmin.template"
			       );
		$reqTbls=array(
				"admins"
			      );

		// Check for required files
		if(!($this->checkReqFiles($reqFiles,$configs)))
			return FALSE;

		/*// Check for required database tables
		if(!($this->checkReqTbls($reqTbls,$configs)))
			return FALSE;*/

		return TRUE;
	}
	public function install($configs=array())
	{
		$defaultTbls=array(
					"admins" => array(
								"id" => "int NOT NULL AUTO_INCREMENT PRIMARY KEY",
								"username" => "varchar(255) NOT NULL UNIQUE",
								"passwd" => "varchar(33) NOT NULL"
							)
				);
		$defaultFiles=array(
			"adminCP.template" => "CQkJCQk8ZGl2IGlkPSJtYWluY29udGVudCIgc3R5bGU9IndpZHRoOjgwJTtmbG9hdDpyaWdodDsiPg0KCQkJCQkJe0FDVElPTlBBR0V9DQoJCQkJCTwvZGl2Pg0KCQkJCQk8ZGl2IGlkPSJvcHRpb25zIiBzdHlsZT0id2lkdGg6MjAlO2NsZWFyOmxlZnQ7Ij4NCjxhIGhyZWY9IntDT05GSUdTX3BhdGhfcm9vdH0/bW9kPWFkbWluQ1AmYWN0PWJhY2t1cCI+QmFja3VwczwvYT48YnIvPg0KCQkJCQkJPGEgaHJlZj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9Y29uZmlnQWRtaW4iPkNvbmZpZ3VyYXRpb25zPC9hPjxici8+DQoJCQkJCQk8YSBocmVmPSJ7Q09ORklHU19wYXRoX3Jvb3R9P21vZD1hZG1pbkNQJmFjdD1kYkFkbWluIj5EYXRhYmFzZSBBZG1pbjwvYT48YnIvPg0KPGEgaHJlZj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9YmxvY2tlZCI+SVAgQmxvY2tpbmc8L2E+PGJyLz4NCgkJCQkJCTxhIGhyZWY9IntDT05GSUdTX3BhdGhfcm9vdH0/bW9kPWFkbWluQ1AmYWN0PW1vZEFkbWluIj5Nb2R1bGUgQWRtaW48L2E+PGJyLz4NCgkJCQkJCTxhIGhyZWY9IntDT05GSUdTX3BhdGhfcm9vdH0/bW9kPWFkbWluQ1AmYWN0PXRlbXBsYXRlQWRtaW4iPlRlbXBsYXRlIEFkbWluPC9hPjxici8+DQoJCQkJCQk8YSBocmVmPSJ7Q09ORklHU19wYXRoX3Jvb3R9P21vZD1hZG1pbkNQJmFjdD10aGVtZU1nciI+VGhlbWUgTWFuYWdlcjwvYT48YnIvPg0KCQkJCQkJPGEgaHJlZj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9dGVzdEVudiI+VGVzdCBFbnZpcm9ubWVudDwvYT48YnIvPg0KCQkJCQkJPGEgaHJlZj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9d2lkZ2V0QWRtaW4iPldpZGdldCBBZG1pbjwvYT48YnIvPg0KCQkJCQkJPGEgaHJlZj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9bG9nb3V0Ij5Mb2dvdXQ8L2E+DQoJCQkJCTwvZGl2Pg==",
			"adminCP_backup.template" => "PGI+Tm90ZTogQmFja3VwcyBhcmUgc3RvcmVkIGluIHRoZSB0ZW1wb3JhcnkgZGlyZWN0b3J5IGFzIHNwZWNpZmllZCBpbiB0aGUgY29uZmlndXJhdGlvbiBmaWxlLjwvYj48YnIvPg0KPGZvcm0gYWN0aW9uPSJ7Q09ORklHU19wYXRoX3Jvb3R9P21vZD1hZG1pbkNQJmFjdD1iYWNrdXAmZnVuYz1jcmVhdGUiIG1ldGhvZD0icG9zdCI+DQoJPHNlbGVjdCBuYW1lPSJidHlwZSI+DQoJCTxvcHRpb24gdmFsdWU9ImFsbCI+QWxsPC9vcHRpb24+DQoJCTxvcHRpb24gdmFsdWU9ImJhY2siPkJhY2stRW5kPC9vcHRpb24+DQoJCTxvcHRpb24gdmFsdWU9ImRiIj5EYXRhYmFzZTwvb3B0aW9uPg0KCQk8b3B0aW9uIHZhbHVlPSJtb2RzIj5Nb2R1bGVzPC9vcHRpb24+DQoJCTxvcHRpb24gdmFsdWU9InRlbXBzIj5UZW1wbGF0ZXM8L29wdGlvbj4NCgk8L3NlbGVjdD4NCgk8aW5wdXQgdHlwZT0ic3VibWl0IiB2YWx1ZT0iQmFja3VwISIvPg0KPC9mb3JtPjxici8+PGJyLz4NCjx0YWJsZT4NCgk8dHI+DQoJCTx0ZCBjb2xzcGFuPSIyIj48Yj5BdmFpbGFibGUgQmFja3Vwczo8L2I+PC90ZD4NCgk8L3RyPg0Ke0JBQ0tVUFNBVkFJTH0NCjwvdGFibGU+DQo8Zm9ybSBhY3Rpb249IntDT05GSUdTX3BhdGhfcm9vdH0/bW9kPWFkbWluQ1AmYWN0PWJhY2t1cCZmdW5jPWNsZWFyIiBtZXRob2Q9InBvc3QiPg0KCTxpbnB1dCB0eXBlPSJzdWJtaXQiIHZhbHVlPSJEZWxldGUgQmFja3VwcyIvPg0KPC9mb3JtPg==",
			"adminCP_backup_BACKUPSAVAIL.template" => "CTx0cj4NCgkJPHRkPjxhIGhyZWY9IntDT05GSUdTX3BhdGhfcm9vdH0/bW9kPWFkbWluQ1AmYWN0PWJhY2t1cCZmdW5jPWdldCZiYWNrdXA9e0ZJTEV9Ij57VFlQRX08L2E+PC90ZD48dGQ+e0RBVEV9PHRkPg0KCTwvdHI+",
			"adminCP_blocked.template" => "CTxmb3JtIGFjdGlvbj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9YmxvY2tlZCIgbWV0aG9kPSJwb3N0Ij4NCgkJPHRhYmxlPg0KCQkJPHRyPg0KCQkJCTx0ZCBjb2xzcGFuPSIzIiBzdHlsZT0idGV4dC1hbGlnbjpjZW50ZXI7Ij5CbG9ja2VkIEhvc3RzPC90ZD4NCgkJCTwvdHI+DQoJCQk8dHI+DQoJCQkJPHRkPklEOjwvdGQ+PHRkPkhvc3RuYW1lL0kuUC46PC90ZD48dGQ+RGF0ZSBCbG9ja2VkOjwvdGQ+DQoJCQk8L3RyPg0KCQkJe0JMT0NLVEJMfQ0KCQkJPHRyPg0KCQkJCTx0ZD4gPC90ZD48dGQ+PGlucHV0IHR5cGU9InRleHQiIG5hbWU9ImFkZF9yZW1vdGVfYWRkciIgdmFsdWU9IiIvPjwvdGQ+PHRkPjxpbnB1dCB0eXBlPSJzdWJtaXQiIG5hbWU9InN1Ym1pdCIgdmFsdWU9IkFkZCIvPjwvdGQ+DQoJCQk8L3RyPg0KCQkJPHRyPg0KCQkJCTx0ZCBjb2xzcGFuPSIzIiBzdHlsZT0idGV4dC1hbGlnbjpjZW50ZXI7Ij48aW5wdXQgdHlwZT0ic3VibWl0IiBuYW1lPSJzdWJtaXQiIHZhbHVlPSJEZWxldGUiLz48L3RkPg0KCQkJPC90cj4NCgkJPC90YWJsZT4NCgk8L2Zvcm0+",
			"adminCP_blocked_BLOCKTBL.template" => "PHRyPjx0ZD48aW5wdXQgdHlwZT0iY2hlY2tib3giIG5hbWU9ImRlbGV0ZV97aWR9IiB2YWx1ZT0ie2lkfSIvPntpZH08L3RkPjx0ZD57cmVtb3RlX2FkZHJ9PC90ZD48dGQ+e2RhdGV9PC90ZD48L3RyPg==",
			"adminCP_configAdmin.template" => "PGRpdiBzdHlsZT0idGV4dC1hbGlnbjpjZW50ZXI7d2lkdGg6MTAwJTsiPg0KCTxzcGFuPjxiPkJlIHZlcnkgY2FyZWZ1bCB3aGVuIGVkaXRpbmcgdGhpcyBmaWxlLCBpZiB5b3UgZG8gc29tZXRoaW5nIHdyb25nIHlvdSBjb3VsZCBicmVhayB0aGUgYWRtaW5pc3RyYXRpdmUgY29udHJvbCBwYW5lbC48L2I+PC9zcGFuPg0KCTxmb3JtIG1ldGhvZD0icG9zdCIgYWN0aW9uPSJ7Q09ORklHU19wYXRoX3Jvb3R9P21vZD1hZG1pbkNQJmFjdD1jb25maWdBZG1pbiZmdW5jPXNhdmUiPg0KCQk8dGV4dGFyZWEgbmFtZT0iZmlsZWNvbnRlbnQiIHN0eWxlPSJ3aWR0aDoxMDAlOyIgcm93cz0iMjAiPntGSUxFQ09OVEVOVH08L3RleHRhcmVhPg0KCQk8aW5wdXQgdHlwZT0ic3VibWl0IiB2YWx1ZT0iU2F2ZSIvPg0KCTwvZm9ybT4NCjwvZGl2Pg==",
			"adminCP_dbAdmin.template" => "PGRpdiBzdHlsZT0idGV4dC1hbGlnbjpjZW50ZXI7Ij4NCgk8Zm9ybSBhY3Rpb249IntDT05GSUdTX3BhdGhfcm9vdH0/bW9kPWFkbWluQ1AmYWN0PWRiQWRtaW4mZnVuYz1kYlNlbGVjdCIgbWV0aG9kPSJwb3N0Ij4NCgkJPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iY3VyZGIiIHZhbHVlPSJ7Q1VSREJ9Ii8+DQoJCTxzZWxlY3QgbmFtZT0iZGJuYW1lIj4NCgkJCXtEQVRBQkFTRVN9DQoJCTwvc2VsZWN0Pg0KCQk8aW5wdXQgdHlwZT0ic3VibWl0IiB2YWx1ZT0iQWNjZXNzIi8+DQoJPC9mb3JtPg0KCTxici8+PGJyLz4NCgk8Zm9ybSBhY3Rpb249IntDT05GSUdTX3BhdGhfcm9vdH0/bW9kPWFkbWluQ1AmYWN0PWRiQWRtaW4mZnVuYz10YmxJbnRlcmFjdCIgbWV0aG9kPSJwb3N0Ij4NCgkJPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iY3VyZGIiIHZhbHVlPSJ7Q1VSREJ9Ii8+DQoJCTxpbnB1dCB0eXBlPSJ0ZXh0IiBuYW1lPSJxdWVyeSIgdmFsdWU9IiIvPg0KCQk8aW5wdXQgdHlwZT0ic3VibWl0IiBuYW1lPSJzdWJtaXQiIHZhbHVlPSJRdWVyeSIvPg0KCTwvZm9ybT48YnIvPg0KCTxzcGFuPntRVUVSWVNVQ0NFU1N9PC9zcGFuPg0KCTxici8+PGJyLz4NCgkJe1RBQkxFU30NCjwvZGl2Pg0K",
			"adminCP_dbAdmin_table.template" => "PGZvcm0gYWN0aW9uPSJ7Q09ORklHU19wYXRoX3Jvb3R9P21vZD1hZG1pbkNQJmFjdD1kYkFkbWluJmZ1bmM9dGJsSW50ZXJhY3QiIG1ldGhvZD0icG9zdCI+DQoJPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0iY3VyZGIiIHZhbHVlPSJ7Q1VSREJ9Ii8+DQoJPGlucHV0IHR5cGU9ImhpZGRlbiIgbmFtZT0idGJsTmFtZSIgdmFsdWU9IntUQkxOQU1FfSIvPg0KCTx0YWJsZSBzdHlsZT0ibWFyZ2luLWJvdHRvbToyMHB4OyI+DQoJCTx0cj4NCgkJCTx0ZCBjb2xzcGFuPSJ7TlVNQ09MU30iIHN0eWxlPSJ0ZXh0LWFsaWduOmNlbnRlcjsiPntUQkxOQU1FfTwvdGQ+DQoJCTwvdHI+DQoJCTx0cj4NCgkJCXtDT0xOQU1FU30gPCEtLS0gQUxURVIgVEFCTEUgLS0tPg0KCQk8L3RyPg0KCQk8dHI+DQoJCQl7VkFMVUVTfSA8IS0tLSBVUERBVEUvREVMRVRFIC0tLT4NCgkJPC90cj4NCgkJPHRyPg0KCQkJe0lOUFVUU30gPCEtLS0gSU5TRVJUUyAtLS0+DQoJCTwvdHI+DQoJCTx0cj4NCgkJCTx0ZCBjb2xzcGFuPSJ7TlVNQ09MU30iIHN0eWxlPSJ0ZXh0LWFsaWduOmNlbnRlcjsiPjxpbnB1dCB0eXBlPSJzdWJtaXQiIG5hbWU9InN1Ym1pdCIgc3R5bGU9IndpZHRoOjMwJTsiIHZhbHVlPSJJTlNFUlQiLz48aW5wdXQgdHlwZT0ic3VibWl0IiBuYW1lPSJzdWJtaXQiIHN0eWxlPSJ3aWR0aDozMCU7IiB2YWx1ZT0iVVBEQVRFIi8+PGlucHV0IHR5cGU9InN1Ym1pdCIgbmFtZT0ic3VibWl0IiBzdHlsZT0id2lkdGg6MzAlOyIgdmFsdWU9IkRFTEVURSIvPjwvdGQ+DQoJCTwvdHI+DQoJPC90YWJsZT4NCjwvZm9ybT4NCg==",
			"adminCP_login.template" => "PGRpdiBzdHlsZT0id2lkdGg6MTAwJTt0ZXh0LWFsaWduOmNlbnRlcjsiPg0KCXtMT0dJTlNVQ0NFU1N9DQoJPGZvcm0gYWN0aW9uPSJ7Q09ORklHU19wYXRoX3Jvb3R9P21vZD1hZG1pbkNQJmFjdD1sb2dpbiIgbWV0aG9kPSJwb3N0Ij4NCgkJPGlucHV0IHR5cGU9InRleHQiIG5hbWU9InVzZXJuYW1lIiB2YWx1ZT0iVXNlcm5hbWUiIG9uY2xpY2s9InRoaXMudmFsdWU9Jyc7Ii8+PGJyLz4NCgkJPGlucHV0IHR5cGU9InBhc3N3b3JkIiBuYW1lPSJwYXNzd29yZCIgdmFsdWU9IlBhc3N3b3JkIiBvbmNsaWNrPSJ0aGlzLnZhbHVlPScnOyIvPjxici8+DQoJCTxpbnB1dCB0eXBlPSJzdWJtaXQiIHZhbHVlPSJMb2dpbiI+DQoJPC9mb3JtPg0KPC9kaXY+DQo=",
			"adminCP_modAdmin.template" => "PGRpdiBzdHlsZT0idGV4dC1hbGlnbjpjZW50ZXI7Ij4NCgk8dGFibGU+DQoJCTx0cj4NCgkJCTx0ZCBjb2xzcGFuPSI0IiBzdHlsZT0idGV4dC1hbGlnbjpjZW50ZXI7Ij5BdmFpbGFibGUgTW9kdWxlczwvdGQ+DQoJCTwvdHI+DQoJCTx0cj4NCgkJCTx0ZD5OYW1lOjwvdGQ+PHRkPkF1dGhvcjo8L3RkPjx0ZD5EYXRlIENyZWF0ZWQ6PC90ZD48dGQ+RW5hYmxlZDo8L3RkPg0KCQk8L3RyPg0KCQl7TU9EVUxFU30NCgk8L3RhYmxlPg0KCTxmb3JtIGVuY3R5cGU9Im11bHRpcGFydC9mb3JtLWRhdGEiIGFjdGlvbj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9bW9kQWRtaW4mZnVuYz11cGxvYWQiIG1ldGhvZD0icG9zdCI+DQoJCTxpbnB1dCB0eXBlPSJmaWxlIiBuYW1lPSJyZmlsZSIvPjxici8+DQoJCTxpbnB1dCB0eXBlPSJzdWJtaXQiIHZhbHVlPSJVcGxvYWQiLz4NCgk8L2Zvcm0+DQo8L2Rpdj4NCg==",
			"adminCP_modAdmin_modules.template" => "PHRyPg0KCTx0ZD57TkFNRX08L3RkPjx0ZD57QVVUSE9SfTwvdGQ+PHRkPntEQVRFfTwvdGQ+PHRkIHN0eWxlPSJwYWRkaW5nOjVweDsiPjxhIGhyZWY9IntDT05GSUdTX3BhdGhfcm9vdH0/bW9kPWFkbWluQ1AmYWN0PW1vZEFkbWluJmZ1bmM9dG9nZ2xlRW5hYmxlZCZtb2R1bGU9e01PREZJTEV9JmN1cnJlbnRTdGF0ZT17RU5BQkxFRH0iPntFTkFCTEVEfTwvYT48L3RkPg0KPC90cj4NCg==",
			"adminCP_templateAdmin.template" => "PGRpdiBzdHlsZT0idGV4dC1hbGlnbjpjZW50ZXI7d2lkdGg6MTAwJTsiPg0KCTxmb3JtIGFjdGlvbj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9dGVtcGxhdGVBZG1pbiZmdW5jPWxvYWQiIG1ldGhvZD0icG9zdCI+DQoJCTxzZWxlY3QgbmFtZT0iZmlsZW5hbWUiPg0KCQkJPG9wdGlvbiB2YWx1ZT0iIj5DdXJyZW50IFRoZW1lIFRlbXBsYXRlczwvb3B0aW9uPg0KCQkJPG9wdGlvbiB2YWx1ZT0iIj49PT09PTwvb3B0aW9uPg0KCQkJe1RFTVBGSUxFU30NCgkJCTxvcHRpb24gdmFsdWU9IiI+IDwvb3B0aW9uPg0KCQkJPG9wdGlvbiB2YWx1ZT0iIj5Nb2R1bGUgVGVtcGxhdGVzPC9vcHRpb24+DQoJCQk8b3B0aW9uIHZhbHVlPSIiPj09PT09PC9vcHRpb24+DQoJCQl7TU9EVEVNUEZJTEVTfQ0KCQkJPG9wdGlvbiB2YWx1ZT0iIj4gPC9vcHRpb24+DQoJCQk8b3B0aW9uIHZhbHVlPSIiPkN1c3RvbSBUZW1wbGF0ZXM8L29wdGlvbj4NCgkJCTxvcHRpb24gdmFsdWU9IiI+PT09PT08L29wdGlvbj4NCgkJCXtDVVNUT01URU1QTEFURVN9DQoJCTwvc2VsZWN0Pjxici8+DQoJCTxpbnB1dCB0eXBlPSJzdWJtaXQiIHZhbHVlPSJFZGl0Ii8+DQoJPC9mb3JtPg0KCTxmb3JtIGFjdGlvbj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9dGVtcGxhdGVBZG1pbiIgbWV0aG9kPSJwb3N0IiBlbmN0eXBlPSJtdWx0aXBhcnQvZm9ybS1kYXRhIj4NCgkJPHRleHRhcmVhIHN0eWxlPSJ3aWR0aDoxMDAlOyIgcm93cz0iMjAiIG5hbWU9ImZpbGVjb250ZW50Ij57RklMRUNPTlRFTlR9PC90ZXh0YXJlYT48YnIvPg0KCQk8aW5wdXQgdHlwZT0iaGlkZGVuIiBuYW1lPSJmaWxlbmFtZSIgdmFsdWU9IntURU1QRklMRX0iLz4NCgkJPGlucHV0IHR5cGU9InN1Ym1pdCIgbmFtZT0ic3VibWl0IiB2YWx1ZT0iU2F2ZSIvPjxpbnB1dCB0eXBlPSJzdWJtaXQiIG5hbWU9InN1Ym1pdCIgdmFsdWU9IkRlbGV0ZSIvPjxici8+PGJyLz4NCgkJPGRpdiBzdHlsZT0id2lkdGg6MTAwJTt0ZXh0LWFsaWduOmxlZnQ7Ij4NCgkJCTxzZWxlY3QgbmFtZT0ibmV3dGVtcHR5cGUiPg0KCQkJCTxvcHRpb24gdmFsdWU9IiI+Q3VzdG9tIFRlbXBsYXRlPC9vcHRpb24+DQoJCQkJPG9wdGlvbiB2YWx1ZT0ibW9kIj5Nb2R1bGUgVGVtcGxhdGU8L29wdGlvbj4NCgkJCQk8b3B0aW9uIHZhbHVlPSJ0aGVtZSI+VGhlbWUgVGVtcGxhdGU8L29wdGlvbj4NCgkJCTwvc2VsZWN0Pg0KCQkJPGlucHV0IHR5cGU9InRleHQiIHN0eWxlPSJ3aWR0aDozODJweDsiIG5hbWU9Im5ld3RlbXBmaWxlIiB2YWx1ZT0idW50aXRsZWQudGVtcGxhdGUiLz4NCgkJCTxpbnB1dCB0eXBlPSJzdWJtaXQiIG5hbWU9InN1Ym1pdCIgc3R5bGU9IndpZHRoOjc1cHg7IiB2YWx1ZT0iTmV3Ii8+PGJyLz4NCgkJCTxzZWxlY3QgbmFtZT0idGVtcHR5cGUiPg0KCQkJCTxvcHRpb24gdmFsdWU9IiI+Q3VzdG9tIFRlbXBsYXRlPC9vcHRpb24+e0NVU1RPTVNVQkRJUlN9DQoJCQkJPG9wdGlvbiB2YWx1ZT0ibW9kIj5Nb2R1bGUgVGVtcGxhdGU8L29wdGlvbj57TU9EU1VCRElSU30NCgkJCQk8b3B0aW9uIHZhbHVlPSJ0aGVtZSI+VGhlbWUgVGVtcGxhdGU8L29wdGlvbj57VEhFTUVTVUJESVJTfQ0KCQkJPC9zZWxlY3Q+DQoJCQk8aW5wdXQgc2l6ZT0iNjAiIHR5cGU9ImZpbGUiIG5hbWU9ImZpbGUiLz48YnIvPg0KCQkJPGlucHV0IHN0eWxlPSJ3aWR0aDo1OTVweDsiIHR5cGU9InN1Ym1pdCIgbmFtZT0ic3VibWl0IiB2YWx1ZT0iVXBsb2FkIi8+DQoJCTwvZGl2Pg0KCTwvZm9ybT4NCjwvZGl2Pg==",
			"adminCP_testEnv.template" => "PGxpPg0Ke0RFUEVOREVOQ0lFU30NCjwvbGk+",
			"adminCP_themeMgr.template" => "PGZvcm0gYWN0aW9uPSJ7Q09ORklHU19wYXRoX3Jvb3R9P21vZD1hZG1pbkNQJmFjdD10aGVtZU1nciIgbWV0aG9kPSJwb3N0Ij4NCgk8c2VsZWN0IG5hbWU9ImZpbGVuYW1lIj4NCntUSEVNRVN9DQoJPC9zZWxlY3Q+PGJyLz4NCgk8aW5wdXQgbmFtZT0ic3VibWl0IiB0eXBlPSJzdWJtaXQiIHZhbHVlPSJTZXQgVGhlbWUiLz4NCgk8aW5wdXQgbmFtZT0ic3VibWl0IiB0eXBlPSJzdWJtaXQiIHZhbHVlPSJEZWxldGUiLz4NCjwvZm9ybT4NCjxmb3JtIGVuY3R5cGU9Im11bHRpcGFydC9mb3JtLWRhdGEiIGFjdGlvbj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9dGhlbWVNZ3ImZnVuYz11cGxvYWQiIG1ldGhvZD0icG9zdCI+DQoJPGlucHV0IHR5cGU9ImZpbGUiIG5hbWU9InJmaWxlIi8+PGJyLz4NCgk8aW5wdXQgdHlwZT0ic3VibWl0IiB2YWx1ZT0iVXBsb2FkIFRoZW1lIi8+DQo8L2Zvcm0+DQo=",
			"adminCP_welcome.template" => "V2VsY29tZSB0byB0aGUgU2ltcGxlU2l0ZSBhZG1pbmlzdHJhdG9yIGNvbnRyb2wgcGFuZWwuICBQbGVhc2Ugc2VsZWN0IGEgc2VjdGlvbiBmcm9tIHRoZSBsZWZ0Lg0K",
			"adminCP_widgetAdmin.template" => "PGRpdiBzdHlsZT0idGV4dC1hbGlnbjpjZW50ZXI7Ij4KCTxmb3JtIGFjdGlvbj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9d2lkZ2V0QWRtaW4mZnVuYz1kZWxldGUiIG1ldGhvZD0icG9zdCI+CgkJPHRhYmxlPgoJCQk8dHI+CgkJCQk8dGQgY29sc3Bhbj0iMyIgc3R5bGU9InRleHQtYWxpZ246Y2VudGVyOyI+V2lkZ2V0cyBBdmFpbGFibGU8L3RkPgoJCQk8L3RyPgoJCQk8dHI+CgkJCQk8dGQ+TmFtZTo8L3RkPjx0ZD5UZW1wbGF0ZSBDb25zdGFudDo8L3RkPjx0ZD5EYXRlIEFkZGVkOjwvdGQ+CgkJCTwvdHI+CgkJCXtXSURHRVRTfQoJCQk8dHI+CgkJCQk8dGQgY29sc3Bhbj0iMyIgc3R5bGU9InRleHQtYWxpZ246Y2VudGVyOyI+PGlucHV0IHR5cGU9InN1Ym1pdCIgdmFsdWU9IkRlbGV0ZSIvPjwvdGQ+CgkJCTwvdHI+CgkJPC90YWJsZT4KCTwvZm9ybT4KCTxmb3JtIGVuY3R5cGU9Im11bHRpcGFydC9mb3JtLWRhdGEiIGFjdGlvbj0ie0NPTkZJR1NfcGF0aF9yb290fT9tb2Q9YWRtaW5DUCZhY3Q9d2lkZ2V0QWRtaW4mZnVuYz11cGxvYWQiIG1ldGhvZD0icG9zdCI+CgkJPGlucHV0IHR5cGU9ImZpbGUiIG5hbWU9InJmaWxlIi8+PGJyLz4KCQk8aW5wdXQgdHlwZT0ic3VibWl0IiB2YWx1ZT0iVXBsb2FkIi8+Cgk8L2Zvcm0+CjwvZGl2Pgo="




		);

		$this->installReqFiles($defaultFiles,$configs);
		$this->installReqTbls($defaultTbls,$configs);
		$dbconf=$configs["database"];
		$adminTbl=$this->db->openTable("admins");
		$count=$adminTbl->select('*');
		if(!$count)
		{
			$adminTbl->insert(array('username' => $dbconf['username'],'passwd' => md5($configs['password'])));
		}
		return TRUE;
	}
	public function getContent($configs=array(),$actionpage="")
	{
		if($_SESSION['is_admin']==1)
			return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP.template","adminCP");
		else
			return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_login.template","adminCP");
	}
	public function getActionPage($configs)
	{
		switch($_GET['act'])
		{
			case "backup":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_backup.template","adminCP");
			case "blocked":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_blocked.template","adminCP");
			case "configAdmin":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_configAdmin.template","adminCP");
			case "dbAdmin":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_dbAdmin.template","adminCP");
			case "modAdmin":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_modAdmin.template","adminCP");
			case "templateAdmin":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_templateAdmin.template","adminCP");
			case "themeMgr":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_themeMgr.template","adminCP");
			case "testEnv":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_testEnv.template","adminCP");
			case "widgetAdmin":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_widgetAdmin.template","adminCP");
			default:
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_welcome.template","adminCP");
		}
		return "";
	}
	public function uninstall()
	{
		$reqFiles=array(
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_backup.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_backup_BACKUPSAVAIL.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_blocked.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_blocked_BLOCKTBL.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_configAdmin.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_dbAdmin.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_dbAdmin_table.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_login.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_modAdmin.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_modAdmin_modules.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_templateAdmin.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_testEnv.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_themeMgr.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_welcome.template",
				$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_widgetAdmin.template"
			       );
		$reqTbls=array(
				"admins"
			      );
		$this->delReqFiles($reqFiles,$configs);
		$this->delReqTbls($reqTbls,$configs);
		return TRUE;
	}
	
	function genDirTreeOut($tree,$curdir="./",$depthdelim="\t",$template="{DEPTH}* {FILE}\n",$dirdisplay="[DIR] ",$type=0,$prevdirs="",$depth=0)
	{
		$output="";
		if(is_array($tree))
			foreach($tree as $k=>$v)
				if(is_dir("$curdir/$k") and !(is_link("$curdir/$k")))
				{
					include($configs['path']['configs']);
					$output.=str_replace("{TYPE}","${type}_",str_replace("{PREVDIRS}",$prevdirs,str_replace("{SELECTED}",(@($configs['filename']=="${type}_${prevdirs}${k}")?" selected=\"selected\"":(($type==2 && $k==$configs['default_theme'])?" selected=\"selected\"":"")),str_replace("{DEPTH}",str_repeat($depthdelim,$depth),str_replace("{FILE}",$dirdisplay.$this->simpleFilter($k,false),$template)).$this->genDirTreeOut($tree[$k],"$curdir/$k",$depthdelim,$template,$dirdisplay,$type,$prevdirs."${k}/",$depth+1))));
				}
				else
					if(!(is_link("$curdir/$k")))
						$output.=str_replace("{TYPE}","${type}_",str_replace("{PREVDIRS}",$prevdirs,str_replace("{SELECTED}",(@($_POST['filename']=="${type}_${prevdirs}${k}")?" selected=\"selected\"":""),str_replace("{DEPTH}",str_repeat($depthdelim,$depth),str_replace("{FILE}",$this->simpleFilter($k,false),$template)))));
		return $output;
	}
	
	// Config File Administration
	public function configAdmin($content,$configs)
	{
		if(@($_GET['func'])=="save")
		{
			$f=@fopen($configs['path']['configs'],"w");
			@fwrite($f,$_POST['filecontent']);
			@fclose($f);
		}
		return str_replace("{FILECONTENT}",str_replace("{","&#123;",str_replace("}","&#125;",htmlspecialchars(file_get_contents($configs['path']['configs'])))),$content);
	}
	
	// Database Administration
	public function dbAdmin($content,$configs)
	{
		switch($_GET['func'])
		{
			case "dbSelect":
				$_POST['curdb']=$_POST['dbname'];
				break;
			case "tblInteract":
				switch($_POST['submit'])
				{
					case "INSERT":
						if($GLOBALS["funcsperformed"]==0)
						{
							$GLOBALS["funcsperformed"]++;
							$keys=array_keys($_POST);
							$inserts=preg_grep("/insert_(.*)/si",$keys);
							$query="INSERT INTO `".$_POST['curdb']."`.`".$_POST['tblName']."` (";
							$arrlen=count($inserts);
							$curkey=0;
							foreach($inserts as $key)
							{
								preg_match("/insert_(.*)/si",$key,$match);
								if($match[1]!="")
									$query.="`${match[1]}`".((++$curkey<$arrlen)?",":"");
							}
							$query.=") VALUES (";
							$curkey=0;
							foreach($inserts as $key)
							{
								preg_match("/insert_(.*)/si",$key,$match);
								if($match[1]!="")
									$query.=$this->db->quote($_POST[$match[0]]).((++$curkey<$arrlen)?",":"");
							}
							$query.=");";
							$this->db->rawQry($query);
						}
						break;
					case "DELETE":
						if($GLOBALS["funcsperformed"]==0)
						{
							$GLOBALS["funcsperformed"]++;
							$keys=array_keys($_POST);
							$deletes=preg_grep("/delete_(.*)/si",$keys);
							$query="DELETE FROM `".$_POST['curdb']."`.`".$_POST['tblName']."` WHERE ";
							$arrlen=count($deletes);
							$curkey=0;
							foreach($deletes as $key)
							{
								preg_match("/delete_(.*)_(.*)/si",$key,$match);
								if($match[1]!="" && $match[2]!="")
									$query.="`${match[1]}`='${match[2]}'".((++$curkey<$arrlen)?" OR ":"");
							}
							$query.=";";
							$this->db->rawQry($query);
						}
						break;
					case "UPDATE":
						if($GLOBALS["funcsperformed"]==0)
						{
							$GLOBALS["funcsperformed"]++;
							$updates=array();
							
							// Check for changes
							$keys=array_keys($_POST);
							$updateKeys=preg_grep("/update_(.*)/si",$keys);
							foreach($updateKeys as $updateKey)
							{
								preg_match("/update_(.*)_(.*)_(.*)_(.*)/si",$updateKey,$match);
								if($match[1]!="" && $match[2]!="" && $match[3]!="")
								{
									$primaryKey=$match[1];
									if($match[4]!=md5($_POST[$match[0]]))
										$updates[$match[2]][$match[3]]=$this->db->quote($_POST[$match[0]]);
								}
							}

							// Execute changes
							$keys=array_keys($updates);
							foreach($keys as $key)
							{
								$x=0;
								$cKeys=array_keys($updates[$key]);
								$max=count($cKeys);
								$query="UPDATE `".$_POST['curdb']."`.`".$_POST['tblName']."` SET ";
								foreach($updates[$key] as $ck=>$cv)
									$query.="`$ck`=$cv".(($x++<$max-1)?",":"");
								$query.=" WHERE `$primaryKey`='$key';";
								$this->db->rawQry($query);
							}
						}
						break;
					case "Query":
						if($GLOBALS["funcsperformed"]==0)
						{
							$GLOBALS["funcsperformed"]++;
							$querysuccess=@$this->db->rawQry($_POST['query']);
							$GLOBALS["databasesuccess"]=($querysuccess==false)?"Query failed!":"Query successful.";
						}
						else
							$content=str_replace("{QUERYSUCCESS}",$GLOBALS["databasesuccess"],$content);
						break;
				}
		}
		$content=str_replace("{CURDB}",$_POST['curdb'],$content);

		$databases="";
		$res=$this->db->sdbGetDatabases();
		foreach($res as $row)
		{
			$dblist[]=$row[0];
			$databases.="<option value=\"${row[0]}\"".(($row[0]==$_POST['curdb'])?" selected=\"selected\"":"").">${row[0]}</option>";
		}
		$content=str_replace("{DATABASES}",$databases,$content);

		$tables=array();
		$subcontent="";
		if($_POST['curdb']!="" && in_array($_POST['curdb'],$dblist))
		{
			$tables=$this->db->sdbGetTables($_POST['curdb']);
			$tblInfo=array();
			foreach($tables as $table)
			{
				$tblInfo[$table]=$this->db->sdbGetColumns($table,$_POST['curdb']);
			}
			foreach($tblInfo as $name => $cols)
			{
				$subcontent.='<form action="{CONFIGS_path_root}?mod=adminCP&act=dbAdmin&func=tblInteract" method="post">

	<input type="hidden" name="curdb" value="'.$_POST['curdb'].'"/>

	<input type="hidden" name="tblName" value="'.$name.'"/>';
				$subcontent .= "<table>\n";
				$subcontent .= "\t<tr>\n\t\t<td colspan=\"".count($cols)."\" style=\"text-align:center;\">$name</td>\n\t</tr>\n\t<tr>\n";
				foreach($cols as $col => $properties)
				{
					$subcontent .= "\t\t<td style=\"text-align:center;\">$col</td>\n";
				}
				$subcontent .= "\t</tr>\n";
				$this->db->resetRows();
				$tblData=$this->db->rawQry("SELECT * FROM `${_POST['curdb']}`.`$name`;",array(),false);

				$primaryKeys=array();
				foreach($tblData as $tdRow)
				{
					$subcontent.="\t<tr>\n";
					foreach($cols as $col => $properties)
					{
						$subcontent .= "\t\t<td>";
						if($properties['Key']=='PRI')
						{
							$primaryKeys[]=$col;
							$subcontent.="<input type=\"checkbox\" name=\"delete_${col}_".htmlspecialchars($tdRow[$col])."\" value=\"".htmlspecialchars($tdRow[$col])."\">";
						}
						$subcontent .= "<input type=\"text\" style=\"width:70%;float:right;\" name=\"update_${primaryKeys[0]}_".$tdRow[$primaryKeys[0]]."_".$col."_".md5($tdRow[$col])."\" value=\"".htmlspecialchars($tdRow[$col])."\"/></td>\n";
					}
					$subcontent.="\t</tr>\n";
				}
				$inserts="";
				foreach($cols as $col)
				{
					$inserts .="<td><input style=\"width:70%;float:right;\" type=\"text\" name=\"insert_${col['Field']}\"/></td>";
				}
				$subcontent.="<tr>\n${inserts}</tr>";
				$subcontent.='<tr>

			<td colspan="'.count($cols).'" style="text-align:center;"><input type="submit" name="submit" style="width:30%;float:left;" value="INSERT"/><input type="submit" name="submit" style="width:30%;" value="UPDATE"/><input type="submit" name="submit" style="width:30%;float:right;" value="DELETE"/></td>

		</tr>';
				$subcontent .= "</table></form>\n";
			}
		}
		$content=str_replace("{TABLES}",$subcontent,$content);
		return $content;
	}

	// Backup Administration
	public function backupAdm($content,$configs)
	{
		global $funcsperformed;
		if($_GET['func']=="get")
		{
			header("Content-type: application/octet-stream");
			header("Content-disposition: attachment; filename=${_GET['backup']}");
			echo file_get_contents($configs['path']['tmpdir']."/${_GET['backup']}");
			die();
		}
		$files=$this->createDirTree($configs['path']['tmpdir'],1);
		if($_GET['func']=="clear")
			foreach($files as $file=>$n)
				@unlink($configs['path']['tmpdir']."/$n");
		else if($_GET['func']=="create" && $funcsperformed==0)
		{
			$funcsperformed++;
			$this->createBak($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'],$configs,$_POST['btype']);
		}
		$backups="";
		foreach($files as $file=>$n)
			if(preg_match("/^SS_([a-z]*)_[a-f0-9]*\.bak\.zip$/si",$n,$matches) && $matches)
			{
				$backups.=file_get_contents($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_backup_BACKUPSAVAIL.template");
				$backups=str_replace("{FILE}",$n,$backups);
				$backups=str_replace("{TYPE}",$matches[1],$backups);
				$backups=str_replace("{DATE}",date("M j, Y G:i:s",filemtime($configs['path']['tmpdir']."/$n")),$backups);
			}
		$content=str_replace("{BACKUPSAVAIL}",$backups,$content);
		return $content;
	}
	public function createBak($maindir,$configs=array(),$type="all",$curdir=NULL,$zipdir="",$zip=NULL,$isChild=FALSE)
	{
		$dbconf=$configs['database'];
		// Create the zip
		if($zip==NULL)
		{
			$zipname=$configs["path"]["tmpdir"]."SS_${type}_".md5((string)time()).".bak.zip";
			$zip=new ZipArchive();
			$zip->open($zipname,ZipArchive::CREATE);
		}
		
		// Setup depending on the backup type
		if($type=="db" || $type=="all")
		{
			$sqlcode="";
			$tables=array();
			$tableNames=$this->db->sdbGetTables();

			// I'm not sure how portable this is going to be tbh...but it works for now
			foreach($tableNames as $table)
			{
				$tables[$table]=array_keys($this->db->sdbGetColumns($table));
				$sqlcode.="\n-- - Creating table...${table}\n".str_replace("\n","",$this->db->sdbGetTblCreate($table)).";\n";
				$insertBaseStr="INSERT INTO `${table}` (`".implode("`, `", $tables[$table])."`) VALUES (";
				$res=$this->db->rawQry("SELECT * FROM `${table}`;", array(), false);
				foreach($res as $row)
				{
					$sqlcode.=$insertBaseStr;
					$values=array();
					foreach($tables[$table] as $column)
					{
						$values[]=str_replace("'","\\'",str_replace("\\","\\\\",$row[$column]));
					}
					$sqlcode.="'".implode("', '",$values)."'";
					$sqlcode.=");\n";
				}
			}
			$f=fopen($configs['path']['tmpdir']."/${dbconf['database']}_db_backup.sql","w");
			fwrite($f,$sqlcode);
			fclose($f);

			$zip->addFile($configs['path']['tmpdir']."/${dbconf['database']}_db_backup.sql","${dbconf['database']}_db_backup.sql");
			if($type=="db")
			{
				$zip->close();
				@unlink($configs['path']['tmpdir']."/${dbconf['database']}_db_backup.sql");
				return;
			}
		}
		else if($type=="mods" && $curdir==NULL)
		{
			$zip->addEmptyDir("includes");
			$zip->addEmptyDir("templates");
			$maindir=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/";
			$mtdir=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates'];
			$this->createBak($mtdir,$configs,$type,(($curdir==NULL)?$mtdir:$curdir),"templates",$zip,TRUE);
			$zipdir="includes";
		}
		else if($type=="temps" && $curdir==NULL)
		{
			$zip->addEmptyDir("Current Theme");
			$zip->addEmptyDir("Modules");
			$maindir=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['templates'];
			$mtdir=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates'];
			$this->createBak($mtdir,$configs,$type,(($curdir==NULL)?$mtdir:$curdir),"Modules",$zip,TRUE);
			$zipdir="Current Theme";
		}
		else if($type=="back")
		{
			$basedir=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'];
			$zip->addEmptyDir("includes");
			$files=array("index.php","include.php",$configs['path']['configs'],"includes/simpledisplay.class.php","includes/simplemodule.interface.php","includes/simplesite.class.php","includes/simpleutils.class.php");
			foreach($files as $file)
				$zip->addFile($basedir.$file,$file);
			$zip->close();
			return;
		}
		
		// Add whatever directory we're in to the zip file
		if($curdir==NULL)
			$curdir=$maindir;
		$dir=@opendir($curdir);
		while(($file=@readdir($dir)))
		{
			if(is_dir($curdir."/".$file) and !($file=="." or $file=="..") and !(is_link("$curdir/$file")))
			{
				if($zipdir!="")
					$newfile="$zipdir/$file";
				else
					$newfile=$file;
				$zip->addEmptyDir("$newfile");
				$this->createBak($maindir,$configs,$type,"$curdir/$file","$newfile",$zip,TRUE);
			}
			else if(!($file=="." or $file==".."))
			{
				if($zipdir!="")
					$newfile="$zipdir/$file";
				else
					$newfile=$file;
				$zip->addFile("$curdir/$file","$newfile");
			}
		}

		// Check if we're in a child function (to avoid closing the zip file prematurely)
		if($isChild==FALSE)
		{
			$zip->close();
			@unlink($configs['path']['tmpdir']."/${dbconf['database']}_db_backup.sql");
		}
	}
	public function restoreBak()
	{
	}

	public function blockingAdm($content,$configs) // I.P. Blocking Configurations
	{
		global $funcsperformed;
		$blockTbl=$this->db->openTable("blocked");
		if($_POST['submit']=="Delete" && $funcsperformed<1)
		{
			$funcsperformed++;
			$keys=array_keys($_POST);
			$delKeys=preg_grep("/delete_(.*)/si",$keys);
			foreach($delKeys as $delKey)
			{
				preg_match("/delete_(.*)/si",$delKey,$matches);
				$deletedId=$matches[1];
				$blockTbl->delete(array('id'=>array('op' => '=','val' => $deletedId)));
			}
		}
		else if($_POST['submit']=="Add" && $funcsperformed<1)
		{
			$funcsperformed++;
			$blockTbl->insert(array('remote_addr' => $_POST['add_remote_addr']));
		}
		else if($funcsperformed>=1)
		{
			SimpleDebug::logInfo("Functions already performed...");
		}

		$table="";
		$blockTbl->resetRows();
		$blockTbl->select('*');
		foreach($blockTbl->sdbGetRows() as $row)
		{
			$table.=file_get_contents($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_blocked_BLOCKTBL.template");
			$table=str_replace("{id}",$row->getId(),$table);
			$table=str_replace("{remote_addr}",$row->getRemote_addr(),$table);
			$table=str_replace("{date}",$row->getDate(),$table);
		}

		$content=str_replace("{BLOCKTBL}",$table,$content);
		return $content;
	}
	
	// Module Administration
	public function modAdmin($content,$configs)
	{
		if(@($_GET['func'])=="toggleEnabled")
			$this->toggleMod(@($_GET['module']),@($_GET['currentState']),$configs);
		else if(@($_GET['func'])=="upload")
			$this->uploadMod($configs);
		$this->loadModules($configs);
		$modsAvailable=array();
		$modsAvailable['enabled']=$this->mods;
		$this->mods=array();
		$this->loadModules($configs,false);
		$modsAvailable['disabled']=$this->mods;
		natcasesort($modsAvailable["enabled"]);
		natcasesort($modsAvailable["disabled"]);
		return str_replace("{MODULES}",$this->mods2Feed($modsAvailable,$configs),$content);
	}
	public function mods2Feed($modsAvailable,$configs) // This should be replaced with SimpleUtils::arr2Feed() with the array coming from a mods table maybe?
	{
		$feed="";
		$x=0;

		global $loadDisabled;
		$oldLD=$loadDisabled;
		$loadDisabled=true;
		foreach($modsAvailable as $mods)
		{
			$x++;
			foreach($mods as $mod)
			{
				$f=@fopen($_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_modAdmin_modules.template","r");
				while(($line=fgets($f)))
					$feed.=$line;
				$feed=str_replace("{MODFILE}",$mod,$feed);
				if(isset($mod::$info))
				{
					if(isset($mod::$info['name']))
					{
						$feed=str_replace("{NAME}", $mod::$info['name'], $feed);
					}
					else
					{
						$feed=str_replace("{NAME}", $mod, $feed);
					}
						if(isset($mod::$info['author']))
					{
						$feed=str_replace("{AUTHOR}", $mod::$info['author'], $feed);
					}
					else
					{
						$feed=str_replace("{AUTHOR}", "No data available...", $feed);
					}
					if(isset($mod::$info['date']))
					{
						$feed=str_replace("{DATE}", $mod::$info['date'], $feed);
					}
					else
					{
						$feed=str_replace("{DATE}", "No data available...", $feed);
					}

				}
				$feed=str_replace("{ENABLED}",(($x==1)?"Yes":"No"),$feed);
			}
		}
		$loadDisabled=$oldLD;
		return $feed;
	}
	public function toggleMod($module,$currentState,$configs) // TODO: SimpleFile class
	{
		$file=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/".((strtolower($currentState)=="yes")?"enabled":"disabled")."/$module.mod.php";
		$newfile=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/".((strtolower($currentState)=="yes")?"disabled":"enabled")."/$module.mod.php";
		if(is_file($file))
			if(copy($file,$newfile))
				unlink($file);
	}
	public function uploadMod($configs)
	{
		$error=0;
		$zipname=$_FILES['rfile']['name'];
		$tmpname=$_FILES['rfile']['tmp_name'];
		if(preg_match("/([^\/])*.zip/si",$zipname,$match) && $match[0]!="")
			$modname=str_replace(".zip","",$match[0]);
		else $error=1;
		$extractDir=$configs['path']['tmpdir']."/$modname";
		$zip=new ZipArchive();
		if($zip->open($tmpname) && $error==0)
		{
			$zip->extractTo($extractDir);
			$zip->close();
			if(is_dir("$extractDir/includes") && is_dir("$extractDir/templates"))
			{
				if(is_file("$extractDir/includes/$modname.mod.php"))
				{
					if(copy("$extractDir/includes/$modname.mod.php",$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/disabled/$modname.mod.php"))
						unlink("$extractDir/includes/$modname.mod.php");
					else $error=1;
				}
				else $error=1;
			}
			else $error=1;
			if(is_dir("$extractDir/templates"))
				$this->recursiveDirCopy("$extractDir/templates",$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']);
			else $error=1;
			$this->recursiveDirDelete($extractDir);
		}
		else $error=1;
		return $error;
	}
	
	// Template Administration
	public function templateAdmin($content,$configs)
	{
		$basedir=$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"];
		if(@($_POST['submit']=="New"))
		{
			$_POST['filename']=(($_POST['newtemptype']=="")?"2_":(($_POST['newtemptype']=="mod")?"1_":"0_")).$_POST['newtempfile'];
			if(!(file_exists(((preg_match("/([0-9])_(.*)/si",@($_POST['filename']),$match) && $match)?(($match[1]==0)?$basedir.$configs['path']['templates']."/${match[2]}":$basedir.(($match[1]==1)?$configs['path']['mod_templates']:$configs['path']['custom_templates'])."/${match[2]}"):""))))
				$_POST['submit']="Save";
		}
		$filepath=((preg_match("/([0-9])_(.*)/si",@($_POST['filename']),$match) && $match)?(($match[1]==0)?$basedir.$configs['path']['templates']."/${match[2]}":$basedir.(($match[1]==1)?$configs['path']['mod_templates']:$configs['path']['custom_templates'])."/${match[2]}"):"");
		switch($_POST['submit'])
		{
			case "Save":
				$f=@fopen($filepath,"w");
				@fwrite($f,$_POST['filecontent']);
				@fclose($f);
				break;
			case "Delete":
				unlink($filepath);
				break;
			case "Upload":
				$f=$_FILES['file'];
				if((preg_match("/(.*)(\.template|\.jpg|\.gif|\.jpeg|\.png|\.)$/si",@($f['name']),$match) && $match))
				{
					switch($_POST['temptype'])
					{
						case "":
							$dest=$basedir.$configs['path']['custom_templates']."/${f['name']}";
							break;
						case "mod":
							$dest=$basedir.$configs['path']['mod_templates']."/${f['name']}";
							break;
						case "theme":
							$dest=$basedir.$configs['path']['templates']."/${f['name']}";
							break;
						default:
							$dest=$basedir.$configs['path']['templates']."/${f['name']}";
							break;
					}

					@copy($f['tmp_name'],$dest);
					@unlink($f['tmp_name']);
				}
				break;
		}		
		$content=str_replace("{TEMPFILES}",$this->genDirTreeOut($this->createDirTree($basedir.$configs['path']['templates']),$basedir.$configs['path']['templates'],"&nbsp;&nbsp;","<option value=\"{TYPE}{PREVDIRS}{FILE}\"{SELECTED}>{DEPTH}{FILE}</option>\n","[DIR] ",0,"",1),$content);
		$content=str_replace("{MODTEMPFILES}",$this->genDirTreeOut($this->createDirTree($basedir.$configs['path']['mod_templates']),$basedir.$configs['path']['mod_templates'],"&nbsp;&nbsp;","<option value=\"{TYPE}{PREVDIRS}{FILE}\"{SELECTED}>{DEPTH}{FILE}</option>\n","[DIR] ",1,"",1),$content);
		$content=str_replace("{CUSTOMTEMPLATES}",$this->genDirTreeOut($this->createDirTree($basedir.$configs['path']['custom_templates']),$basedir.$configs['path']['custom_templates'],"&nbsp;&nbsp;","<option value=\"{TYPE}{PREVDIRS}{FILE}\"{SELECTED}>{DEPTH}{FILE}</option>\n","[DIR] ",2,"",1),$content);
		$content=str_replace("{TEMPFILE}",@$_POST['filename'],$content);
		$content=str_replace("{FILECONTENT}",((preg_match("/([0-9])_(.*)/si",@($_POST['filename']),$match) && $match)?(($match[1]==0)?$this->simpleFilter(file_get_contents($basedir.$configs['path']['templates']."/${match[2]}"),false):$this->simpleFilter(file_get_contents($basedir.(($match[1]==1)?$configs['path']['mod_templates']:$configs['path']['custom_templates'])."/${match[2]}"),false)):""),$content);

		return $content;
	}
	
	// Widget Administration
	public function widgetAdmin($content,$configs) // More need for simpleFile
	{
		switch($_GET['func'])
		{
			case "delete":
				$this->delWidget($configs);
				break;
			case "upload":
				$this->uploadWidget($configs);
				break;
		}
		$widgetsTxt="";
		$widgetsArr=array();
		$widgetsDir=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/widgets/";
		$dir=opendir($widgetsDir);
		while(@($file=readdir($dir)))
			if(preg_match("/(.*)\.widget\.php$/si",$file,$matches) && (@$matches))
				$widgetsArr[]=$matches[1];
		foreach($widgetsArr as $widget)
			$widgetsTxt.="<tr><td><input type=\"checkbox\" name=\"delete_".htmlspecialchars($widget)."\" value=\"".htmlspecialchars($widget)."\"/>$widget</td><td>&#123;WIDGET_$widget&#125;</td><td>".date("m/d/Y",filemtime($widgetsDir.$widget.".widget.php"))."</td></tr>";
		return str_replace("{WIDGETS}",$widgetsTxt,$content);
	}
	public function delWidget($configs)
	{
		$keys=array_keys($_POST);
		$delKeys=preg_grep("/delete_(.*)/si",$keys);
		foreach($delKeys as $delKey)
		{
			preg_match("/delete_(.*)/si",$delKey,$matches);
			$widgetname=$matches[1];
			$widget=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/widgets/$widgetname.widget.php";
			@unlink($widget);
		}
	}
	public function uploadWidget($configs)
	{
		$error=0;
		$filename=$_FILES['rfile']['name'];
		$tmpname=$_FILES['rfile']['tmp_name'];
		if(preg_match("/([^\/])*.widget.php/si",$filename,$match) && $match[0]!="")
			$widgetname=str_replace(".widget.php","",$match[0]);
		else $error=1;
		if(copy($tmpname,$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/widgets/$widgetname.widget.php"))
			unlink($tmpname);
		else $error=1;
		return $error;
	}

	
	// Theme Management
	public function themeMgr($content,$configs)
	{
		$content=str_replace("{THEMES}",$this->genDirTreeOut($this->createDirTree($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['themes'],1),$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['themes'],"&nbsp","<option value=\"{FILE}\"{SELECTED}>{FILE}</option><br/>","",2),$content);
		if($_GET['func']=="upload")
		{
			$error=0;
			$zipname=$_FILES['rfile']['name'];
			$tmpname=$_FILES['rfile']['tmp_name'];
			if(preg_match("/(.*)\.zip/si",$zipname,$match) && $match[0]!="")
				$themename=$match[1];
			else $error=1;
			$extractDir=$configs['path']['tmpdir']."/$themename";
			$zip=new ZipArchive();
			if($zip->open($tmpname) && $error==0)
			{
				$zip->extractTo($extractDir);
				$zip->close();
				if(is_dir("$extractDir/$themename"))
					$this->recursiveDirCopy($extractDir,$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['themes']);
			}
			$this->recursiveDirDelete($extractDir);
		}
		else
		{
			if($_POST['submit']=="Set Theme")
			{
				$origconfig=file_get_contents($configs['path']['configs']);
				$f=@fopen($configs['path']['configs'],"w");
				@fwrite($f,str_replace("\$configs[\"default_theme\"]=\"${configs['default_theme']}\";","\$configs[\"default_theme\"]=\"${_POST['filename']}\";",$origconfig));
				@fclose($f);
				include($configs['path']['configs']);
				$_POST=array();
				$content=$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['templates']."/overall.template","adminCP");
			}
			else if($_POST['submit']=="Delete")
				$this->recursiveDirDelete($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['themes']."/${_POST['filename']}");
		}
		return $content;
	}
	
	// Test Environment
	function testEnvironment($content,$configs)
	{
		$results=array();
		if(!(is_dir($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['templates']))) // Check if templates directory exists, if not flag it
			$results[]="<ul>Templates directory does not exist.  How are you seeing this?";
		if(!(is_dir($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/"))) // Check if module directory exists, if not flag it
			$results[]="<ul>Modules directory does not exist, therefore no mods will be used.";
		if(!(is_file($configs['path']['configs']))) // Check if config file exists, if not flag it
			$results[]="<ul>Configuration file does not exist.";
		if($this->db->sdbGetErrorLevel()) // Database info is correct, if not flag it
			$results[]="<ul>Database connection information is incorrect.";
		if(!(class_exists("ZipArchive"))) // Check if ZipArchive is installed, if not flag it
			$results[]="<ul>ZipArchive is not installed, you will not be able to upload themes or modules.";
		if(!(is_writable($configs['path']['configs']))) // Check if configuration file is writable, if not flag it
			$results[]="<ul>Config file is not writable, you will not be able to edit it from the admin control panel.";
		if(!(is_writable($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['themes']))) // Check if themes directory is writable, if not flag it
			$results[]="<ul>Theme directory is not writable, you will not be able to upload new themes.";
		if(!(is_writable($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']))) // Check if mod templates directory is writable, if not flag it
			$results[]="<ul>Module templates directory is not writable, any modules you upload will not have the required templates.";
		if(!(is_writable($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['custom_templates']))) // Check if custom templates directory is writable, if not flag it
			$results[]="<ul>Custom templates directory is not writable, you will not be able to upload any files there.";
		if(!(is_writable($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods"))) // Check if modules directory is writable, if not flag it
			$results[]="<ul>Modules directory is not writable, you will not be able to upload any modules.";
		if($results==array())
			$results[]="No dependency or file permission errors, everything should work fine.";
		return str_replace("{DEPENDENCIES}",implode("</ul>",$results),$content);
	}
}
?>
