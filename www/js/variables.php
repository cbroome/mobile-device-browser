<?php

$raw = file_get_contents('../../config/wurflStub.inc');
if($raw)
{
    $conf = unserialize($raw);
    
    $brands = $conf['brands'];
    $groups = $conf['groups'];
    $fullCaps = $conf['capabilitiesFull'];
    $capabilities= $conf['capabilities'];
    $capability_data_types = $conf['capability_data_types'];
    $brand_id = $conf['brand_id'];
    $model_id = $conf['model_id'];
    
    foreach($fullCaps as $row) {
        $capArray[] = $groups[ $row['group_id'] ][ 'name' ] . ' - ' . $row['name'];
    }
    
}
?>
var capabilities = <?=json_encode($capabilities);?>;
var capabilitiesFull = <?=json_encode($fullCaps);?>;
var capabilityDataTypes = <?=json_encode($capability_data_types);?>;
var groups = <?=json_encode($groups);?>;
var capStrings = <?=json_encode($capArray);?>;
var brands = <?=json_encode($brands);?>;
