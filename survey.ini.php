;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; UCCASS CONFIGURATION FILE ;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;
; Unit Command Climate Assessment and
; Survey System
;
;
; <?php exit(); ?>
; Do not remove the above line. This line will
; prevent this file from being read if called
; through a web server
;
; Edit the following lines to
; match your configuration
;
; Any non-alphanumeric values must
; be enclosed in double quotes unless
; the values are being entered through
; the web interface
; OK: variable = 1
; OK: variable = word
; BAD: variable = this&that or a space
; BAD: variable = 'this&that'
; OK: variable = "this&that"
;
;;;;;;;;;;;;;;;;;
; Path Examples ;
;;;;;;;;;;;;;;;;;
; Windows: c:\\path\\to\\directory
; Windows: c:/path/to/directory
; Windows: /path/to/directory (if on C: drive)
; *nix: /path/to/dir

;;;;;;;;;;;;;;
; SITE SETUP ;
;;;;;;;;;;;;;;

; Site Name
;
; Will appear in Title Bar and Main Page
site_name = "Unit Command Climate Assessment and Survey System (UCCASS)"

; Default Template
;
; Default template to use for the
; main site and surveys. This must
; match the name of a Directory in
; the templates/ folder.
default_template = Default

; Administrator Password
;
; Used to log into Admin area
admin_password = password

; Page Break Text
;
; This is the text the users will enter
; into the text box to create a page
; break in their surveys. The text is
; case insensitive.
page_break = "%PAGE BREAK%"

; Text Results
;
; Number of text results to show
; per page when viewing text answers
; to surveys.
text_results_per_page = 50

; Image Extensions
;
; Comma separated list of the file extensions
; that bar graph images are allowed to have
image_extensions = "gif,jpg,jpeg,png"

; Image Width
;
; Width of image (in pixels) used
; for 100% answers on the
; survey results page
image_width = 200

; Filter Limit

; If the number of completed surveys returned
; from a filtered result set is less than or
; equal to this number, the filtered results
; will not be shown. This is to maintain
; anonominity because the answers could
; possibly be filtered such that the results
; from a single person could be identified.
; IT IS STRONGLY RECOMMENDED YOU KEEP THIS
; THIS NUMBER AT 3 OR HIGHER TO MAINTAIN
; ANONYMITY.
filter_limit = 3

; Track IP Addresses
;
; If set, the IP address
; of the user will be tracked in
; the 'ip_track' table. These IPs
; cannot be related back to the
; answers the user gave in any way.
; The program does not currently make
; use of the IP addresses, so you
; would need to implement a system to
; react to the stored IP addresses
; 0 = OFF
; 1 = ON
track_ip = 0

; Text Filter
;
; A comma separated list of words that
; will not be saved in the database if
; they are the sole response in text
; answers. For example, if users just
; type "none" or "n/a" into the text box,
; they will not be saved. Leave empty
; to not filter anything from user's answers
text_filter = "none, na, n/a, no, nothing, nope, asdf"

;;;;;;;;;;;;;;;;;;;;;;;;;
;Database Configuration ;
;;;;;;;;;;;;;;;;;;;;;;;;;


; Database Type (mysql, mssql, etc)
db_type = mysql

; Database Host
db_host = localhost

; Database User
db_user =

; Database Password
db_password =

; Database Name
db_database =

; Database Table Prefix
;
; Use this to create your tables for
; this survey program with
; a prefix, so they are not confused
; with other tables from other
; programs in the same database. Leave
; blank for no prefix and to use the
; default table names.
db_tbl_prefix =

;;;;;;;;;;;;;;;;;;;;;;;;
; SMARTY CONFIGURATION ;
;;;;;;;;;;;;;;;;;;;;;;;;

; Path to Smarty
;
; If you have your own installation
; of Smarty and do not want to use
; the one included with this program,
; provide the full system path to the
; Smarty.class.php file. Do not include
; trailing slash. Leave blank to use
; the version of Smarty included with
; this program.
smarty_path =

;;;;;;;;;;;;;;;;;;;;;;;
; ADOdb Configuration ;
;;;;;;;;;;;;;;;;;;;;;;;

; Path to ADOdb
;
; If you have your own installation of
; ADOdb, provide the full system path to
; the adodb.inc.php file. Do not include
; trailing slash. Leave blank to use the
; version of ADOdb that comes with the
; program.
adodb_path =

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; UCCASS Configuration File ;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;