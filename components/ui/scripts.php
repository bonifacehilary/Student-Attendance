<?php
/**
 * Shared scripts before </body>.
 * Set $assetContext and optional $extraFootJs before including.
 */
use StudentAttendance\Utils\Assets;

$assetContext = $assetContext ?? 'public';
$extraFootJs = $extraFootJs ?? [];

Assets::renderFoot($assetContext, $extraFootJs);
