<?php

namespace MediaWiki\Extension\Workflows\Util;

use MediaWiki\Context\RequestContext;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOptions;

class DataPreprocessor {

	/**
	 *
	 * @var Parser
	 */
	private $parser;

	/**
	 *
	 * @var RequestContext
	 */
	private $requestContext;

	/**
	 *
	 * @param Parser $parser
	 * @param RequestContext|null $requestContext
	 */
	public function __construct( $parser, $requestContext = null ) {
		$this->parser = $parser;
		$this->requestContext = $requestContext;

		if ( $this->requestContext === null ) {
			// This is not very nice, but unfortunately there are some extensions that rely on
			// global request context rather than on parser context. e.g. BlueSpiceSMWConnector
			// does security trimming of ASK-queries based on the request context.
			// Therefore we have to override it.
			$this->requestContext = RequestContext::getMain();
		}
	}

	/**
	 *
	 * @param array $data
	 * @param array $contextData
	 * @param DataPreprocessorContext $context
	 * @return array
	 */
	public function preprocess( $data, $contextData, DataPreprocessorContext $context ) {
		$contextUser = $context->getUser();
		$contextTitle = $context->getTitle();

		$origUser = $this->requestContext->getUser();
		$origTitle = $this->requestContext->getTitle();

		$this->requestContext->setTitle( $contextTitle );
		$this->requestContext->setUser( $contextUser );

		$this->parser->setPage( $contextTitle );
		$this->parser->setUser( $contextUser );

		$parserOptions = ParserOptions::newFromUser( $contextUser );
		$this->parser->setOptions( $parserOptions );
		$this->parser->clearState();

		$preprocessed = [];
		foreach ( $data as $dataKey => $dataValue ) {
			if ( is_array( $dataValue ) ) {
				// This can only happen when parsing values that come OUT of the activity
				// Input from the XML can never be an array
				// We assume that activity will not output an array of values that need processing,
				// so we just pass them through to be flattened for the next activity
				$preprocessed[$dataKey] = $dataValue;
				continue;
			}
			$frame = $this->parser->getPreprocessor()->newCustomFrame( $contextData );
			$preprocessed[$dataKey] = $this->parser->preprocess(
				$dataValue,
				$contextTitle,
				$parserOptions,
				// Not FlaggedRevs compatible!
				$context->getRevisionId(),
				$frame
			);
		}

		$this->requestContext->setTitle( $origTitle );
		$this->requestContext->setUser( $origUser );

		return $preprocessed;
	}
}
