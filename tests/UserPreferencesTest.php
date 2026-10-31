<?php

class UserPreferencesTest extends RTTestCase
{
	/**
	 * @group small
	 */
	public function testStoresTypedText ()
	{
		global $sic, $configCache, $remote_username;
		$typed = 'Rack & "row"';
		// transformRequestData() stores the escaped form in $_REQUEST.
		$escaped = htmlspecialchars ($typed, ENT_QUOTES, 'UTF-8');
		$this->assertNotSame ($typed, $escaped);

		$prev_altered = isset ($configCache['SHOW_LAST_TAB']['is_altered'])
			&& $configCache['SHOW_LAST_TAB']['is_altered'] === 'yes';
		$prev_value = getConfigVar ('SHOW_LAST_TAB');
		$saved_request = array ();
		foreach (array ('num_vars', '0_varname', '0_varvalue') as $key)
		{
			$saved_request[$key] = array (
				'sic' => array_key_exists ($key, $sic) ? $sic[$key] : NULL,
				'had_sic' => array_key_exists ($key, $sic),
				'request' => array_key_exists ($key, $_REQUEST) ? $_REQUEST[$key] : NULL,
				'had_request' => array_key_exists ($key, $_REQUEST),
			);
		}

		$sic['num_vars'] = $_REQUEST['num_vars'] = '1';
		$sic['0_varname'] = $_REQUEST['0_varname'] = 'SHOW_LAST_TAB';
		$sic['0_varvalue'] = $_REQUEST['0_varvalue'] = $escaped;
		$level = ob_get_level ();
		ob_start ();
		try
		{
			saveMyPreferences ();
			ob_end_clean ();
			$this->assertSame ($typed, getConfigVar ('SHOW_LAST_TAB'));
			$stored = loadUserConfigCache ($remote_username);
			$this->assertSame ($typed, $stored['SHOW_LAST_TAB']['varvalue']);
		}
		finally
		{
			while (ob_get_level () > $level)
				ob_end_clean ();
			if ($prev_altered)
				setUserConfigVar ('SHOW_LAST_TAB', $prev_value);
			elseif (isset ($configCache['SHOW_LAST_TAB']['is_altered'])
				&& $configCache['SHOW_LAST_TAB']['is_altered'] === 'yes')
				resetUserConfigVar ('SHOW_LAST_TAB');
			foreach ($saved_request as $key => $saved)
			{
				if ($saved['had_sic'])
					$sic[$key] = $saved['sic'];
				else
					unset ($sic[$key]);
				if ($saved['had_request'])
					$_REQUEST[$key] = $saved['request'];
				else
					unset ($_REQUEST[$key]);
			}
		}
	}
}
