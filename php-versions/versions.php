<?php

use Kirby\Actions\Php;

require getenv('RUNNER_TEMP') . '/semver/vendor/autoload.php';
require __DIR__ . '/../lib/Php.php';

$require = json_decode(file_get_contents('composer.json'), true)['require']['php'] ?? null;

if ($require === null) {
	echo "::error::composer.json requires no PHP version\n";
	exit(1);
}

$php = new Php($require);

// rather than testing fewer versions than composer.json promises
if ($php->isSupported() === false) {
	echo '::error::composer.json allows PHP versions below ' . Php::VERSIONS[0] . ", which getkirby/actions doesn't support\n";
	exit(1);
}

// the PHP versions that can be tested, as far as composer.json allows them
$versions = $php->versions();

if ($versions === []) {
	echo '::error::composer.json allows none of the PHP versions ' . implode(', ', Php::VERSIONS) . "\n";
	exit(1);
}

file_put_contents(
	getenv('GITHUB_OUTPUT'),
	'versions=' . json_encode($versions) . "\nlowest=" . $versions[0] . "\n",
	FILE_APPEND
);
