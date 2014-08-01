#!/usr/bin/php
<?php
include("../../includes/classes/simplefile.class.php");

$stdin=fopen("php://stdin","r"); // Hehe, you can actually use simplefile for this :P
echo "URL: ";
$filename=trim(fgets($stdin));
fclose($stdin);

$file=new SimpleFile($filename, false, "/", true);
$file->open();
echo $file->getContent();
/*echo "Original:\n".$file->getContent()."\n";
$file->close();

$stdin=fopen("php://stdin","r"); // Hehe, you can actually use SimpleFile for this :P
echo "\nOriginal Filename: ";
$filename=trim(fgets($stdin));
echo "New Filename: ";
$newfilename=trim(fgets($stdin));
echo "Second New Filename: ";
$newfilename2=trim(fgets($stdin));
fclose($stdin);

$file=new SimpleFile($filename, false, "/", true);
var_dump($file);
*///$file->copy($newfilename);
//$file->move($newfilename);
var_dump($file);
$file->move("somethingdifferent.txt");
var_dump($file);
$file->close();
?>
