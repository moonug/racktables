<?php

class AttributeReportTest extends RTTestCase
{
	private $object_a;
	private $object_b;
	private $object_c;
	private $object_d;
	private $attr_ids;
	private $extra_attr;
	private $saved_rackcode;
	private $saved_pageno;
	private $saved_tabno;
	private $saved_op;
	private $saved_attr_cache;

	public function setUp () : void
	{
		global $rackCode, $pageno, $tabno, $op, $object_attribute_cache;

		@session_start ();
		$this->saved_rackcode = $rackCode;
		$this->saved_pageno = $pageno;
		$this->saved_tabno = $tabno;
		$this->saved_op = $op;
		$this->saved_attr_cache = $object_attribute_cache;
		$pageno = 'depot';
		$tabno = 'attreport';
		$op = '';
		$allowed = getRackCode ("allow {\$page_object}\nallow {\$page_depot}");
		$rackCode = $allowed['load'];

		$result = usePreparedSelectBlade
		(
			'SELECT AM.attr_id, AM.chapter_id FROM AttributeMap AM INNER JOIN Attribute A ON A.id = AM.attr_id ' .
			'WHERE AM.objtype_id = 4 AND A.type = \'string\' ORDER BY AM.attr_id LIMIT 1'
		);
		$row = $result->fetch (PDO::FETCH_ASSOC);
		$this->assertNotEmpty ($row, 'the test object type has no string attributes');
		$this->attr_ids = array ($row['attr_id']);

		$this->object_a = commitAddObject ($this->myString ('report-a'), '', 4, '');
		$this->object_b = commitAddObject ($this->myString ('report-b'), '', 4, '');
		$this->object_c = commitAddObject ($this->myString ('report-c'), '', 4, '');
		$this->object_d = commitAddObject ($this->myString ('report-d'), '', 4, '');

		// A second attribute with a name that sorts before the seeded ones but
		// has a larger id, so column ordering is observable.
		usePreparedInsertBlade ('Attribute', array ('type' => 'string', 'name' => 'AAA ' . $this->myString ('report')));
		$this->extra_attr = lastInsertID ();
		usePreparedInsertBlade ('AttributeMap', array (
			'objtype_id' => 4,
			'attr_id' => $this->extra_attr,
			'chapter_id' => $row['chapter_id'],
		));

		commitUpdateAttrValue ($this->object_a, $this->attr_ids[0], 'a.example');
		commitUpdateAttrValue ($this->object_b, $this->attr_ids[0], 'b.example');
	}

	public function tearDown () : void
	{
		global $rackCode, $pageno, $tabno, $op, $object_attribute_cache, $entityCache;
		$rackCode = $this->saved_rackcode;
		$pageno = $this->saved_pageno;
		$tabno = $this->saved_tabno;
		$op = $this->saved_op;
		$object_attribute_cache = $this->saved_attr_cache;
		unset ($entityCache['complete']['object'], $entityCache['partial']['object']);
		commitDeleteObject ($this->object_a);
		commitDeleteObject ($this->object_b);
		commitDeleteObject ($this->object_c);
		commitDeleteObject ($this->object_d);
		usePreparedDeleteBlade ('AttributeMap', array ('attr_id' => $this->extra_attr));
		usePreparedDeleteBlade ('Attribute', array ('id' => $this->extra_attr));
	}

	private function objects ()
	{
		$ret = array ();
		foreach (array ($this->object_a, $this->object_b, $this->object_c) as $object_id)
			$ret[$object_id] = spotEntity ('object', $object_id);
		return $ret;
	}

	private function objectName ($object_id)
	{
		return spotEntity ('object', $object_id)['name'];
	}

	private function attrName ($object_id, $attr_id)
	{
		return getAttrValues ($object_id)[$attr_id]['name'];
	}

	private function statementCount ()
	{
		global $dbxlink;
		$row = $dbxlink->query ("SHOW SESSION STATUS LIKE 'Com_select'")->fetch (PDO::FETCH_NUM);
		return (int) $row[1];
	}

