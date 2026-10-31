<?php

class ObjectHistorySpoilerTest extends RTTestCase
{
	/**
	 * @group small
	 * @dataProvider providerSplit
	 */
	public function testSplit ($count, $visible_count, $older_count)
	{
		$rows = array ();
		for ($i = 1; $i <= $count; $i++)
			$rows[] = array ('ctime' => $i);
		list ($visible, $older) = objectHistorySpoilerSplit ($rows);
		$this->assertCount ($visible_count, $visible);
		$this->assertCount ($older_count, $older);
		if ($count == 0)
			return;
		$this->assertSame (1, ($older ? $older : $visible)[0]['ctime']);
		$this->assertSame ($count, $visible[$visible_count - 1]['ctime']);
		if ($older)
			$this->assertSame ($count - 3, $visible[0]['ctime']);
	}

	public function providerSplit ()
	{
		return array
		(
			array (0, 0, 0),
			array (6, 6, 0),
			array (7, 4, 3),
			array (31, 4, 27),
		);
	}
}
