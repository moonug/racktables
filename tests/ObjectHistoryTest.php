<?php

class ObjectHistoryTest extends RTTestCase
{
	private static $object_id;
	private static $oldest;
	private static $newest;
	private $saved_pageno;
	private $saved_tabno;
	private $had_pageno;
	private $had_tabno;

	public static function setUpBeforeClass () : void
	{
		self::$object_id = commitAddObject (self::myStringStatic ('hist', __CLASS__), NULL, 4, NULL);
		usePreparedDeleteBlade ('ObjectHistory', array ('id' => self::$object_id));
		self::$oldest = 'hist-oldest-' . getmypid ();
		self::$newest = 'hist-newest-' . getmypid ();
		for ($i = 0; $i < 31; $i++)
		{
			if ($i == 0)
				$comment = self::$oldest;
			elseif ($i == 30)
				$comment = self::$newest;
			else
				$comment = 'hist-mid-' . $i;
			usePreparedExecuteBlade
			(
				'INSERT INTO ObjectHistory (id, name, label, objtype_id, asset_no, has_problems, comment, ctime, user_name) ' .
				'VALUES (?, ?, NULL, 4, NULL, ?, ?, ?, ?)',
				array (self::$object_id, 'n', 'no', $comment, sprintf ('2020-01-01 00:%02d:00', $i), 'tester')
			);
		}
	}

	public static function tearDownAfterClass () : void
	{
		commitDeleteObject (self::$object_id);
	}

	protected function setUp () : void
	{
		global $pageno, $tabno;
		$this->had_pageno = isset ($pageno);
		$this->had_tabno = isset ($tabno);
		$this->saved_pageno = $this->had_pageno ? $pageno : NULL;
		$this->saved_tabno = $this->had_tabno ? $tabno : NULL;
		$pageno = 'object';
		$tabno = 'default';
	}

	protected function tearDown () : void
	{
		global $pageno, $tabno;
		if ($this->had_pageno)
			$pageno = $this->saved_pageno;
		else
			unset ($pageno);
		if ($this->had_tabno)
			$tabno = $this->saved_tabno;
		else
			unset ($tabno);
	}

	/**
	 * @group small
	 */
	public function testFoldsOlderRowsOnThePage ()
	{
		$html = $this->captureHistory ();
		$this->assertStringContainsString ('<summary>Show 27 more</summary>', $html);
		$this->assertStringNotContainsString ('Display last 30 items from 31', $html);
		$this->assertStringNotContainsString ('Show full history', $html);
		$details_end = strpos ($html, '</details>');
		$this->assertNotFalse ($details_end);
		$older_html = substr ($html, 0, $details_end);
		$visible_html = substr ($html, $details_end);
		$this->assertStringContainsString (self::$oldest, $older_html);
		$this->assertStringContainsString ('hist-mid-26', $older_html);
		$this->assertStringNotContainsString (self::$newest, $older_html);
		$this->assertStringContainsString ('hist-mid-27', $visible_html);
		$this->assertStringContainsString (self::$newest, $visible_html);
		$this->assertStringNotContainsString (self::$oldest, $visible_html);
	}

	private function captureHistory ()
	{
		ob_start ();
		try
		{
			renderObjectHistory (self::$object_id);
			return ob_get_clean ();
		}
		catch (Exception $e)
		{
			ob_end_clean ();
			throw $e;
		}
	}
}
