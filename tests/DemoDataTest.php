<?php

// The demo inventory is generated: the committed SQL must be exactly what
// scripts/make-demo-data.php produces today, otherwise a model change went
// out without regenerating the artifact.
class DemoDataTest extends RTTestCase
{
	/**
	 * @group small
	 */
	public function testGeneratedSqlIsUpToDate (): void
	{
		$root = dirname (__DIR__);
		$script = $root . '/scripts/make-demo-data.php';
		$committed = $root . '/scripts/init-demo-data.sql';
		$process = proc_open
		(
			escapeshellarg (PHP_BINARY) . ' ' . escapeshellarg ($script),
			array (1 => array ('pipe', 'w'), 2 => array ('pipe', 'w')),
			$pipes
		);
		$this->assertIsResource ($process);
		$generated = stream_get_contents ($pipes[1]);
		$errors = stream_get_contents ($pipes[2]);
		fclose ($pipes[1]);
		fclose ($pipes[2]);
		$this->assertSame (0, proc_close ($process), $errors);
		$this->assertSame
		(
			hash ('sha256', file_get_contents ($committed)),
			hash ('sha256', $generated),
			'Run php scripts/make-demo-data.php > scripts/init-demo-data.sql'
		);
	}
}
