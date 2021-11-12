<?php

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use BlueSpice\Discovery\Hook\BlueSpiceDiscoveryTemplateDataProviderAfterInit;
use BlueSpice\Discovery\ITemplateDataProvider;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Permissions\PermissionManager;

class AddActions implements SkinTemplateNavigation__UniversalHook, BlueSpiceDiscoveryTemplateDataProviderAfterInit {
	/** @var PermissionManager  */
	private $permissionManager;

	/**
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( PermissionManager $permissionManager ) {
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @param \SkinTemplate $sktemplate
	 * @param array $links
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$title = $sktemplate->getTitle();
		if ( !$title->exists() || $title->isSpecialPage() || !$title->isContentPage() ) {
			return;
		}
		$links['actions']['wf_start'] = [
			'text' => $sktemplate->getContext()->msg( "workflows-ui-action-start" )->text(),
			'href' => '#',
			'class' => false,
			'id' => 'ca-wf-start',
			'position' => 10,
		];
	}

	/**
	 *
	 * @param ITemplateDataProvider $registry
	 * @return void
	 */
	public function onBlueSpiceDiscoveryTemplateDataProviderAfterInit( $registry ): void {
		$registry->register( 'actions_secondary', 'ca-wf_start' );
		$registry->unregister( 'toolbox', 'ca-wf_start' );
	}
}
