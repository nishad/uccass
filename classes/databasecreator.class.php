<?php
//======================================================
// Copyright (C) 2004 John W. Holmes, All Rights Reserved
//
// This file is part of the Unit Command Climate
// Assessment and Survey System (UCCASS)
//
// UCCASS is free software; you can redistribute it and/or
// modify it under the terms of the Affero General Public License as
// published by Affero, Inc.; either version 1 of the License, or
// (at your option) any later version.
//
// http://www.affero.org/oagpl.html
//
// UCCASS is distributed in the hope that it will be
// useful, but WITHOUT ANY WARRANTY; without even the implied warranty
// of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// Affero General Public License for more details.
//====================================================== 

/*/ Make sure adodb-xmlschema.inc.php is included
if( !defined( '_ADODB_LAYER' ) ) {
	$adodb_dir = 'ADOdb';	
	$adodb_files = array('adodb.inc.php', 'adodb-datadict.inc.php', 'adodb-xmlschema.inc.php');
	foreach($adodb_files as $key => $fileName) { 
		$fileName = $adodb_dir . '/' . $fileName;
		require_once($fileName);
	}
}*/

$error = FALSE;

require_once('main.class.php');

/**
 * Creates tables [and sequences] for UCCASS using an adodb xmlschema of it.
 * If some of the objects (tables, indices, sequences) exist already they're
 * not dropped but modified to fit the (new) definition.
 * 
 * @author <a href="mailto:jakubholy@jakubholy.net">Jakub Holy</a>
 * @version $id$
 */
class Uccass_DbCreator {
	
	/** @var object ADOConnection connected to a database to which to apply the schema. */
	var $adoConnection = NULL;
	
	/** Print an error. */
	function err($msg,$doExit=1){ echo "<h5 style='color: red'>$msg</h5>"; if($doExit){exit;} }
	/** Print an info message. */
	function msg($msg){ echo "<h5 style='color: green'>$msg</h5>"; }
	
	/** an adoSchema */
		var $schema;
	
	/**
	 * Static function: Create an instance of Uccass_DbCreator with an
	 * adoconnection set.
	 * @return mixed a new Uccass_DbCreator or false if the creation failed
	 */
	function createInstance()
	{
		$survey = new UCCASS_Main();	// To access config and ADOConnection
		
		if(!isset($survey->error_occurred))
		{
		    if(!$survey->db) 
		    { return false; }
			else			
			{
				// Include needed files
				foreach( array('adodb-datadict.inc.php', 'adodb-xmlschema.inc.php') as $adodb_file )
				{
					$adodb_file = $survey->CONF['adodb_path'] .'/'. $adodb_file;
			        if(file_exists($adodb_file))
			        { require_once($adodb_file); }
			        else
			        { 
			        	$survey->error($survey->lang['file_not_found'] . ': ' . $adodb_file); 
			        	return false; 
			        }
				}
				// Create the $dbCreator
				return $dbCreator = new Uccass_DbCreator($survey->db); 
			}
			//$sql = $dbCreator->createDatabase($survey->CONF['db_tbl_prefix']);
		}
		else
		{ return false; }
	} // createInstance
	
	/** 
	 * Create a new creator for the given database.
	 * @param adoConnection object ADOConnection connected to a database to
	 * which to apply the schema. 
	 */
	function Uccass_DbCreator(&$adoConnection) {
		$this->adoConnection = &$adoConnection;
		$this->schema = new adoSchema( $this->adoConnection );
	} // UccassDbCreator
	
	/** See adoSchema->SetUpgradeMethod */
	function SetUpgradeMethod($method)
	{ return $this->schema->SetUpgradeMethod($method); }
	
	/**
	 * Connect to the DB and create tables, sequences, insert initial data.
	 * NOTE: Include 'adodb-datadict.inc.php', 'adodb-xmlschema.inc.php'.
	 * 
	 * @return mixed On success return the sql executed (without sequences), on
	 * failure return false.
	 */
	function createDatabase($name_prefix = '', $schemaFile = 'survey-adodb_schema.xml') {
		
		// 1. CREATE TABLES + INDICES via adodb-xmlschema, insert data
		error_reporting(E_ALL ^ E_NOTICE);	// ignore notices about ' Only variable references should be returned by reference' etc.
		$schema = &$this->schema;
		if($schema->SetPrefix($name_prefix, false) !== TRUE)
		{ 
			echo "<h3>ERROR - Uccass_DbCreator.createDatabase: SetPrefix failed (perhaps too long or invalid format [start with a letter, contain only chars/numbers/_]?).</h3>";
			return FALSE;
		}
		$schema->ParseSchema( $schemaFile );
		// $this->msg("Schema parsed - going to execute it.");
		$schema->ContinueOnError(true);
		$result_nr = $schema->ExecuteSchema(); // 0 failed, 1 errors, 2 success
		$resultMsg = array(0 => 'failed', 1 => 'errors', 2 => 'success' );
		// $xml = $schema->ParseSchema( $schemaFile, TRUE ); // DEBUG
		// echo 'XML:<code><pre>'.htmlspecialchars($xml) . '</pre></code>';
		if($result_nr != 2) 
		{
			return false;
			//$this->err('Schema execution hasn\'t succeded. Reason: ' . $resultMsg[$result_nr]);
		} 
		// else 
		// { $this->msg('Schema successfully executed [tables and indices created, data inserted]'); }
		return $schema->PrintSQL( 'TEXT' ); // sql as text
	} // createDatabase
} // UccassDbCreator
?>
<!-- ######################################################################################################## -->
<?php
/*
// 1.CONNECT TO THE DB
$mysqlNotPostgres = false;
$dbName = 'delme';

$db = NewADOConnection( ($mysqlNotPostgres)?'mysql':'postgres7' );
if($db != null){ $conn = $db->Connect('candy:'.(($mysqlNotPostgres)?'6306':'5432'),'uccass','uccass',$dbName); }
else { err("Couldn't create adodb connection object"); }

if(!$conn) {
	err("Connection to db failed!",0);
	$db->ErrorNo();echo "xxx";
    err('Error connecting to database: '. $db->ErrorMsg() );
}
	
			
$dbCreator = new UccassDbCreator($db);
$sql = $dbCreator->createDatabase();
echo '<hr>'.$sql;
*/
?>
