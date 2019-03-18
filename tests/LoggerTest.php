<?php

use AKEB\Logger\Logger;
use AKEB\Logger\Routes\FileRoute;
use AKEB\Logger\Routes\GzipRoute;

error_reporting(E_ALL);

class LoggerTest extends PHPUnit\Framework\TestCase {

	function setUp() {
		$this->logger = new Logger();
		$this->dirname = 'tests/tmp';
		@mkdir($this->dirname);
		$this->dir = dir($this->dirname);
	}

	function tearDown() {
		$this->logger = null;
		while (false !== ($entry = $this->dir->read())) {
			if ($entry == '.' || $entry == '..') continue;
			unlink($this->dirname.'/'.$entry);
		}
		$this->dir->close();
		@rmdir($this->dirname);
		$this->dirname = null;

	}

	function test_Route() {
		$this->assertInstanceOf('Directory', $this->dir);
		$this->assertNotFalse($this->dir->read());

		$this->assertInstanceOf('Psr\Log\LoggerInterface', $this->logger);
		$this->assertInstanceOf('SplObjectStorage', $this->logger->routes);
	}

	/**
	 * @depends test_Route
	 */
	function test_Route_attach() {
		$this->assertCount(0, $this->logger->routes);
		$this->logger->routes->attach(new FileRoute([
			'isEnable' => true,
			'filePath' => $this->dirname.'/test1.txt',
		]));
		$this->assertCount(1, $this->logger->routes);
		$this->logger->routes->attach(new FileRoute([
			'isEnable' => true,
			'filePath' => $this->dirname.'/test2.txt',
		]));
		$this->assertCount(2, $this->logger->routes);
	}

	function test_Logger_log() {
		$this->logger->routes->attach(new FileRoute([
			'isEnable' => true,
			'filePath' => $this->dirname.'/test1.txt',
		]));
		$this->logger->info("Info");
		$this->logger->routes->attach(new FileRoute([
			'isEnable' => true,
			'template' => "{message}",
			'filePath' => $this->dirname.'/test2.txt',
		]));
		$this->logger->info("Info2");
		$data = file_get_contents($this->dirname.'/test2.txt');
		$this->assertEquals($data, "Info2\n");
		$this->logger->routes->attach(new FileRoute([
			'isEnable' => false,
			'filePath' => $this->dirname.'/test3.txt',
		]));
		$this->logger->error("Error");
		$this->logger->routes->attach((object)'test');
		$this->logger->error("Error");

		$this->logger->routes->attach(new FileRoute([
			'isEnable' => true,
			'filePath' => $this->dirname.'/test4.txt',
		]));
		$this->logger->error("ErrorText new");
		$fileRoute = new FileRoute();
		$date = $fileRoute->getDate();
		$testString = $date." error ErrorText new\n";
		$this->assertStringEqualsFile($this->dirname.'/test4.txt', $testString);
	}

	function test_Logger_gziplog() {
		$route = new GzipRoute([
			'isEnable' => true,
			'filePath' => $this->dirname.'/test1.txt.gz',
		]);
		$this->logger->routes->attach($route);
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
		$this->logger->info("Info");
		unset($_SERVER['HTTP_X_FORWARDED_FOR']);
		$_SERVER['REMOTE_ADDR'] = '127.0.0.2';
		$this->logger->error("Error Text");
		unset($_SERVER['REMOTE_ADDR']);
		$this->logger->warning("Warning Text");
		$this->logger->routes->detach($route);
		$route = null;
		$data = gzfile($this->dirname.'/test1.txt.gz', 0);
		$this->assertEquals($data[0],date("Y-m-d H:i:s")." || ".time()." || 127.0.0.1 || Info ||\n");
		$this->assertEquals($data[1],date("Y-m-d H:i:s")." || ".time()." || 127.0.0.2 || Error Text ||\n");
		$this->assertEquals($data[2],date("Y-m-d H:i:s")." || ".time()." || undefined || Warning Text ||\n");

	}
}