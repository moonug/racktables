<?php
# Builds scripts/init-demo-data.sql: the "ISA Net" demo inventory for
# RackTables. It never connects to a database, it only prints SQL, so the
# generated file can be reviewed like any other source file.
#
#   php scripts/make-demo-data.php > scripts/init-demo-data.sql
#
# The inventory tells a story: the network of the Interstellar Alliance,
# ten sites of which nine are operational and one (Babylon 4) is a memorial.
# Everything below is deterministic: no random numbers, no wall clock.

const ID_BASE_OBJECT = 100000;
const ID_BASE_PORT = 200000;
const ID_BASE_TAG = 9000;
const ID_BASE_VDOM = 100;
const ID_BASE_NET = 5000;

const OT_PDU = 2;
const OT_SERVER = 4;
const OT_SAN = 5;
const OT_ROUTER = 7;
const OT_SWITCH = 8;
const OT_PATCHPANEL = 9;
const OT_UPS = 12;
const OT_FW = 798;
const OT_CONSOLE = 1644;
const OT_RACK = 1560;
const OT_ROW = 1561;
const OT_LOCATION = 1562;

# Port ladder: management and OOB stay on 1G copper, server data is 25G,
# access uplinks 40G, distribution and core climb with the site size and
# inter-site links are 800G. The 200G/400G/800G pairs do not exist in the
# 0.22.1 dictionary, so the generated SQL adds them in the 10001+ range.
$speeds = array (
	'800g' => array (19, 10003),
	'400g' => array (18, 10002),
	'200g' => array (17, 10001),
	'100g' => array (15, 1670),
	'40g'  => array (10, 1663),
	'25g'  => array (16, 1653),
	'1g'   => array (1, 24),
	'serial' => array (1, 29),
	'virtual' => array (1, 31),
);

# tier: xl/l/m/s. The rack count and the number of servers per rack set the
# size of a site; the tier picks the uplink speed and the site-level gear.
$sites = array (
	array ('slug' => 'geneva', 'prefix' => 'gva', 'name' => 'Geneva HQ', 'region' => 'core',
		'sid' => 1, 'space' => 'space/milky-way/sol/earth/geneva', 'vendor' => 'earthdome-systems',
		'tier' => 'xl', 'racks' => 16, 'servers' => 4, 'contact' => 'Talia Winters'),
	array ('slug' => 'mars', 'prefix' => 'mrs', 'name' => 'Mars Ares City', 'region' => 'core',
		'sid' => 2, 'space' => 'space/milky-way/sol/mars/ares-city', 'vendor' => 'earthdome-systems',
		'tier' => 'l', 'racks' => 11, 'servers' => 3, 'contact' => 'Stephen Franklin'),
	array ('slug' => 'proxima3', 'prefix' => 'px3', 'name' => 'Proxima III New Crete', 'region' => 'frontier',
		'sid' => 3, 'space' => 'space/milky-way/proxima-centauri/proxima-iii/new-crete', 'vendor' => 'drazi-freehold',
		'tier' => 'm', 'racks' => 7, 'servers' => 2, 'contact' => 'Vyvyan'),
	array ('slug' => 'orion7', 'prefix' => 'or7', 'name' => 'Orion VII Outpost', 'region' => 'frontier',
		'sid' => 4, 'space' => 'space/milky-way/orion/orion-vii/orion-outpost', 'vendor' => 'earthdome-systems',
		'tier' => 'm', 'racks' => 7, 'servers' => 2, 'contact' => 'Tau'),
	array ('slug' => 'babylon5', 'prefix' => 'b5', 'name' => 'Babylon 5 Station', 'region' => 'frontier',
		'sid' => 5, 'space' => 'space/milky-way/epsilon-eridani/babylon-5', 'vendor' => 'earthdome-systems',
		'tier' => 'xl', 'racks' => 16, 'servers' => 4, 'contact' => 'Susan Ivanova'),
	array ('slug' => 'epsilon3', 'prefix' => 'e3', 'name' => 'Epsilon III Great Machine', 'region' => 'frontier',
		'sid' => 6, 'space' => 'space/milky-way/epsilon-eridani/epsilon-iii/great-machine', 'vendor' => 'minbari-optics',
		'tier' => 's', 'racks' => 3, 'servers' => 1, 'contact' => 'Draal'),
	array ('slug' => 'minbar', 'prefix' => 'mbr', 'name' => 'Minbar Yshalla', 'region' => 'allied',
		'sid' => 7, 'space' => 'space/milky-way/minbar/minbar-home/yshalla', 'vendor' => 'minbari-optics',
		'tier' => 'l', 'racks' => 11, 'servers' => 3, 'contact' => 'Delenn'),
	array ('slug' => 'narn', 'prefix' => 'nrn', 'name' => 'Narn Homeworld', 'region' => 'allied',
		'sid' => 8, 'space' => 'space/milky-way/narn/narn-home/narn-outpost', 'vendor' => 'narn-industries',
		'tier' => 'm', 'racks' => 7, 'servers' => 2, 'contact' => "G'Kar"),
	array ('slug' => 'centauri', 'prefix' => 'ctr', 'name' => 'Centauri Prime', 'region' => 'core',
		'sid' => 9, 'space' => 'space/milky-way/centauri/centauri-prime/imperial-network', 'vendor' => 'centauri-royal-works',
		'tier' => 'l', 'racks' => 11, 'servers' => 3, 'contact' => 'Londo Mollari'),
	array ('slug' => 'babylon4', 'prefix' => 'b4', 'name' => 'Babylon 4 (lost)', 'region' => 'lost',
		'sid' => 10, 'space' => 'space/lost/hyperspace/babylon-4', 'vendor' => 'earthdome-systems',
		'tier' => 's', 'racks' => 3, 'servers' => 1, 'contact' => 'Jeffrey Sinclair'),
);

$tiers = array (
	'xl' => array ('cores' => 2, 'dists' => 4, 'fws' => 2, 'lbs' => 2, 'consoles' => 2, 'sans' => 1,
		'dist_core' => '400g', 'router_core' => '400g'),
	'l' => array ('cores' => 2, 'dists' => 3, 'fws' => 1, 'lbs' => 1, 'consoles' => 1, 'sans' => 1,
		'dist_core' => '200g', 'router_core' => '200g'),
	'm' => array ('cores' => 2, 'dists' => 2, 'fws' => 1, 'lbs' => 0, 'consoles' => 1, 'sans' => 0,
		'dist_core' => '100g', 'router_core' => '100g'),
	's' => array ('cores' => 1, 'dists' => 1, 'fws' => 1, 'lbs' => 0, 'consoles' => 1, 'sans' => 0,
		'dist_core' => '100g', 'router_core' => '100g'),
);

$regions = array (
	'core' => 'Earth Alliance Core',
	'frontier' => 'Frontier Region',
	'allied' => 'Allied Worlds',
	'lost' => 'Lost Expeditions',
);

$vendor_models = array (
	'earthdome-systems' => array ('router' => 'orion-router', 'switch' => 'agamemnon-switch',
		'server' => 'hyperion-server', 'fw' => 'starfury-fw', 'san' => 'starlight-san'),
	'minbari-optics' => array ('router' => 'sharlin-router', 'switch' => 'whitestar-switch',
		'server' => 'sharlin-server', 'fw' => 'nial-fw', 'san' => 'minbari-starlight-san'),
	'narn-industries' => array ('router' => 'tloth-router', 'switch' => 'gquan-switch',
		'server' => 'tloth-server', 'fw' => 'gquan-fw', 'san' => 'narn-hurr-storage'),
	'centauri-royal-works' => array ('router' => 'primus-router', 'switch' => 'vorchan-switch',
		'server' => 'primus-server', 'fw' => 'vorchan-fw', 'san' => 'centauri-hurr-storage'),
	'drazi-freehold' => array ('router' => 'sunhawk-router', 'switch' => 'sunhawk-switch',
		'server' => 'sunhawk-server', 'fw' => 'sunhawk-fw', 'san' => 'drazi-hurr-storage'),
);

$service_tags = array ('tachyon-relay', 'jumpgate-control', 'station-services', 'monitoring', 'storage', 'backup');

# ---------------------------------------------------------------------------
# State and small helpers.

$sql = '';
$next_object = ID_BASE_OBJECT;
$next_port = ID_BASE_PORT;
$next_tag = ID_BASE_TAG;
$asset_seq = 0;
$cable_seq = 0;

$objects = array ();      # id => record
$ports = array ();        # id => record
$port_index = array ();   # "object:name" => port id
$links = array ();        # list of [porta, portb, cable]
$attrs = array ();        # list of [object_id, tid, attr_id, value]
$entity_links = array (); # list of [parent_type, parent_id, child_type, child_id]
$rack_space = array ();   # list of [rack_id, unit, atom, object_id]
$tag_storage = array ();  # list of [realm, entity_id, tag id]
$tag_rows = array ();     # tag id => [parent, assignable, name]
$tag_ids = array ();      # path => tag id

$networks4 = array ();    # list of [id, ip(int), mask, name]
$networks6 = array ();    # list of [id, ip(bin), mask, last(bin), name]
$vlan_ipv4 = array ();
$vlan_ipv6 = array ();
$alloc4 = array ();       # list of [object_id, ip(int), name, type]
$alloc6 = array ();       # list of [object_id, ip(bin), name, type]

