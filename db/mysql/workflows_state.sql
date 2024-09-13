-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: db/workflows_state.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/workflows_state (
  wfs_workflow_id VARBINARY(255) NOT NULL,
  wfs_state VARBINARY(255) NOT NULL,
  wfs_last_event VARBINARY(255) NOT NULL,
  wfs_initiator INT DEFAULT NULL,
  wfs_assignees LONGTEXT DEFAULT NULL,
  wfs_touched BINARY(14) DEFAULT NULL,
  wfs_started BINARY(14) DEFAULT NULL,
  wfs_payload LONGTEXT DEFAULT NULL,
  PRIMARY KEY(wfs_workflow_id)
) /*$wgDBTableOptions*/;
