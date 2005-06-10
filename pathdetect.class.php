<?php

class PathDetect
{
    var $path;
    var $html;

    function PathDetect()
    {
        //Install path
        if(isset($_SERVER['PATH_TRANSLATED']) && !empty($_SERVER['PATH_TRANSLATED']))
        { $this->CONF['path'] = dirname($_SERVER['PATH_TRANSLATED']); }
        elseif(isset($_SERVER['SCRIPT_FILENAME']) && !empty($_SERVER['SCRIPT_FILENAME']))
        { $this->CONF['path'] = dirname($_SERVER['SCRIPT_FILENAME']); }
        else
        { $this->CONF['path'] = ''; }

        //Determine protocol of web pages
        if(isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'],'ON') == 0)
        { $protocol = 'https://'; }
        else
        { $protocol = 'http://'; }

        //HTML address of this program
        $dir_name = dirname($_SERVER['PHP_SELF']);
        if($dir_name == '\\')
        { $dir_name = ''; }

        $port = '';
        if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443')
        { $port = ':'.$_SERVER['SERVER_PORT']; }

        $this->CONF['html'] = $protocol . $_SERVER['SERVER_NAME'] . $port . $dir_name;
    }

    function path()
    { return $this->CONF['path']; }

    function html()
    { return $this->CONF['html']; }
}
?>