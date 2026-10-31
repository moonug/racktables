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
		$status = 0;
		$generated = shell_exec (escapeshellarg (PHP_BINARY) . ' ' . escapeshellarg ($script) . ' 2>/dev/null');
		$this->assertSame (file_get_contents ($committed), $generated);
	}
}
