
var selectedCapabilities = {};
var timeout = null;
var dchart = null;
var renderedChart = false;

function showLoadingScreen()
{
    $('loadB').show();
    $('loadF').show();
    
    timeout = setTimeout(plead, 20000);    

}

function hideLoadingScreen()
{
    hideCapabilityEdit();
    $('loadB').hide();
    $('loadF').hide();
    
    $('loadMessage').innerHTML = '';
    clearTimeout(timeout);
    closePlead();
}



function showCapabilityEdit(params)
{
    hideLoadingScreen();
    
    if(typeof params != 'undefined') {
        
        $('group_name').innerHTML = params.group_name;
        $('capability_name').innerHTML = params.capability_name;
        $('base_type').innerHTML = params.capability_data_type;
        $('capabilityQueryDest').innerHTML = ''; 
        $('capabilityQueryDest').appendChild( params.queryControl );
        $('capeOptIn').hide();
    }
    
    $('loadB').show();
    $('capabilityEdit').show();
    
}

function capeOpted(obj)
{
    if( obj.checked ) {
        $('capeOptIn').show();
    } else {
        $('capeOptIn').hide();
    }
}

function hideCapabilityEdit(){
    $('cape_apply_criteria').checked = false;
    $('loadB').hide();
    $('capabilityEdit').hide();

}


function clearCriteria() {
    
    if(renderedChart) {
        var conf = confirm("Do you want to clear the results and start a new search?");
        if(!conf) {
            return false; 
        }
        renderedChart = false; 
        new Effect.Move('slab', {x: 526});
        $('columnQuery').className = 'columnHalf';
        
        setTimeout( function(){
                // dchart = null;
                dchart.destructor();
                $('chartGoesHere').innerHTML = '';
            }, 1000 );
    }
    
    $('criteriaColumns').innerHTML = '';
    $('criteriaParams').innerHTML = '';
    $('criteriaHolder').innerHTML = '';

    $('criteriaWhere').hide();   
    
    $('columnCapeSelect').show();    
    return false;
}


function loadMessage(message)
{
    $('loadMessage').innerHTML = message;
}

function plead()
{
    $('subMessage').style.display = '';
}

function closePlead()
{
    $('subMessage').style.display = 'none';
}

function selectBrand(obj)
{
    var brand = obj.options[obj.selectedIndex].value;
    
    var ml = $('modelSelect').options.length - 1;
    for(var j=ml;j>=0;j--) {
        $('modelSelect').options[j]=null;
    }
    
    // Stupid fucking Chrome fix
    var sortArray = new Array();
    for(var i in brands[brand]['models'])
    {
        var name = brands[brand]['models'][i].toLowerCase();
        sortArray.push(name+'|||'+i);
    }
    sortArray.sort();
    
    
    for(var j=0;obj=sortArray[j];j++)
    {
        var matches = obj.split(/\|\|\|/);
        
        var i = matches[1];
        var mod = brands[brand]['models'][i];
        var opt = document.createElement('option');
        opt.text = mod;
        opt.value = i;
        $('modelSelect').options.add(opt);
    }
}



function selectBrandCapability(obj)
{
    var b = obj.options[obj.selectedIndex].text;
    removeCapability(brandId);
    if(brandId > 0) {
        addCapability(brandId, b);
    }
}

function selectModelCapability(obj)
{
    
    removeCapability(modelId);
    removeCapability(brandId);
    if(brandId > 0) {
        var b = $('brandSelect').options[$('brandSelect').selectedIndex].text;
        addCapability(brandId, b);
    }
    if(modelId > 0) {
        var b = obj.options[obj.selectedIndex].text;
        addCapability(modelId, b);
    }

}


function addCapabilityFromSearch()
{
    var capeName = $('capabilitySearch').value;
    for(var i in capabilities)
    {
        if(capeName == capabilities[i])
        {
            addCapability(i);
        }
    }
}


