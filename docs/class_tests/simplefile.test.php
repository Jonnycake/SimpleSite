#!/usr/bin/php
<?php
include("../../includes/interfaces/simplefile.interface.php");
include("../../includes/classes/simplefile.class.php");

$stdin=fopen("php://stdin","r"); // Hehe, you can actually use SimpleFile for this :P
echo "\nOriginal Filename: ";
$filename=trim(fgets($stdin));
echo "New Filename: ";
$newfilename=trim(fgets($stdin));
echo "Copied Filename: ";
$newfilename2=trim(fgets($stdin));
fclose($stdin);

// Open the file up
$file=SimpleFile::openFile($filename);
$file->move($newfilename, false, $file); // Move it
$file->copy($filename, false, $fileoldcopy);
$file->copy($newfilename2, false, $filecopy);// Copy from the new location
$origContent=$file->readAll();

// Edit outside of SimpleSite class
$f=fopen($newfilename, "w");
fwrite($f, "${origContent}\n\tthis has been edited.");
fclose($f);

// Read the copied file
echo $filecopy->readAll();

// Delete the copied file
SimpleFileObject::delete($filecopy);

//$file=new SimpleFileInfo("test.txt");
//$file_object=$file->openFile();
/*$f=SimpleFile::openFile("test.txt.1");
$x=$f->readAll();
//$f->copy("something");
echo $f->md5sum()."\n";
//$f->move("test.txt.5", $f);
$file=fopen("test.txt.1", "w+");
fwrite($file, $x." this has been edited.");
fclose($file);
echo $f->md5sum()."\n";
echo $f->readAll();
//echo $f->getRealPath();
print_r($f);
/*$file=SimpleFile::openFile("something");
//echo $file->copy("test.txt");
$file->delete($file);*/
//$file->tail();
//var_dump($file);
//echo "Replaced $count times...";
//$f=SimpleFile::openFile("/home/jonathan/bigfile");
//echo $f->readAll();
?>
