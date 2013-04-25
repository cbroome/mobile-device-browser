<?php

require '../config/init.php';


$_SESSION['last_seen'] = time();
$raw = file_get_contents('../config/wurflStub.inc');
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


$dbh = new PDO("mysql:dbname=" . DB_DATABASE . ";host=" . DB_HOST, DB_USERNAME, DB_PASSWORD);

$sql = "SELECT g.id as group_id,
            g.name as groupt,
            c.id as capability_id,
            c.name as capability,
            c.description,
            c.options
        FROM groups g, capabilities c
        WHERE g.id = c.group_id
        ORDER BY g.name, c.name";
$sth = $dbh->prepare($sql); 
if(!$sth->execute())
{
    $error = "Could not retrieve capability heirarchy.";
    print $error;
}
else {
    
    while($row = $sth->fetch(PDO::FETCH_ASSOC))
    {
        $tree[ $row['group_id'] ]['group'] = str_replace("_", " ", $row['groupt']);
        $tree[ $row['group_id'] ]['capabilities'][ $row['capability_id'] ] = array (
                'capability' => str_replace("_", " ", $row['capability']),
                'description' => str_replace("\n", "<br />", $row['description']),
                'options' =>str_replace("\n", "<br />", $row['options'])
            );
    }
}


// Get the current version
$sql = 'SELECT version, official_url FROM wurfl_version ORDER BY last_updated DESC LIMIT 1';
$sth = $dbh->prepare($sql); 
if(!$sth->execute())
{
    
}
else {
    $version_info = $sth->fetch(PDO::FETCH_ASSOC);
}



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Mobile Device Browser - Search and Compare Mobile Devices by Features and Capabilities </title>
    <meta name="description" content="Search for mobile phones and other mobile devices by their supported capabilities. Powered by the WURFL database." />
    <meta name="keywords" content="mobile, wurfl, device, look up, user agent, capability, search, iphone, android, developer, windows 7 phone, development" />

        <!--  I see you... -->
        <script type="text/javascript">
        
          var _gaq = _gaq || [];
          _gaq.push(['_setAccount', 'UA-890822-5']);
          _gaq.push(['_trackPageview']);
        
          (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
          })();
        
        </script>

        <!-- ...you see me -->

</head>

