<?php

namespace MediaWiki\Extension\Workflows\Storage\Event\Mixin;

use Ramsey\Uuid\UuidInterface;

trait ElementTrait {

	/** @var string */
	private $elementID;

	/**
	 * @param UuidInterface $id
	 * @param string $elementID
	 */
	public function __construct( UuidInterface $id, $elementID ) {
		parent::__construct( $id );
		$this->elementID = $elementID;
	}

	public function getElementId(): string {
		return $this->elementID;
	}

	/**
	 * @param array $payload
	 * @return string|null
	 */
	public static function elementIdFromPayload( $payload ) {
		if ( isset( $payload['elementId'] ) ) {
			return $payload['elementId'];
		}
		return null;
	}

	public function toPayload(): array {
		return array_merge( [
			'elementID' => $this->elementID,
		], parent::toPayload() );
	}

	protected static function decodePayloadData( array $payload ): array {
		$data = parent::decodePayloadData( $payload );

		return [
			'id' => $data['id'],
			'elementID' => $payload['elementID'],
		];
	}
}