function out ($line)
{
	global $sql;
	$sql .= $line . "\n";
}

function q ($value)
{
	return "'" . str_replace (
		array ('\\', "'", "\n", "\r", "\t"),
		array ('\\\\', "\\'", '\\n', '\\r', '\\t'),
		(string) $value
	) . "'";
}

function emit_insert ($table, $columns, $rows, $chunk = 200)
{
	if (! count ($rows))
		return;
	$collist = '`' . implode ('`, `', $columns) . '`';
	foreach (array_chunk ($rows, $chunk) as $part)
	{
		out ("INSERT INTO `$table` ($collist) VALUES");
		out ('  ' . implode (",\n  ", $part) . ';');
		out ('');
	}
}

function tag_id ($path)
{
	global $tag_ids, $tag_rows, $next_tag;
	if (isset ($tag_ids[$path]))
		return $tag_ids[$path];
	$parts = explode ('/', $path);
	$parent = NULL;
	$prefix = '';
	foreach ($parts as $name)
	{
		$prefix = $prefix === '' ? $name : $prefix . '/' . $name;
		if (isset ($tag_ids[$prefix]))
		{
			$parent = $tag_ids[$prefix];
			continue;
		}
		$id = $next_tag++;
		$tag_rows[$id] = array ($parent, 'no', $name);
		$tag_ids[$prefix] = $id;
		$parent = $id;
	}
	# The leaf is the only assignable node of a path.
	$tag_rows[$tag_ids[$path]][1] = 'yes';
	return $tag_ids[$path];
}

function add_tag ($realm, $entity_id, $path)
{
	global $tag_storage;
	$tag_storage[] = array ($realm, $entity_id, tag_id ($path));
}

function realm_of ($type)
{
	switch ($type)
	{
		case OT_RACK: return 'rack';
		case OT_ROW: return 'row';
		case OT_LOCATION: return 'location';
		default: return 'object';
	}
}

function add_object ($site, $name, $label, $type, $comment, $tags, $problems = 'no')
{
	global $objects, $next_object, $asset_seq;
	$id = $next_object++;
	$asset_seq++;
	$asset = in_array ($type, array (OT_LOCATION, OT_ROW)) ? NULL
		: sprintf ('ISA-%s-%04d', strtoupper ($site['prefix']), $asset_seq);
	$objects[$id] = array (
		'id' => $id, 'name' => $name, 'label' => $label, 'type' => $type,
		'asset' => $asset, 'comment' => $comment, 'problems' => $problems,
		'site' => $site['slug'], 'sid' => $site['sid'],
	);
	if (in_array ($site['slug'], array ('epsilon3', 'babylon4')) && $type != OT_ROW)
		$tags[] = 'security-class/restricted';
	foreach ($tags as $path)
		if ($path !== NULL)
			add_tag (realm_of ($type), $id, $path);
	return $id;
}

function add_port ($object_id, $name, $speed)
{
	global $ports, $next_port, $port_index, $speeds;
	list ($iif, $oif) = $speeds[$speed];
	$id = $next_port++;
	$ports[$id] = array ('id' => $id, 'object_id' => $object_id, 'name' => $name,
		'iif' => $iif, 'oif' => $oif, 'l2' => NULL);
	$port_index[$object_id . ':' . $name] = $id;
	return $id;
}

function port_of ($object_id, $name)
{
	global $port_index;
	if (! isset ($port_index[$object_id . ':' . $name]))
		throw new RuntimeException ("no port ${name} on object ${object_id}");
	return $port_index[$object_id . ':' . $name];
}

function add_link ($a_object, $a_port, $b_object, $b_port)
{
	global $links, $cable_seq;
	$cable_seq++;
	$links[] = array (port_of ($a_object, $a_port), port_of ($b_object, $b_port),
		sprintf ('C-%04d', $cable_seq));
}

# Put a device into the next free units of a rack. Servers use units 37-40,
# the access switch 41 and the patch panel 42, so network gear grows down
# from unit 36; the row UPS keeps units 1-2.
function mount_device (&$cursor, $rack_id, $units, $object_id)
{
	global $rack_space;
	if (! isset ($cursor[$rack_id]))
		$cursor[$rack_id] = 36;
	$bottom = $cursor[$rack_id] - $units + 1;
	if ($bottom < 3)
		throw new RuntimeException ("rack ${rack_id} has no room for object ${object_id}");
	for ($unit = $cursor[$rack_id]; $unit >= $bottom; $unit--)
		$rack_space[] = array ($rack_id, $unit, 'front', $object_id);
	$cursor[$rack_id] = $bottom - 1;
}

function ip4_int ($dotted)
{
	return ip2long ($dotted);
}

# MySQL hex literal for either an IPv4 or an IPv6 address.
function ip_hex ($dotted)
{
	return '0x' . strtoupper (bin2hex (inet_pton ($dotted)));
}

function ip6_last ($dotted, $mask)
{
	$bits = inet_pton ($dotted);
	for ($i = $mask; $i < 128; $i++)
	{
		$byte = intdiv ($i, 8);
		$bits[$byte] = chr (ord ($bits[$byte]) & ~(1 << (7 - ($i % 8))));
	}
	for ($i = $mask; $i < 128; $i++)
	{
		$byte = intdiv ($i, 8);
		$bits[$byte] = chr (ord ($bits[$byte]) | (1 << (7 - ($i % 8))));
	}
	return '0x' . strtoupper (bin2hex ($bits));
}

function add_network4 ($dotted, $mask, $name)
{
	global $networks4;
	$id = ID_BASE_NET + count ($networks4);
	$networks4[] = array ($id, ip4_int ($dotted), $mask, $name);
	return $id;
}

function add_network6 ($dotted, $mask, $name)
{
	global $networks6;
	$id = ID_BASE_NET + count ($networks6);
	$networks6[] = array ($id, ip_hex ($dotted), $mask, ip6_last ($dotted, $mask), $name);
	return $id;
}

function add_alloc4 ($object_id, $dotted, $name, $type = 'regular')
{
	global $alloc4;
	$alloc4[] = array ($object_id, ip4_int ($dotted), $name, $type);
}

function add_alloc6 ($object_id, $dotted, $name, $type = 'regular')
{
	global $alloc6;
	$alloc6[] = array ($object_id, ip_hex ($dotted), $name, $type);
}

# ---------------------------------------------------------------------------
# Locations, rows, racks and devices.

$root = add_object (array ('slug' => 'isa', 'prefix' => 'isa', 'sid' => 0), 'ISA Net', 'ISA',
	OT_LOCATION, 'Root of the ISA Net location tree', array (NULL));

$region_ids = array ();
foreach ($regions as $key => $name)
{
	$id = add_object (array ('slug' => 'isa', 'prefix' => 'isa', 'sid' => 0), $name, strtoupper ($key),
		OT_LOCATION, 'Region container', array (NULL));
	$region_ids[$key] = $id;
	$entity_links[] = array ('location', $root, 'location', $id);
}

$site_devices = array ();

