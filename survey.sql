# MySQL-Front Dump 2.5
#
# Host: usap.gordon.army.mil   Database: uccass
# --------------------------------------------------------
# Server version 4.0.16-nt


#
# Table structure for table 'answer_types'
#

DROP TABLE IF EXISTS `answer_types`;
CREATE TABLE `answer_types` (
  `aid` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `type` enum('T','S','MS','MM','N') NOT NULL default 'T',
  `label` varchar(255) NOT NULL default '',
  `sid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`aid`)
) TYPE=MyISAM;



#
# Dumping data for table 'answer_types'
#

INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("1", "Textbox (Large)", "T", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("2", "Sentence (255 characters)", "S", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("3", "Yes / No", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("4", "True / False", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("5", "Agree / Disagree (5 options)", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("6", "Yes / No / Don&#039;t Know - Frequency", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("9", "Discrimination Types", "MM", "Check all that apply", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("10", "Apply to Extent", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("11", "High / Moderate / Low", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("12", "Yes / No - Affected By", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("13", "Well / Borderline / Never", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("14", "Like / Borderline / Dislike", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("15", "Yes / Maybe / Not", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("16", "Agree / Disagree / Don&#039;t Use Them", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("17", "Rank", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("103", "Time in Current Position", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("107", "Gender", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("108", "Race", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("24", "None", "N", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("106", "Supervisor", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("26", "Yes / No / NA", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("30", "None to Extremely High with Slight", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("101", "Assigned To Office", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("39", "Good / Okay / Bad", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("102", "Time on Fort Gordon", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("105", "Employee Grade", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("109", "Military / Civilian", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("110", "Agree / Disagree (3 options)", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("111", "Great Extent / Moderate / None", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("112", "None / Moderate / High", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("113", "Low / Moderate / High", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("115", "Sources", "MM", "Check all that apply", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("116", "Amount", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("117", "Amount (w/zero)", "MS", "", "2");
INSERT INTO `answer_types` (`aid`, `name`, `type`, `label`, `sid`) VALUES("118", "Medical Contacts", "MM", "Check all that apply", "2");


#
# Table structure for table 'answer_values'
#

DROP TABLE IF EXISTS `answer_values`;
CREATE TABLE `answer_values` (
  `avid` int(11) NOT NULL auto_increment,
  `aid` int(10) unsigned NOT NULL default '0',
  `value` varchar(255) NOT NULL default '',
  `group_id` int(10) unsigned NOT NULL default '0',
  `image` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`avid`),
  KEY `aid` (`aid`)
) TYPE=MyISAM;



#
# Dumping data for table 'answer_values'
#

INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("1", "3", "Yes", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("2", "3", "No", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("3", "4", "True", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("4", "4", "False", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("5", "5", "Strongly Agree", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("6", "5", "Agree", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("7", "5", "Neither agree nor disagree", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("8", "5", "Disagree", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("9", "5", "Strongly Disagree", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("10", "6", "No", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("11", "6", "Yes - Once in a while", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("12", "6", "Yes - Frequently", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("13", "6", "Yes - Very frequently", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("14", "6", "Don&#039;t know", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("164", "101", "U.S. Army Signal Center (eg. DOT, OCOS, TSMs)", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("25", "9", "No", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("26", "9", "Yes - National Orgin", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("27", "9", "Yes - Religious", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("28", "9", "Yes - Gender", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("29", "9", "Yes - Racial", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("30", "10", "Very great extent", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("31", "10", "Great extent", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("32", "10", "Moderate extent", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("33", "10", "Slight Extent", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("34", "10", "Not at all", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("35", "11", "Very high", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("36", "11", "High", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("37", "11", "Moderate", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("38", "11", "Low", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("39", "11", "Very Low", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("40", "12", "No", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("41", "12", "Yes - Did not affect me", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("42", "12", "Yes - Affected me", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("43", "13", "Very well", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("44", "13", "Well", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("45", "13", "Borderline", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("46", "13", "Poorly", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("47", "13", "Never", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("48", "14", "Like a lot", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("49", "14", "Borderline", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("50", "14", "Dislike", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("51", "15", "Definitely Yes", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("52", "15", "Cautiously Yes", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("53", "15", "Maybe", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("54", "15", "Probably Not", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("55", "15", "Definitely Not", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("56", "16", "Agree", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("57", "16", "Disagree", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("58", "16", "Don&#039;t use them", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("59", "17", "E1", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("60", "17", "E2", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("61", "17", "E3", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("62", "17", "E4", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("63", "17", "E5", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("64", "17", "E6", "6", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("65", "17", "E7", "7", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("66", "17", "E8", "8", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("67", "17", "E9", "9", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("68", "17", "WO1", "10", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("69", "17", "CW2", "11", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("70", "17", "CW3", "12", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("71", "17", "CW4", "13", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("72", "17", "CW5", "14", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("73", "17", "O1", "15", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("74", "17", "O2", "16", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("75", "17", "O3", "17", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("76", "17", "O4", "18", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("77", "17", "O5", "19", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("78", "17", "O6", "20", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("79", "103", "Less than 1 month", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("80", "103", "1 - 6 months", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("81", "103", "6 - 12 months", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("82", "103", "1 - 2 years", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("83", "103", "2 - 3 years", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("84", "103", "3 - 4 years", "6", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("85", "103", "4 - 5 years", "7", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("86", "103", "5 - 10 years", "8", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("87", "103", "10 - 15 years", "9", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("88", "103", "15 - 20 years", "10", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("89", "103", "20+ years", "11", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("90", "107", "Male", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("91", "107", "Female", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("92", "108", "Asian", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("93", "108", "Black", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("94", "108", "Hispanic", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("95", "108", "Other", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("96", "108", "White", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("117", "106", "Yes", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("118", "106", "No", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("119", "26", "Yes", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("120", "26", "No", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("121", "26", "Not Applicable", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("165", "101", "15th Signal Brigade", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("134", "30", "None", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("135", "30", "Slight", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("136", "30", "Moderate", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("137", "30", "High", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("138", "30", "Very High", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("139", "30", "Extremely High", "6", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("166", "101", "LCIT", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("167", "101", "RNCOA", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("168", "101", "U.S. Army Garrison Command", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("169", "101", "93rd Signal Brigade", "6", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("170", "101", "116th Military Intelligence Group", "7", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("171", "101", "513th Military Intelligence Brigade", "8", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("172", "101", "EAMC", "9", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("173", "101", "DENTAC", "10", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("174", "101", "SE Regional Vet Command", "11", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("177", "39", "Good", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("178", "39", "Okay", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("179", "39", "Bad", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("180", "17", "O7", "21", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("181", "102", "Less than 1 month", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("182", "102", "1 - 6 months", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("183", "102", "6 - 12 months", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("184", "102", "1 - 2 years", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("185", "102", "2 - 3 years", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("186", "102", "3 - 4 years", "6", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("187", "102", "4 - 5 years", "7", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("188", "102", "5 - 10 years", "8", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("189", "102", "10 - 15 years", "9", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("190", "102", "15 - 20 years", "10", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("191", "102", "20+ years", "11", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("200", "105", "1", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("201", "105", "2", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("202", "105", "3", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("203", "105", "4", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("204", "105", "5", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("205", "105", "6", "6", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("206", "105", "7", "7", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("207", "105", "8", "8", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("208", "105", "9", "9", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("209", "105", "10", "10", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("210", "105", "11", "11", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("211", "105", "12", "12", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("212", "105", "13", "13", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("213", "105", "14", "14", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("214", "105", "15", "15", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("215", "109", "Military", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("216", "109", "Civilian", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("217", "110", "Agree", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("218", "110", "Neither agree nor disagree", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("219", "110", "Disagree", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("220", "111", "Great extent", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("221", "111", "Moderate extent", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("222", "111", "Not at all", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("223", "112", "None", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("224", "112", "Moderate", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("225", "112", "High", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("226", "113", "Low", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("227", "113", "Moderate", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("228", "113", "High", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("233", "115", "Signal Paper", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("234", "115", "Augusta Chronicle", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("235", "115", "Marques", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("236", "115", "Command Channel", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("237", "115", "Flyers in MWR facilities", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("238", "115", "Commercial TV / Radio", "6", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("239", "116", "1", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("240", "116", "2", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("241", "116", "3", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("242", "116", "4", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("243", "116", "5", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("244", "116", "6", "6", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("245", "116", "7", "7", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("246", "116", "8", "8", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("247", "116", "9", "9", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("248", "116", "10", "10", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("249", "116", "more than 10", "11", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("272", "117", "more than 10", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("251", "117", "0", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("252", "117", "1", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("253", "117", "2", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("254", "117", "3", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("255", "117", "4", "6", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("256", "117", "5", "7", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("257", "117", "6", "8", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("258", "117", "7", "9", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("259", "117", "8", "10", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("260", "117", "9", "11", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("261", "117", "10", "12", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("262", "118", "TMC or Hospital Commander", "1", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("263", "118", "Patient Representative", "2", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("264", "118", "Health Benefit Advisor", "3", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("265", "118", "TRICARE Service Center", "4", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("266", "118", "Chain of Command", "5", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("267", "118", "None", "6", "bar.gif");
INSERT INTO `answer_values` (`avid`, `aid`, `value`, `group_id`, `image`) VALUES("268", "118", "Other", "7", "bar.gif");


#
# Table structure for table 'dependencies'
#

DROP TABLE IF EXISTS `dependencies`;
CREATE TABLE `dependencies` (
  `dep_id` int(11) NOT NULL auto_increment,
  `sid` int(10) unsigned NOT NULL default '0',
  `qid` int(10) unsigned NOT NULL default '0',
  `dep_qid` int(10) unsigned NOT NULL default '0',
  `dep_aid` int(10) unsigned NOT NULL default '0',
  `dep_option` varchar(10) NOT NULL default '',
  PRIMARY KEY  (`dep_id`),
  UNIQUE KEY `sid` (`sid`,`qid`,`dep_qid`,`dep_aid`)
) TYPE=MyISAM;



#
# Dumping data for table 'dependencies'
#

INSERT INTO `dependencies` (`dep_id`, `sid`, `qid`, `dep_qid`, `dep_aid`, `dep_option`) VALUES("1", "2", "37", "34", "177", "Hide");
INSERT INTO `dependencies` (`dep_id`, `sid`, `qid`, `dep_qid`, `dep_aid`, `dep_option`) VALUES("12", "2", "35", "32", "179", "Require");
INSERT INTO `dependencies` (`dep_id`, `sid`, `qid`, `dep_qid`, `dep_aid`, `dep_option`) VALUES("3", "2", "36", "33", "178", "Hide");
INSERT INTO `dependencies` (`dep_id`, `sid`, `qid`, `dep_qid`, `dep_aid`, `dep_option`) VALUES("4", "2", "36", "33", "177", "Hide");
INSERT INTO `dependencies` (`dep_id`, `sid`, `qid`, `dep_qid`, `dep_aid`, `dep_option`) VALUES("13", "2", "36", "33", "179", "Require");
INSERT INTO `dependencies` (`dep_id`, `sid`, `qid`, `dep_qid`, `dep_aid`, `dep_option`) VALUES("6", "2", "35", "32", "178", "Hide");
INSERT INTO `dependencies` (`dep_id`, `sid`, `qid`, `dep_qid`, `dep_aid`, `dep_option`) VALUES("7", "2", "35", "32", "177", "Hide");
INSERT INTO `dependencies` (`dep_id`, `sid`, `qid`, `dep_qid`, `dep_aid`, `dep_option`) VALUES("14", "2", "37", "34", "179", "Require");
INSERT INTO `dependencies` (`dep_id`, `sid`, `qid`, `dep_qid`, `dep_aid`, `dep_option`) VALUES("9", "2", "29", "26", "90", "Hide");
INSERT INTO `dependencies` (`dep_id`, `sid`, `qid`, `dep_qid`, `dep_aid`, `dep_option`) VALUES("10", "2", "28", "26", "91", "Hide");
INSERT INTO `dependencies` (`dep_id`, `sid`, `qid`, `dep_qid`, `dep_aid`, `dep_option`) VALUES("11", "2", "37", "34", "178", "Hide");


#
# Table structure for table 'ip_track'
#

DROP TABLE IF EXISTS `ip_track`;
CREATE TABLE `ip_track` (
  `ip` varchar(15) default NULL,
  `sid` int(10) unsigned default NULL,
  KEY `sid` (`sid`)
) TYPE=MyISAM;



#
# Dumping data for table 'ip_track'
#



#
# Table structure for table 'questions'
#

DROP TABLE IF EXISTS `questions`;
CREATE TABLE `questions` (
  `qid` int(11) NOT NULL auto_increment,
  `question` text NOT NULL,
  `aid` int(11) NOT NULL default '0',
  `sid` int(11) NOT NULL default '0',
  `page` int(11) NOT NULL default '0',
  `num_answers` int(11) NOT NULL default '0',
  `num_required` int(11) NOT NULL default '0',
  `oid` int(10) unsigned NOT NULL default '0',
  `orientation` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`qid`),
  KEY `aid` (`aid`),
  KEY `sid` (`sid`)
) TYPE=MyISAM;



#
# Dumping data for table 'questions'
#

INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("31", "For the following questions, please rate the food at the restaurants listed.", "24", "2", "3", "1", "0", "8", "Vertical");
INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("30", "What is your favorite hobby?", "2", "2", "2", "1", "0", "7", "Vertical");
INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("29", "Do you like to shop?", "3", "2", "2", "1", "0", "3", "Vertical");
INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("28", "Do you like to fish?", "3", "2", "2", "1", "0", "2", "Dropdown");
INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("26", "Please choose your gender:", "107", "2", "1", "1", "1", "1", "Vertical");
INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("32", "McDonalds", "39", "2", "3", "1", "0", "9", "Matrix");
INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("33", "Burger King", "39", "2", "3", "1", "0", "11", "Matrix");
INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("34", "KFC", "39", "2", "3", "1", "0", "12", "Matrix");
INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("35", "Why do you think food is bad at McDonalds?", "2", "2", "4", "1", "0", "12", "Vertical");
INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("36", "Why do you think food is bad at Burger King?", "2", "2", "4", "1", "0", "13", "Vertical");
INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("37", "Why do you think food is bad at KFC?", "2", "2", "4", "1", "0", "14", "Vertical");
INSERT INTO `questions` (`qid`, `question`, `aid`, `sid`, `page`, `num_answers`, `num_required`, `oid`, `orientation`) VALUES("38", "Any other comments?", "1", "2", "5", "1", "0", "16", "Vertical");


#
# Table structure for table 'results'
#

DROP TABLE IF EXISTS `results`;
CREATE TABLE `results` (
  `rid` int(11) NOT NULL auto_increment,
  `sid` int(11) NOT NULL default '0',
  `qid` int(11) NOT NULL default '0',
  `avid` int(11) NOT NULL default '0',
  `entered` timestamp(14) NOT NULL,
  `sequence` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rid`),
  KEY `sid` (`sid`),
  KEY `qid` (`qid`),
  KEY `sequence` (`sequence`),
  KEY `avid` (`avid`)
) TYPE=MyISAM;



