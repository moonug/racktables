<?php

class BulkLinksTest extends RTTestCase
{
	private $object_a;
	private $object_b;
	private $port_a1;
	private $port_a2;
	private $port_b1;
	private $port_b2;

	public function setUp () : void
	{
		$this->object_a = commitAddObject ($this->myString ('bulk-a'), '', 4, '');
		$this->object_b = commitAddObject ($this->myString ('bulk-b'), '', 4, '');
		$this->port_a1 = commitAddPort ($this->object_a, 'Gi1/0/1', '1-24', '', '');
		$this->port_a2 = commitAddPort ($this->object_a, 'Gi1/0/2', '1-24', '', '');
		$this->port_b1 = commitAddPort ($this->object_b, 'Gi1/0/1', '1-24', '', '');
		$this->port_b2 = commitAddPort ($this->object_b, 'Gi1/0/2', '1-24', '', '');
	}

	public function tearDown () : void
	{
		commitDeleteObject ($this->object_a);
		commitDeleteObject ($this->object_b);
	}

	private function port ($object_id, $port_name)
	{
		$ports = getObjectPortsAndLinks ($object_id);
		$key = scanArrayForItem ($ports, 'name', $port_name);
		$this->assertNotNull ($key, "port ${port_name} not found");
		return $ports[$key];
	}

	private function objectName ($object_id)
	{
		return spotEntity ('object', $object_id)['name'];
	}

	private function line ($a, $b = NULL, $cable = NULL)
	{
		$name_a = $this->objectName ($this->object_a);
		$name_b = $this->objectName ($this->object_b);
		$ret = "${name_a} ${a}";
		$ret .= $b === NULL ? ' - -' : " ${name_b} ${b}";
		if ($cable !== NULL)
			$ret .= ' ' . $cable;
		return $ret;
	}

	/**
	 * @group small
	 * @dataProvider providerParse
	 */
	public function testParse ($line, $expected)
	{
		$this->assertSame ($expected, parseBulkLinkLine ($line));
	}

	public function providerParse ()
	{
		return array
		(
			array ('a Gi1/0/1 b Gi1/0/2', array (
				'a' => array ('name' => 'a', 'port' => 'Gi1/0/1', 'create' => FALSE, 'unlink' => FALSE),
				'b' => array ('name' => 'b', 'port' => 'Gi1/0/2', 'create' => FALSE, 'unlink' => FALSE),
				'cable' => NULL,
			)),
			array ('a *Gi1/0/1 b !Gi1/0/2 C-1', array (
				'a' => array ('name' => 'a', 'port' => 'Gi1/0/1', 'create' => TRUE, 'unlink' => FALSE),
				'b' => array ('name' => 'b', 'port' => 'Gi1/0/2', 'create' => FALSE, 'unlink' => TRUE),
				'cable' => 'C-1',
			)),
			array ('a Gi1/0/1 - -', array (
				'a' => array ('name' => 'a', 'port' => 'Gi1/0/1', 'create' => FALSE, 'unlink' => FALSE),
				'b' => NULL,
				'cable' => NULL,
			)),
		);
	}

	/**
	 * @group small
	 * @dataProvider providerParseFails
	 */
	public function testParseFails ($line)
	{
		$this->expectException (InvalidArgumentException::class);
		parseBulkLinkLine ($line);
	}

	public function providerParseFails ()
	{
		return array
		(
			array ('a'),
			array ('a Gi1/0/1 b'),
			array ('a Gi1/0/1 b Gi1/0/2 too many'),
			array ('- -'),
		);
	}

	/**
	 * @group small
	 */
	public function testLink ()
	{
		$result = commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/2', 'C-1'));
		$this->assertSame (1, $result['linked']);
		$this->assertSame (0, $result['unlinked']);
		$this->assertSame (array (), $result['failed']);
		$this->assertSame (1, $this->port ($this->object_a, 'Gi1/0/1')['linked']);
		$this->assertSame ('C-1', $this->port ($this->object_a, 'Gi1/0/1')['cableid']);
		$this->assertSame ($this->port_b2, $this->port ($this->object_a, 'Gi1/0/1')['remote_id']);
	}

