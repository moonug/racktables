<?php

class ShortenIfNameTest extends RTTestCase
{
	/**
	 * @group small
	 * @dataProvider providerIosHighSpeed
	 */
	public function testIosHighSpeed ($breed, $long_name, $short_name)
	{
		$this->assertSame ($short_name, shortenIfName ($long_name, $breed));
	}

	public function providerIosHighSpeed ()
	{
		$cases = array
		(
			'TwentyFiveGigE1/1/1' => 'twe1/1/1',
			'TwentyFiveGigE42/43/44' => 'twe42/43/44',
			'FortyGigabitEthernet1/1/1' => 'fo1/1/1',
			'FortyGigabitEthernet42/43/44' => 'fo42/43/44',
			'AppGigabitEthernet1/0/1' => 'ap1/0/1',
			'TwentyFiveGigE 1/2/3' => 'twe1/2/3',
			'FortyGigabitEthernet 1/2/3' => 'fo1/2/3',
			'AppGigabitEthernet 1/2/3' => 'ap1/2/3',
			'Ap1/0/1' => 'ap1/0/1',
		);
		$ret = array();
		foreach (array (NULL, 'ios12', 'ios15') as $breed)
			foreach ($cases as $long_name => $short_name)
				$ret[] = array ($breed, $long_name, $short_name);
		return $ret;
	}
}
