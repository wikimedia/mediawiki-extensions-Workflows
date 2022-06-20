<?php

namespace MediaWiki\Extension\Workflows\Data;

class Record extends \MWStake\MediaWiki\Component\DataStore\Record {
	public const ID = 'id';
	public const TITLE = 'title';
	public const PAGE_PREFIXED_TEXT = 'page_prefixed_text';
	public const PAGE_LINK = 'page_link';
	public const ASSIGNEE = 'assignee';
	public const STATE = 'state';
	public const STATE_LABEL = 'state_label';
	public const HAS_NOTICE = 'has_notice';
	public const START_TS = 'start_ts';
	public const START_FORMATTED = 'start_formatted';
	public const LAST_TS = 'last_ts';
	public const LAST_FORMATTED = 'last_formatted';
}
