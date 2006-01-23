<?php

$lang = array();

$lang['version'] = 'v1.8.2';

//Installation Text and Warnings (config.class.php)
$lang['conf_field_replacement'] = "</pre><strong>$1</strong>: <input type=\"text\" name=\"$1\" value=\"$2\" size=\"40\"></td></tr><tr><td><pre>\n";
$lang['conf_form_begin'] = '<html><head><title>UCCASS Configuration</title></head><body>
                               <form method="POST" action="install.php">
                               <table cellpadding="4" border="1"><tr><td>
                               Install Type:
                                 <select name="installation_type" size="1">
                                   <option value="">Choose...</option>
                                   <option value="updateconfigonly">Update Configuration Only</option>
                                   <option value="newinstallation">New Installation</option>
                                   <option value="upgrade_104">Upgrade From v1.04</option>
                                   <option value="upgrade_105">Upgrade From v1.05</option>
                                   <option value="upgrade_106">Upgrade From v1.06</option>
                                   <option value="upgrade_180">Upgrade From v1.8.0</option>
                                   <option value="upgrade_181">Upgrade From v1.8.1</option>
                                   <option value="upgrade_182">Upgrade From v1.8.2</option>
                                 </select>
                               </td></tr><tr><td>
                               <pre>';
$lang['conf_form_end'] = '</pre></td></tr>
                               <tr><td>
                               <input type="submit" name="config_submit" value="Save All Settings">
                               </td></tr>
                               </form>';
$lang['config_change_success'] = 'Configuration values have been saved.<br><br>
              Click on the link below to access the Survey System:';

//Configuration Warnings (main.class.php)
$lang['error'] = 'Error';
$lang['install_warning'] = 'WARNING: install.php file still exists. UCCASS will not run with this file present.
                            Click <a href="install.php">here</a> to run the installation program or move/rename the install.php
                            file so that the installation program can not be re-run.';
$lang['config_parse_error'] = 'Error parsing configuration file.';
$lang['config_not_found'] = 'Cannot find configuration file.';
$lang['config_not_read'] = 'Cannot read configuration file. Check permissions.';
$lang['config_not_write'] = 'Cannot write to configuration file. Check permissions.';
$lang['file_not_found'] = 'Cannot find file';
$lang['template_path_warning'] = 'WARNING: Cannot find default template path. Expecting: ';
$lang['template_path_writable_warning'] = 'WARNING: Smarty compiled template directory is not writable. Please refer to the installation document for instructions.';
$lang['db_connect_error'] = 'Error connecting to database: ';

//Installation Version Notices
$lang['install_v104_v105_good'] = '<p><strong>Upgrade from v1.04 to v1.05 successful.</strong></p>';
$lang['install_v105_v106_good'] = '<p><strong>Upgrade from v1.05 to v1.06 successful.</strong></p>';
$lang['install_v106_v180_good'] = '<p><strong>Upgrade from v1.06 to v1.8.0 successful.</strong></p>
                                   <strong>v1.8.0 Notice</strong>: A default administrator user was created with a
                                   username of &quot;admin&quot; and a password of &quot;password&quot;. Because
                                   of the changes in the access controls for v1.8.0, you will need to use the default
                                   Admin user to reset the access controls on all of your surveys. v1.8 no longer uses
                                   edit, take or results passwords, but instead allows you to create users for each survey
                                   and control what each user has access to. Any existing surveys have been changed to
                                   no access control (anyone can take them) and private results (only admin can see them).
                                   If you had private surveys or public results, use the default Admin user to recreate
                                   those access controls with the new system.<br /><br />';
$lang['install_v180_v181_good'] = '<p><strong>Upgrade from v1.8.0 to v1.8.1 successful.</strong></p>';
$lang['install_181_good'] = '<p><strong>Upgrade from v1.8.1 to v1.8.2 successful.</strong></p>';
$lang['upgrade_182_good'] = '<p><strong>Upgrade successful.</strong></p>';
$lang['install_v181_good'] = '<p><strong>New installation of v1.8.1 completed (and database tables were created) successfully.</strong></p>';
$lang['install_v182_good'] = '<p><strong>New installation of v1.8.2 completed (and database tables were created) successfully.</strong></p>';
$lang['install_config_only'] = '<p><strong>Configuration updated successfully.</strong></p>';
$lang['install_no_choose'] = '<p>You did not choose an installation type. Please go back to the installation page
                              and choose an installation type at the top of the page.</p>';
$lang['install_bad'] = '<p>Installation was not successful due to the above errors.</p>';
$lang['install_good'] = '<p>Installation sucessful. To complete the installation, the <strong>install.php</strong> file must
                         be deleted or removed from the web root. Doing so will prevent anyone from re-running
                         your installation and aquiring your database information or changing your site\'s information.</p>
                         <p>Default Credentials:<br />
                         <blockquote>
                         <strong>Username:</strong> admin<br />
                         <strong>Password:</strong> password<br />
                         </blockquote>
                         It is advised that you change the login and password immediately.</p>
                         <p>Click on the link below to begin using your Survey System.</p>';
?>