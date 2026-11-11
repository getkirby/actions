<?php

use Composer\Semver\VersionParser;

require getenv('RUNNER_TEMP') . '/semver/vendor/autoload.php';

// the PHP versions that can be tested, as far as composer.json allows them
$candidates = ['8.2', '8.3', '8.4', '8.5', '8.6'];
$require    = json_decode(file_get_contents('composer.json'), true)['require']['php'] ?? null;

if ($require === null) {
	echo "::error::composer.json requires no PHP version\n";
	exit(1);
}

$parser     = new VersionParser();
$constraint = $parser->parseConstraints($require);
$versions   = array_values(array_filter(
	$candidates,
	fn (string $version): bool => $constraint->matches($parser->parseConstraints('~' . $version . '.0'))
));

if ($versions === []) {
	echo '::error::composer.json allows none of the PHP versions ' . implode(', ', $candidates) . "\n";
	exit(1);
}

file_put_contents(
	getenv('GITHUB_OUTPUT'),
	'versions=' . json_encode($versions) . "\nlowest=" . $versions[0] . "\n",
	FILE_APPEND
);
