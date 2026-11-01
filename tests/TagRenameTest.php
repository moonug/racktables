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
		commitDeleteObject ($this->object_id);
		usePreparedDeleteBlade ('TagTree', array ('id' => $this->child_id));
		usePreparedDeleteBlade ('TagTree', array ('id' => array ($this->parent_id, $this->unused_id)));
	}

	/**
	 * @group small
	 */
	public function testUsedTagCannotBeRenamed ()
	{
		$this->expectException (InvalidRequestArgException::class);
		assertTagRenameAllowed ($this->child_id, $this->myString ('renamed'));
	}

	/**
	 * @group small
	 */
	public function testTagWithUsedDescendantCannotBeRenamed ()
	{
		$this->expectException (InvalidRequestArgException::class);
		assertTagRenameAllowed ($this->parent_id, $this->myString ('renamed'));
	}

	/**
	 * @group small
	 */
	public function testUnusedTagCanBeRenamed ()
	{
		$this->expectNotToPerformAssertions ();
		assertTagRenameAllowed ($this->unused_id, $this->myString ('renamed'));
	}

	/**
	 * @group small
	 */
	public function testSameNameIsAllowed ()
	{
		global $taglist;

		$this->expectNotToPerformAssertions ();
		assertTagRenameAllowed ($this->child_id, $taglist[$this->child_id]['tag']);
	}
}
