<?php

return Kirby\PhpCs\Config::create()->setFinder(
	PhpCsFixer\Finder::create()->in([__DIR__ . '/src', __DIR__ . '/tests'])
);
