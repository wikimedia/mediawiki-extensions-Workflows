<?php

namespace MediaWiki\Extension\Workflows\Storage\Event;

use MediaWiki\Extension\Workflows\Storage\Event\Mixin\ElementTrait;

final class TaskExpired extends Event {
	use ElementTrait;
}
