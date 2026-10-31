<?php

class ChildSummaryTest extends RTTestCase
{
	/**
	 * @group small
	 */
	public function testSixChildrenStayOpen ()
	{
		$this->assertSame (
			'wap1<br>wap2<br>wap3<br>wap4<br>wap5<br>wap6',
			foldChildList (array ('wap1', 'wap2', 'wap3', 'wap4', 'wap5', 'wap6'))
		);
	}

	/**
	 * @group small
	 */
	public function testSevenChildrenFold ()
	{
		$this->assertSame (
			'wap1<br>wap2<br>wap3<br>wap4'
				. '<br><details><summary>Show 3 more</summary>wap5<br>wap6<br>wap7</details>',
			foldChildList (array ('wap1', 'wap2', 'wap3', 'wap4', 'wap5', 'wap6', 'wap7'))
		);
	}
}
