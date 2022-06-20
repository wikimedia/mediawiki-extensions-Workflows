<?php

namespace MediaWiki\Extension\Workflows\Data;

use MWStake\MediaWiki\Component\DataStore\FieldType;

class Schema extends \MWStake\MediaWiki\Component\DataStore\Schema {
	public function __construct() {
		parent::__construct( [
			Record::ID => [
				self::FILTERABLE => false,
				self::SORTABLE => false,
				self::TYPE => FieldType::STRING
			],
			Record::TITLE => [
				self::FILTERABLE => true,
				self::SORTABLE => true,
				self::TYPE => FieldType::STRING
			],
			Record::PAGE_PREFIXED_TEXT => [
				self::FILTERABLE => true,
				self::SORTABLE => true,
				self::TYPE => FieldType::STRING
			],
			Record::PAGE_LINK => [
				self::FILTERABLE => false,
				self::SORTABLE => false,
				self::TYPE => FieldType::STRING
			],
			Record::ASSIGNEE => [
				self::FILTERABLE => false,
				self::SORTABLE => false,
				self::TYPE => FieldType::STRING
			],
			Record::STATE => [
				self::FILTERABLE => true,
				self::SORTABLE => true ,
				self::TYPE => FieldType::STRING
			],
			Record::STATE_LABEL => [
				self::FILTERABLE => false,
				self::SORTABLE => false ,
				self::TYPE => FieldType::STRING
			],
			Record::HAS_NOTICE => [
				self::FILTERABLE => false,
				self::SORTABLE => false ,
				self::TYPE => FieldType::BOOLEAN
			],
			Record::START_TS => [
				self::FILTERABLE => false,
				self::SORTABLE => true ,
				self::TYPE => FieldType::STRING
			],
			Record::START_FORMATTED => [
				self::FILTERABLE => false,
				self::SORTABLE => false ,
				self::TYPE => FieldType::STRING
			],
			Record::LAST_TS => [
				self::FILTERABLE => false,
				self::SORTABLE => true ,
				self::TYPE => FieldType::STRING
			],
			Record::LAST_FORMATTED => [
				self::FILTERABLE => false,
				self::SORTABLE => false ,
				self::TYPE => FieldType::STRING
			],
		] );
	}
}
