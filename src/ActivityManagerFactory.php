<?php

namespace MediaWiki\Extension\Workflows;

use MediaWiki\Extension\Workflows\Util\DataPreprocessor;
use MediaWiki\User\UserFactory;

class ActivityManagerFactory {

	/** @var LogicObjectFactory */
	private $logicObjectFactory;
	/** @var DataPreprocessor */
	private $preprocessor;
	/** @var PropertyValidatorFactory */
	private $propertyValidatorFactory;
	/** @var UserFactory */
	private $userFactory;

	/**
	 * @param LogicObjectFactory $logicObjectFactory
	 * @param DataPreprocessor $preprocessor
	 * @param PropertyValidatorFactory $propertyValidatorFactory
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		LogicObjectFactory $logicObjectFactory, DataPreprocessor $preprocessor,
		PropertyValidatorFactory $propertyValidatorFactory, UserFactory $userFactory
	) {
		$this->logicObjectFactory = $logicObjectFactory;
		$this->preprocessor = $preprocessor;
		$this->propertyValidatorFactory = $propertyValidatorFactory;
		$this->userFactory = $userFactory;
	}

	/**
	 * @return ActivityManager
	 */
	public function newActivityManager(): ActivityManager {
		return new ActivityManager(
			$this->logicObjectFactory,
			$this->preprocessor,
			$this->propertyValidatorFactory,
			$this->userFactory
		);
	}
}
