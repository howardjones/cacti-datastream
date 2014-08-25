<?php

require 'vendor/autoload.php';

function plugin_influx_install () {
	api_plugin_register_hook('influx', 'config_arrays',       'influx_config_arrays',        'setup.php');
	api_plugin_register_hook('influx', 'config_settings',     'influx_config_settings',      'setup.php');
	api_plugin_register_hook('influx', 'poller_output',       'influx_poller_output',        'setup.php');
	api_plugin_register_hook('influx', 'poller_bottom',       'influx_poller_bottom',        'setup.php');
	// api_plugin_register_hook('influx', 'poller_command_args', 'influx_poller_command_args',  'setup.php');
	// api_plugin_register_hook('influx', 'boost_poller_bottom', 'influx_boost_bottom',         'setup.php');

	influx_setup_table_new ();
}

/* plugin_influx_uninstall - a generic uninstall routine.  Right now it will do nothing as I
     don't want the tables removed from the system except for forcably by the user.  This
     may change at some point.
   @returns - null */
function plugin_influx_uninstall () {
	/* Do any extra Uninstall stuff here */
}


/* plugin_influx_version - obtains the current version of the plugin in a PIA 2.x
     fashion.  The legacy function is also provided for backwards compatibility, although
     it's no required.
   @returns - (string) the current plugin version */
function plugin_influx_version () {
	return array(
		'name'      => 'influx',
		'version'   => '0.1',
		'longname'  => 'Influx Exporter',
		'author'    => 'Howard Jones',
		'homepage'  => 'http://www.cacti.net',
		'email'	    => 'forums@cacti.net',
		'url'       => 'http://www.cacti.net'
	);
}



/* plugin_influx_check_config - this routine will verify if there is any upgrade steps that
     need to be performed on the plugin.
   @returns - (bool) always returns true for some reason */
function plugin_influx_check_config () {
	/* Here we will check to ensure everything is configured */
	influx_check_upgrade();
	return TRUE;
}

/* plugin_influx_upgrade - this routine is similar to the config_check.  My guess is that
     the author, aka me, is doing something wrong here as a result of this discovery.
   @returns - (bool) always returns true for some reason */
function plugin_influx_upgrade () {
	/* Here we will upgrade to the newest version */
	influx_check_upgrade();
	return FALSE;
}

/* influx_check_upgrade - this generic routine verifies if the plugin needs upgrading or
     not.  If it does require upgrading, then it performs that upgrade and updates
     the plugin config table with the new version.
   @returns - NULL */
function influx_check_upgrade () {
	global $config;

	$files = array('index.php', 'plugins.php');
	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
		return;
	}

	$current = plugin_influx_version();
	$current = $current['version'];
	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='influx'");
	if (sizeof($old) && $current != $old["version"]) {
		/* if the plugin is installed and/or active */
		if ($old["status"] == 1 || $old["status"] == 4) {
			/* re-register the hooks */
			plugin_influx_install();

			/* perform a database upgrade */
			influx_database_upgrade();
		}

		/* update the plugin information */
		$info = plugin_influx_version();
		$id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='influx'");
		db_execute("UPDATE plugin_config
			SET name='" . $info["longname"] . "',
			author='"   . $info["author"]   . "',
			webpage='"  . $info["homepage"] . "',
			version='"  . $info["version"]  . "'
			WHERE id='$id'");
	}
}


/* influx_database_upgrade - this routine is where I "should" be performing the upgrade.
     I guess I will have to change that at some point from the previous function.
   @returns - (bool) always returns true for some reason */
function influx_database_upgrade() {
	global $plugins, $config;

	return TRUE;
}

/* influx_check_dependencies - this routine is where I would check for other plugin
     dependencies.  There only plugin dependency at this moment is the PIA itself.
     So, I will always return true at the moment.
   @returns - (bool) always returns true since there are not dependencies */
function influx_check_dependencies() {
	global $plugins, $config;
	return TRUE;
}

/* influx_setup_table_new - this routine creates all influx table if they don't
     already exist.  At some point, they would work better with the uninstall routine
     but not for now.
   @returns - NULL */
function influx_setup_table_new () {
	
}

function influx_config_arrays ()
{
}

function influx_config_settings ()
{
    global $tabs, $settings;

    $tabs["influx"] = "Influx";

    /* check for an upgrade */
    plugin_influx_check_config();

    $temp = array(
        "influx_hq_header" => array(
            "friendly_name" => "InfluxDB Data Export",
            "method" => "spacer",
        ),
        "influx_enable" => array(
            "friendly_name" => "Enable Poller Data Export",
            "description" => "Should poller data be exported?",
            "method" => "checkbox",
            "default" => ""
        ),
        "influx_protocol" => array(
            "friendly_name" => "Output Protocol",
            "description" => "What type of system is data exported to?",
            "default" => "influxdb",
            "method" => "drop_array",
            "array" => array("influxdb"=>"InfluxDB", "graphite"=>"graphite", "rabbitmq"=>"RabbitMQ")
        ),
        "influx_servername" => array(
            "friendly_name" => "Remote Server Hostname (or IP)",
            "description" => "The hostname of the server",
            "method" => "textbox",
            "max_length" => 40,
        ),
        "influx_serverport" => array(
            "friendly_name" => "Remote Server Port",
            "description" => "The port to connect to",
            "method" => "textbox",
            "max_length" => 5,
            "size" => 5
        ),
        "influx_datakey" => array(
            "friendly_name" => "Data Key",
            "description" => "The topic to use for exporting data",
            "method" => "textbox",
            "max_length" => 40,
        ),

    );

    if (isset($settings["influx"])) {
        $settings["influx"] = array_merge($settings["influx"], $temp);
    } else {
        $settings["influx"]=$temp;
    }


}


function influx_poller_output(&$rrd_update_array)
{
    global $config;

    $path_rra = $config["rra_path"];






    return $rrd_update_array;
}


/* dsstats_poller_bottom - this routine launches the main dsstats poller so that it might
     calculate the Hourly, Daily, Weekly, Monthly, and Yearly averages.  It is forked independently
     to the Cacti poller after all polling has finished.
   @arg $output - (mixed) This is information passed to this plugin and returned pristine for other plugins
   @returns - (mixed) The untouched $output variable for other plugins to use. */
function influx_poller_bottom ($output) {
    global $config;
    include_once($config["base_path"] . "/lib/poller.php");

}

function influx_version()
{
    return plugin_influx_version();
}