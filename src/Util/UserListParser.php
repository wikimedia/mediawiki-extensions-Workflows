<?php

namespace MediaWiki\Extension\Workflows\Util;

class UserListParser {

	/**
	 * Accepts an array, a JSON array string or a comma/newline-separated string
	 *
	 * @param array|string|null $value
	 * @return string[]
	 */
	public static function parse( $value ): array {
		if ( is_string( $value ) ) {
			$trimmed = trim( $value );
			$decoded = null;
			if ( str_starts_with( $trimmed, '[' ) ) {
				$decoded = json_decode( $trimmed, true );
			}
			$value = is_array( $decoded ) ? $decoded : preg_split( '/[,\n]/', $trimmed );
		}
		if ( !is_array( $value ) ) {
			return [];
		}
		$users = [];
		foreach ( $value as $item ) {
			if ( !is_scalar( $item ) ) {
				continue;
			}
			$item = trim( (string)$item );
			if ( $item !== '' ) {
				$users[] = $item;
			}
		}
		return array_values( array_unique( $users ) );
	}
}
