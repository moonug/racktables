<?php

class TagRenameTest extends RTTestCase
{
	private $object_id;
	private $parent_id;
	private $child_id;
	private $unused_id;

	public function setUp () : void
	{
		global $taglist;

		usePreparedInsertBlade ('TagTree', array ('tag' => $this->myString ('parent')));
		$this->parent_id = lastInsertID ();
		usePreparedInsertBlade ('TagTree', array (
			'tag' => $this->myString ('child'),
			'parent_id' => $this->parent_id,
		));
		$this->child_id = lastInsertID ();
		usePreparedInsertBlade ('TagTree', array ('tag' => $this->myString ('unused')));
		$this->unused_id = lastInsertID ();
		$taglist = addTraceToNodes (getTagList ());

		$this->object_id = commitAddObject ($this->myString ('server'), NULL, 4, NULL, array ($this->child_id));
	}

	public function tearDown () : void
	{
		global $sic;
		unset (
			$_REQUEST['tag_id'], $_REQUEST['tag_name'], $_REQUEST['parent_id'],
			$_REQUEST['is_assignable'], $_REQUEST['color']
		);
		unset ($sic['tag_id'], $sic['tag_name'], $sic['parent_id'], $sic['is_assignable'], $sic['color']);
		commitDeleteObject ($this->object_id);
		usePreparedDeleteBlade ('TagTree', array ('id' => $this->child_id));
		usePreparedDeleteBlade ('TagTree', array ('id' => array ($this->parent_id, $this->unused_id)));
	}

	private function postTagUpdate ($tag_id, $tag_name, $parent_id = 0, $is_assignable = 'yes', $color = '')
	{
		global $sic;
		$fields = array (
			'tag_id' => $tag_id,
			'tag_name' => $tag_name,
			'parent_id' => $parent_id,
			'is_assignable' => $is_assignable,
			'color' => $color,
		);
		foreach ($fields as $name => $value)
			$_REQUEST[$name] = $sic[$name] = (string) $value;
	}

	/**
	 * @group small
	 */
	public function testUsedTagCannotBeRenamed ()
	{
		global $taglist;
		$this->expectException (InvalidRequestArgException::class);
		$this->expectExceptionMessage ('Used tags cannot be renamed');
		assertTagRenameAllowed ($taglist, $this->child_id, $this->myString ('renamed'));
	}

	/**
	 * @group small
	 */
	public function testTagWithUsedDescendantCannotBeRenamed ()
	{
		global $taglist;
		// The guard must fire on the descendant, so the parent itself is unused.
		$usage = getTagUsage (TRUE);
		$this->assertSame (0, $usage[$this->parent_id]['refcnt']['total']);

		$this->expectException (InvalidRequestArgException::class);
		$this->expectExceptionMessage ('Tags with used descendants cannot be renamed');
		assertTagRenameAllowed ($taglist, $this->parent_id, $this->myString ('renamed'));
	}

	/**
	 * @group small
	 */
	public function testUnusedTagCanBeRenamed ()
	{
		global $taglist;
		$this->expectNotToPerformAssertions ();
		assertTagRenameAllowed ($taglist, $this->unused_id, $this->myString ('renamed'));
	}

	/**
	 * @group small
	 */
	public function testSameNameIsAllowed ()
	{
		global $taglist;

		$this->expectNotToPerformAssertions ();
		assertTagRenameAllowed ($taglist, $this->child_id, $taglist[$this->child_id]['tag']);
	}

	/**
	 * @group small
	 */
	public function testUnknownTagIsRejected ()
	{
		global $taglist;
		$this->expectException (InvalidRequestArgException::class);
		assertTagRenameAllowed ($taglist, 99999999, $this->myString ('renamed'));
	}

	/**
	 * @group small
	 */
	public function testHandlerRefusesUsedTagRename ()
	{
		global $taglist;

		$this->postTagUpdate ($this->child_id, $this->myString ('renamed'));
		$this->expectException (InvalidRequestArgException::class);
		$this->expectExceptionMessage ('Used tags cannot be renamed');
		updateTag ();
	}

	/**
	 * @group small
	 */
	public function testHandlerRenamesUnusedTag ()
	{
		global $taglist;

		$new_name = $this->myString ('renamed-unused');
		$this->postTagUpdate ($this->unused_id, $new_name);
		updateTag ();
		$taglist = addTraceToNodes (getTagList ());
		$this->assertSame ($new_name, $taglist[$this->unused_id]['tag']);
	}

	/**
	 * @group small
	 */
	public function testHandlerAllowsColorChangeOnUsedTag ()
	{
		global $taglist;

		$name = $taglist[$this->child_id]['tag'];
		$this->postTagUpdate ($this->child_id, $name, $this->parent_id, 'yes', '112233');
		updateTag ();
		$taglist = addTraceToNodes (getTagList ());
		$this->assertSame ('112233', $taglist[$this->child_id]['color']);
	}

	/**
	 * @group small
	 */
	public function testGuardUsesTheSuppliedTagList ()
	{
		global $taglist;
		usePreparedInsertBlade ('TagTree', array (
			'tag' => $this->myString ('late'),
			'parent_id' => $this->unused_id,
		));
		$late = lastInsertID ();
		addTagForEntity ('object', $this->object_id, $late);
		$snapshot = addTraceToNodes (getTagList ());
		try
		{
			$this->assertArrayNotHasKey ($late, $taglist);
			$this->expectException (InvalidRequestArgException::class);
			$this->expectExceptionMessage ('Tags with used descendants cannot be renamed');
			assertTagRenameAllowed ($snapshot, $this->unused_id, $this->myString ('renamed'));
		}
		finally
		{
			deleteTagForEntity ('object', $this->object_id, $late);
			usePreparedDeleteBlade ('TagTree', array ('id' => $late));
		}
	}

	/**
	 * @group small
	 */
	public function testUnusedDescendantDoesNotBlockRename ()
	{
		global $taglist;
		usePreparedInsertBlade ('TagTree', array (
			'tag' => $this->myString ('spare'),
			'parent_id' => $this->unused_id,
		));
		$spare = lastInsertID ();
		try
		{
			$this->expectNotToPerformAssertions ();
			assertTagRenameAllowed (addTraceToNodes (getTagList ()), $this->unused_id, $this->myString ('renamed'));
		}
		finally
		{
			usePreparedDeleteBlade ('TagTree', array ('id' => $spare));
		}
	}

	/**
	 * @group small
	 */
	public function testDescendantsSurviveACycle ()
	{
		$nodes = array (
			1 => array ('parent_id' => 3),
			2 => array ('parent_id' => 1),
			3 => array ('parent_id' => 2),
			4 => array ('parent_id' => 1),
		);
		$children = getTagDescendents (3, $nodes);
		sort ($children);
		$this->assertSame (array (1, 2, 4), $children);
	}

	/**
	 * @group small
	 */
	public function testCommitRefusesUsedTagRename ()
	{
		global $taglist;
		$name = $taglist[$this->child_id]['tag'];
		try
		{
			commitUpdateTag ($this->child_id, $this->myString ('renamed'), 0, 'yes');
			$this->fail ('a used tag was renamed');
		}
		catch (InvalidRequestArgException $e)
		{
			$this->assertStringContainsString ('Used tags cannot be renamed', $e->getMessage ());
		}
		$this->assertSame ($name, getTagList ()[$this->child_id]['tag']);
	}
}