	/**
	 * @group small
	 */
	public function testBatchMatchesSingleObjectLookup ()
	{
		global $object_attribute_cache;
		$poison = array (array (
			'id' => 999999,
			'name' => 'poison',
			'type' => 'string',
			'value' => 'poison',
			'a_value' => 'poison',
		));
		foreach ($this->objects () as $object)
			$object_attribute_cache[$object['id']] = $poison;
		$report = getObjectAttributeReport ($this->objects ());
		$this->assertSame ('a.example', $report['attrs'][$this->object_a][$this->attr_ids[0]]['value']);

		$object_attribute_cache = array ();
		$report = getObjectAttributeReport ($this->objects ());
		foreach ($this->objects () as $object)
		{
			$expected = array ();
			foreach (getAttrValues ($object['id']) as $attr_id => $record)
				if (isset ($report['display'][$attr_id]) && attributeValueIsSet ($record))
					$expected[$attr_id] = $record;
			$actual = isset ($report['attrs'][$object['id']]) ? $report['attrs'][$object['id']] : array ();
			$this->assertEquals ($expected, $actual);
		}
	}

	/**
	 * @group small
	 */
	public function testFetchCostDoesNotGrowWithTheObjectCount ()
	{
		$extra = array ();
		try
		{
			for ($i = 0; $i < 10; $i++)
				$extra[] = commitAddObject ($this->myString ('report-extra-' . $i), '', 4, '');
			$objects = $this->objects ();
			foreach ($extra as $object_id)
				$objects[$object_id] = spotEntity ('object', $object_id);
			$few_objects = $this->objects ();

			$before = $this->statementCount ();
			getObjectAttributeReport ($few_objects);
			$few = $this->statementCount () - $before;
			$before = $this->statementCount ();
			getObjectAttributeReport ($objects);
			$many = $this->statementCount () - $before;
		}
		finally
		{
			foreach ($extra as $object_id)
				commitDeleteObject ($object_id);
		}
		$this->assertGreaterThan (0, $few, 'the statement counter did not move');
		$this->assertLessThanOrEqual ($few, $many);
	}

	/**
	 * @group small
	 */
	public function testOnlyAttributesWithValuesAreShown ()
	{
		$report = getObjectAttributeReport ($this->objects ());
		$this->assertArrayHasKey ($this->attr_ids[0], $report['display']);
		$this->assertArrayNotHasKey ($this->extra_attr, $report['display']);
		$this->assertSame ('a.example', $report['attrs'][$this->object_a][$this->attr_ids[0]]['value']);
		$this->assertSame ('b.example', $report['attrs'][$this->object_b][$this->attr_ids[0]]['value']);
		$c_attrs = isset ($report['attrs'][$this->object_c]) ? $report['attrs'][$this->object_c] : array ();
		$this->assertArrayNotHasKey ($this->attr_ids[0], $c_attrs);
	}

	/**
	 * @group small
	 */
	public function testZeroIsAValue ()
	{
		commitUpdateAttrValue ($this->object_a, $this->attr_ids[0], '0');
		$report = getObjectAttributeReport (array ($this->object_a => spotEntity ('object', $this->object_a)));
		$this->assertArrayHasKey ($this->attr_ids[0], $report['display']);
		$this->assertSame ('0', $report['attrs'][$this->object_a][$this->attr_ids[0]]['value']);
	}

	/**
	 * @group small
	 */
	public function testDateValueIsShown ()
	{
		$result = usePreparedSelectBlade
		(
			'SELECT AM.attr_id FROM AttributeMap AM INNER JOIN Attribute A ON A.id = AM.attr_id ' .
			'WHERE AM.objtype_id = 4 AND A.type = \'date\' LIMIT 1'
		);
		$row = $result->fetch (PDO::FETCH_ASSOC);
		if ($row === FALSE)
			$this->markTestSkipped ('the test object type has no date attributes');
		$timestamp = 1600000000;
		commitUpdateAttrValue ($this->object_c, $row['attr_id'], (string) $timestamp);
		$report = getObjectAttributeReport (array ($this->object_c => spotEntity ('object', $this->object_c)));
		$this->assertArrayHasKey ($row['attr_id'], $report['display']);
		$this->assertSame ((string) $timestamp, (string) $report['attrs'][$this->object_c][$row['attr_id']]['value']);
	}

