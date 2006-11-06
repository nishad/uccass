CREATE TABLE `dyna_answer_selectors` (
  `avid` int(11) NOT NULL default '0',
  `selector` varchar(255) NOT NULL default '',
  KEY `idx_dyna_answer_selectors_avid` (`avid`),
  KEY `idx_dyna_answer_selectors_selector` (`selector`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `surveys` ADD COLUMN manual_codes INT(11) NOT NULL default '0';

ALTER TABLE `answer_types` ADD COLUMN `is_dynamic` INT(11) NOT NULL default '0';

CREATE TABLE time_limit_sequence (
  id int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

INSERT INTO time_limit_sequence SELECT * FROM sequence;

DROP TABLE sequence;