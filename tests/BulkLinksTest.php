<?php

class BulkLinksTest extends RTTestCase
{
	private $object_a;
	private $object_b;
	private $port_a1;
	private $port_a2;
	private $port_b1;
	private $port_b2;
	private $port_b3;
	private $port_b_long;
	private $saved_rackcode;

	public function setUp () : void
	{
		global $rackCode;
		$this->saved_rackcode = $rackCode;

		$this->object_a = commitAddObject ($this->myString ('bulk-a'), '', 4, '');
		$this->object_b = commitAddObject ($this->myString ('bulk-b'), '', 4, '');
		$this->port_a1 = commitAddPort ($this->object_a, 'Gi1/0/1', '1-19', '', '');
		$this->port_a2 = commitAddPort ($this->object_a, 'Gi1/0/2', '1-24', '', '');
		$this->port_b1 = commitAddPort ($this->object_b, 'Gi1/0/1', '1-24', '', '');
		$this->port_b2 = commitAddPort ($this->object_b, 'Gi1/0/2', '1-24', '', '');
		$this->port_b3 = commitAddPort ($this->object_b, 'Gi1/0/9', '1-19', '', '');
		$this->port_b_long = commitAddPort ($this->object_b, 'GigabitEthernet1/0/5', '1-24', '', '');
		$allowed = getRackCode ('allow {$any_op}');
		$rackCode = $allowed['load'];
	}