foreach ($sites as $index => $site)
{
	$tier = $tiers[$site['tier']];
	$domain_id = ID_BASE_VDOM + $index;
	$live = $site['slug'] != 'babylon4';
	$problems = $live ? 'no' : 'yes';
	$vendor = $site['vendor'];
	$models = $vendor_models[$vendor];
	$space = $site['space'];

	$location = add_object ($site, $site['name'], strtoupper ($site['prefix']), OT_LOCATION,
		'Site of the ISA Net backbone', array ($space, 'env/prod'), $problems);
	$entity_links[] = array ('location', $region_ids[$site['region']], 'location', $location);
	$attrs[] = array ($location, OT_LOCATION, 14, $site['contact']);

	$racks = array ();
	$rows = array ();
	$row_racks = array ();
	$row_count = (int) ceil ($site['racks'] / 4);
	$rack_no = 0;
	for ($r = 0; $r < $row_count; $r++)
	{
		$letter = chr (ord ('A') + $r);
		$take = min (4, $site['racks'] - $rack_no);
		$row = add_object ($site, strtoupper ($site['prefix']) . " Row ${letter}",
			'SITE' . $letter, OT_ROW, "Aisle ${letter} of the ${site['name']} data hall",
			array (NULL));
		$rows[$letter] = $row;
		$row_racks[$letter] = array ();
		$entity_links[] = array ('location', $location, 'row', $row);
		for ($i = 0; $i < $take; $i++)
		{
			$rack_no++;
			$rack = add_object ($site, sprintf ('%s-R%02d', strtoupper ($site['prefix']), $rack_no),
				sprintf ('RACK-%02d', $rack_no), OT_RACK,
				"42U rack in row ${letter}", array ($space, 'env/prod'), $problems);
			$racks[$rack_no] = $rack;
			$row_racks[$letter][] = $rack_no;
			$entity_links[] = array ('row', $row, 'rack', $rack);
			$attrs[] = array ($rack, OT_RACK, 27, 42);
			$attrs[] = array ($rack, OT_RACK, 29, $rack_no);
		}
	}

	$first_row_letter = chr (ord ('A'));

	# Site-level gear is mounted like any other 1U/2U device. The racks take
	# it in turn so one rack does not hold the whole core.
	$net_cursor = array ();
	$gear_racks = array_values ($racks);
	$gear_index = 0;
	$place = function ($units, $object_id) use (&$net_cursor, $gear_racks, &$gear_index)
	{
		$rack_id = $gear_racks[$gear_index++ % count ($gear_racks)];
		mount_device ($net_cursor, $rack_id, $units, $object_id);
	};
	$place_in = function ($rack_id, $units, $object_id) use (&$net_cursor)
	{
		mount_device ($net_cursor, $rack_id, $units, $object_id);
	};

	$routers = array ();
	for ($i = 1; $i <= 2; $i++)
	{
		$id = add_object ($site, sprintf ('%s-rtr-%02d', $site['prefix'], $i),
			sprintf ('%s-RTR-%02d', strtoupper ($site['prefix']), $i), OT_ROUTER,
			'Backbone router, inter-site 800G and intra-site ' . strtoupper ($tier['router_core']),
			array ($space, 'env/prod', 'hw/' . $vendor . '/' . $models['router'], 'automation/managed'),
			$problems);
		$routers[] = $id;
		$attrs[] = array ($id, OT_ROUTER, 3, sprintf ('%s-rtr-%02d.%s.isa-net.example.com', $site['prefix'], $i, $site['slug']));
		$attrs[] = array ($id, OT_ROUTER, 1, sprintf ('SN-%s-R%02d', strtoupper ($site['prefix']), $i));
		for ($p = 0; $p < 4; $p++)
			add_port ($id, 'Hu0/0/' . $p, '800g');
		for ($p = 0; $p < 8; $p++)
			add_port ($id, 'Hu0/1/' . $p, $tier['router_core']);
		for ($p = 0; $p < 4; $p++)
			add_port ($id, 'Te0/2/' . $p, '100g');
		add_port ($id, 'lo0', 'virtual');
		add_port ($id, 'mgmt0', '1g');
		$place (2, $id);
	}

	$cores = array ();
	for ($i = 1; $i <= $tier['cores']; $i++)
	{
		$id = add_object ($site, sprintf ('%s-core-%02d', $site['prefix'], $i),
			sprintf ('%s-CORE-%02d', strtoupper ($site['prefix']), $i), OT_SWITCH,
			'Core switch, ' . strtoupper ($tier['dist_core']) . ' fabric',
			array ($space, 'env/prod', 'hw/' . $vendor . '/' . $models['switch'], 'automation/managed'),
			$problems);
		$cores[] = $id;
		$attrs[] = array ($id, OT_SWITCH, 3, sprintf ('%s-core-%02d.%s.isa-net.example.com', $site['prefix'], $i, $site['slug']));
		$attrs[] = array ($id, OT_SWITCH, 1, sprintf ('SN-%s-C%02d', strtoupper ($site['prefix']), $i));
		for ($p = 0; $p < 16; $p++)
			add_port ($id, 'Hu1/1/' . $p, $tier['dist_core']);
		for ($p = 0; $p < 8; $p++)
			add_port ($id, 'Te1/2/' . $p, '100g');
		$site_vlans = in_array ($site['tier'], array ('xl', 'l')) ? array (100, 200, 300, 500) : array (100, 200, 300);
		foreach ($site_vlans as $vlan)
			add_port ($id, 'Vlan' . $vlan, 'virtual');
		add_port ($id, 'mgmt0', '1g');
		$place (2, $id);
	}

	$dists = array ();
	for ($i = 1; $i <= $tier['dists']; $i++)
	{
		$id = add_object ($site, sprintf ('%s-dist-%02d', $site['prefix'], $i),
			sprintf ('%s-DIST-%02d', strtoupper ($site['prefix']), $i), OT_SWITCH,
			'Distribution switch, 40G to access',
			array ($space, 'env/prod', 'hw/' . $vendor . '/' . $models['switch'], 'automation/managed'),
			$problems);
		$dists[] = $id;
		$attrs[] = array ($id, OT_SWITCH, 3, sprintf ('%s-dist-%02d.%s.isa-net.example.com', $site['prefix'], $i, $site['slug']));
		$attrs[] = array ($id, OT_SWITCH, 1, sprintf ('SN-%s-D%02d', strtoupper ($site['prefix']), $i));
		for ($p = 0; $p < 4; $p++)
			add_port ($id, 'Hu1/1/' . $p, $tier['dist_core']);
		for ($p = 1; $p <= 24; $p++)
			add_port ($id, 'Fo1/0/' . $p, '40g');
		add_port ($id, 'mgmt0', '1g');
		$place (1, $id);
	}

	$fws = array ();
	for ($i = 1; $i <= $tier['fws']; $i++)
	{
		$id = add_object ($site, sprintf ('%s-fw-%02d', $site['prefix'], $i),
			sprintf ('%s-FW-%02d', strtoupper ($site['prefix']), $i), OT_FW,
			'Network security cluster member',
			array ($space, 'env/prod', 'hw/' . $vendor . '/' . $models['fw'], 'automation/managed'),
			$problems);
		$fws[] = $id;
		$attrs[] = array ($id, OT_FW, 3, sprintf ('%s-fw-%02d.%s.isa-net.example.com', $site['prefix'], $i, $site['slug']));
		for ($p = 0; $p < 8; $p++)
			add_port ($id, 'Te1/1/' . $p, '100g');
		for ($p = 1; $p <= 4; $p++)
			add_port ($id, 'Gi1/0/' . $p, '1g');
		add_port ($id, 'mgmt0', '1g');
		$place (1, $id);
	}

	$lbs = array ();
	for ($i = 1; $i <= $tier['lbs']; $i++)
	{
		$id = add_object ($site, sprintf ('%s-lb-%02d', $site['prefix'], $i),
			sprintf ('%s-LB-%02d', strtoupper ($site['prefix']), $i), OT_SERVER,
			'Load balancer pair member',
			array ($space, 'env/prod', 'hw/isa-standard/valen-lb', 'svc/load balancer', 'automation/managed'),
			$problems);
		$lbs[] = $id;
		$attrs[] = array ($id, OT_SERVER, 3, sprintf ('%s-lb-%02d.%s.isa-net.example.com', $site['prefix'], $i, $site['slug']));
		$attrs[] = array ($id, OT_SERVER, 1, sprintf ('SN-%s-L%02d', strtoupper ($site['prefix']), $i));
		add_port ($id, 'eth0', '25g');
		add_port ($id, 'eth1', '25g');
		add_port ($id, 'ipmi0', '1g');
		$place (1, $id);
	}

	$consoles = array ();
	for ($i = 1; $i <= $tier['consoles']; $i++)
	{
		$id = add_object ($site, sprintf ('%s-cons-%02d', $site['prefix'], $i),
			sprintf ('%s-CONS-%02d', strtoupper ($site['prefix']), $i), OT_CONSOLE,
			'Serial console server for out-of-band access',
			array ($space, 'env/prod', 'hw/isa-standard/whitestar-console', 'automation/managed'),
			$problems);
		$consoles[] = $id;
		$attrs[] = array ($id, OT_CONSOLE, 3, sprintf ('%s-cons-%02d.%s.isa-net.example.com', $site['prefix'], $i, $site['slug']));
		for ($p = 0; $p < 16; $p++)
			add_port ($id, 'serial' . $p, 'serial');
		add_port ($id, 'eth0', '1g');
		add_port ($id, 'mgmt0', '1g');
		$place (1, $id);
	}

	$sans = array ();
	for ($i = 1; $i <= $tier['sans']; $i++)
	{
		$id = add_object ($site, sprintf ('%s-san-%02d', $site['prefix'], $i),
			sprintf ('%s-SAN-%02d', strtoupper ($site['prefix']), $i), OT_SAN,
			'Storage array for station services',
			array ($space, 'env/prod', 'hw/pakmara-systems/hurr-storage', 'svc/storage'),
			$problems);
		$sans[] = $id;
		for ($p = 0; $p < 4; $p++)
			add_port ($id, 'eth' . $p, '25g');
		add_port ($id, 'ipmi0', '1g');
		$place (2, $id);
	}

	# Rows: one OOB switch and one row UPS, plus the access switch and the
	# server population of every rack.
	$oob = array ();
	$access = array ();
	$servers = array ();
	$switches = array ();
	foreach ($rows as $letter => $row)
	{
		$oob_id = add_object ($site, sprintf ('%s-oob-%s', $site['prefix'], strtolower ($letter)),
			sprintf ('%s-OOB-%s', strtoupper ($site['prefix']), $letter), OT_SWITCH,
			'Out-of-band management switch for row ' . $letter,
			array ($space, 'env/prod', 'hw/' . $vendor . '/' . $models['switch'], 'svc/monitoring', 'automation/managed'),
			$problems);
		$oob[$letter] = $oob_id;
		$attrs[] = array ($oob_id, OT_SWITCH, 3, sprintf ('%s-oob-%s.%s.isa-net.example.com', $site['prefix'], strtolower ($letter), $site['slug']));
		for ($p = 1; $p <= 24; $p++)
			add_port ($oob_id, 'Gi1/0/' . $p, '1g');
		add_port ($oob_id, 'Te1/1/1', '40g');
		add_port ($oob_id, 'mgmt0', '1g');
		$place_in ($racks[$row_racks[$letter][0]], 1, $oob_id);

		$ups_id = add_object ($site, sprintf ('%s-ups-%s', $site['prefix'], strtolower ($letter)),
			sprintf ('%s-UPS-%s', strtoupper ($site['prefix']), $letter), OT_UPS,
			'Row-level UPS', array ($space, 'env/prod'), $problems);
		$rack_space[] = array ($racks[$row_racks[$letter][0]], 1, 'front', $ups_id);
		$rack_space[] = array ($racks[$row_racks[$letter][0]], 2, 'front', $ups_id);

		foreach ($row_racks[$letter] as $rack_number)
		{
			$rack_id = $racks[$rack_number];
			$access_id = add_object ($site, sprintf ('%s-acc-%02d', $site['prefix'], $rack_number),
				sprintf ('%s-ACC-%02d', strtoupper ($site['prefix']), $rack_number), OT_SWITCH,
				'Rack access switch, 25G to servers and 40G uplinks',
				array ($space, 'env/prod', 'hw/' . $vendor . '/' . $models['switch'], 'automation/managed'),
				$problems);
			$access[$rack_number] = $access_id;
			$attrs[] = array ($access_id, OT_SWITCH, 3, sprintf ('%s-acc-%02d.%s.isa-net.example.com', $site['prefix'], $rack_number, $site['slug']));
			for ($p = 1; $p <= 4; $p++)
				add_port ($access_id, 'Te1/1/' . $p, '40g');
			for ($p = 1; $p <= 24; $p++)
				add_port ($access_id, 'Te1/0/' . $p, '25g');
			for ($p = 1; $p <= 4; $p++)
				add_port ($access_id, 'Gi1/0/' . $p, '1g');
			add_port ($access_id, 'mgmt0', '1g');
			$rack_space[] = array ($rack_id, 41, 'front', $access_id);
			$switches[] = array ('id' => $access_id, 'role' => 'access', 'rack' => $rack_number);

			$servers[$rack_number] = array ();
			for ($k = 1; $k <= $site['servers']; $k++)
			{
				$srv = add_object ($site, sprintf ('%s-srv-%02d-%02d', $site['prefix'], $rack_number, $k),
					sprintf ('%s-SRV-%02d-%02d', strtoupper ($site['prefix']), $rack_number, $k), OT_SERVER,
					'Application server',
					array ($space, $k == 1 && $index == 0 ? 'env/pilot' : 'env/prod',
						'hw/' . $vendor . '/' . $models['server'],
						'svc/' . $service_tags[($rack_number + $k) % count ($service_tags)]),
					$problems);
				$servers[$rack_number][] = $srv;
				$attrs[] = array ($srv, OT_SERVER, 3, sprintf ('%s-srv-%02d-%02d.%s.isa-net.example.com', $site['prefix'], $rack_number, $k, $site['slug']));
				$attrs[] = array ($srv, OT_SERVER, 1, sprintf ('SN-%s-S%02d%02d', strtoupper ($site['prefix']), $rack_number, $k));
				add_port ($srv, 'eth0', '25g');
				add_port ($srv, 'ipmi0', '1g');
				$rack_space[] = array ($rack_id, 40 - ($k - 1), 'front', $srv);
			}

			for ($p = 1; $p <= 2; $p++)
			{
				$pdu = add_object ($site, sprintf ('%s-pdu-%02d-%d', $site['prefix'], $rack_number, $p),
					sprintf ('%s-PDU-%02d-%d', strtoupper ($site['prefix']), $rack_number, $p), OT_PDU,
					'Rack PDU, zero-U mounted', array ($space, 'env/prod'), $problems);
				$entity_links[] = array ('rack', $rack_id, 'object', $pdu);
			}

			if ($site['tier'] != 's')
			{
				$pp = add_object ($site, sprintf ('%s-pp-%02d', $site['prefix'], $rack_number),
					sprintf ('%s-PP-%02d', strtoupper ($site['prefix']), $rack_number), OT_PATCHPANEL,
					'24-port copper patch panel', array ($space, 'env/prod'), $problems);
				for ($p = 1; $p <= 24; $p++)
					add_port ($pp, (string) $p, '1g');
				$rack_space[] = array ($rack_id, 42, 'rear', $pp);
			}
		}
	}

	foreach ($cores as $core)
		$switches[] = array ('id' => $core, 'role' => 'core');
	foreach ($dists as $dist)
		$switches[] = array ('id' => $dist, 'role' => 'dist');

	$site_devices[$site['slug']] = array (
		'config' => $site, 'tier' => $tier, 'live' => $live, 'domain' => $domain_id,
		'location' => $location, 'racks' => $racks, 'rows' => $rows, 'row_racks' => $row_racks,
		'routers' => $routers, 'cores' => $cores, 'dists' => $dists, 'fws' => $fws,
		'lbs' => $lbs, 'consoles' => $consoles, 'sans' => $sans, 'oob' => $oob,
		'access' => $access, 'servers' => $servers, 'switches' => $switches,
		'first_row' => $first_row_letter,
	);
}

