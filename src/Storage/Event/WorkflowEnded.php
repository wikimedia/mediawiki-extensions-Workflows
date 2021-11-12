<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ElementTrait;

final class WorkflowEnded extends Event {
	use ElementTrait;
}
