CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/workflows_event (
    `wfe_event_id` VARCHAR( 255 ) NOT NULL,
    `wfe_event_type` VARCHAR( 255 ) NULL,
    `wfe_aggregate_root_id` VARCHAR( 255 ) NOT NULL,
    `wfe_aggregate_root_version` INT NULL,
    `wfe_time_of_recording` VARBINARY( 255 ) NOT NULL,
    `wfe_payload` BLOB NULL
) /*$wgDBTableOptions*/;
