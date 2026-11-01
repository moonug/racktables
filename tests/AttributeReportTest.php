<?php

class AttributeReportTest extends RTTestCase
{
	private $object_a;
	private $object_b;
	private $object_c;
	private $attr_ids;

	public function setUp () : void
	{
		$this->object_a = commitAddObject ($this->myString ('report-a'), '', 4, '');
		$this->object_b = commitAddObject ($this->myString ('report-b'), '', 4, '');
		$this->object_c = commitAddObject ($this->myString ('report-c'), '', 4, '');
		$result = usePreparedSelectBlade
		(
			'SELECT AM.attr_id FROM AttributeMap AM INNER JOIN Attribute A ON A.id = AM.attr_id ' .
			'WHERE AM.objtype_id = 4 AND A.type = \'string\' ORDER BY AM.attr_id LIMIT 1'
		);
		$this->attr_ids = $result->fetchAll (PDO::FETCH_COLUMN, 0);
		$this->assertNotEmpty ($this->attr_ids, 'the test object type has no string attributes');
		$result = usePreparedSelectBlade
		(
			'SELECT attr_id FROM AttributeMap WHERE objtype_id = 4 AND attr_id != ? ORDER BY attr_id LIMIT 1',
			array ($this->attr_ids[0])
		);
		$other = $result->fetchColumn ();
		if ($other !== FALSE)
			$this->attr_ids[] = $other;
		commitUpdateAttrValue ($this->object_a, $this->attr_ids[0], 'a.example');
		commitUpdateAttrValue ($this->object_b, $this->attr_ids[0], 'b.example');
	}

	public function tearDown () : void
	{
		commitDeleteObject ($this->object_a);
		commitDeleteObject ($this->object_b);
		commitDeleteObject ($this->object_c);
	}

	private function objects ()
	{
		$ret = array ();
		foreach (array ($this->object_a, $this->object_b, $this->object_c) as $object_id)
			$ret[$object_id] = spotEntity ('object', $object_id);
		return $ret;
	}

	/**
	 * @group small
	 */
	public function testBatchMatchesSingleObjectLookup ()
	{
		$report = getObjectAttributeReport ($this->objects ());
		foreach (array ($this->object_a, $this->object_b, $this->object_c) as $object_id)
			$this->assertEquals (getAttrValues ($object_id), $report['attrs'][$object_id]);
	}

	/**
	 * @group small
	 */
	public function testOnlyAttributesWithValuesAreShown ()
	{
		$report = getObjectAttributeReport ($this->objects ());
		$this->assertArrayHasKey ($this->attr_ids[0], $report['display']);
		$this->assertSame ('a.example', $report['attrs'][$this->object_a][$this->attr_ids[0]]['value']);
		$this->assertSame ('b.example', $report['attrs'][$this->object_b][$this->attr_ids[0]]['value']);
		$this->assertNull ($report['attrs'][$this->object_c][$this->attr_ids[0]]['value']);
		if (isset ($this->attr_ids[1]))
			$this->assertArrayNotHasKey ($this->attr_ids[1], $report['display']);
	}

	/**
	 * @group small
	 */
	public function testEmptyList ()
	{
		$report = getObjectAttributeReport (array ());
		$this->assertSame (array (), $report['attrs']);
		$this->assertSame (array (), $report['display']);
	}
}