<style type="text/css">@import url('./js/dhtmlxgrid/dhtmlxgrid.css');</style>
<style type="text/css">@import url('./css/style.css');</style>
<link type="text/css" rel="stylesheet" media="screen" href="./js/dhtmlxgrid/skins/dhtmlxgrid_dhx_skyblue.css" />
<body class="wurflGUI"><div class="header">


        <div class="content">
            <h1>
                <a href="http://<?=$_SERVER['HTTP_HOST'];?>"><img src="/images/logo.png"  alt="Mobile Device Browser" border="0" /></a>
            </h1>
            <div class="donateHolder">
                <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                    <input type="hidden" name="cmd" value="_s-xclick">
                    <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
                    <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                    <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
                </form>
                
            </div>
            <div class="contactBox">
                <a href="#" id="contactButton"><span>Contact</span></a>
            </div>
        </div>
        
    </div><div class="header-description">
        
        <div class="content">
            <div class="titleBlock">
                <h2>
                    Search for mobile phones, tablets, and other devices released in the past six years by their features and capabilities.
                </h2>
                <div class="disclaimer">
                    Powered by <a href="<?=$version_info['official_url'];?>" target="_blank">WURFL</a>
                    |
                    Current Version: &quot;<span class="version"><?=$version_info['version'];?></span>&quot;
                </div>
            </div>
            <div class="clearit">&nbsp;</div>
        </div>
    </div>




    <div class="content" style="overflow: hidden; height: 500px;">
        <div id="loadB" class="pback" style="display: none;">&nbsp;</div>
        <div id="loadF" class="pfront" style="display: none;">
            <img src="./images/ajax-loader.gif" alt="Loading..." />
            <div id="loadMessage"></div>
            <div id="subMessage" style="display: none">
                Still working.... 
            </div>
        </div>
        
        
        <!-- Backgrounds -->
        <div style="position: absolute; height: 100%; width: 15px; background: url(./images/bg.gradient.lft.png) repeat-y left; z-index: 15;" >&nbsp;</div>
        <div style="position: absolute; height: 100%; width: 15px; background: url(./images/bg.gradient.rgt.png) repeat-y right; z-index: 15; left: 1009px;" >&nbsp;</div> 
        
        
        <!-- Capability Edit -->
        
        
        <div id="capabilityEdit" class="pfront" style="display: none;">
        
            <div class="window">
                
                <form method="post" onsubmit="return addCapabilityToQuery(this)">
                    <div class="closeButton" onclick="hideCapabilityEdit()">close</div>
                    
                    <div class="name">
                        <span id="group_name"></span>
                        :
                        <span id="capability_name"></span>
                    </div>
                    <div class="line">type : <span id="base_type"> </span></div>
                    <div class="line">description : </div>
                    <div class="description" id="capability_description"> </div>
                    
                    <div class="capabilty_controls">
                        <div>
                            <label>
                                <input type="checkbox" name="cape_apply_criteria" id="cape_apply_criteria" onclick="capeOpted(this)" />
                                Apply search criteria
                            </label>
                        </div>
                    </div>
                    <div id="capeOptIn">
                        <div id="capabilityQueryDest"></div>
                        <div class="line">Additional Notes : </div>
                        <div id="instructions"></div>
                    </div>
                    <br clear="all" />
    
                    <input type="submit" value="Add Capability to Query" />
                    <input type="button" value="Close" onclick="hideCapabilityEdit()" />
                </form>
            </div>
          
        </div>
        
            
        <div id="slab" class="moveableSlab" style="padding-top: 15px; width: 1800px;">
            
            
            
            <!-- First Column: capability select tool -->
            
            
            <div id="columnCapeSelect" class="columnHalf">
            
                <div class="section">
                    
                    <div class="title">Select Up to Five Capabilities to Include in the Output</div>
                    
                    <div class="description">
                        You can apply search criteria to each capability to limit
                        the number of devices returned in the result set.
                    </div>
                    
                </div>
                     
                <div class="queryBox">
                    <div class="title">Search</div>
                    <input type="text" name="capabilitySearch" id="capabilitySearch" class="autocomplete" />
                    <input type="button" name="findCapbility" value="Add" onclick="addCapabilityFromSearch()" />
                    <div id="resultBlock" class="autocomplete" style="display: none;"></div>
                    
                    <div class="title">Capability List</div>
                    <div class="groupTree">
                        
                        <? foreach($tree as $group): ?>
                        
                            <div class="group">
                                
                                <div class="name"><?=$group['group'];?></div>
                                <div class="capabilities">
                                    
                                    <?
                                        $rowClass = '';
                                        foreach($group['capabilities'] as $cid=>$cape) {
                                            $rowClass = ($rowClass == 'odd') ? 'even' : 'odd';
                                    ?>
                                    
                                        <div class="capability <?=$rowClass;?>">
                                            <span onclick="addCapability(<?=$cid;?>)"><?=$cape['capability'];?></span>
                                            <span class="question-mark" title="<?=strip_tags($cape['description']);?>">?</span>
                                        </div>
                                        
                                    <? } ?>
                                    
                                </div>
                                
                            </div>
                        
                        <? endforeach; ?>
                        
                    </div>
                    
                </div>
            </div>
            
            
            
            
            <!-- Second Column -->
            
            
            <div id="columnQuery" class="columnHalf">
                <div class="section">
                    <div class="title">Your Current Query</div>
                </div>
                
                <div>
                    
                    <form id="criteriaForm" method="post" onsubmit="return searchWurfl()" >
                        
                        <div id="criteriaHolder">
                            
                        </div>
                        
                        <div class="criteriaString">
                            Show &quot;brand name&quot;, &quot;model name&quot;, &quot;user agent&quot;
                            <span id="criteriaColumns"></span>
                            for all mobile devices    
                            <span id="criteriaWhere" style="display: none;">where:</span>
                        </div>
                        <div>
                            <ul id="criteriaParams">
                                
                            </ul>
                        </div>
                        
                        
        
                        <div align="center">
                            <input type="button" value="Search Wurfl" onclick="return searchWurfl()" />
                            <input type="button" value="Reset Query String" onclick="clearCriteria()" />
                        </div>
        
                    </form>
                </div>
            </div>
            
            
            
            
            
            
            <!-- Third, Results column -->
            
            <div id="columnResults" class="columnTwoThirds">
                <div class="section">
                    <div class="title">Results</div>   
                </div>
                <div id="chartGoesHere" style="height: 350px; width: 670px;"></div>
                <div class="options">
                    <a href="#" onclick="return output('CSV')">Export to an Excel-compatible CSV file</a>
                </div>
                
                <form method="post" action="./externalGenerator.php" id="ext_form">
                    <input type="hidden" name="output_type" id="output_type"/>
                    <input type="hidden" name="output_data" id="output_data" />
                </form>
            </div>
            
            
            
            
            
            
            
    
            <div class="clearit">&nbsp;</div>
        
        </div>
        
        
        
    </div>
    

    <div class="footer">
        <div class="content">
            <div class="linkbar">
                Similar Errata: <a href="http://www.ditherandbicker.com">Dither and Bicker.</a>
            </div>
        </div>
    </div>
    
</body>

<script type="text/javascript" src="./js/prototype.js"></script>
<script type="text/javascript" src="./js/scriptaculous/scriptaculous.js"></script>
<script type="text/javascript" src="./js/scriptaculous/effects.js"></script>
<script type="text/javascript" src="./js/scriptaculous/controls.js"></script>
<script type="text/javascript" src="./js/wurfl.js"></script>

<script type="text/javascript" src="./js/dhtmlxgrid/dhtmlxcommon.js"></script>
<script type="text/javascript" src="./js/dhtmlxgrid/dhtmlxgrid.js"></script>
<script type="text/javascript" src="./js/dhtmlxgrid/dhtmlxgridcell.js"></script>
<script type="text/javascript" src="./js/dhtmlxgrid/ext/dhtmlxgrid_srnd.js"></script>
<script type="text/javascript" src="./js/dhtmlxgrid/ext/dhtmlxgrid_filter.js"></script>
<script type="text/javascript" src="./js/dhtmlxgrid/ext/dhtmlxgrid_nxml.js"></script>

<script type="text/javascript" src="./js/variables.php"></script>

<script type="text/javascript">
/* <![CDATA[ */

var bigTree = <?=json_encode($tree); ?>;

var brandId = <?=$brand_id;?>;
var modelId = <?=$model_id;?>;

$('contactButton').onclick='mail'+'to:contact@ditherandbicker.com';

new Autocompleter.Local('capabilitySearch', 'resultBlock', capStrings,
                        {
                            'partialSearch' : true,
                            'fullSearch': true,
                            'partialChars' : 3,
                            'choices' : 20
                        });
/* ]]> */
</script>
</htmL>