#
# Dumping data for table 'results'
#

INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("169", "2", "34", "178", "20031216143307", "45");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("168", "2", "33", "178", "20031216143307", "45");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("167", "2", "32", "178", "20031216143307", "45");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("166", "2", "26", "91", "20031216143307", "45");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("165", "2", "29", "1", "20031216143307", "45");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("164", "2", "26", "91", "20031216143054", "44");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("163", "2", "29", "1", "20031216143054", "44");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("162", "2", "26", "90", "20031216142445", "43");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("161", "2", "28", "1", "20031216142445", "43");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("160", "2", "26", "90", "20031216142339", "42");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("159", "2", "28", "1", "20031216142339", "42");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("158", "2", "26", "91", "20031216141607", "41");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("157", "2", "29", "2", "20031216141607", "41");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("156", "2", "26", "91", "20031216141548", "40");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("155", "2", "29", "1", "20031216141548", "40");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("154", "2", "26", "90", "20031216141538", "39");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("153", "2", "28", "1", "20031216141538", "39");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("170", "2", "29", "1", "20031220153537", "46");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("171", "2", "26", "91", "20031220153537", "46");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("172", "2", "28", "1", "20031220153554", "47");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("173", "2", "26", "90", "20031220153554", "47");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("174", "2", "28", "1", "20031230231136", "48");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("175", "2", "26", "90", "20031230231136", "48");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("176", "2", "28", "1", "20040107140853", "50");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("177", "2", "26", "90", "20040107140853", "50");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("178", "2", "32", "177", "20040107140853", "50");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("179", "2", "33", "177", "20040107140853", "50");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("180", "2", "34", "177", "20040107140853", "50");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("181", "2", "28", "1", "20040107140930", "51");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("182", "2", "26", "90", "20040107140930", "51");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("183", "2", "32", "177", "20040107140930", "51");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("184", "2", "33", "177", "20040107140930", "51");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("185", "2", "34", "177", "20040107140930", "51");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("186", "2", "29", "2", "20040107142950", "52");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("187", "2", "26", "91", "20040107142950", "52");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("188", "2", "32", "178", "20040107142950", "52");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("189", "2", "33", "178", "20040107142950", "52");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("190", "2", "34", "178", "20040107142950", "52");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("191", "2", "29", "1", "20040107143100", "53");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("192", "2", "26", "91", "20040107143100", "53");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("193", "2", "32", "177", "20040107143100", "53");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("194", "2", "33", "178", "20040107143100", "53");
INSERT INTO `results` (`rid`, `sid`, `qid`, `avid`, `entered`, `sequence`) VALUES("195", "2", "34", "179", "20040107143100", "53");


