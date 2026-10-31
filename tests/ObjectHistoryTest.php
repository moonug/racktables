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
	private $had_full;
	private $saved_full;

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
		$this->had_full = array_key_exists ('full', $_REQUEST);
		$this->saved_full = $this->had_full ? $_REQUEST['full'] : NULL;
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
		if ($this->had_full)
			$_REQUEST['full'] = $this->saved_full;
		else
			unset ($_REQUEST['full']);
	}

	/**
	 * @group small
	 */
	public function testShowsLatestRowsAndLink ()
	{
		unset ($_REQUEST['full']);
		$html = $this->captureHistory ();
		$this->assertStringContainsString ('Display last 30 items from 31', $html);
		$this->assertStringContainsString ('Show full history', $html);
		$this->assertStringContainsString (self::$newest, $html);
		$this->assertStringNotContainsString (self::$oldest, $html);
	}

	/**
	 * @group small
	 */
	public function testFullRequestShowsEveryRow ()
	{
		$_REQUEST['full'] = '1';
		$html = $this->captureHistory ();
		$this->assertStringNotContainsString ('Show full history', $html);
		$this->assertStringContainsString (self::$oldest, $html);
		$this->assertStringContainsString (self::$newest, $html);
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
