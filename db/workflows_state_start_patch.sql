ALTER TABLE /*$wgDBprefix*/workflows_state ADD COLUMN wfs_started VARCHAR(15) NULL AFTER wfs_touched;

