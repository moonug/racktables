<?php

class SearchFilterTest extends RTTestCase
{
	/**
	 * @group small
	 */
	public function testDropsTheObjectTheViewerCannotOpen ()
	{
		global $pageno_by_etype;
		$pageno_by_etype['object'] = 'object';
		$summary = array
		(
			'object' => array
			(
				1 => array ('id' => 1),
				2 => array ('id' => 2),
			),
			'custom' => array
			(
				9 => array ('id' => 9),
			),
		);
		$entity_for = function ($realm, $record)
		{
			return array ('id' => $record['id'], 'realm' => $realm);
		};
		$visible = function ($page, $tab, $entity)
		{
			return $page === 'object' && $entity['id'] === 1;
		};
		$filtered = filterSearchSummary ($summary, $entity_for, $visible);
		$this->assertSame (array (1 => array ('id' => 1)), $filtered['object']);
		$this->assertSame ($summary['custom'], $filtered['custom']);
	}

	/**
	 * @group small
	 */
	public function testUserKeyUsesUserId ()
	{
		$this->assertSame (7, searchEntityId ('user', array ('user_id' => 7)));
		$this->assertNull (searchEntityId ('user', array ('id' => 7)));
		$this->assertSame (3, searchEntityId ('object', array ('id' => 3)));
	}

	/**
	 * @group small
	 */
	public function testKeepsAddressWithoutANetworkWhenThePageIsAllowed ()
	{
		$summary = array
		(
			'ipv4addressbydq' => array
			(
				'addr' => array ('ip' => 'abcd'),
			),
			'custom' => array
			(
				9 => array ('id' => 9),
			),
		);
		$entity_for = function ($realm, $record)
		{
			return NULL;
		};
		$visible = function ($page, $tab, $entity)
		{
			return $page === 'ipaddress' && $entity === NULL;
		};
		$filtered = filterSearchSummary ($summary, $entity_for, $visible);
		$this->assertSame ($summary['ipv4addressbydq'], $filtered['ipv4addressbydq']);
		$this->assertSame ($summary['custom'], $filtered['custom']);
	}

	/**
	 * @group small
	 */
	public function testDropsAddressWithoutANetworkWhenThePageIsDenied ()
	{
		$summary = array
		(
			'ipv4addressbydq' => array
			(
				'addr' => array ('ip' => 'abcd'),
			),
			'custom' => array
			(
				9 => array ('id' => 9),
			),
		);
		$entity_for = function ($realm, $record)
		{
			return NULL;
		};
		$visible = function ($page, $tab, $entity)
		{
			return FALSE;
		};
		$filtered = filterSearchSummary ($summary, $entity_for, $visible);
		$this->assertArrayNotHasKey ('ipv4addressbydq', $filtered);
		$this->assertSame ($summary['custom'], $filtered['custom']);
	}

	/**
	 * @group small
	 */
	public function testChecksAddressAgainstItsNetworkPage ()
	{
		$summary = array
		(
			'ipv4addressbydq' => array
			(
				'addr' => array ('ip' => 'abcd', 'net_id' => 5),
			),
			'custom' => array
			(
				9 => array ('id' => 9),
			),
		);
		$realms = array ();
		$entity_for = function ($realm, $record) use (&$realms)
		{
			$realms[] = $realm;
			return array ('id' => $record['net_id'], 'realm' => 'ipv4net');
		};
		$seen = NULL;
		$visible = function ($page, $tab, $entity) use (&$seen)
		{
			$seen = $page;
			return $page === 'ipv4net';
		};
		$filtered = filterSearchSummary ($summary, $entity_for, $visible);
		$this->assertSame (array ('network'), $realms);
		$this->assertSame ('ipv4net', $seen);
		$this->assertSame ($summary['ipv4addressbydq'], $filtered['ipv4addressbydq']);
		$this->assertSame ($summary['custom'], $filtered['custom']);
	}

	/**
	 * @group small
	 */
	public function testAddressRedirectTargetFollowsTheOpenedPage ()
	{
		$record = array ('ip' => 'abcd', 'net_id' => 5);
		$empty = array
		(
			'ip_bin' => 'abcd',
			'name' => '',
			'comment' => '',
			'reserved' => 'no',
			'allocs' => array (),
			'vslist' => array (),
			'vsglist' => array (),
			'rsplist' => array (),
			'outpf' => array (),
			'inpf' => array (),
		);
		$allocated = $empty;
		$allocated['allocs'] = array (array ('object_id' => 42));
		$target = searchAddressRedirectTarget ($record, $allocated);
		$this->assertSame ('object', $target['page']);
		$this->assertSame (42, $target['id']);

		$service = $empty;
		$service['vslist'] = array (1 => array ('id' => 7));
		$target = searchAddressRedirectTarget ($record, $service);
		$this->assertSame ('ipaddress', $target['page']);

		$target = searchAddressRedirectTarget ($record, $empty);
		$this->assertSame ('ipv4net', $target['page']);
		$this->assertSame (5, $target['id']);
	}