# ---------------------------------------------------------------------------
# Cabling. One pass per site after every device exists, so both ends of a
# link are known.

$server_ip6 = array ();
$infra_links = array ();   # routed links that get a point-to-point /126

$dist_port_seq = array ();
$dist_port = function ($dist_id) use (&$dist_port_seq)
{
	if (! isset ($dist_port_seq[$dist_id]))
		$dist_port_seq[$dist_id] = 1;
	return 'Fo1/0/' . $dist_port_seq[$dist_id]++;
};

foreach ($site_devices as $slug => &$site)
{
	$live = $site['live'];
	$cores = $site['cores'];
	$dists = $site['dists'];
	$nd = count ($dists);
	$nc = count ($cores);
	$routers = $site['routers'];

	# Routers to each other and to the core.
	if (count ($routers) > 1)
	{
		add_link ($routers[0], 'Hu0/1/0', $routers[1], 'Hu0/1/0');
		$infra_links[$slug][] = array ($routers[0], 'Hu0/1/0', $routers[1], 'Hu0/1/0');
	}
	foreach ($cores as $ci => $core)
	{
		add_link ($routers[0], 'Hu0/1/' . (1 + $ci), $core, 'Hu1/1/0');
		$infra_links[$slug][] = array ($routers[0], 'Hu0/1/' . (1 + $ci), $core, 'Hu1/1/0');
		if (count ($routers) > 1)
		{
			add_link ($routers[1], 'Hu0/1/' . (1 + $ci), $core, 'Hu1/1/1');
			$infra_links[$slug][] = array ($routers[1], 'Hu0/1/' . (1 + $ci), $core, 'Hu1/1/1');
		}
	}

	# Core pair.
	if ($nc > 1)
		add_link ($cores[0], 'Hu1/1/2', $cores[1], 'Hu1/1/2');

	# Distribution uplinks: each distribution switch reaches every core.
	$core_hu = 3;
	foreach ($dists as $dist)
	{
		foreach ($cores as $core)
			add_link ($dist, 'Hu1/1/' . array_search ($core, $cores), $core, 'Hu1/1/' . $core_hu++);
	}

	# Firewalls and consoles hang off the first 100G ports of the core pair.
	foreach ($site['fws'] as $fi => $fw)
	{
		add_link ($fw, 'Te1/1/0', $cores[0], 'Te1/2/' . (2 * $fi));
		$infra_links[$slug][] = array ($fw, 'Te1/1/0', $cores[0], 'Te1/2/' . (2 * $fi));
		if ($nc > 1)
		{
			add_link ($fw, 'Te1/1/1', $cores[1], 'Te1/2/' . (2 * $fi));
			$infra_links[$slug][] = array ($fw, 'Te1/1/1', $cores[1], 'Te1/2/' . (2 * $fi));
		}
	}
	foreach ($site['consoles'] as $ci => $console)
		add_link ($console, 'eth0', $site['oob'][$site['first_row']], 'Gi1/0/' . (23 + $ci));

	# Load balancers sit next to the access layer in the first two racks.
	foreach ($site['lbs'] as $li => $lb)
	{
		$rack_number = 2 * $li + 1;
		add_link ($lb, 'eth0', $site['access'][$rack_number], 'Te1/0/23');
		add_link ($lb, 'eth1', $site['access'][$rack_number + 1], 'Te1/0/23');
	}

	# OOB switches, access uplinks, server data and IPMI.
	$access_seq = 0;
	foreach ($site['rows'] as $letter => $row_id)
	{
		$oob_id = $site['oob'][$letter];
		add_link ($oob_id, 'Te1/1/1', $dists[$access_seq % $nd], $dist_port ($dists[$access_seq % $nd]));
		$oob_port = 1;
		foreach ($site['row_racks'][$letter] as $rack_number)
		{
			$access = $site['access'][$rack_number];
			$d1 = $dists[$access_seq % $nd];
			$d2 = $dists[($access_seq + 1) % $nd];
			add_link ($access, 'Te1/1/1', $d1, $dist_port ($d1));
			add_link ($access, 'Te1/1/2', $d2, $dist_port ($d2));
			$access_seq++;

			add_link ($access, 'mgmt0', $oob_id, 'Gi1/0/' . $oob_port++);
			foreach ($site['servers'][$rack_number] as $k => $srv)
			{
				add_link ($srv, 'eth0', $access, 'Te1/0/' . ($k + 1));
				add_link ($srv, 'ipmi0', $oob_id, 'Gi1/0/' . $oob_port++);
			}
		}
	}
}
unset ($site);

