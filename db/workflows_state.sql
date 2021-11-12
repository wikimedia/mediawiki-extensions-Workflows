CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/workflows_state (
    `wfs_workflow_id` VARCHAR( 255 ) NOT NULL PRIMARY KEY,
    `wfs_state` VARCHAR( 255 ) NOT NULL,
    `wfs_last_event` VARCHAR( 255 ) NOT NULL,
    `wfs_initiator` INT NULL,
    `wfs_touched` VARCHAR(15) NULL,
    `wfs_payload` TEXT NULL
) /*$wgDBTableOptions*/;
