<?php

# This LocalSettings is only used during the staging process to create a deployable artifact
# of MediaWiki.

require_once "$IP/../mw-config/mw-config/multiversion/MWMultiVersion.php";
$multiversion = MWMultiVersion::getInstance();
$wgServer = "https://www.droidwiki.org";
require_once "$IP/../mw-config/mw-config/ExtensionSetup.php";