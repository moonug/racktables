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
		);
		$entity_for = function ($realm, $record)
		{
			return NULL;
		};
		$visible = function ($page, $tab, $entity)
		{
			return FALSE;
		};
		$this->assertSame (array (), filterSearchSummary ($summary, $entity_for, $visible));
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
		);
		$entity_for = function ($realm, $record)
		{
			return array ('id' => $record['net_id'], 'realm' => 'ipv4net');
		};
		$seen = NULL;
		$visible = function ($page, $tab, $entity) use (&$seen)
		{
			$seen = $page;
			return $page === 'ipv4net';
		};
		$filtered = filterSearchSummary ($summary, $entity_for, $visible);
		$this->assertSame ('ipv4net', $seen);
		$this->assertSame ($summary['ipv4addressbydq'], $filtered['ipv4addressbydq']);
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
