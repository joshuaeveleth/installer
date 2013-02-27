<?php

include_once('installer/DatabaseUtils.class.php');
include_once('installer/OsUtils.class.php');
include_once('installer/Log.php');
include_once('installer/InstallReport.class.php');
include_once('installer/AppConfig.class.php');
include_once('installer/Installer.class.php');
include_once('installer/Validator.class.php');
include_once('installer/InputValidator.class.php');
include_once('installer/phpmailer/class.phpmailer.php');

if ($argc < 2)
{
	echo 'Usage is php '.__FILE__.' <outputdir>'.PHP_EOL;
	echo "<outputdir> = directory in which to create the package".PHP_EOL;

	if(OsUtils::getOsName() == OsUtils::LINUX_OS)
		echo "(if it does not start with a '/' the running directory will be used as base directory)".PHP_EOL;
	elseif(OsUtils::getOsName() == OsUtils::WINDOWS_OS)
		echo "(if it does not start with driver letter (e.g. C:\\) the running directory will be used as base directory)".PHP_EOL;

	exit(-1);
}

$baseDir = trim($argv[1]);
if(OsUtils::getOsName() == OsUtils::LINUX_OS && $baseDir[0] != '/')
{
	$baseDir = __DIR__ . "/$baseDir";
}
elseif(OsUtils::getOsName() == OsUtils::WINDOWS_OS && !preg_match('/^[A-Za-z]:[\\/\\\\]/', $baseDir))
{
	$baseDir = __DIR__ . "/$baseDir";
}
$baseDir = str_replace('\\', '/', $baseDir);

// installation might take a few minutes
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);

date_default_timezone_set(@date_default_timezone_get());

// start the log
startLog(__DIR__ . '/package.' . date("d.m.Y_H.i.s") . '.log');

AppConfig::init(__DIR__);
OsUtils::setLogPath(__DIR__ . '/package.' . date("d.m.Y_H.i.s") . '.details.log');

$options = getopt('hsc');
if(isset($options['h']))
{
	echo 'Usage is php ' . __FILE__ . ' [arguments]'.PHP_EOL;
	echo "-h - Show this help." . PHP_EOL;
	echo "-s - Silent mode, no questions will be asked." . PHP_EOL;
	echo "-c - Run configurator." . PHP_EOL;
	echo PHP_EOL;
	echo "Examples:" . PHP_EOL;
	echo "php ' . __FILE__ . ' -s" . PHP_EOL;
	echo "php ' . __FILE__ . ' -c" . PHP_EOL;
}

$silentRun = isset($options['s']);
$configure = false;
if(isset($options['c']))
	$configure = true;
if(!$silentRun && !$configure && AppConfig::getTrueFalse(null, "Would you like to configure the package?", 'y'))
	$configure = true;
if ($configure)
	AppConfig::configure($silentRun, true);

$directoryConstructorDir = __DIR__ . '/directoryConstructor';
$xmlUri = "$directoryConstructorDir/directories." . AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE) . '.xml';
$xmlUri = str_replace('\\', '/', $xmlUri);

$attributes = array(
	'package.dir' => $baseDir,
	'package.type' => AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE),
	'package.version' => AppConfig::get(AppConfigAttribute::KALTURA_VERSION),
	'BASE_DIR' => $baseDir . '/tmp',
	'xml.uri' => $xmlUri,
);

logMessage(L_USER, "Packaging...", false);
if(!OsUtils::phing($directoryConstructorDir, 'Pack', $attributes))
{
	logMessage(L_USER, " failed.", true, 3);
	exit(-1);
}

logMessage(L_USER, " successfully finished", true, 3);
exit(0);