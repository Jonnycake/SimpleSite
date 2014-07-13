    <?php
    if(SIMPLESITE!=1)
        die("Can't access this file directly.");
    class testModule extends SimpleDisplay implements simpleModule
    {
        public static $info=array(  "author"  => "Jon Stockton",
                                    "name"    => "Test Module",
                                    "version" => "1.0",
                                    "date"    => "April 9, 2012"
                                );
					  
        public function choosePage()
        {
            return "";
        }
        public function sideparse($content)
        {
            return str_replace("{TEST}","Hello World!",$content);
        }
        public function isInstalled($configs=array())
        {
            return $this->checkReqFiles(
                                        array(
                                         $_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/testModule.template"
                                        ),
                                        $configs
                                       );
        }
        public function install($configs=array())
        {
            $this->installReqFiles(array("testModule.template" => "e1RFU1R9"),$configs);
            return TRUE;
        }
        public function getContent($configs=array())
        {
            return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/testModule.template","testModule");
        }
    }
    ?>