# Inter-site ring: one 800G link per adjacent pair of living sites, plus a
# redundant Geneva-Babylon 5 shortcut.
$ring = array ('geneva', 'mars', 'proxima3', 'orion7', 'babylon5', 'epsilon3', 'minbar', 'narn', 'centauri');
$ring_count = count ($ring);
for ($i = 0; $i < $ring_count; $i++)
{
	$a = $site_devices[$ring[$i]];
	$b = $site_devices[$ring[($i + 1) % $ring_count]];
	# Each router leaves on Hu0/0/0 and receives on Hu0/0/1, so a transit
	# router never terminates two cables on one port.
	add_link ($a['routers'][0], 'Hu0/0/0', $b['routers'][0], 'Hu0/0/1');
}
add_link ($site_devices['geneva']['routers'][1], 'Hu0/0/0', $site_devices['babylon5']['routers'][1], 'Hu0/0/0');

# Every ring link also has a routed point-to-point subnet, IPv6 first and a
# pair of legacy IPv4 /30s for the oldest routers.
for ($i = 0; $i < $ring_count; $i++)
{
	$a = $site_devices[$ring[$i]]['routers'][0];
	$b = $site_devices[$ring[($i + 1) % $ring_count]]['routers'][0];
	add_alloc6 ($a, sprintf ('fd00:ff00:%04x::1', $i + 1), 'Hu0/0/0', 'point2point');
	add_alloc6 ($b, sprintf ('fd00:ff00:%04x::2', $i + 1), 'Hu0/0/1', 'point2point');
	if ($i < 2)
	{
		add_alloc4 ($a, sprintf ('10.255.%d.1', $i + 1), 'Hu0/0/0', 'point2point');
		add_alloc4 ($b, sprintf ('10.255.%d.2', $i + 1), 'Hu0/0/1', 'point2point');
	}
}

# ---------------------------------------------------------------------------
# VLAN domains, VLANs, switches, desired and cached 802.1Q configuration.

$vlan_domains = array ();
$vlan_descriptions = array ();
$vlan_switches = array ();
$pvm = array ();         # list of [object_id, port_name, mode]
$pav = array ();         # list of [object_id, port_name, vlan]
$pnv = array ();         # list of [object_id, port_name, vlan]
$cached_pvm = array ();
$cached_pav = array ();
$cached_pnv = array ();
$vlan_plan = array ();

$template_of = array ('access' => 10, 'dist' => 11, 'core' => 12);

foreach ($site_devices as $slug => &$site)
{
	if (! $site['live'])
		continue;
	$domain = $site['domain'];
	$tier_name = $site['config']['tier'];
	$vlan_plan[$slug] = array (100 => 'mgmt', 200 => 'prod', 300 => 'storage', 400 => 'backup');
	if (in_array ($tier_name, array ('xl', 'l')))
		$vlan_plan[$slug][500] = 'oob';
	if ($tier_name == 'xl')
	{
		$vlan_plan[$slug][210] = 'prod-station';
		$vlan_plan[$slug][220] = 'prod-services';
	}
	ksort ($vlan_plan[$slug]);

	$vlan_domains[] = array ($domain, $slug);
	foreach ($vlan_plan[$slug] as $vlan => $label)
		$vlan_descriptions[] = array ($domain, $vlan,
			in_array ($vlan, array (100, 200)) ? 'compulsory' : 'ondemand',
			sprintf ('%s %s', $slug, $label));

	$sync_index = 0;
	foreach ($site['switches'] as $switch)
	{
		$switch_id = $switch['id'];
		$role = $switch['role'];
		$out_of_sync = ($role == 'access' && $sync_index++ % 5 == 0) ? 'yes' : 'no';
		$vlan_switches[] = array ($switch_id, $domain, $template_of[$role],
			$out_of_sync == 'no' ? 4 : 1, $out_of_sync);

		$trunk = array (100, 200, 300, 400);
		if (in_array ($tier_name, array ('xl', 'l')))
			$trunk[] = 500;
		if ($tier_name == 'xl')
			$trunk = array_merge ($trunk, array (210, 220));
		sort ($trunk);

		$ports_here = array ();
		switch ($role)
		{
		case 'access':
			foreach (array (1, 2, 3, 4) as $p)
				$ports_here['Te1/1/' . $p] = array ('trunk', 100, $trunk);
			# The largest sites split production across 200, 210 and 220
			# by rack; the port must sit in the VLAN its servers are in.
			$prod_vlan = 200;
			if ($tier_name == 'xl' && $switch['rack'] > 8)
				$prod_vlan = $switch['rack'] > 12 ? 220 : 210;
			$server_count = $site['config']['servers'];
			for ($p = 1; $p <= $server_count; $p++)
				$ports_here['Te1/0/' . $p] = array ('access', $prod_vlan, array ($prod_vlan));
			foreach (array (1, 2, 3, 4) as $p)
				$ports_here['Gi1/0/' . $p] = array ('access', 100, array (100));
			$ports_here['mgmt0'] = array ('access', 100, array (100));
			break;
		case 'dist':
			foreach (array (0, 1, 2, 3) as $p)
				$ports_here['Hu1/1/' . $p] = array ('trunk', 100, $trunk);
			for ($p = 1; $p <= 24; $p++)
				$ports_here['Fo1/0/' . $p] = array ('trunk', 100, $trunk);
			$ports_here['mgmt0'] = array ('access', 100, array (100));
			break;
		case 'core':
			for ($p = 0; $p < 16; $p++)
				$ports_here['Hu1/1/' . $p] = array ('trunk', 100, $trunk);
			for ($p = 0; $p < 8; $p++)
				$ports_here['Te1/2/' . $p] = array ('trunk', 100, $trunk);
			$ports_here['mgmt0'] = array ('access', 100, array (100));
			break;
		}

		foreach ($ports_here as $port_name => $conf)
		{
			list ($mode, $native, $allowed) = $conf;
			$pvm[] = array ($switch_id, $port_name, $mode);
			$cached_pvm[] = array ($switch_id, $port_name, $mode);
			foreach ($allowed as $vlan)
				$pav[] = array ($switch_id, $port_name, $vlan);
			$pnv[] = array ($switch_id, $port_name, $native);
			if ($out_of_sync == 'no')
			{
				foreach ($allowed as $vlan)
					$cached_pav[] = array ($switch_id, $port_name, $vlan);
				$cached_pnv[] = array ($switch_id, $port_name, $native);
			}
		}
	}
}
unset ($site);

$vst_rows = array (
	array (10, 1, 'access switch 25G', 'sheridan'),
	array (11, 1, 'distribution switch', 'sheridan'),
	array (12, 1, 'core switch', 'sheridan'),
);
$vst_rules = array (
	array (10, 10, '#^Te1/0/#', 'access', '200', '25G server ports'),
	array (10, 20, '#^Te1/1/#', 'uplink', '', '40G uplinks'),
	array (10, 30, '#^Gi#', 'access', '100', '1G management ports'),
	array (10, 40, '#^mgmt#', 'access', '100', 'management port'),
	array (10, 999, '#^#', 'none', '', 'unmatched'),
	array (11, 10, '#^Hu#', 'uplink', '', 'to core'),
	array (11, 20, '#^Fo#', 'downlink', '200,300,400', 'to access switches'),
	array (11, 40, '#^mgmt#', 'access', '100', 'management port'),
	array (11, 999, '#^#', 'none', '', 'unmatched'),
	array (12, 10, '#^Hu#', 'uplink', '', 'to routers and distribution'),
	array (12, 20, '#^Te#', 'downlink', '200,300,400', 'to firewalls and peers'),
	array (12, 40, '#^mgmt#', 'access', '100', 'management port'),
	array (12, 999, '#^#', 'none', '', 'unmatched'),
);

# ---------------------------------------------------------------------------
# IPv4/IPv6 plan. IPv6 carries the service networks, IPv4 survives on
# management and on the Centauri legacy estate.

$host_seq = array ();
$legacy_vlan = array ();

foreach ($site_devices as $slug => &$site)
{
	if (! $site['live'])
		continue;
	$sid = $site['config']['sid'];
	add_network6 (sprintf ('fd00:%04x::', $sid), 32, $slug . ' site aggregate');
	foreach ($vlan_plan[$slug] as $vlan => $label)
	{
		$net6 = add_network6 (sprintf ('fd00:%04x:%04x::', $sid, $vlan), 64,
			sprintf ('%s vlan %d %s', $slug, $vlan, $label));
		$vlan_ipv6[] = array ($site['domain'], $vlan, $net6);
	}
	add_network6 (sprintf ('fd00:%04x:ffff::', $sid), 64, $slug . ' loopbacks');

	$mgmt4 = add_network4 (sprintf ('10.%d.100.0', $sid), 24, $slug . ' management');
	$vlan_ipv4[] = array ($site['domain'], 100, $mgmt4);
	if (in_array ($site['config']['tier'], array ('xl', 'l')))
	{
		$oob4 = add_network4 (sprintf ('10.%d.50.0', $sid), 24, $slug . ' out-of-band');
		$vlan_ipv4[] = array ($site['domain'], 500, $oob4);
	}

	if ($slug == 'centauri')
	{
		$legacy_octet = 1;
		foreach ($vlan_plan[$slug] as $vlan => $label)
		{
			$net4 = add_network4 (sprintf ('172.16.%d.0', $legacy_octet), 24,
				sprintf ('%s legacy vlan %d %s', $slug, $vlan, $label));
			$vlan_ipv4[] = array ($site['domain'], $vlan, $net4);
			$legacy_vlan[$vlan] = $legacy_octet++;
		}
	}
	if ($slug == 'babylon5')
	{
		$vip4 = add_network4 ('10.5.200.0', 24, 'babylon5 load balancer VIPs');
		$vlan_ipv4[] = array ($site['domain'], 200, $vip4);
	}

	$host_seq[$slug] = 10;
}
unset ($site);