// Shows the capability modal select box.
function addCapability(id, initVal)
{
    if(capabilitiesFull[id] != undefined && capabilitiesFull[id] != null)
    {
        var cf = capabilitiesFull[id];
        var queryComp = new Element('div', {'class': 'capabilityCriteria', 'id': 'capability-'+id});
        
        var capability_name = cf.name;
        var group_name = groups[cf.group_id].name;
        
        var description = bigTree[cf.group_id]['capabilities'][id]['description'];
        var options = bigTree[cf.group_id]['capabilities'][id]['options'];
        
        $('capability_description').innerHTML = description;
        $('instructions').innerHTML = options;
        
        var capeCounter = new Element('input', {'type':'hidden', 'name': 'capability_id', 'value': id});

        queryComp.appendChild(capeCounter);


        var cdt = capabilityDataTypes[ cf.capability_data_type_id ];
        
        if(cdt == 'boolean') {
            var booleanBox = new Element('div', {'class': 'boolbox'});
            
            var booleanTrueDiv = new Element('div', {'class' : 'bool'});
            var booleanTrueLabel = new Element('label');
            booleanTrueLabel.innerHTML = 'true';
            var booleanTrueRadio = new Element('input', {'type': 'radio', 'name': 'capability[boolean]',
                                               'value' : 'true', 'checked': 'true'});
            
            booleanTrueDiv.appendChild(booleanTrueLabel);
            booleanTrueDiv.appendChild(booleanTrueRadio);
            
            var booleanFalseDiv = new Element('div', {'class' : 'bool'});
            var booleanFalseLabel = new Element('label');
            booleanFalseLabel.innerHTML = 'false';
            var booleanFalseRadio = new Element('input', {'type': 'radio', 'name': 'capability[boolean]',
                                               'value' : 'false'});
            
            booleanFalseDiv.appendChild(booleanFalseLabel);
            booleanFalseDiv.appendChild(booleanFalseRadio);
            
            booleanBox.appendChild(booleanTrueDiv);
            booleanBox.appendChild(booleanFalseDiv);
            
            queryComp.appendChild(booleanBox);
            queryComp.appendChild(new Element('div', {'class': 'clearit'}));
        }
        else if(cdt == 'date') {
                var greaterThanBox = new Element('div', {'class': 'cleared'});
                var greaterThanTop = new Element('div');
                var greaterThanBottom = new Element('div', {'class': 'description'});
                var greaterThanLabel = new Element('label');
                greaterThanLabel.innerHTML = 'after date';
                var greaterThanInput = new Element('input', {'type' : 'text', 'name': 'capability[greater]'});
                greaterThanTop.appendChild(greaterThanLabel);
                greaterThanTop.appendChild(greaterThanInput);
                greaterThanBottom.innerHTML = 'Format: YYYY/MM';
                greaterThanBox.appendChild(greaterThanTop);
                greaterThanBox.appendChild(greaterThanBottom);
                
                
                var lessThanBox = new Element('div', {'class': 'cleared'});
                var lessThanTop = new Element('div');
                var lessThanBottom = new Element('div', {'class': 'description'});
                var lessThanLabel = new Element('label');
                lessThanLabel.innerHTML = 'prior to date';
                var lessThanInput = new Element('input', {'type' : 'text', 'name': 'capability[less]'});
                lessThanTop.appendChild(lessThanLabel);
                lessThanTop.appendChild(lessThanInput);
                lessThanBottom.innerHTML = 'Format: YYYY/MM';
                lessThanBox.appendChild(lessThanTop);
                lessThanBox.appendChild(lessThanBottom);
                
                queryComp.appendChild(greaterThanBox);
                queryComp.appendChild(lessThanBox);                
        }
        else if( cdt == 'number' )
        {
            var equalBox = new Element('div', {'class': 'equalBox cleared'});
            var equalLabel = new Element('label');
            equalLabel.innerHTML = 'Is equal to ';
            var equalField = new Element('input', {'type': 'text', 'name': 'capability[equal]'});
            if(initVal != undefined && initVal != null) {
                equalField.value = initVal;
            }
            equalBox.appendChild(equalLabel);
            equalBox.appendChild(equalField);
            queryComp.appendChild(equalBox);
        
            var greaterThanBox = new Element('div', {'class': 'cleared'});
            var greaterThanTop = new Element('div');
            var greaterThanBottom = new Element('div', {'class': 'description'});
            var greaterThanLabel = new Element('label');
            greaterThanLabel.innerHTML = 'Is greater than ';
            var greaterThanInput = new Element('input', {'type' : 'text', 'name': 'capability[greater]'});
            greaterThanTop.appendChild(greaterThanLabel);
            greaterThanTop.appendChild(greaterThanInput);
            greaterThanBottom.innerHTML = 'minimum: ' + cf.minimum;
            greaterThanBox.appendChild(greaterThanTop);
            greaterThanBox.appendChild(greaterThanBottom);
            
            
            
            var lessThanBox = new Element('div', {'class': 'cleared'});
            var lessThanTop = new Element('div');
            var lessThanBottom = new Element('div', {'class': 'description'});
            var lessThanLabel = new Element('label');
            lessThanLabel.innerHTML = 'Is less than ';
            var lessThanInput = new Element('input', {'type' : 'text', 'name': 'capability[less]'});
            lessThanTop.appendChild(lessThanLabel);
            lessThanTop.appendChild(lessThanInput);
            lessThanBottom.innerHTML = 'maximum: ' + cf.maximum;
            lessThanBox.appendChild(lessThanTop);
            lessThanBox.appendChild(lessThanBottom);
            
            queryComp.appendChild(greaterThanBox);
            queryComp.appendChild(lessThanBox);
        }
        else {
            // Assume Text
            
            var equalBox = new Element('div', {'class': 'equalBox'});
            var equalLabel = new Element('label');
            equalLabel.innerHTML = 'Search String ';
            var equalField = new Element('input', {'type': 'text', 'name': 'capability[like]'});
            if(initVal != undefined && initVal != null) {
                equalField.value = initVal;
            }
            equalBox.appendChild(equalLabel);
            equalBox.appendChild(equalField);
            queryComp.appendChild(equalBox);

        }
    
        showCapabilityEdit({ 'capability_name' : capability_name,
                           'group_name' : group_name,
                           'capability_data_type' : cdt,
                           'queryControl' : queryComp}); 
        
    }
    else {
        throw('Could not find capability for '+id);
    }
}

