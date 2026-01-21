<?php
/**
 * PHPUnit Bootstrap
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Brain Monkey setup.
require_once __DIR__ . '/stubs/wordpress-stubs.php';

// Test base classes.
require_once __DIR__ . '/TestCase.php';