# Device addresses: management on IPv6 and legacy IPv4, service addresses on
# the server data ports, gateways on the core VLAN interfaces.
foreach ($objects as $object_id => $object)
{
	$sid = $object['sid'];
	$slug = $object['site'];
	if (! isset ($site_devices[$slug]) || ! $site_devices[$slug]['live'])
		continue;
	if (! isset ($objects[$object_id]))
		continue;
	$mgmt_port = isset ($port_index[$object_id . ':mgmt0']) ? 'mgmt0'
		: (isset ($port_index[$object_id . ':ipmi0']) ? 'ipmi0' : NULL);
	if ($mgmt_port === NULL)
		continue;
	$host = $host_seq[$slug]++;
	# Servers keep their BMC on the dedicated OOB network where the site has
	# one; management ports stay on the management VLAN.
	$oob = $mgmt_port == 'ipmi0' && in_array ($site_devices[$slug]['config']['tier'], array ('xl', 'l'));
	$vlan6 = $oob ? 500 : 100;
	$vlan4_net = $oob ? 50 : 100;
	add_alloc6 ($object_id, sprintf ('fd00:%04x:%04x::%x', $sid, $vlan6, $host), $mgmt_port,
		$object['type'] == OT_ROUTER ? 'router' : 'regular');
	add_alloc4 ($object_id, sprintf ('10.%d.%d.%d', $sid, $vlan4_net, $host), $mgmt_port,
		$object['type'] == OT_ROUTER ? 'router' : 'regular');
}

foreach ($site_devices as $slug => &$site)
{
	if (! $site['live'])
		continue;
	$sid = $site['config']['sid'];
	# Core switch gateways for the service VLANs.
	$core = $site['cores'][0];
	$site_vlans = in_array ($site['config']['tier'], array ('xl', 'l')) ? array (100, 200, 300, 500) : array (100, 200, 300);
	foreach ($site_vlans as $vlan)
	{
		add_alloc6 ($core, sprintf ('fd00:%04x:%04x::1', $sid, $vlan), 'Vlan' . $vlan, 'router');
		if ($vlan == 100)
			add_alloc4 ($core, sprintf ('10.%d.100.1', $sid), 'Vlan100', 'router');
		if ($vlan == 500)
			add_alloc4 ($core, sprintf ('10.%d.50.1', $sid), 'Vlan500', 'router');
	}
	# Router loopbacks.
	foreach ($site['routers'] as $ri => $router)
		add_alloc6 ($router, sprintf ('fd00:%04x:ffff::%x', $sid, $ri + 1), 'lo0', 'router');

	# Server data addresses, and the real servers of the station load
	# balancer that live on them.
	$host = 10;
	foreach ($site['servers'] as $rack_number => $servers)
	{
		$vlan = 200;
		if ($site['config']['tier'] == 'xl' && $rack_number > 8)
			$vlan = $rack_number > 12 ? 220 : 210;
		foreach ($servers as $srv)
		{
			$addr6 = sprintf ('fd00:%04x:%04x::%x', $sid, $vlan, $host);
			add_alloc6 ($srv, $addr6, 'eth0');
			$server_ip6[$srv] = $addr6;
			if ($slug == 'centauri')
			{
				$addr4 = sprintf ('172.16.%d.%d', $legacy_vlan[$vlan], $host);
				add_alloc4 ($srv, $addr4, 'eth0');
			}
			$host++;
		}
	}

	# Load balancer data interfaces sit in the production VLAN, storage
	# arrays in the storage VLAN.
	foreach ($site['lbs'] as $lb)
		foreach (array ('eth0', 'eth1') as $if)
		{
			$host_id = $host++;
			add_alloc6 ($lb, sprintf ('fd00:%04x:00c8::%x', $sid, $host_id), $if);
			if ($slug == 'centauri')
				add_alloc4 ($lb, sprintf ('172.16.%d.%d', $legacy_vlan[200], $host_id), $if);
		}
	foreach ($site['sans'] as $san)
		for ($p = 0; $p < 4; $p++)
			add_alloc6 ($san, sprintf ('fd00:%04x:012c::%x', $sid, $host++), 'eth' . $p);

	# Routed infra links: one /126 per link, inside the site aggregate.
	$seq = 0;
	foreach ($infra_links[$slug] as $link)
	{
		$seq++;
		add_network6 (sprintf ('fd00:%04x:fffe:%04x::', $sid, $seq), 126,
			sprintf ('%s infra link %d', $slug, $seq));
		add_alloc6 ($link[0], sprintf ('fd00:%04x:fffe:%04x::1', $sid, $seq), $link[1], 'point2point');
		add_alloc6 ($link[2], sprintf ('fd00:%04x:fffe:%04x::2', $sid, $seq), $link[3], 'point2point');
	}
}
unset ($site);

# Legacy point-to-point links between the backbone routers.
for ($i = 0; $i < count ($ring); $i++)
{
	add_network6 (sprintf ('fd00:ff00:%04x::', $i + 1), 126,
		sprintf ('backbone p2p %s-%s', $ring[$i], $ring[($i + 1) % count ($ring)]));
	if ($i < 2)
		add_network4 (sprintf ('10.255.%d.0', $i + 1), 30,
			sprintf ('legacy backbone p2p %s-%s', $ring[$i], $ring[($i + 1) % count ($ring)]));
}

# ---------------------------------------------------------------------------
# Load balancing on Babylon 5: two virtual services, two real-server pools.

$lb_vs = array ();
$lb_pools = array ();
$lb_servers = array ();
if (isset ($site_devices['babylon5']))
{
	$station = $site_devices['babylon5'];
	$app_servers = array ();
	foreach (array (1, 2, 3, 4) as $rack_number)
		foreach ($station['servers'][$rack_number] as $srv)
			$app_servers[] = $srv;

	$lb_pools = array (
		array (1, 'station-services', array_slice ($app_servers, 0, 4)),
		array (2, 'tachyon-relay', array_slice ($app_servers, 4, 3)),
	);
	$lb_vs = array (
		array (1, '10.5.200.100', 'fd00:0005:00c8::100', 443, 'station-services'),
		array (2, '10.5.200.101', 'fd00:0005:00c8::101', 8443, 'tachyon-relay'),
	);
}

# ---------------------------------------------------------------------------
# Users and permissions.

$users = array (
	'sheridan' => 'John Sheridan',
	'garibaldi' => 'Michael Garibaldi',
	'ivanova' => 'Susan Ivanova',
	'gkar' => "G'Kar",
	'delenn' => 'Delenn',
	'londo' => 'Londo Mollari',
	'guest' => 'Guest',
);

$rackcode = <<<'END'
# ISA Net permissions.
#
# Local accounts are turned into role tags for the rest of the request with
# a context modifier. A role tag on its own means nothing: every site rule
# ANDs it with the tag of the site the object belongs to, so an operator can
# only reach the site named in their role.
#
# Predicates are reused rather than repeated: [crew] collects the operators,
# [switching] selects the gear the 802.1Q engine knows about, and the rules
# below build on both. The first matching rule decides; the deny comes before
# any context is applied, so only the command can open a restricted site.

define [command] {$username_sheridan} or {$username_admin}

define [earth-crew] {$username_ivanova}
define [babylon5-crew] {$username_ivanova} or {$username_garibaldi}
define [narn-crew] {$username_gkar}
define [minbar-crew] {$username_delenn}
define [centauri-crew] {$username_londo}
define [allied-crew] [narn-crew] or [minbar-crew]
define [crew] [earth-crew] or [babylon5-crew] or [allied-crew] or [centauri-crew]

# Forwarding devices plus whatever else the 802.1Q engine manages, without
# the quarantine sites and without gear that is not mounted in a rack.
define [forwarding] {$typeid_7} or {$typeid_8} or {$typeid_798} or {$typeid_1644}
define [switching] ([forwarding] or {$runs_8021Q}) and not ({restricted} or {$unmounted})

allow [command]

deny {restricted}

context insert {earth-operator} on [earth-crew]
context insert {babylon5-operator} on [babylon5-crew]
context insert {narn-operator} on [narn-crew]
context insert {minbar-operator} on [minbar-crew]
context insert {centauri-operator} on [centauri-crew]

allow {earth-operator} and {earth}
allow {babylon5-operator} and {babylon-5}
allow {narn-operator} and {narn}
allow {minbar-operator} and {minbar}
allow {centauri-operator} and {centauri-prime}

# Crews inspect ports and the saved 802.1Q configuration of switching gear
# fleet-wide, not only inside their own site.
allow [crew] and [switching] and ({$tab_ports} or {$tab_8021qports})

# The security chief reads the change log of everything he can open.
allow {$username_garibaldi} and not {restricted} and {$tab_log}

# Allied worlds read each other's change logs.
allow [allied-crew] and ({narn} or {minbar}) and {$tab_log}

# Read-only directory for everyone else.
allow {$tab_default}
END;

# Role tags are only ever inserted into a context, never assigned to an
# entity, but they still have to exist for the placement to be meaningful.
foreach (array ('earth-operator', 'babylon5-operator', 'narn-operator',
	'minbar-operator', 'centauri-operator') as $role)
	tag_id ('role/' . $role);

