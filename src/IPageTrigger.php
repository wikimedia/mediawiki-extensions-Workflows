<?php

namespace MediaWiki\Extension\Workflows;

use MediaWiki\Title\Title;

interface IPageTrigger extends ITrigger {

	/**
	 * @param Title $title
	 * @return mixed
	 */
	public function setTitle( Title $title );
}
