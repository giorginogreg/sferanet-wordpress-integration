<?php

namespace Sferanet_Wordpress_Integration\Statuses;

class Generic_Status {

	const INSERTING = 'INS';
	const UPDATING = 'MOD'; // When some data is updated, pass this status
	const DELETING = 'CANC';
	const WORK_IN_PROGRESS = 'WP'; // To pass in INSERTING status when work is done
	const RELOAD = 'WPRELOAD';     // Reload all internal elements (childs removed) -> status will then need to be set to UPDATING

}