#
# Table structure for table 'results_text'
#

DROP TABLE IF EXISTS `results_text`;
CREATE TABLE `results_text` (
  `rid` int(11) NOT NULL auto_increment,
  `sid` int(11) NOT NULL default '0',
  `qid` int(11) NOT NULL default '0',
  `answer` text NOT NULL,
  `entered` timestamp(14) NOT NULL,
  `sequence` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rid`),
  KEY `sid` (`sid`),
  KEY `qid` (`qid`),
  KEY `sequence` (`sequence`),
  FULLTEXT KEY `answer` (`answer`)
) TYPE=MyISAM;



#
# Dumping data for table 'results_text'
#

INSERT INTO `results_text` (`rid`, `sid`, `qid`, `answer`, `entered`, `sequence`) VALUES("1", "2", "30", "Computers", "20040107140853", "50");
INSERT INTO `results_text` (`rid`, `sid`, `qid`, `answer`, `entered`, `sequence`) VALUES("2", "2", "38", "Yes, I have a few about fish and other crap like that. :)", "20040107140853", "50");
INSERT INTO `results_text` (`rid`, `sid`, `qid`, `answer`, `entered`, `sequence`) VALUES("3", "2", "30", "Computers", "20040107140930", "51");
INSERT INTO `results_text` (`rid`, `sid`, `qid`, `answer`, `entered`, `sequence`) VALUES("4", "2", "38", "Yes, I have a few comments about the cost of fresh seafood and peanut butter. It&#039;s high, you know??? :)", "20040107140930", "51");
INSERT INTO `results_text` (`rid`, `sid`, `qid`, `answer`, `entered`, `sequence`) VALUES("5", "2", "30", "Shopping", "20040107142950", "52");
INSERT INTO `results_text` (`rid`, `sid`, `qid`, `answer`, `entered`, `sequence`) VALUES("6", "2", "38", "Just testing &lt;img&gt; and &lt;strong&gt; type stuff. :)", "20040107142950", "52");
INSERT INTO `results_text` (`rid`, `sid`, `qid`, `answer`, `entered`, `sequence`) VALUES("7", "2", "30", "Something", "20040107143100", "53");
INSERT INTO `results_text` (`rid`, `sid`, `qid`, `answer`, `entered`, `sequence`) VALUES("8", "2", "37", "Fatty", "20040107143100", "53");
INSERT INTO `results_text` (`rid`, `sid`, `qid`, `answer`, `entered`, `sequence`) VALUES("9", "2", "38", "Just one more thing... if I can think of it. ", "20040107143100", "53");


#
# Table structure for table 'sequence'
#

DROP TABLE IF EXISTS `sequence`;
CREATE TABLE `sequence` (
  `sequence` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`sequence`)
) TYPE=MyISAM;



#
# Dumping data for table 'sequence'
#

INSERT INTO `sequence` (`sequence`) VALUES("1");
INSERT INTO `sequence` (`sequence`) VALUES("2");
INSERT INTO `sequence` (`sequence`) VALUES("3");
INSERT INTO `sequence` (`sequence`) VALUES("4");
INSERT INTO `sequence` (`sequence`) VALUES("5");
INSERT INTO `sequence` (`sequence`) VALUES("6");
INSERT INTO `sequence` (`sequence`) VALUES("7");
INSERT INTO `sequence` (`sequence`) VALUES("8");
INSERT INTO `sequence` (`sequence`) VALUES("9");
INSERT INTO `sequence` (`sequence`) VALUES("10");
INSERT INTO `sequence` (`sequence`) VALUES("11");
INSERT INTO `sequence` (`sequence`) VALUES("12");
INSERT INTO `sequence` (`sequence`) VALUES("13");
INSERT INTO `sequence` (`sequence`) VALUES("14");
INSERT INTO `sequence` (`sequence`) VALUES("15");
INSERT INTO `sequence` (`sequence`) VALUES("16");
INSERT INTO `sequence` (`sequence`) VALUES("17");
INSERT INTO `sequence` (`sequence`) VALUES("18");
INSERT INTO `sequence` (`sequence`) VALUES("19");
INSERT INTO `sequence` (`sequence`) VALUES("20");
INSERT INTO `sequence` (`sequence`) VALUES("21");
INSERT INTO `sequence` (`sequence`) VALUES("22");
INSERT INTO `sequence` (`sequence`) VALUES("23");
INSERT INTO `sequence` (`sequence`) VALUES("24");
INSERT INTO `sequence` (`sequence`) VALUES("25");
INSERT INTO `sequence` (`sequence`) VALUES("26");
INSERT INTO `sequence` (`sequence`) VALUES("27");
INSERT INTO `sequence` (`sequence`) VALUES("28");
INSERT INTO `sequence` (`sequence`) VALUES("29");
INSERT INTO `sequence` (`sequence`) VALUES("30");
INSERT INTO `sequence` (`sequence`) VALUES("31");
INSERT INTO `sequence` (`sequence`) VALUES("32");
INSERT INTO `sequence` (`sequence`) VALUES("33");
INSERT INTO `sequence` (`sequence`) VALUES("34");
INSERT INTO `sequence` (`sequence`) VALUES("35");
INSERT INTO `sequence` (`sequence`) VALUES("36");
INSERT INTO `sequence` (`sequence`) VALUES("37");
INSERT INTO `sequence` (`sequence`) VALUES("38");
INSERT INTO `sequence` (`sequence`) VALUES("39");
INSERT INTO `sequence` (`sequence`) VALUES("40");
INSERT INTO `sequence` (`sequence`) VALUES("41");
INSERT INTO `sequence` (`sequence`) VALUES("42");
INSERT INTO `sequence` (`sequence`) VALUES("43");
INSERT INTO `sequence` (`sequence`) VALUES("44");
INSERT INTO `sequence` (`sequence`) VALUES("45");
INSERT INTO `sequence` (`sequence`) VALUES("46");
INSERT INTO `sequence` (`sequence`) VALUES("47");
INSERT INTO `sequence` (`sequence`) VALUES("48");
INSERT INTO `sequence` (`sequence`) VALUES("49");
INSERT INTO `sequence` (`sequence`) VALUES("50");
INSERT INTO `sequence` (`sequence`) VALUES("51");
INSERT INTO `sequence` (`sequence`) VALUES("52");
INSERT INTO `sequence` (`sequence`) VALUES("53");
INSERT INTO `sequence` (`sequence`) VALUES("54");
INSERT INTO `sequence` (`sequence`) VALUES("55");
INSERT INTO `sequence` (`sequence`) VALUES("56");
INSERT INTO `sequence` (`sequence`) VALUES("57");
INSERT INTO `sequence` (`sequence`) VALUES("58");


#
# Table structure for table 'surveys'
#

DROP TABLE IF EXISTS `surveys`;
CREATE TABLE `surveys` (
  `sid` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `welcome_text` text NOT NULL,
  `thank_you_text` text NOT NULL,
  `start` int(11) NOT NULL default '0',
  `end` int(11) NOT NULL default '0',
  `active` int(11) NOT NULL default '0',
  `survey_access` varchar(10) default 'public',
  `survey_password` varchar(255) default NULL,
  `results_access` varchar(10) default 'public',
  `results_password` varchar(255) default NULL,
  `edit_password` varchar(20) default NULL,
  `template` varchar(25) default NULL,
  PRIMARY KEY  (`sid`)
) TYPE=MyISAM;



#
# Dumping data for table 'surveys'
#

INSERT INTO `surveys` (`sid`, `name`, `welcome_text`, `thank_you_text`, `start`, `end`, `active`, `survey_access`, `survey_password`, `results_access`, `results_password`, `edit_password`, `template`) VALUES("2", "Example Survey", "Welcome to the example survey.", "Thank you for taking the example survey.", "0", "0", "1", "public", "", "public", "", "password", "Default");