	/**
	 * @group small
	 */
	public function testAttributesOfFilteredOutObjectsAreHidden ()
	{
		commitUpdateAttrValue ($this->object_d, $this->extra_attr, 'outside');
		$report = getObjectAttributeReport ($this->objects ());
		$this->assertArrayNotHasKey ($this->extra_attr, $report['display']);
		$this->assertArrayNotHasKey ($this->object_d, $report['attrs']);
	}

	/**
	 * @group small
	 */
	public function testColumnsAreSortedByName ()
	{
		commitUpdateAttrValue ($this->object_a, $this->extra_attr, 'value-b');
		$report = getObjectAttributeReport (array ($this->object_a => spotEntity ('object', $this->object_a)));
		$this->assertSame (array ((int) $this->extra_attr, (int) $this->attr_ids[0]), array_keys ($report['display']));
	}

	/**
	 * @group small
	 */
	public function testColumnsAreAUnionOfTheVisibleObjects ()
	{
		commitUpdateAttrValue ($this->object_b, $this->extra_attr, 'b-extra');
		$objects = $this->objects ();
		$report = getObjectAttributeReport ($objects);
		$rendered = attributeReportColumns ($objects, $report['display'], $report['attrs']);
		$this->assertSame (array ((int) $this->extra_attr, (int) $this->attr_ids[0]), array_keys ($rendered['columns']));
		$this->assertArrayHasKey ($this->extra_attr, $rendered['cells'][$this->object_b]);
		$this->assertArrayHasKey ($this->attr_ids[0], $rendered['cells'][$this->object_a]);
		$c_cells = isset ($rendered['cells'][$this->object_c]) ? $rendered['cells'][$this->object_c] : array ();
		$this->assertArrayNotHasKey ($this->attr_ids[0], $c_cells);
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

	/**
	 * @group small
	 */
	public function testRenderReport ()
	{
		global $tab, $tabhandler;
		$this->assertSame ('Attr report', $tab['depot']['attreport']);
		$this->assertSame ('renderObjectAttributeReport', $tabhandler['depot']['attreport']);
		$html = getOutputOf ('renderObjectAttributeReport');
		$name = $this->attrName ($this->object_a, $this->attr_ids[0]);
		$this->assertStringContainsString ('<th>Common name</th>', $html);
		$this->assertSame (1, substr_count ($html, '<th>' . htmlspecialchars ($name) . '</th>'));
		$this->assertStringContainsString ('a.example', $html);
		$this->assertStringContainsString ($this->objectName ($this->object_a), $html);
	}

	/**
	 * @group small
	 */
	public function testAttributePermissionHidesColumn ()
	{
		global $rackCode;
		$denied = $this->attrName ($this->object_a, $this->attr_ids[0]);
		$kept = $this->attrName ($this->object_a, $this->extra_attr);
		commitUpdateAttrValue ($this->object_a, $this->extra_attr, 'kept.example');
		$allowed = getRackCode ("deny {\$attr_{$this->attr_ids[0]}}\nallow {\$page_object}\nallow {\$page_depot}");
		$rackCode = $allowed['load'];
		$html = getOutputOf ('renderObjectAttributeReport');
		$this->assertStringContainsString ('Common name', $html);
		$this->assertStringContainsString ($this->objectName ($this->object_b), $html);
		$this->assertStringContainsString ($kept, $html);
		$this->assertStringContainsString ('kept.example', $html);
		$this->assertStringNotContainsString ($denied, $html);
		$this->assertStringNotContainsString ('a.example', $html);
	}

	/**
	 * @group small
	 */
	public function testObjectPermissionHidesRowsAndColumns ()
	{
		global $rackCode;
		$allowed = getRackCode
		(
			"allow {\$page_object} and {\$id_{$this->object_a}}\n" .
			"deny {\$page_object}\n" .
			"allow {\$page_depot}"
		);
		$rackCode = $allowed['load'];
		$html = getOutputOf ('renderObjectAttributeReport');
		$name = $this->attrName ($this->object_a, $this->attr_ids[0]);
		$this->assertStringContainsString ('Objects (1)', $html);
		$this->assertStringContainsString ($this->objectName ($this->object_a), $html);
		$this->assertSame (1, substr_count ($html, '<th>' . htmlspecialchars ($name) . '</th>'));
		$this->assertStringContainsString ('a.example', $html);
		$this->assertStringNotContainsString ($this->objectName ($this->object_b), $html);
		$this->assertStringNotContainsString ('b.example', $html);
	}

	/**
	 * @group small
	 */
	public function testAttributePermissionIsPerObject ()
	{
		global $rackCode;
		$name = $this->attrName ($this->object_a, $this->attr_ids[0]);
		$allowed = getRackCode
		(
			"deny {\$attr_{$this->attr_ids[0]}} and not {\$id_{$this->object_b}}\n" .
			"allow {\$page_object}\nallow {\$page_depot}"
		);
		$rackCode = $allowed['load'];
		$html = getOutputOf ('renderObjectAttributeReport');
		$this->assertSame (1, substr_count ($html, '<th>' . htmlspecialchars ($name) . '</th>'));
		$this->assertStringContainsString ('b.example', $html);
		$this->assertStringNotContainsString ('a.example', $html);
	}

	/**
	 * @group small
	 */
	public function testPageScopedDenyHidesColumn ()
	{
		global $rackCode;
		$name = $this->attrName ($this->object_a, $this->attr_ids[0]);
		$allowed = getRackCode
		(
			"deny {\$page_depot} and {\$attr_{$this->attr_ids[0]}}\n" .
			"allow {\$page_object}\nallow {\$page_depot}"
		);
		$rackCode = $allowed['load'];
		$html = getOutputOf ('renderObjectAttributeReport');
		$this->assertStringContainsString ($this->objectName ($this->object_b), $html);
		$this->assertStringNotContainsString ($name, $html);
		$this->assertStringNotContainsString ('b.example', $html);
	}

	/**
	 * @group small
	 */
	public function testColumnWithoutVisibleValuesIsNotShown ()
	{
		global $rackCode;
		$name = $this->attrName ($this->object_a, $this->attr_ids[0]);
		// Only object_c has no value, so only its permission could keep the
		// column alive; a column nobody can fill must not be printed. Denying
		// every other object keeps the test independent of database content.
		$allowed = getRackCode
		(
			"deny {\$attr_{$this->attr_ids[0]}} and not {\$id_{$this->object_c}}\n" .
			"allow {\$page_object}\nallow {\$page_depot}"
		);
		$rackCode = $allowed['load'];
		$html = getOutputOf ('renderObjectAttributeReport');
		$this->assertStringContainsString ($this->objectName ($this->object_c), $html);
		$this->assertStringNotContainsString ($name, $html);
	}

	/**
	 * @group small
	 */
	public function testMgmtProtocolLinkUsesTheObjectContext ()
	{
		$result = usePreparedSelectBlade ('SELECT attr_id FROM AttributeMap WHERE objtype_id = 4 AND attr_id = 3');
		if ($result->fetch () === FALSE)
			$this->markTestSkipped ('the test object type has no FQDN attribute');
		commitUpdateAttrValue ($this->object_a, 3, 'mgmt.example');
		// A switch formats in its own context: the rule matches type 4 only,
		// so reusing the first object's context would produce an ssh link here.
		$plain_id = commitAddObject ($this->myString ('report-plain'), '', 8, '');
		$html = '';
		$old = getConfigVar ('MGMT_PROTOS');
		setConfigVar ('MGMT_PROTOS', 'ssh: {$typeid_4}');
		getMgmtProtosConfig (TRUE);
		try
		{
			commitUpdateAttrValue ($plain_id, 3, 'plain.example');
			$html = getOutputOf ('renderObjectAttributeReport');
		}
		finally
		{
			setConfigVar ('MGMT_PROTOS', $old);
			getMgmtProtosConfig (TRUE);
			commitDeleteObject ($plain_id);
		}
		$this->assertStringContainsString ('ssh://mgmt.example', $html);
		$this->assertStringContainsString ('plain.example', $html);
		$this->assertStringNotContainsString ('ssh://plain.example', $html);
	}
}
