<?php

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Permissions\PermissionManager;

class AddActions implements SkinTemplateNavigation__UniversalHook {
	/** @var PermissionManager */
	private $permissionManager;

	/**
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( PermissionManager $permissionManager ) {
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @param \SkinTemplate $sktemplate
	 * @param array &$links
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$user = $sktemplate->getUser();
		if ( !$this->permissionManager->userHasRight( $user, 'workflows-execute' ) ) {
			return;
		}

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
}
