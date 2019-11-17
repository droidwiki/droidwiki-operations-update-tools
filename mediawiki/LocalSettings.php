<?php

# This LocalSettings is only used during the staging process to create a deployable artifact
# of MediaWiki.

require_once "$IP/../mw-config/mw-config/multiversion/MWMultiVersion.php";
$multiversion = MWMultiVersion::getInstance();
$wgServer = "https://www.droidwiki.org";

# Wikibase is kind of a special case here as it is loaded conditionally where the default is
# "do not load". This breaks the l10n update, as WB measges are not included.
$wmgUseWikibaseRepo = true;
$wmgUseWikibaseClient = true;

require_once "$IP/../mw-config/mw-config/ExtensionSetup.php";