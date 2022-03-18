<?php

namespace MediaWiki\Extension\Workflows;

use Title;

interface IPageTrigger extends ITrigger {

	/**
	 * @param Title $title
	 * @return mixed
	 */
	public function setTitle( Title $title );
}