	public function tearDown () : void
	{
		global $rackCode;
		$rackCode = $this->saved_rackcode;
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

	private function firstIncompatibleType ($oif_id)
	{
		$result = usePreparedSelectBlade ('SELECT id FROM PortOuterInterface ORDER BY id');
		foreach ($result->fetchAll (PDO::FETCH_COLUMN, 0) as $candidate)
			if (! arePortTypesCompatible ($candidate, $oif_id) && ! arePortTypesCompatible ($oif_id, $candidate))
				return $candidate;
		$this->fail ('no port type is incompatible with ' . $oif_id);
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
			// Only the leading marker is special; the rest of the token is a
			// port name, which may contain markers itself.
			array ('a Gi1/0*1 b Gi1/0!2', array (
				'a' => array ('name' => 'a', 'port' => 'Gi1/0*1', 'create' => FALSE, 'unlink' => FALSE),
				'b' => array ('name' => 'b', 'port' => 'Gi1/0!2', 'create' => FALSE, 'unlink' => FALSE),
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
			// A forgotten port name must not turn into a cable id.
			array ('a Gi1/0/1 - other'),
			// A cable id makes no sense on an unlink line.
			array ('a Gi1/0/1 - C-1'),
			array ('a Gi1/0/1 - - C-1'),
			// A marker alone is not a port name.
			array ('a * b Gi1/0/1'),
			array ('a ! b Gi1/0/1'),
			// A second marker is a typo, not the start of a port name.
			array ('a *!Gi1/0/1 b Gi1/0/2'),
			array ('a !*Gi1/0/1 b Gi1/0/2'),
			array ('a **Gi1/0/1 b Gi1/0/2'),
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
	public function testCableIsUpdated ()
	{
		commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/2', 'C-1'));
		$result = commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/2', 'C-2'));
		$this->assertSame (0, $result['linked']);
		$this->assertSame (1, $result['updated']);
		$this->assertSame ('C-2', $this->port ($this->object_a, 'Gi1/0/1')['cableid']);
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
	public function testUnlinkOfFreePortIsHarmless ()
	{
		$result = commitBulkLinks ($this->line ('Gi1/0/1'));
		$this->assertSame (0, $result['unlinked']);
		$this->assertSame (array (), $result['failed']);
	}

	/**
	 * @group small
	 */
	public function testCreatePort ()
	{
		$result = commitBulkLinks ($this->line ('Gi1/0/2', '*Gi1/0/7'));
		$this->assertSame (1, $result['linked']);
		$port = $this->port ($this->object_b, 'Gi1/0/7');
		$this->assertSame (1, (int) $port['iif_id']);
		$this->assertSame (24, (int) $port['oif_id']);
		$this->assertSame ($this->port_a2, $port['remote_id']);
	}

	/**
	 * @group small
	 */
	public function testCreatePortOnFirstSide ()
	{
		$result = commitBulkLinks ($this->line ('*Gi1/0/8', 'Gi1/0/9'));
		$this->assertSame (1, $result['linked']);
		$port = $this->port ($this->object_a, 'Gi1/0/8');
		$this->assertSame (1, (int) $port['iif_id']);
		$this->assertSame (19, (int) $port['oif_id']);
		$this->assertSame ($this->port_b3, $port['remote_id']);
	}

	/**
	 * @group small
	 */
	public function testBreedAbbreviation ()
	{
		$line = $this->objectName ($this->object_a) . ' Gi1/0/1 ' .
			$this->objectName ($this->object_b) . ' gi1/0/5';
		$result = commitBulkLinks ($line);
		$this->assertSame (1, $result['linked']);
		$this->assertSame ($this->port_b_long, $this->port ($this->object_a, 'Gi1/0/1')['remote_id']);
	}

	/**
	 * @group small
	 */
	public function testExactPortNameWinsOverAbbreviation ()
	{
		$short = commitAddPort ($this->object_b, 'Gi1/0/5', '1-24', '', '');
		$result = commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/5'));
		$this->assertSame (1, $result['linked']);
		$this->assertSame ($short, $this->port ($this->object_a, 'Gi1/0/1')['remote_id']);
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
	public function testForceRelinkOnSecondSide ()
	{
		commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/1'));
		$result = commitBulkLinks ($this->line ('Gi1/0/2', '!Gi1/0/1'));
		$this->assertSame (1, $result['linked']);
		$this->assertSame (1, $result['unlinked']);
		$this->assertSame ($this->port_b1, $this->port ($this->object_a, 'Gi1/0/2')['remote_id']);
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
	public function testSelfLinkIsAnError ()
	{
		$name = $this->objectName ($this->object_a);
		$result = commitBulkLinks ("${name} Gi1/0/1 ${name} Gi1/0/1");
		$this->assertSame (0, $result['linked']);
		$this->assertCount (1, $result['failed']);
		$this->assertStringContainsString ('itself', $result['failed'][0]['error']);
	}

	/**
	 * @group small
	 */
	public function testIncompatibleTypes ()
	{
		$type = $this->firstIncompatibleType (19);
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
	public function testFailedLineKeepsExistingLink ()
	{
		commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/1'));
		commitBulkLinks ($this->line ('Gi1/0/2', 'Gi1/0/2'));
		$result = commitBulkLinks ($this->line ('!Gi1/0/1', 'Gi1/0/2'));
		$this->assertSame (0, $result['linked']);
		$this->assertCount (1, $result['failed']);
		$this->assertSame ($this->port_b1, $this->port ($this->object_a, 'Gi1/0/1')['remote_id']);
		$this->assertSame ($this->port_a2, $this->port ($this->object_b, 'Gi1/0/2')['remote_id']);
	}

	/**
	 * @group small
	 */
	public function testForceRelinkKeepsLinksOnIncompatibleTypes ()
	{
		$type = $this->firstIncompatibleType (24);
		$bad_port = commitAddPort ($this->object_b, 'bad', $type, '', '');
		commitBulkLinks ($this->line ('Gi1/0/2', 'Gi1/0/1'));
		$result = commitBulkLinks ($this->line ('!Gi1/0/2', 'bad'));
		$this->assertSame (0, $result['linked']);
		$this->assertCount (1, $result['failed']);
		$this->assertSame ($this->port_b1, $this->port ($this->object_a, 'Gi1/0/2')['remote_id']);
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
		$this->assertSame ($this->port_b1, $this->port ($this->object_a, 'Gi1/0/1')['remote_id']);
	}

	/**
	 * @group small
	 */
	public function testHandlerKeepsSubmittedText ()
	{
		global $sic;
		@session_start ();
		unset ($_SESSION['bulk-linker-state']);
		$text = $this->line ('Gi1/0/999', 'Gi1/0/2');
		$_REQUEST['in'] = $text;
		$sic['in'] = $text;
		try
		{
			handleBulkLinkRequest ();
			$this->assertArrayHasKey ('bulk-linker-state', $_SESSION);
			$this->assertSame ($text, $_SESSION['bulk-linker-state']['text']);
			$this->assertSame (1, $_SESSION['bulk-linker-state']['errors'][0]['line']);
		}
		finally
		{
			unset ($_REQUEST['in'], $sic['in'], $_SESSION['bulk-linker-state']);
		}
	}

	/**
	 * @group small
	 */
	public function testRenderKeepsFailedLinesWithNumbers ()
	{
		global $pageno, $tabno;
		$saved_pageno = $pageno;
		$saved_tabno = $tabno;
		$pageno = 'depot';
		$tabno = 'linker';
		@session_start ();
		$_SESSION['bulk-linker-state'] = array (
			'text' => $this->line ('Gi1/0/999', 'Gi1/0/2'),
			'errors' => array (array (
				'line' => 1,
				'text' => 'x',
				'error' => 'no port named Gi1/0/999',
			)),
		);
		try
		{
			$html = getOutputOf ('renderBulkLinkForm');
		}
		finally
		{
			$pageno = $saved_pageno;
			$tabno = $saved_tabno;
		}
		$this->assertStringContainsString ('Line 1: no port named Gi1/0/999', $html);
		$this->assertArrayNotHasKey ('bulk-linker-state', $_SESSION);
	}

	/**
	 * @group small
	 */
	public function testDeniedObjectPermissionBlocksLink ()
	{
		global $rackCode;
		$allowed = getRackCode ("deny {\$id_{$this->object_b}}\nallow {\$any_op}");
		$rackCode = $allowed['load'];
		$result = commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/2'));
		$rackCode = $this->saved_rackcode;
		$this->assertSame (0, $result['linked']);
		$this->assertCount (1, $result['failed']);
		$this->assertStringContainsString ('no permission to modify ports', $result['failed'][0]['error']);
		$this->assertNull ($this->port ($this->object_a, 'Gi1/0/1')['remote_id']);
	}

	/**
	 * @group small
	 */
	public function testUnlinkNeedsPermissionOnBothEnds ()
	{
		global $rackCode;
		commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/1'));
		$allowed = getRackCode ("deny {\$op_unlinkPort} and {\$id_{$this->object_b}}\nallow {\$any_op}");
		$rackCode = $allowed['load'];
		$result = commitBulkLinks ($this->line ('Gi1/0/1'));
		$rackCode = $this->saved_rackcode;
		$this->assertSame (0, $result['unlinked']);
		$this->assertCount (1, $result['failed']);
		$this->assertStringContainsString ('no permission to modify ports', $result['failed'][0]['error']);
		$this->assertStringContainsString ($this->objectName ($this->object_b), $result['failed'][0]['error']);
		$this->assertSame ($this->port_b1, $this->port ($this->object_a, 'Gi1/0/1')['remote_id']);
	}

	/**
	 * @group small
	 */
	public function testUnlinkNeedsNearSidePermission ()
	{
		global $rackCode;
		commitBulkLinks ($this->line ('Gi1/0/1', 'Gi1/0/1'));
		$allowed = getRackCode ("deny {\$op_unlinkPort} and {\$id_{$this->object_a}}\nallow {\$any_op}");
		$rackCode = $allowed['load'];
		$result = commitBulkLinks ($this->line ('Gi1/0/1'));
		$rackCode = $this->saved_rackcode;
		$this->assertSame (0, $result['unlinked']);
		$this->assertCount (1, $result['failed']);
		$this->assertStringContainsString ($this->objectName ($this->object_a), $result['failed'][0]['error']);
		$this->assertSame ($this->port_b1, $this->port ($this->object_a, 'Gi1/0/1')['remote_id']);
	}

	/**
	 * @group small
	 */
	public function testForceRelinkNeedsFarEndPermission ()
	{
		global $rackCode;
		$object_c = NULL;
		$result = array ();
		$remote = NULL;
		try
		{
			$object_c = commitAddObject ($this->myString ('bulk-c'), '', 4, '');
			$name_c = $this->objectName ($object_c);
			$port_c = commitAddPort ($object_c, 'Gi1/0/1', '1-19', '', '');
			linkPorts ($this->port_a1, $port_c);
			$allowed = getRackCode ("deny {\$op_unlinkPort} and {\$id_{$object_c}}\nallow {\$any_op}");
			$rackCode = $allowed['load'];
			$result = commitBulkLinks ($this->line ('!Gi1/0/1', 'Gi1/0/2'));
			$remote = $this->port ($this->object_a, 'Gi1/0/1')['remote_id'];
		}
		finally
		{
			$rackCode = $this->saved_rackcode;
			if ($object_c !== NULL)
				commitDeleteObject ($object_c);
		}
		$this->assertSame (0, $result['linked']);
		$this->assertCount (1, $result['failed']);
		$this->assertStringContainsString ('no permission to modify ports', $result['failed'][0]['error']);
		$this->assertStringContainsString ($name_c, $result['failed'][0]['error']);
		$this->assertSame ($port_c, $remote);
	}

	/**
	 * @group small
	 */
	public function testAmbiguousPortNameIsAnError ()
	{
		commitAddPort ($this->object_b, 'Gi1/0/1', '1-19', '', '');
		$result = commitBulkLinks ($this->line ('Gi1/0/2', 'Gi1/0/1'));
		$this->assertSame (0, $result['linked']);
		$this->assertCount (1, $result['failed']);
		$this->assertStringContainsString ('is ambiguous', $result['failed'][0]['error']);
		$this->assertNull ($this->port ($this->object_a, 'Gi1/0/2')['remote_id']);
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
