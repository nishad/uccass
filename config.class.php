<?php

/**********************
* CONFIGURATION CLASS *
***********************
*
* (c) 2003 U.S. Army
* All Rights Reserved
*
* This class will load a standard
* php.ini style configuration file
* and create a form to edit the
* values within the file. The class
* will accept the changes and re-write
* the file with the new values
***********************/

class Config
{
    /**************
    * CONSTRUCTOR *
    **************/
    function Config($file = '')
    {
        // Determine protocol of web pages
        if(isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'],'ON') == 0)
        { $this->CONF['protocol'] = 'https://'; }
        else
        { $this->CONF['protocol'] = 'http://'; }

        // HTML address of this program
        $this->CONF['html'] = $this->CONF['protocol'] . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']);

        // Determine web address of current page
        $this->CONF['current_page'] = $this->CONF['protocol'] . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'];

        // If $file was passed, load it
        if($file != '')
        { $this->load_file($file); }
        
        return;
    }
        
    /************
    * LOAD FILE *
    ************/
    function load_file($file)
    {
        // Check that file exists
        if(file_exists($file))
        {
            // Check that file can be
            // opened for reading
            if($fp = fopen($file,"r"))
            {
                // Read ini file and replace all
                // variable = value lines with
                // form elements
                $ini_file = fread($fp,filesize($file));
                $ini_file = str_replace("\\","\\\\",$ini_file);
                $ini_file = preg_replace('/^([a-z0-9_.-]+)\s?=\s?"?(.*)?"?$/im',
                                         "</pre>$1: <input type=\"text\" name=\"$1\" value=\"$2\"></td></tr><tr><td><pre>\n",
                                         $ini_file);
                
                //Strip semi-colons from the beginning
                //of comments and apply htmlentities()
                //to the rest of the data.
                $ini_file = preg_replace('/^;(.*)$/em','@htmlentities(stripslashes("$1"));',$ini_file);
                
                $this->form = "<form method=\"POST\" action=\"{$this->CONF['current_page']}\">
                               <table cellpadding=\"4\" border=\"1\"><tr><td>
                               <pre>{$ini_file}</pre></td></tr>
                               <tr><td>
                               <input type=\"submit\" name=\"config_submit\" value=\"Save All Settings\">
                               </td></tr>
                               </form>";
            }
            else
            { $this->error("Cannot read configuration file: $file"); return; }
        }
        else
        { $this->error("Configuration file does not exist: $file"); return; }
        
        fclose($fp);
        
        return;
    }

    /*****************************
    * PROCESS CONFIGURATION FORM *
    *****************************/
    function process_config($file)
    {
        if(file_exists($file))
        {
            $fp = fopen($file,"r");
            $ini_file = fread($fp,filesize($file));
            fclose($fp);
            
            if($fp = fopen($file,"w"))
            {                              
                foreach($_POST as $key=>$value)
                {                     
                    if(preg_match("/[^a-z0-9]/i",$value))
                    { $value = '"' . $value . '"'; }
                    $ini_file = preg_replace("/^".$key."\s?=.*$/m","$key = $value",$ini_file);
                }
                
                if(!fwrite($fp,$ini_file))
                { $this->error("Cannot write to file"); return; }
                
                fclose($fp);
            }
            else
            { $this->error("Cannot write to file: $file"); return; }
        }
        else
        { $this->error("Config file does not exist: $file"); return; }
        
        return TRUE;
    }
    
    /***************
    * SUCCESS PAGE *
    ***************/
    function success()
    {
        echo "Configuration values have been saved.<br><br>
              Click on the link below to access the Survey System:
              <a href=\"{$this->CONF['html']}/index.php\">"
              .htmlentities($_POST['site_name'])."</a>";
        return;
    }
    
    /****************
    * ERROR HANDLER *
    ****************/
    function error($msg)
    {
        echo '<table width="50%" align="center" border="1">
              <tr><td>Error</td></tr><tr><td>' . $msg . '</td></tr>
              </table>';
        
        return;
    }
}

?>