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
            switch($_REQUEST['installation_type'])
            {
                case 'upgrade_104':
                    include('upgrades/upgrade_104_105.php');
                    $sql_error = $c->load_sql_file('upgrades/upgrade_104_105.sql',TRUE);
                    $error = !$upgrade_104_105 | $sql_error;
                break;

                case 'newinstallation':
                    $sql_file = 'survey.sql';
                    $error = $c->load_sql_file($sql_file);
                break;

                case 'updateconfigonly':
                break;

                default:
                    $error = TRUE;
                    echo 'You did not choose an installation type. Please go back to the installation page and choose an installation type at the top of the page.';
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
{
    $form = $c->show_form();

    //Have PHP detect file and html paths and provide them
    //if the values are empty in ini file.
    include('pathdetect.class.php');
    $pd = new PathDetect;

    $form = str_replace('name="path" value=""','name="path" value="' . $pd->path() . '"',$form);
    $form = str_replace('name="html" value=""','name="html" value="' . $pd->html() . '"',$form);

    echo $form;
}

?>