# ---------------------------------------------------------------------------
# Emit.

$devices = 0;
foreach ($objects as $object)
	if (! in_array ($object['type'], array (OT_LOCATION, OT_ROW, OT_RACK)))
		$devices++;

out ('-- ISA Net demo inventory for RackTables.');
out ('--');
out ('-- Generated by scripts/make-demo-data.php; do not edit by hand.');
out ('-- Interstellar Alliance network: 10 sites, ' . $devices . ' devices,');
out ('-- ' . count ($vst_rows) . ' VLAN templates, IPv6-first addressing, RackCode permissions.');
out ('--');
out ('-- Loads into an empty database only: it replaces Config.enterprise and RackCode.');
out ('--');
out ('-- ID map: objects ' . ID_BASE_OBJECT . '+, ports ' . ID_BASE_PORT . '+, tags ' . ID_BASE_TAG . '+,');
out ('-- VLAN domains ' . ID_BASE_VDOM . '+, networks ' . ID_BASE_NET . '+, new port types 10001+.');
out ('');
out ('SET NAMES utf8;');
out ('');
out ('-- 200G/400G/800G do not exist in the 0.22.1 dictionary.');
out ('INSERT INTO `PortInnerInterface` (`id`, `iif_name`) VALUES');
out ("  (17, 'QSFP56'),");
out ("  (18, 'QSFP-DD'),");
out ("  (19, 'OSFP');");
out ('INSERT INTO `PortOuterInterface` (`id`, `oif_name`) VALUES');
out ("  (10001, '200GBase-SR4'),");
out ("  (10002, '400GBase-DR4'),");
out ("  (10003, '800GBase-DR8');");
out ('INSERT INTO `PortInterfaceCompat` (`iif_id`, `oif_id`) VALUES (17, 10001), (18, 10002), (19, 10003);');
out ('INSERT INTO `PortCompat` (`type1`, `type2`) VALUES (10001, 10001), (10002, 10002), (10003, 10003);');
out ('');
out ("REPLACE INTO `Config` VALUES ('enterprise','ISA Net','string','no','no','no','Organization name');");
out ("REPLACE INTO `Config` VALUES ('IPV4LB_LISTSRC','{load balancer}','string','yes','no','no','List source: IPv4 load balancers');");
out ('');

$rows = array ();
foreach ($tag_rows as $id => $tag)
	$rows[] = sprintf ('(%d, %s, %s, %s)', $id,
		$tag[0] === NULL ? 'NULL' : $tag[0], q ($tag[1]), q ($tag[2]));
emit_insert ('TagTree', array ('id', 'parent_id', 'is_assignable', 'tag'), $rows);

$rows = array ();
foreach ($objects as $object)
	$rows[] = sprintf ('(%d, %s, %s, %d, %s, %s, %s)', $object['id'],
		q ($object['name']), $object['label'] === NULL ? 'NULL' : q ($object['label']),
		$object['type'], $object['asset'] === NULL ? 'NULL' : q ($object['asset']),
		q ($object['problems']), q ($object['comment']));
emit_insert ('Object', array ('id', 'name', 'label', 'objtype_id', 'asset_no', 'has_problems', 'comment'), $rows);

# ObjectLog: the memorial of Babylon 4, the rebuilt Narn network, the site
# fenced off by the Great Machine, station changes and commissioning records.
$log_story = array (
	'babylon4' => array (
		array ('sinclair', 'Last telemetry received before the station vanished'),
		array ('sinclair', 'Kept as a memorial, do not recommission'),
	),
	'narn' => array (
		array ('gkar', 'Rebuilt after the occupation'),
		array ('gkar', 'Firmware reloaded after the war'),
	),
	'epsilon3' => array (
		array ('draal', 'Access restricted by the Great Machine'),
	),
);
$object_log = array ();
foreach ($objects as $object)
{
	if (in_array ($object['type'], array (OT_LOCATION, OT_ROW, OT_RACK)))
		continue;
	if (! isset ($log_story[$object['site']]))
		continue;
	foreach ($log_story[$object['site']] as $entry)
		$object_log[] = array ($object['id'], $entry[0], $entry[1]);
}
foreach (array_slice ($site_devices['babylon5']['servers'][1], 0, 2) as $srv)
	$object_log[] = array ($srv, 'ivanova', 'Joined the station-services pool');
foreach ($site_devices['babylon5']['lbs'] as $lb)
	$object_log[] = array ($lb, 'garibaldi', 'VIP moved after the tariff reform');
foreach ($site_devices as $site_data)
	foreach (array_merge ($site_data['routers'], $site_data['cores'], $site_data['dists']) as $id)
		$object_log[] = array ($id, $site_data['config']['contact'],
			'Commissioned as part of the ' . $site_data['config']['name'] . ' network');

$rows = array ();
$log_epoch = gmmktime (9, 0, 0, 1, 15, 2026);
foreach ($object_log as $seq => $entry)
	$rows[] = sprintf ('(%d, %s, %s, %s)', $entry[0], q ($entry[1]),
		q (gmdate ('Y-m-d H:i:s', $log_epoch + 2220 * ($seq + 1))), q ($entry[2]));
emit_insert ('ObjectLog', array ('object_id', 'user', 'date', 'content'), $rows);

$rows = array ();
foreach ($attrs as $attr)
{
	$type = $objects[$attr[0]]['type'];
	if (in_array ($attr[2], array (27, 29)))
		$rows[] = sprintf ('(%d, %d, %d, NULL, %d, NULL)', $attr[0], $type, $attr[2], $attr[3]);
	else
		$rows[] = sprintf ('(%d, %d, %d, %s, NULL, NULL)', $attr[0], $type, $attr[2], q ($attr[3]));
}
emit_insert ('AttributeValue', array ('object_id', 'object_tid', 'attr_id', 'string_value', 'uint_value', 'float_value'), $rows);

$rows = array ();
foreach ($entity_links as $link)
	$rows[] = sprintf ("('%s', %d, '%s', %d)", $link[0], $link[1], $link[2], $link[3]);
emit_insert ('EntityLink', array ('parent_entity_type', 'parent_entity_id', 'child_entity_type', 'child_entity_id'), $rows);

$rows = array ();
foreach ($rack_space as $mount)
	# A mounted device occupies both its face and the interior atom; that is
	# how RackTables marks a unit as taken.
	foreach (array ($mount[2], 'interior') as $atom)
		$rows[] = sprintf ("(%d, %d, '%s', 'T', %d)", $mount[0], $mount[1], $atom, $mount[3]);
emit_insert ('RackSpace', array ('rack_id', 'unit_no', 'atom', 'state', 'object_id'), $rows);

$rows = array ();
foreach ($tag_storage as $tag)
	$rows[] = sprintf ("('%s', %d, %d, 'sheridan', '2026-01-01')", $tag[0], $tag[1], $tag[2]);
emit_insert ('TagStorage', array ('entity_realm', 'entity_id', 'tag_id', 'user', 'date'), $rows);

$rows = array ();
foreach ($ports as $port)
	$rows[] = sprintf ('(%d, %d, %s, %d, %d, %s, NULL)', $port['id'], $port['object_id'],
		q ($port['name']), $port['iif'], $port['oif'], $port['l2'] === NULL ? 'NULL' : q ($port['l2']));
emit_insert ('Port', array ('id', 'object_id', 'name', 'iif_id', 'type', 'l2address', 'reservation_comment'), $rows);

# One cable per port: the Link table has separate unique keys on porta and
# portb, so the database alone would accept a port in two rows.
$linked_port = array ();
foreach ($links as $link)
	foreach (array ($link[0], $link[1]) as $port)
	{
		if (isset ($linked_port[$port]))
			throw new RuntimeException ("port ${port} is linked twice");
		$linked_port[$port] = TRUE;
	}

$rows = array ();
foreach ($links as $link)
	$rows[] = sprintf ('(%d, %d, %s)', $link[0], $link[1], q ($link[2]));
emit_insert ('Link', array ('porta', 'portb', 'cable'), $rows);

$rows = array ();
foreach ($vlan_domains as $domain)
	$rows[] = sprintf ('(%d, %s)', $domain[0], q ($domain[1]));
emit_insert ('VLANDomain', array ('id', 'description'), $rows);

$rows = array ();
foreach ($vlan_descriptions as $vlan)
	$rows[] = sprintf ('(%d, %d, %s, %s)', $vlan[0], $vlan[1], q ($vlan[2]), q ($vlan[3]));
emit_insert ('VLANDescription', array ('domain_id', 'vlan_id', 'vlan_type', 'vlan_descr'), $rows);

$rows = array ();
foreach ($vst_rows as $vst)
	$rows[] = sprintf ('(%d, %d, %s, %s)', $vst[0], $vst[1], q ($vst[2]), q ($vst[3]));
emit_insert ('VLANSwitchTemplate', array ('id', 'mutex_rev', 'description', 'saved_by'), $rows);

$rows = array ();
foreach ($vst_rules as $rule)
	$rows[] = sprintf ('(%d, %d, %s, %s, %s, %s)', $rule[0], $rule[1], q ($rule[2]),
		q ($rule[3]), q ($rule[4]), q ($rule[5]));
emit_insert ('VLANSTRule', array ('vst_id', 'rule_no', 'port_pcre', 'port_role', 'wrt_vlans', 'description'), $rows);