function removeCapability(id)
{
    var cid = 'capability-' + id;
    if( $(cid) ) {
        $(cid).parentNode.removeChild( $(cid) );
    }
}

function capabilityInCriteria( capability_id )
{
    var rv = false;
    var cids = document.getElementsByName('capability[ids][]');
    for(var i =0, c= null; c = cids[i]; i++) {
        if(c.value == capability_id) {
            rv = true;
            break;
        }
    }
    return rv;
}

// Add capability to the query preview.
function addCapabilityToQuery(fobj)
{
    try{
        
        var ci = document.getElementsByName('capability[ids][]');
        if(ci.length > 4 )
        {
            alert('Maximum of five capabilities');
        }
        else if(!isNaN(fobj.capability_id.value))
        {
            var criteriaHolder = $('criteriaHolder');
            var capability_id = fobj.capability_id.value;
            var cname = capabilitiesFull[capability_id].name.replace(/_/g, ' ');                
            
            if(!capabilityInCriteria(capability_id))
            {
                var capability_id_obj = new Element('input', {'type': 'hidden', 'name': 'capability[ids][]', 'value': capability_id});
                criteriaHolder.appendChild(capability_id_obj);
                $('criteriaColumns').innerHTML += ', &quot;' + cname + '&quot;';
            }
            else {
                // Reinserting capability, remove any existing criteria.
                var spans = $$('#criteriaParams input[value="'+ capability_id +'"]');
                for(var j = 0, spa = null; spa = spans[j]; j++) {
                    spa.parentNode.parentNode.removeChild(spa.parentNode);    
                }
                
                // Remove the hidden inputs
                ['greater', 'boolean', 'equal', 'less', 'like'].each(function(inptype) {
                    var inps = $$('#criteriaHolder input[name="capability['+capability_id+']['+inptype+']"]');
                    inps.each(function(inp){ inp.parentNode.removeChild(inp); })
                });
            }
            
        
            if(fobj.cape_apply_criteria.checked) {
                var criteria = [];
                // for( var name in fobj ) {
                for(var j = 0, obj = null; obj = fobj[j]; j++) {
                    if(obj.name)
                    {
                        var name = obj.name;
                        if(name != 'capability_id' &&  name.match(/capability/) && criteria.indexOf(name) == -1)
                        {
                            var cape = fobj[name];
                            var string = '';
        
                            cape_name = 'capability[' + capability_id +']';
                            cape_value = '';
                            
                            if(name.match(/boolean/)) {
                                cape_name += '[boolean]';
                                for(var i=0, bool = null; bool = cape[i]; i++)
                                {
                                    if(bool.checked) {
                                        string += ' is ' + bool.value;
                                        cape_value = bool.value; 
                                    }
                                }
                            }
                            else if(cape.value && cape.value.length > 0) {
                                
                                cape_value = cape.value;
                                
                                if(name.match(/greater/)) {
                                    cape_name += '[greater]';
                                    string += ' is greater than ' + cape.value;
                                }
                                else if(name.match(/less/)) {
                                    cape_name += '[less]';
                                    string += ' is less than ' + cape.value;
                                }
                                else if(name.match(/equal/)) {
                                    cape_name += '[equal]';
                                    string += ' is equal to ' + cape.value;
                                }
                                else {
                                    cape_name += '[like]';
                                    string += ' is like &quot;' + cape.value + '&quot;';
                                }
                                
                            }
                            
                            // Add this criteria to the criterias set during this pass.  Helps to
                            // avoid checking the boolean values twice. 
                            criteria.push(name);
                            if(string.length > 0)
                            {
                                $('criteriaWhere').show();
                                var li = new Element('li');
                                $('criteriaParams').appendChild(li);
                                li.innerHTML = '<span class="criteriaName">' + cname + '</span> ' + string
                                            + '<input type="hidden" name="critlist" value="' + capability_id + '" />';
                                
                                var cape_criteria = new Element('input', {'type': 'hidden', 'value': cape_value, 'name': cape_name});
                                document.getElementById('criteriaHolder').appendChild(cape_criteria);
                                
                           }
                        }
                    }
                }
            }
            
            hideCapabilityEdit();
        
        }
    } catch(e) {
        console.log(e.message);
    }
    return false;
    
}



