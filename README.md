# Workflows

## Installation
Execute

    composer require mediawiki/workflows dev-REL1_35
within MediaWiki root or add `mediawiki/workflows` to the
`composer.json` file of your project

## Activation
Add

    wfLoadExtension( 'Workflows' );
to your `LocalSettings.php` or the appropriate `settings.d/` file.
