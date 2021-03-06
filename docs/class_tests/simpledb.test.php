#!/usr/bin/php
<?php
include("../../includes/classes/simpledebug.class.php");
include("../../includes/classes/simpledb.class.php");
$stdin=fopen("php://stdin","r");

// Get database information
echo "DB Type: ";
$configs['type']=trim(fgets($stdin));
echo "DB Hostname: ";
$configs['host']=trim(fgets($stdin));
echo "DB Login: ";
$configs['username']=trim(fgets($stdin));
echo "DB Password: ";
$configs['password']=trim(fgets($stdin));
echo "DB Database: ";
$configs['database']=trim(fgets($stdin));
echo "DB Table Prefix: ";
$configs['tbl_prefix']=trim(fgets($stdin));

fclose($stdin);

// Tests to perform, may assume presence of specific tables
$relationships=array( 
			"role_privs" => array(
						"id"=>"role_id",
						"privileges"=>array(
							"priv_id"=>"id",
						)
					)
		);
$tests=array(

		array(
			"function"    => "rawQry",
			"description" => "Test Raw Queries",
			"queries"     => array(
						"SELECT username FROM SS_admins;"
					)
		),
		array(
			"function"    => "select",
			"description" => "Test Joins",
			"queries"     => array(
						array(
							'*',
							array(),
							array(
								'JOIN'  => $relationships,
								'JTYPE' => 'LEFT'
							)
						 )
					 )
		)
);


$sdb=new SimpleDB($configs,true); // Inherent test of constructor
$tbl=$sdb->openTable("roles");
//echo $tbl->join("Table_1", $relationships, "LEFT");
//$tbl->select('*', array(), array('JOIN'=>$relationships, 'JTYPE'=>'LEFT'));
//var_dump($tbl->sdbGetRows());
if($sdb->connected())
{
	foreach($tests as $test)
	{
		switch($test['function'])
		{
			case "rawQry":
				echo "Testing raw queries...\n";
				foreach($test['queries'] as $query)
				{
					echo $query."\n";
					$rows=$sdb->rawQry($query,array(),false);
					print_r($rows);
					echo "\n";
				}
				break;
			case "select":
				echo "Testing select function...\n";
				foreach($test['queries'] as $query)
				{
					$tbl->select($query[0], $query[1], $query[2]);
					var_dump($tbl->sdbGetRows());
				}
			default:
				echo "Unknown function: ${test['function']}.\n";
				break;
		}
	}
}
else
{
	echo "Couldn't connect to the database. :(\n";
}
?>