	/**
	 * @group small
	 */
	public function testLoneAddressChecksThePageTheRedirectOpens ()
	{
		$record = array ('ip' => 'abcd', 'net_id' => 5);
		$empty = array
		(
			'ip_bin' => 'abcd',
			'name' => '',
			'comment' => '',
			'reserved' => 'no',
			'allocs' => array (),
			'vslist' => array (),
			'vsglist' => array (),
			'rsplist' => array (),
			'outpf' => array (),
			'inpf' => array (),
		);
		$allocated = $empty;
		$allocated['allocs'] = array (array ('object_id' => 42));
		$seen = $this->watchLoneAddress ($record, $allocated);
		$this->assertTrue ($seen['kept']);
		$this->assertSame (array ('object'), $seen['pages']);
		$this->assertSame (array ('object'), $seen['realms']);

		$service = $empty;
		$service['vslist'] = array (1 => array ('id' => 7));
		$seen = $this->watchLoneAddress ($record, $service);
		$this->assertTrue ($seen['kept']);
		$this->assertSame (array ('ipaddress'), $seen['pages']);
		$this->assertSame (array ('network'), $seen['realms']);

		$seen = $this->watchLoneAddress ($record, $empty);
		$this->assertTrue ($seen['kept']);
		$this->assertSame (array ('ipv4net'), $seen['pages']);
		$this->assertSame (array ('ipv4net'), $seen['realms']);

		$seen = $this->watchLoneAddress ($record, $allocated, FALSE);
		$this->assertFalse ($seen['kept']);
		$this->assertSame (array ('object'), $seen['pages']);
		$this->assertSame (array ('object'), $seen['realms']);
	}

	private function watchLoneAddress ($record, $address, $allow = TRUE)
	{
		$pages = array ();
		$realms = array ();
		$entity_for = function ($realm, $cell) use (&$realms)
		{
			$realms[] = $realm;
			return array ('id' => isset ($cell['id']) ? $cell['id'] : NULL, 'realm' => $realm);
		};
		$visible = function ($page, $tab, $entity) use (&$pages, $allow)
		{
			$pages[] = $page;
			return $allow;
		};
		return array
		(
			'kept' => searchLoneAddressVisible ($record, $address, $entity_for, $visible),
			'pages' => $pages,
			'realms' => $realms,
		);
	}

	/**
	 * @group small
	 */
	public function testHiddenNeighborUsesTheRedirectPage ()
	{
		global $pageno_by_etype;
		$pageno_by_etype['object'] = 'object';
		$pages = array ();
		$filtered = filterSearchSummary (
			$this->addressBesideObject (),
			$this->redirectEntityFor (),
			function ($page, $tab, $entity) use (&$pages)
			{
				$pages[] = $page;
				return $page === 'ipv4net';
			},
			$this->preparedAllocation ()
		);
		$this->assertSame (array ('object', 'object'), $pages);
		$this->assertArrayNotHasKey ('ipv4addressbydq', $filtered);
		$this->assertArrayNotHasKey ('object', $filtered);
	}

	/**
	 * @group small
	 */
	public function testOpenObjectKeepsTheAddressWhenTheNetworkIsClosed ()
	{
		global $pageno_by_etype;
		$pageno_by_etype['object'] = 'object';
		$pages = array ();
		$summary = $this->addressBesideObject ();
		$filtered = filterSearchSummary (
			$summary,
			$this->redirectEntityFor (),
			function ($page, $tab, $entity) use (&$pages)
			{
				$pages[] = $page;
				return $page === 'object' && $entity['id'] === 42;
			},
			$this->preparedAllocation ()
		);
		$this->assertSame (array ('object', 'object'), $pages);
		$this->assertSame ($summary['ipv4addressbydq'], $filtered['ipv4addressbydq']);
		$this->assertArrayNotHasKey ('object', $filtered);
	}

	/**
	 * @group small
	 */
	public function testTwoAddressesShrinkToTheRedirect ()
	{
		$pages = array ();
		$filtered = filterSearchSummary (
			array (
				'ipv4addressbydq' => array (
					'gone' => array ('ip' => 'gone'),
					'stay' => array ('ip' => 'stay', 'net_id' => 5),
				),
			),
			$this->redirectEntityFor (),
			function ($page, $tab, $entity) use (&$pages)
			{
				$pages[] = $page;
				return $page === 'ipv4net';
			},
			$this->preparedAllocation ()
		);
		$this->assertSame (array ('ipaddress', 'ipv4net', 'object'), $pages);
		$this->assertArrayNotHasKey ('ipv4addressbydq', $filtered);
	}

	private function addressBesideObject ()
	{
		return array (
			'object' => array (
				2 => array ('id' => 2),
			),
			'ipv4addressbydq' => array (
				'addr' => array ('ip' => 'abcd', 'net_id' => 5),
			),
		);
	}

	private function redirectEntityFor ()
	{
		return function ($realm, $record)
		{
			if ($realm === 'network')
				return isset ($record['net_id'])
					? array ('id' => $record['net_id'], 'realm' => 'ipv4net')
					: NULL;
			return array ('id' => isset ($record['id']) ? $record['id'] : NULL, 'realm' => $realm);
		};
	}

	private function preparedAllocation ()
	{
		$address = array (
			'ip_bin' => 'abcd',
			'name' => '',
			'comment' => '',
			'reserved' => 'no',
			'allocs' => array (array ('object_id' => 42)),
			'vslist' => array (),
			'vsglist' => array (),
			'rsplist' => array (),
			'outpf' => array (),
			'inpf' => array (),
		);
		return function ($ip) use ($address)
		{
			return $address;
		};
	}

	/**
	 * @group small
	 */
	public function testLikePatternEscapesWildcards ()
	{
		$this->assertSame ('%a!%b!_c%', searchLikePattern ('a%b_c'));
		$this->assertSame ('%a!!b%', searchLikePattern ('a!b'));
		$this->assertSame ("name LIKE ? ESCAPE '!'", searchLikeExpr ('name'));
	}
}
