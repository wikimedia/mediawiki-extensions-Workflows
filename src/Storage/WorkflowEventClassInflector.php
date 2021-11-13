<?php

namespace MediaWiki\Extension\Workflows\Storage;

use EventSauce\EventSourcing\ClassNameInflector;

class WorkflowEventClassInflector implements ClassNameInflector {

	public function classNameToType( string $className ): string {
		$bits = explode( '\\', $className );
		$eventName = array_pop( $bits );
		$bits = array_filter( $bits, static function ( $item ) {
			return !empty( $item );
		} );
		if ( empty( $bits ) ) {
			return $eventName;
		}
		return implode( '.', $bits ) . '!' . $eventName;
	}

	public function typeToClassName( string $eventName ): string {
		$parts = explode( '!', $eventName );
		$event = array_pop( $parts );
		if ( empty( $parts ) ) {
			return $event;
		}

		return str_replace( '.', '\\', $parts[0] ) . '\\' . $event;
	}

	public function instanceToType( object $instance ): string {
		return $this->classNameToType( get_class( $instance ) );
	}
}