/*
 * Web Service Stuff....
 */
function searchWurfl()
{
    var caps = document.getElementsByName('capability[ids][]');
    if(caps.length > 10)
    {
        alert('10 Capabilities Maximum.');
    }
    else if( !renderedChart )
    {
        showLoadingScreen();
        loadMessage('Processing Request');
        var r = Math.random();
        selectedCapabilities = new Object();
        var parameters = 'operation=search';
        var cf = $('criteriaForm').elements;
        for(var i=0; c=cf[i];i++)
        {
            parameters += '&';
            if(c.type.toLowerCase() == 'radio') {
                if(c.checked)
                {
                    parameters += c.name + '=' + c.value;
                }
            }
            else
            {
                parameters += c.name + '=' + c.value;
            }
            
            if(c.name == 'capability[ids][]')
            {
                selectedCapabilities[c.value] = 1;
            }
        }
        
        try{
            var pageTracker = _gat._getTracker("UA-890822-5");
            pageTracker._trackPageview("/wurfl/device_search.html");
        }
        catch(e) {
            if(console && console.log) {
                //console.log(e.message);
            }
        }
        
        new Ajax.Request('./processor.php?r='+r, {
                'method': 'post',
                'parameters': parameters,
                'onSuccess': function (response) {
                    // $('destination').innerHTML = '';
                    loadMessage('Building Results');
                    var result = response.responseJSON.result;

                    if(result != null) {
                        
                        
                        hideLoadingScreen();
                        
                        if(result.devices.length < 1) {
                            alert('The query returned no devices.');
                        }
                        else {
                            
                            renderedChart = true;

          
                            $('chartGoesHere').innerHTML = '';
                            
                            
                            // build the dhtmlx chart...
                            
                            var columns = ['brand name', 'model name', 'user agent'];
                            for( var i = 0, col = null; col = result.columns[i]; i++) {
                                if( columns.indexOf(col) == -1) {
                                    columns.push( col );
                                }
                            }
                            
                            var colHeader = '', colAlign = '', colWidths = '', colTypes = '',
                                colSort= '', colFilter =''; 
                            for(var i =0, col = null; col = columns[i]; i++)
                            {
                                if(i > 0) {
                                    colHeader += ',';
                                    colAlign += ',';
                                    colWidths += ',';
                                    colTypes += ',';
                                    colSort += ',';
                                    colFilter += ',';
                                }
                                
                                colHeader += col;
                                if(i > 2) {
                                    colAlign += 'right';
                                }
                                else {
                                    colAlign += 'left';
                                }
                                if(col == 'brand name') {
                                    colWidths += '80';
                                }
                                else {
                                    colWidths += '*';
                                }
                                colTypes += 'ro';
                                colSort += 'str';
                                colFilter += '#text_filter';
                                
                            }
                            
                            
                            buildChart(colHeader, colAlign, colWidths, colTypes, colSort, colFilter);
    
    
                            
    
                            // Add data to the chart...
                            var masterObj = {'rows': []};
                            for(var id in result.devices)
                            {
                                var row = result.devices[id]; 
                                var obj = {'id' : id, data:[] };
                                
                                for(var j = 0, col = null; col = columns[j]; j++) {
                                    obj.data.push( row[ col ]);    
                                }
                                masterObj.rows.push( obj );
                                
                            }
                                                    
                            dchart.parse(masterObj, 'json');
                            
                            $('columnQuery').className = 'columnOneThird';
                            new Effect.Move('slab', {x: -526});
                        }
                    
                    }
                    hideLoadingScreen();
                    
                }
            });
    }
    
    return false;
}


