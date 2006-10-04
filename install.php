<?php
$query = '';
$error = FALSE;

include('classes/main.class.php');
include('classes/config.class.php');

$ini_file = 'survey.ini.php';

$c = new UCCASS_Config($ini_file);

if(count($_POST) > 0)
{
    if($c->process_config($ini_file))
    {
        include('classes/survey.class.php');

        $survey = new UCCASS_Main();

        if(!isset($survey->error_occurred))
        {
            $error = FALSE;

            switch($_REQUEST['installation_type'])
            {
                case 'upgrade_104': //upgrade from 1.04 to 1.05
                    $sql_error1 = $c->load_sql_file('upgrades/upgrade_104_105-1.sql',TRUE);
                    include('upgrades/upgrade_104_105.php');
                    $sql_error2 = $c->load_sql_file('upgrades/upgrade_104_105-2.sql',TRUE);
                    $error = !$upgrade_104_105 | $sql_error1 | $sql_error2;
                    if(!$error)
                    { echo $c->lang('upgrade_v104_v105_good'); }

                case 'upgrade_105': // upgrade from 1.05 to 1.06
                    echo $c->lang('upgrade_v105_v106_good');

                case 'upgrade_106': //upgrade from 1.06 to 1.8.0
                    $sql_error = $c->load_sql_file('upgrades/upgrade_106_180.sql',TRUE);
                    $error = $error | $sql_error;
                    if(!$error)
                    { echo $c->lang('upgrade_v106_v180_good'); }

                case 'upgrade_180': //upgrade from 1.8.0 to 1.8.1
                    $sql_error = $c->load_sql_file('upgrades/upgrade_180_181.sql',TRUE);
                    $error = $error | $sql_error;
                    if(!$error)
                    { echo $c->lang('upgrade_v180_v181_good'); }

                case 'upgrade_181': //upgrade from 1.8.1 to 1.8.2
                    $sql_error = $c->load_sql_file('upgrades/upgrade_181_182.sql',TRUE);
                    $error = $error | $sql_error;
                    if(!$error) {
                        $successMsg = $c->lang('upgrade_v181_v182_good');
                    }
                	$ignoreData = true;

                case 'newinstallation':
                	require('classes/databasecreator.class.php');
                	$dbCreator = Uccass_DbCreator::createInstance();
                	if(isset($ignoreData))
                	{ ;$dbCreator->SetIgnoreData($ignoreData); }
                    if($dbCreator)
                    {
                        $success = $dbCreator->createDatabase($survey->CONF['db_tbl_prefix']);
                    	if($success)
                    	{
                    		if(!isset($successMsg))
                    		{ $successMsg = $c->lang('install_v182_good'); }
                    		echo $successMsg;
                    	}
                    }
                break;

                case 'updateconfigonly':
                    echo $c->lang('install_config_only');
                break;

                default:
                    $error = TRUE;
                    echo $c->lang('install_no_choose');
            }

            if($error)
            { echo $c->lang('install_bad'); }
            else
            {
                //Attempt to create directory to see if $use_sub_dirs needs to be turned off within Smarty
                $oldmask = umask(0);
                $dir = $survey->smarty->compile_dir;
                if(!mkdir($dir . '/test', 0777)) {
                    echo $c->lang('smarty_sub_dir_warning');
                }
                umask($oldmask);

                echo $c->lang('install_good');
                echo "<p><a href=\"{$survey->CONF['html']}\">" . $c->lang('begin') . '</a></p>';
            }
        }
    }
}
else
{
    $form = $c->show_form();

    //Have PHP detect file and html paths and provide them
    //if the values are empty in ini file.
    include('classes/pathdetect.class.php');
    $pd = new UCCASS_PathDetect;

    $form = str_replace('name="path" value=""','name="path" value="' . $pd->path() . '"',$form);
    $form = str_replace('name="html" value=""','name="html" value="' . $pd->html() . '"',$form);

    echo $form;
}

?>