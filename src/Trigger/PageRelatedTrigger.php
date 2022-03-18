<?php

namespace MediaWiki\Extension\Workflows\Trigger;

use MediaWiki\Extension\Workflows\IPageTrigger;
use Title;

class PageRelatedTrigger extends GenericTrigger implements IPageTrigger {
	/** @var Title|null */
	protected $title = null;

	/**
	 * @param Title $title
	 */
	public function setTitle( Title $title ) {
		$this->title = $title;
	}

	/**
	 * @param Title $title
	 * @param array $qualifyingData
	 * @return bool
	 */
	public function appliesToPage( Title $title, $qualifyingData = [] ): bool {
		// In no includes are specified, everything is included
		$matches = !isset( $this->includes );
		foreach ( $this->rules as $type => $data ) {
			if ( $type === 'include' ) {
				$matches = $this->titleFits( $title, $data, $qualifyingData );
			}
			if ( $type === 'exclude' ) {
				$matches = !$this->titleFits( $title, $data, $qualifyingData );
			}
		}

		return $matches;
	}

	protected function getContextData() {
		if ( !$this->title === null ) {
			return parent::getContextData();
		}
		return parent::getContextData() + [
			'pageId' => $this->title->getArticleID(),
			'revision' => $this->title->getLatestRevID()
		];
	}

	/**
	 * @param Title $title
	 * @param array $data
	 * @param array $qualifyingData
	 * @return bool
	 */
	protected function titleFits( Title $title, array $data, $qualifyingData = [] ) {
		foreach ( $data as $type => $value ) {
			if ( !is_array( $value ) ) {
				$value = [ $value ];
			}
			switch ( $type ) {
				case 'namespace':
					if ( in_array( $title->getNamespace(), $value ) ) {
						return true;
					}
					break;
				case 'category':
					$belongsTo = array_map( static function ( $category ) {
						$bits = explode( ':', $category );
						return array_pop( $bits );
					}, array_keys( $title->getParentCategories() ) );
					$value = array_map( static function ( $category ) {
						return str_replace( ' ', '_', $category );
					}, $value );
					if ( !empty( array_intersect( $belongsTo, $value ) ) ) {
						return true;
					}
					break;
				case 'editType':
					if ( !isset( $qualifyingData['editType'] ) ) {
						return false;
					}
					if ( $qualifyingData['editType'] === $value ) {
						return true;
					}
					break;
			}
		}

		return false;
	}

	/**
	 * TODO: This part here needs rework (and the parts it calls)
	 * We need a better unified testing for rules
	 *
	 * @param array $qualifyingData
	 * @return bool
	 */
	public function shouldTrigger( $qualifyingData = [] ): bool {
		if ( !$this->title ) {
			return false;
		}
		return $this->appliesToPage( $this->title, $qualifyingData );
	}
}
