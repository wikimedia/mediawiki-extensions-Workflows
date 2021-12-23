<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use Title;

class IntegrateCodeEditor {

	/**
	 * @param Title $title
	 * @param string &$languageCode
	 * @return bool
	 */
	public static function onCodeEditorGetPageLanguage( Title $title, &$languageCode ) {
		$currentContentModel = $title->getContentModel();
		if ( $currentContentModel === 'BPMN' ) {
			$languageCode = 'xml';
			return false;
		}

		return true;
	}
}
