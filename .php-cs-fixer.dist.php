<?php

return Kirby\PhpCs\Config::create()->setFinder(
	PhpCsFixer\Finder::create()
		->in([__DIR__ . '/lib', __DIR__ . '/php-versions'])
		->append([__DIR__ . '/bin/kirby-actions'])
);
