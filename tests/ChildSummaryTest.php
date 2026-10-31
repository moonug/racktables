<?php

class ChildSummaryTest extends RTTestCase
{
	/**
	 * @group small
	 */
	public function testSixChildrenStayOpen ()
	{
		$cell = array ('realm' => 'object', 'id' => 1);
		$six = array ('Contains wireless access point' => 'wap1<br>wap2<br>wap3<br>wap4<br>wap5<br>wap6');
		$this->assertSame ($six, foldChildSummary ($cell, $six));
	}

	/**
	 * @group small
	 */
	public function testSevenChildrenFold ()
	{
		$cell = array ('realm' => 'object', 'id' => 1);
		$seven = array ('Contains wireless access point' => 'wap1<br>wap2<br>wap3<br>wap4<br>wap5<br>wap6<br>wap7');
		$folded = 'wap1<br>wap2<br>wap3<br>wap4'
			. '<br><details><summary>Show 3 more</summary>wap5<br>wap6<br>wap7</details>';
		$this->assertSame (array ('Contains wireless access point' => $folded), foldChildSummary ($cell, $seven));
		$this->assertSame ($seven, foldChildSummary (array ('realm' => 'rack'), $seven));
	}
}
