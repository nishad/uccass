-- $CVSHeader: uccass/ADOdb/session/adodb-sessions.mysql.sql,v 1.1 2005/06/10 21:04:46 jholmes763 Exp $

CREATE DATABASE /*! IF NOT EXISTS */ adodb_sessions;

USE adodb_sessions;

DROP TABLE /*! IF EXISTS */ sessions;

CREATE TABLE /*! IF NOT EXISTS */ sessions (
	sesskey		CHAR(32)	/*! BINARY */ NOT NULL DEFAULT '',
	expiry		INT(11)		/*! UNSIGNED */ NOT NULL DEFAULT 0,
	expireref	VARCHAR(64)	DEFAULT '',
	data		LONGTEXT	DEFAULT '',
	PRIMARY KEY	(sesskey),
	INDEX expiry (expiry)
);
