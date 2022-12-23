<?php

namespace MediaWiki\Extension\Workflows\Storage\Event\Mixin;

use MediaWiki\MediaWikiServices;
use User;

trait ActorTrait {
	/** @var User */
	private $actor;

	public function getActor(): ?User {
		return $this->actor;
	}

	public static function actorFromPayload( $payload ): ?User {
		if ( isset( $payload['actor'] ) && $payload['actor'] !== null ) {
			return MediaWikiServices::getInstance()->getUserFactory()
				->newFromId( $payload['actor'] );
		}
		return null;
	}

	public function actorToPayload() {
		return $this->actor instanceof User ? $this->actor->getId() : null;
	}
}