$rows = array ();
foreach ($vlan_switches as $switch)
	$rows[] = sprintf ("(%d, %d, %d, %d, '%s', 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00', '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
		$switch[0], $switch[1], $switch[2], $switch[3], $switch[4]);
emit_insert ('VLANSwitch', array ('object_id', 'domain_id', 'template_id', 'mutex_rev', 'out_of_sync',
	'last_errno', 'last_change', 'last_push_started', 'last_push_finished', 'last_error_ts'), $rows);

$rows = array ();
foreach ($networks4 as $net)
	$rows[] = sprintf ('(%d, %d, %d, %s)', $net[0], $net[1], $net[2], q ($net[3]));
emit_insert ('IPv4Network', array ('id', 'ip', 'mask', 'name'), $rows);

$rows = array ();
foreach ($networks6 as $net)
	$rows[] = sprintf ('(%d, %s, %d, %s, %s)', $net[0], $net[1], $net[2], $net[3], q ($net[4]));
emit_insert ('IPv6Network', array ('id', 'ip', 'mask', 'last_ip', 'name'), $rows);

$rows = array ();
foreach ($vlan_ipv4 as $map)
	$rows[] = sprintf ('(%d, %d, %d)', $map[0], $map[1], $map[2]);
emit_insert ('VLANIPv4', array ('domain_id', 'vlan_id', 'ipv4net_id'), $rows);

$rows = array ();
foreach ($vlan_ipv6 as $map)
	$rows[] = sprintf ('(%d, %d, %d)', $map[0], $map[1], $map[2]);
emit_insert ('VLANIPv6', array ('domain_id', 'vlan_id', 'ipv6net_id'), $rows);

$rows = array ();
foreach ($alloc4 as $alloc)
	$rows[] = sprintf ('(%d, %d, %s, %s)', $alloc[0], $alloc[1], q ($alloc[2]), q ($alloc[3]));
emit_insert ('IPv4Allocation', array ('object_id', 'ip', 'name', 'type'), $rows);

$rows = array ();
foreach ($alloc6 as $alloc)
	$rows[] = sprintf ('(%d, %s, %s, %s)', $alloc[0], $alloc[1], q ($alloc[2]), q ($alloc[3]));
emit_insert ('IPv6Allocation', array ('object_id', 'ip', 'name', 'type'), $rows);

$rows = array ();
foreach ($cached_pvm as $row)
	$rows[] = sprintf ('(%d, %s, %s)', $row[0], q ($row[1]), q ($row[2]));
emit_insert ('CachedPVM', array ('object_id', 'port_name', 'vlan_mode'), $rows);

$rows = array ();
foreach ($pvm as $row)
	$rows[] = sprintf ('(%d, %s, %s)', $row[0], q ($row[1]), q ($row[2]));
emit_insert ('PortVLANMode', array ('object_id', 'port_name', 'vlan_mode'), $rows);

$rows = array ();
foreach ($pav as $row)
	$rows[] = sprintf ('(%d, %s, %d)', $row[0], q ($row[1]), $row[2]);
emit_insert ('PortAllowedVLAN', array ('object_id', 'port_name', 'vlan_id'), $rows);

$rows = array ();
foreach ($pnv as $row)
	$rows[] = sprintf ('(%d, %s, %d)', $row[0], q ($row[1]), $row[2]);
emit_insert ('PortNativeVLAN', array ('object_id', 'port_name', 'vlan_id'), $rows);

$rows = array ();
foreach ($cached_pav as $row)
	$rows[] = sprintf ('(%d, %s, %d)', $row[0], q ($row[1]), $row[2]);
emit_insert ('CachedPAV', array ('object_id', 'port_name', 'vlan_id'), $rows);

$rows = array ();
foreach ($cached_pnv as $row)
	$rows[] = sprintf ('(%d, %s, %d)', $row[0], q ($row[1]), $row[2]);
emit_insert ('CachedPNV', array ('object_id', 'port_name', 'vlan_id'), $rows);

if (count ($lb_pools))
{
	$rows = array ();
	foreach ($lb_pools as $pool)
		$rows[] = sprintf ('(%d, %s, NULL, NULL)', $pool[0], q ($pool[1]));
	emit_insert ('IPv4RSPool', array ('id', 'name', 'vsconfig', 'rsconfig'), $rows);

	$rows = array ();
	$rs_id = 0;
	foreach ($lb_pools as $pool)
		foreach ($pool[2] as $srv)
		{
			$rs_id++;
			$rows[] = sprintf ('(%d, %s, %s, %d, %d, NULL, %s)', $rs_id, q ('yes'),
				ip_hex ($server_ip6[$srv]), 443, $pool[0], q ('station service'));
		}
	emit_insert ('IPv4RS', array ('id', 'inservice', 'rsip', 'rsport', 'rspool_id', 'rsconfig', 'comment'), $rows);

	$rows = array ();
	foreach ($lb_vs as $vs)
		$rows[] = sprintf ("(%d, %s, %d, 'TCP', %s, NULL, NULL)", $vs[0], ip_hex ($vs[1]), $vs[3], q ($vs[4]));
	emit_insert ('IPv4VS', array ('id', 'vip', 'vport', 'proto', 'name', 'vsconfig', 'rsconfig'), $rows);

	$rows = array ();
	foreach ($lb_vs as $vs)
		foreach ($site_devices['babylon5']['lbs'] as $lb)
			$rows[] = sprintf ('(%d, %d, %d, NULL, NULL, NULL)', $lb, $vs[0], $vs[0]);
	emit_insert ('IPv4LB', array ('object_id', 'rspool_id', 'vs_id', 'prio', 'vsconfig', 'rsconfig'), $rows);

	$rows = array ();
	foreach ($lb_vs as $vs)
		$rows[] = sprintf ('(%d, %s, NULL, NULL)', $vs[0], q ($vs[4]));
	emit_insert ('VS', array ('id', 'name', 'vsconfig', 'rsconfig'), $rows);

	$rows = array ();
	foreach ($lb_vs as $vs)
	{
		$rows[] = sprintf ('(%d, %s, NULL, NULL)', $vs[0], ip_hex ($vs[1]));
		$rows[] = sprintf ('(%d, %s, NULL, NULL)', $vs[0], ip_hex ($vs[2]));
	}
	emit_insert ('VSIPs', array ('vs_id', 'vip', 'vsconfig', 'rsconfig'), $rows);

	$rows = array ();
	foreach ($lb_vs as $vs)
		$rows[] = sprintf ('(%d, %s, %d, NULL, NULL)', $vs[0], q ('TCP'), $vs[3]);
	emit_insert ('VSPorts', array ('vs_id', 'proto', 'vport', 'vsconfig', 'rsconfig'), $rows);

	$rows = array ();
	foreach ($lb_vs as $vs)
		foreach ($site_devices['babylon5']['lbs'] as $lb)
		{
			$rows[] = sprintf ('(%d, %d, %s, %d, 1, NULL, NULL)', $lb, $vs[0], ip_hex ($vs[1]), $vs[0]);
			$rows[] = sprintf ('(%d, %d, %s, %d, 1, NULL, NULL)', $lb, $vs[0], ip_hex ($vs[2]), $vs[0]);
		}
	emit_insert ('VSEnabledIPs', array ('object_id', 'vs_id', 'vip', 'rspool_id', 'prio', 'vsconfig', 'rsconfig'), $rows);

	$rows = array ();
	foreach ($lb_vs as $vs)
		foreach ($site_devices['babylon5']['lbs'] as $lb)
			$rows[] = sprintf ('(%d, %d, %s, %d, %d, NULL, NULL)', $lb, $vs[0], q ('TCP'), $vs[3], $vs[0]);
	emit_insert ('VSEnabledPorts', array ('object_id', 'vs_id', 'proto', 'vport', 'rspool_id', 'vsconfig', 'rsconfig'), $rows);
}

$rows = array ();
$uid = 2;
foreach ($users as $name => $realname)
	$rows[] = sprintf ('(%d, %s, %s, %s)', $uid++, q ($name), q (sha1 ($name)), q ($realname));
emit_insert ('UserAccount', array ('user_id', 'user_name', 'user_password_hash', 'user_realname'), $rows);

out ('INSERT INTO `Script` (`script_name`, `script_text`) VALUES');
out ('  (' . q ('RackCode') . ', ' . q ($rackcode) . ')');
out ('  ON DUPLICATE KEY UPDATE `script_text` = VALUES(`script_text`);');
# A database that was already initialised keeps a parsed copy of RackCode;
# blank it so the next request parses the rules above instead of the old ones.
out ("REPLACE INTO `Script` (`script_name`, `script_text`) VALUES ('RackCodeCache', '');");
# Marker for app-init: the inventory is complete only when this row is
# committed together with the rest of the file.
out ("REPLACE INTO `Script` (`script_name`, `script_text`) VALUES ('DemoDataLoaded', '1');");
out ('');

fwrite (STDERR, sprintf ("objects: %d (devices %d), ports: %d, links: %d, tags: %d, networks: %d/%d\n",
	count ($objects), $devices, count ($ports), count ($links), count ($tag_rows),
	count ($networks4), count ($networks6)));

if ($devices < 750 || $devices > 860)
{
	fwrite (STDERR, "device count ${devices} is outside the intended 750..860 range\n");
	exit (1);
}

echo $sql;