function buildChart(colHeader, colAlign, colWidths, colTypes, colSort, colFilter)
{

    dchart = new dhtmlXGridObject('chartGoesHere');
    dchart.setImagePath("js/dhtmlxgrid/imgs/");
    dchart.setHeader( colHeader );
    dchart.attachHeader( colFilter );
    dchart.setInitWidths( colWidths );
    dchart.setColAlign( colAlign );
    dchart.setColTypes( colTypes );
    dchart.setColSorting( colSort );
    dchart.setSkin("dhx_skyblue");
    dchart.init();    
    dchart.enableSmartRendering(true, '200');
    
}


function describeDevice(id)
{
    if(id != undefined && id != null)
    {
        
        if($('desc-'+id).innerHTML.length > 5) {
            $('desc-'+id).innerHTML = '';
        }
        else
        {
            showLoadingScreen();
            loadMessage('Sending Request');
            var r = Math.random();        
            new Ajax.Request('./processor.php?r='+r, {
                    'method': 'post',
                    'parameters': {'operation': 'describeDevice', 'deviceId': id},
                    'onSuccess': function (response) {
                            var res = response.responseJSON.result;
                            var id = response.responseJSON.id;
                            
                            loadMessage('Building Results');
                            if(res != null)
                            {
                                $('desc-'+id).innerHTML = '';
                                for(var j = 0; key = res.keys[j]; j++)
                                {
                                    var r = res.capabilities[key];
                                    
                                    var statClass = 'stat';
                                    if (selectedCapabilities[key] != null && selectedCapabilities[key] != undefined && selectedCapabilities[key] == 1)
                                    {
                                        statClass += ' selected';
                                    }
                                    var par = new Element('div', {'class': statClass});                                
                                    var name = new Element('div', {'class': 'name'});
                                    name.innerHTML = r.name;
                                    var value = new Element('div', {'class': 'value'});
                                    value.innerHTML = r.value;
                                    
                                    par.appendChild(name);
                                    par.appendChild(value);
                                    par.appendChild( new Element('div', {'class': 'clearit'}) );
                                    
                                    
                                    
                                    $('desc-'+id).appendChild( par );
                                    
                                }
                            }
                            
                            hideLoadingScreen();
                        
                        }
                });
        }
    }
    return false;
}


/*
 * Generate the external file
 */


function output( type )
{
    if(type == 'CSV') {
        try{
            var pageTracker = _gat._getTracker("UA-890822-5");
            pageTracker._trackPageview("/wurfl/export_"+type+".html");
        }
        catch(e) { }
        $('output_type').value = 'csv';
        $('output_data').value = dchart.serializeToCSV();
        $('ext_form').submit();
    }
    /*
    else if(type == 'XML') {
        $('output_type').value = 'xml';
        $('output_data').value = dchart.serialize();
        $('ext_form').submit();
    }
    */
    return false;
}




function getOutput(outputType)
{
    $('outputType').value = outputType;
        
    if(validateResultForm())
    {
        try{
            var pageTracker = _gat._getTracker("UA-890822-5");
            pageTracker._trackPageview("/wurfl/export_"+outputType+".html");
        }
        catch(e) {
            if(console && console.log) {
                // console.log(e.message);
            }
        }
        
        var capString = '';
        for(var j in selectedCapabilities) {
            if(capString.length>0) {
                capString += ',';
            }
            capString += j+'';    
        }
        $('selectedCapabilities').value = capString;
        $('results').submit();
    }
    return false;
}

function validateResultForm(formObj)
{
    if($('outputType').value.length < 1)
    {
        alert('Internal form error, please resubmit');
        return false;
    }
    
    var devs = document.getElementsByName('device[]');
    if(devs.length < 1)
    {
        return false;
    }
    
    return true;
}



