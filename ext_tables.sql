#
# Table structure for table 'sys_file_metadata'
#
CREATE TABLE sys_file_metadata
(
	bynder2_thumb_mini varchar(1024) DEFAULT '' NOT NULL,
	bynder2_thumb_thul varchar(1024) DEFAULT '' NOT NULL,
	bynder2_thumb_webimage varchar(1024) DEFAULT '' NOT NULL,
);

#
# Table structure for table 'sys_file'
#
CREATE TABLE sys_file
(
	# BUGFIX: TYPO3 issue: 106836
	# Default int (11) too small.Keep same size (20) from earlier TYPO3 versions
	size bigint(20) DEFAULT '0' NOT NULL,
);
