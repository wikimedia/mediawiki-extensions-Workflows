<?php

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

namespace MediaWiki\Extension\Workflows\MediaWiki\Hook;

use MediaWiki\Extension\Workflows\Trigger\PageRelatedTrigger;
use MediaWiki\Extension\Workflows\TriggerRepo;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\PermissionManager;
use SkinTemplate;

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
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$user = $sktemplate->getUser();
		$title = $sktemplate->getTitle();
		if ( $title && !$title->exists() ) {
			return;
		}
		if ( $title->getContentModel() === 'BPMN' ) {
			$this->addEditXmlAction( $sktemplate, $links );
		}
		if ( $title->isSpecialPage() || !$title->isContentPage() ) {
			return;
		}

		if ( $this->permissionManager->userHasRight( $user, 'workflows-view' ) ) {
			$links['actions']['wf_view_for_page'] = [
				'text' => $sktemplate->getContext()->msg(
					"workflows-ui-workflow-overview-dialog-title-list-page"
				)->text(),
				'href' => '#',
				'class' => false,
				'id' => 'ca-wf_view_for_page',
				'position' => 12,
			];
		}

		if ( $this->permissionManager->userHasRight( $user, 'workflows-execute' ) ) {
			/** @var TriggerRepo $triggerRepo */
			$triggerRepo = MediaWikiServices::getInstance()->getService( 'WorkflowTriggerRepo' );
			$triggers = $triggerRepo->getActive( 'manual' );
			/** @var PageRelatedTrigger $trigger */
			foreach ( $triggers as $trigger ) {
				$trigger->setTitle( $title );
				if ( $trigger->shouldTrigger() ) {
					$sktemplate->getOutput()->addJsConfigVars( 'workflowsAllowed', $trigger->getAttributes() );
					$links['actions']['wf_start'] = [
						'text' => $sktemplate->getContext()->msg( "workflows-ui-action-start" )->text(),
						'href' => '#',
						'class' => false,
						'id' => 'ca-wf_start',
						'position' => 10,
					];
					return;
				}
			}
		}
	}

	/**
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 *
	 * @return void
	 */
	private function addEditXmlAction( SkinTemplate $sktemplate, array &$links ) {
		if ( !isset( $links['views']['edit'] ) ) {
			return;
		}
		$links['views']['edit']['text'] = $sktemplate->msg( 'workflows-ui-action-edit' )->text();
		$links['views']['edit']['title'] = $sktemplate->msg( 'workflows-ui-action-edit' )->text();
		$links['views']['editxml'] = $links['views']['edit'];
		$links['views']['editxml']['text'] = $sktemplate->msg( 'workflows-ui-action-editxml' )->text();
		$links['views']['editxml']['title'] = $sktemplate->msg( 'workflows-ui-action-editxml' )->text();
		$links['views']['editxml']['href'] = $sktemplate->getTitle()->getLinkURL( [ 'action' => 'editxml' ] );
	}
}
