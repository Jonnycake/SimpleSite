<?php
include("../../includes/classes/simplefile.class.php");

$stdin=fopen("php://stdin","r");
echo "File: ";
$filename=trim(fgets($stdin));
fclose($stdin);

$file=new SimpleFile($filename);
$file->open();

echo "Original:\n".$file->getContent()."\n";

//$file->delete();
?>
