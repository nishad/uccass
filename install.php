<?php
$query = '';
$error = FALSE;

include("config.class.php");

$ini_file = "survey.ini.php";

$c = new Config($ini_file);

if(count($_POST) > 0)
{
    if($c->process_config($ini_file))
    {
        include("survey.class.php");

        $survey = new Survey();

        if(!isset($survey->error_occurred))
        {
            $sql_file = 'survey.sql';
            $file = file($sql_file);
            foreach($file as $line)
            {
                if($line{0} != '#' && strlen($line) > 0)
                {
                    $query .= trim($line);
                    if(substr($query,-1) == ";")
                    {
                        $query = preg_replace('/^CREATE TABLE (`?)/','CREATE TABLE \\1' . $survey->CONF['db_tbl_prefix'],$query);
                        $query = preg_replace('/^INSERT INTO (`?)/','INSERT INTO \\1' . $survey->CONF['db_tbl_prefix'],$query);
                        $query = substr($query,0,-1);

                        $rs = $survey->db->Execute($query);
                        if($rs === FALSE)
                        {
                            $error = TRUE;
                            echo '<br><br>' . $query . $survey->db->ErrorMsg();
                        }
                        $query = '';
                    }
                }
            }

            if($error)
            { echo "<br><br>Installation was not successful due to the above errors."; }
            else
            {
                echo "Installation sucessful. To complete the installation, the install.php file must
                      be deleted or removed from the web root. Doing so will prevent anyone from re-running
                      your installation and aquiring your database information or changing your site's information.
                      <br><br>
                      Once complete, you may click <a href=\"{$survey->CONF['html']}/index.php\">here</a> to
                      begin using your Survey System";
            }
        }
    }
}
else
{ echo $c->form; }

?>