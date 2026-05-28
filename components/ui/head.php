<?php
/**
 * Shared <head> for EduAttend pages.
 * Set $pageTitle and $assetContext ('student'|'admin'|'public') before including.
 */
use StudentAttendance\Utils\Assets;

$pageTitle = $pageTitle ?? Assets::APP_NAME;
$assetContext = $assetContext ?? 'public';
$extraHeadJs = $extraHeadJs ?? [];

Assets::renderHeadStart($pageTitle, $assetContext);

if (!empty($pageStyles)) {
    echo "    <style>\n", $pageStyles, "\n    </style>\n";
}

Assets::renderHeadScripts($assetContext, $extraHeadJs);
echo "</head>\n";
