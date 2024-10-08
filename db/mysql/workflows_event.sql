-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: db/workflows_event.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/workflows_event (
  wfe_event_id VARBINARY(255) NOT NULL,
  wfe_event_type VARBINARY(255) DEFAULT NULL,
  wfe_aggregate_root_id VARBINARY(255) NOT NULL,
  wfe_aggregate_root_version INT DEFAULT NULL,
  wfe_time_of_recording VARBINARY(255) NOT NULL,
  wfe_payload LONGBLOB DEFAULT NULL
) /*$wgDBTableOptions*/;
