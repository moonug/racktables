<?php

class PermissionDeniedTest extends RTTestCase
{
	private $saved_context;
	private $had_context;

	protected function setUp () : void
	{
		global $configCache, $pageno, $tabno, $user_given_tags, $target_given_tags, $auto_tags, $expl_tags, $impl_tags;
		$this->had_context = array_key_exists ('PERMISSION_DENIED_SHOW_CONTEXT', $configCache);
		$this->saved_context = $this->had_context ? $configCache['PERMISSION_DENIED_SHOW_CONTEXT'] : NULL;
		$pageno = 'denied-probe-page';
		$tabno = 'denied-probe-tab';
		$user_given_tags = array ();
		$target_given_tags = array ();
		$auto_tags = array ();
		$expl_tags = array ();
		$impl_tags = array ();
	}

	protected function tearDown () : void
	{
		global $configCache;
		if ($this->had_context)
			$configCache['PERMISSION_DENIED_SHOW_CONTEXT'] = $this->saved_context;
		else
			unset ($configCache['PERMISSION_DENIED_SHOW_CONTEXT']);
	}

	/**
	 * @group small
	 */
	public function testHidesContextWhenUnset ()
	{
		global $configCache;
		unset ($configCache['PERMISSION_DENIED_SHOW_CONTEXT']);
		$html = $this->captureDenial ();
		$this->assertStringContainsString ('You do not have permission to view this page.', $html);
		$this->assertStringNotContainsString ('User given tags:', $html);
		$this->assertStringNotContainsString ('denied-probe-page', $html);
	}

	/**
	 * @group small
	 */
	public function testHidesContextWhenNo ()
	{
		global $configCache;
		$configCache['PERMISSION_DENIED_SHOW_CONTEXT']['varvalue'] = 'no';
		$html = $this->captureDenial ();
		$this->assertStringContainsString ('You do not have permission to view this page.', $html);
		$this->assertStringNotContainsString ('User given tags:', $html);
		$this->assertStringNotContainsString ('denied-probe-page', $html);
	}

	/**
	 * @group small
	 */
	public function testShowsContextWhenYes ()
	{
		global $configCache;
		if (! isset ($configCache['PERMISSION_DENIED_SHOW_CONTEXT']))
			$configCache['PERMISSION_DENIED_SHOW_CONTEXT'] = array ('varvalue' => 'yes');
		else
			$configCache['PERMISSION_DENIED_SHOW_CONTEXT']['varvalue'] = 'yes';
		$html = $this->captureDenial ();
		$this->assertStringContainsString ('User given tags:', $html);
		$this->assertStringContainsString ('denied-probe-page', $html);
		$this->assertStringContainsString ('denied-probe-tab', $html);
		$this->assertStringNotContainsString ('You do not have permission to view this page.', $html);
	}

	private function captureDenial ()
	{
		ob_start ();
		$exception = new RTPermissionDenied ();
		$exception->dispatch ();
		return ob_get_clean ();
	}
}