	/**
	 * @group small
	 */
	public function testLinkIsIdempotent ()
	{
		$line = $this->line ('Gi1/0/1', 'Gi1/0/2');
		commitBulkLinks ($line);
		$result = commitBulkLinks ($line);
		$this->assertSame (0, $result['linked']);
		$this->assertSame (1, $result['already']);
	}

	/**
	 * @group small
	 */
	public function testUnlink ()
	{
		commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/2'));
		$result = commitBulkLinks ($this->line ('Gi1/0/1'));
		$this->assertSame (0, $result['linked']);
		$this->assertSame (1, $result['unlinked']);
		$this->assertSame (0, $this->port ($this->object_a, 'Gi1/0/1')['linked']);
		$this->assertSame (0, $this->port ($this->object_b, 'Gi1/0/2')['linked']);
	}

	/**
	 * @group small
	 */
	public function testCreatePort ()
	{
		$result = commitBulkLinks ($this->line ('Gi1/0/1', '*Gi1/0/9'));
		$this->assertSame (1, $result['linked']);
		$port = $this->port ($this->object_b, 'Gi1/0/9');
		$this->assertEquals (1, $port['iif_id']);
		$this->assertEquals (24, $port['oif_id']);
		$this->assertEquals ($this->port_a1, $port['remote_id']);
	}

	/**
	 * @group small
	 */
	public function testForceRelink ()
	{
		commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/1'));
		$result = commitBulkLinks ($this->line ('!Gi1/0/1', 'Gi1/0/2'));
		$this->assertSame (1, $result['linked']);
		$this->assertSame (1, $result['unlinked']);
		$this->assertSame ($this->port_b2, $this->port ($this->object_a, 'Gi1/0/1')['remote_id']);
		$this->assertSame (0, $this->port ($this->object_b, 'Gi1/0/1')['linked']);
	}

	/**
	 * @group small
	 */
	public function testAlreadyLinkedIsAnError ()
	{
		commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/1'));
		$result = commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/2'));
		$this->assertSame (0, $result['linked']);
		$this->assertCount (1, $result['failed']);
		$this->assertStringContainsString ('already linked', $result['failed'][0]['error']);
	}

	/**
	 * @group small
	 */
	public function testIncompatibleTypes ()
	{
		$result = usePreparedSelectBlade
		(
			'SELECT type1 FROM PortCompat WHERE type1 != 24 AND type2 != 24 LIMIT 1'
		);
		$type = $result->fetchColumn ();
		$bad_port = commitAddPort ($this->object_b, 'bad', $type, '', '');
		$result = commitBulkLinks ($this->line ('Gi1/0/1', 'bad'));
		$this->assertSame (0, $result['linked']);
		$this->assertCount (1, $result['failed']);
		$this->assertStringContainsString ('not compatible', $result['failed'][0]['error']);
		usePreparedDeleteBlade ('Port', array ('id' => $bad_port));
	}

	/**
	 * @group small
	 */
	public function testMixedResultKeepsFailedLines ()
	{
		$text = implode ("\n", array (
			$this->line ('Gi1/0/1', 'Gi1/0/1'),
			$this->line ('Gi1/0/999', 'Gi1/0/2'),
		));
		$result = commitBulkLinks ($text);
		$this->assertSame (1, $result['linked']);
		$this->assertCount (1, $result['failed']);
		$this->assertSame (2, $result['failed'][0]['line']);
		$this->assertStringContainsString ('Gi1/0/999', $result['failed'][0]['text']);
	}

	/**
	 * @group small
	 */
	public function testUnknownObject ()
	{
		$result = commitBulkLinks ('no-such-object Gi1/0/1 ' . $this->objectName ($this->object_b) . ' Gi1/0/1');
		$this->assertSame (0, $result['linked']);
		$this->assertCount (1, $result['failed']);
		$this->assertStringContainsString ('no object named', $result['failed'][0]['error']);
	}
}
