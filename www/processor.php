<?php

require '../config/init.php';

if(SITE_MODE == 'prod')
{
    error_reporting(E_ERROR);
}


                
if(isset($_POST['operation']) && $_POST['operation'])
{
    switch($_POST['operation'])
    {
        
        case 'search' :
            $capability = isset($_POST['capability']) ? $_POST['capability'] : array();
            processor(search($capability));
            break;
        
        
        case 'describeDevice' :
            processor(describeDevice($_POST['deviceId']));
            break;
        
    }
}


/*
 *
 * Functions
 * 
 */


/**
 * Export a json object...
 *
 * @param   array   $result
 * 
 */
function processor($result)
{
    header('Content-type: application/json');
    print json_encode($result);
}

/**
 * Return devices based on search parameters...
 *
 * @param   array   $capabilities
 * @return  array
 */
function search($capabilities)
{
    // Only make the db connection after concluding there's valid
    // search criteria.
    
    
    $raw = file_get_contents('../config/wurflStub.inc');
    $devices = array();
    if($raw)
    {
        $conf = unserialize($raw);
        $cf = $conf['capabilitiesFull'];
        $cdt = $conf['capability_data_types'];
        $brand_id = $conf['brand_id'];
        $model_id = $conf['model_id'];

    }
    else {
        return array('error' => 'Could not load data');
    }
    
    $count = 0;
    $joins = '';
    
    $sess = session_id();
    $sess = strtolower($sess);
    $badChars = array('#', ';', ' ', "\n", "\t", 'drop');
    $sess = str_replace($badChars, '', $sess);
    
    $brandSearchQuery = $modelSearchQuery = ""; 
    
    $tempTable = 'search' . $sess;
    
    if(empty($capabilities) || count($capabilities['ids']) < 6)
    {
                
        try {
            $dbh = new PDO("mysql:dbname=" . DB_DATABASE . ";host=" . DB_HOST, DB_USERNAME, DB_PASSWORD);
        
        
            $sql = '
                    SELECT *
                    FROM wurfl_devices wd
                ';
            $table_id = 1;
            
            if(!empty($capabilities) )
            {
                $rep_values = array();
                $brand_values = array();
                $model_values = array();
                foreach($capabilities['ids'] as $cid)
                {
                    
                    if($cid == $brand_id) {
                        if( isset($capabilities[$cid]['like'])) {
                            $v = strtolower($capabilities[$cid]['like']);
                            $v = trim($v);
                            $brandSearchQuery = " AND LOWER(value_string) LIKE(?)\n";
                            $brand_values[] = '%' . $v . '%';
                        }
                        
                    }
                    else if( $cid == $model_id) {
                        if( isset($capabilities[$cid]['like'])) {
                            $v = strtolower( $capabilities[$cid]['like']);
                            $v = trim($v);
                            $modelSearchQuery = " AND LOWER(value_string) LIKE(?)\n";
                            $model_values[] = '%' . $v . '%';
                        }
                    }
                    else
                    {
                        
                        $sub = '';
                        $cdtid = $cf[$cid]['capability_data_type_id'];
               
                        $rep_values[] = $cid;
                        switch($cdt[$cdtid]) {
                            
                            case 'number' :
                                $col = 'dc.value_number';
                                if(isset($capabilities[$cid]['equal']) &&  is_numeric($capabilities[$cid]['equal']) ) {
                                    $sub .= " AND value_number = ? \n";
                                    $rep_values[] = $capabilities[$cid]['equal'];
                                }
                                if(isset($capabilities[$cid]['greater']) && is_numeric($capabilities[$cid]['greater'])) {
                                    $sub .= " AND value_number >= ? \n";
                                    $rep_values[] = $capabilities[$cid]['greater']; 
                                }
                                if(isset($capabilities[$cid]['less']) && is_numeric($capabilities[$cid]['less'])) {
                                    $sub .= " AND value_number <= ? \n";
                                    $rep_values[] = $capabilities[$cid]['less'];
                                }
                                
                                break;
                            
                            case 'date' :
                                $col = 'from_unixtime( dc.value_number, "%Y/%m")';
                                if(isset($capabilities[$cid]['greater']) && preg_match("/\d{4}\/\d{2}/", $capabilities[$cid]['greater']))
                                {
                                    $g = trim($capabilities[$cid]['greater']);
                                    $greater = strtotime( $g . '/01' ); // First day of the month.
                                    $sub .= " AND value_number >= ? \n ";
                                    $rep_values[] = $greater; 
                                }
                                if(isset($capabilities[$cid]['less']) && preg_match("/\d{4}\/\d{2}/", $capabilities[$cid]['less']))
                                {
                                    $l = trim($capabilities[$cid]['less']);
                                    $less = strtotime( $l . '/01' ); // First day of the month.
                                    $sub .= " AND value_number <= ? \n ";
                                    $rep_values[] = $less;
                                }
                                
                                break;
                            
                            case 'boolean' :
                                $col = 'dc.value_boolean';
                                if(isset($capabilities[$cid]['boolean']) )
                                {
                                    if($capabilities[$cid]['boolean'] == 'true') {
                                        $sub = ' AND value_boolean = 1 ';
                                    }
                                    else if($capabilities[$cid]['boolean'] == 'false') {
                                        $sub = ' AND value_boolean = 0 ';
                                    }
                                }
                                break;
                            
                            case 'string' :
                            case 'hex_color' :
                                $col = 'dc.value_string';
                                if( isset($capabilities[$cid]['like'])) {
                                    $v = strtolower( $capabilities[$cid]['like'] );
                                    $v = trim($v);
                                    $sub .= " AND LOWER(value_string) LIKE(?)\n";
                                    $rep_values[] = '%' . $v . '%';
                                }
                                
                                break;
                        }
                        
                        
                        $tableName = 'temp' . $cid;
                        
                        $sql .= "
                            INNER JOIN (
                                SELECT dc.wurfl_device_id,
                                    c.id as {$tableName}_capability_id,
                                    c.name as {$tableName}_capability,
                                    $col as {$tableName}_value
                                FROM wurfl_devices wd, device_capabilities dc, capabilities c 
                                WHERE capability_id = ?
                                    AND wd.id = dc.wurfl_device_id
                                    AND wd.recent_device = 1
                                    AND c.id = dc.capability_id
                                    $sub 
                            ) $tableName 
                            ON $tableName.wurfl_device_id = wd.id
                            ";
                    }    
                                
                }
            }
            
            // always include brand and model.
            $sql .= "
                INNER JOIN (
                    SELECT dc.wurfl_device_id,
                        c.id as brand_capability_id,
                        c.name as brand_capability,
                        dc.value_string as brand_value
                    FROM wurfl_devices wd, device_capabilities dc, capabilities c 
                    WHERE capability_id = ?
                        AND wd.id = dc.wurfl_device_id
                        AND wd.recent_device = 1
                        AND c.id = dc.capability_id
                        $brandSearchQuery
                ) brand 
                ON brand.wurfl_device_id = wd.id
                INNER JOIN (
                    SELECT dc.wurfl_device_id,
                        c.id as model_capability_id,
                        c.name as model_capability,
                        dc.value_string as model_value
                    FROM wurfl_devices wd, device_capabilities dc, capabilities c 
                    WHERE capability_id = ?
                        AND wd.id = dc.wurfl_device_id
                        AND wd.recent_device = 1
                        AND c.id = dc.capability_id
                        $modelSearchQuery 
                ) model 
                ON model.wurfl_device_id = wd.id
                WHERE wd.recent_device = 1
                    AND wd.name NOT LIKE '%generic%'
                ";
            
            array_unshift($brand_values, $brand_id);
            array_unshift($model_values, $model_id);
            
            
    
            $sth = $dbh->prepare($sql);
            $start = 1; 
            foreach($rep_values as $rep_val) {
                $sth->bindParam($start++, $rep_val, getPdoType($rep_val));
            }
            
            foreach($brand_values as $brand_val) {
                $sth->bindParam($start++, $brand_val, getPdoType($brand_val));
            }
            
            foreach($model_values as $model_val) {
                $sth->bindParam($start++, $model_val, getPdoType($model_val));
            }
           
        
            if(!$sth->execute()) {
                throw new Exception("Error! " . print_r($sth->errorInfo(), true));
            }
           
           
            /*
             * retrieve results
             */
            
            $last_id = 0;
            
            $devices['result'] = array(
                        
                'devices' => array(),
                'columns' => array()
                
            );
            
            while( $row = $sth->fetch(PDO::FETCH_ASSOC) )
            {
                $devices['result']['keys'][] = $row['id'];
                // $devices['result']['devices'][$row['id']] = $row;
                
                $devices['result']['devices'][$row['id']] = array  (
                    'user agent' => $row['user_agent'],
                    'brand name' => $row['brand_value'],
                    'model name' => $row['model_value'],
                    
                );
                
                // $capArray
                if(!empty($capabilities))
                {
                    foreach( $capabilities['ids'] as $cid)
                    {
                        if($cid != $brand_id && $cid != $model_id)
                        {
                            $cName = str_replace('_', ' ', $cf[$cid]['name']);
                            
                            $cdtid = $cf[$cid]['capability_data_type_id'];
                            if( $cdt[$cdtid] == 'boolean' ) {
                            
                                $devices['result']['devices'][$row['id']][$cName] = ( $row['temp' . $cid . '_value'] == 1) ? 'true' : 'false';
                               
                            }
                            else
                            {
                                $devices['result']['devices'][$row['id']][$cName] = $row['temp' . $cid . '_value'];
                            }
                        }
                    }
                }
                
                $last_id = $row['id'];
                
            }
            
            if(!empty($devices['result']['devices'])) {
                $devices['result']['columns'] = array_keys($devices['result']['devices'][$last_id]);
            }
        
        } catch (PDOException $e) {
            $devices['error'] =  'Database error: ' . $e->getMessage();
        }           
       
               
        
        return $devices;
    }
}


/**
 * Get all capabilities for a device with the given id.
 *
 * @param   int     $id
 * @return  array
 */
function describeDevice($id)
{
    
    $device = array();
    if(is_numeric($id))
    {
        try
        {
            
            $device['id'] = $id;
            $raw = file_get_contents('../config/wurflStub.inc');
            $conf = unserialize($raw);
            $cf = $conf['capabilitiesFull'];
            $cdt = $conf['capability_data_types'];
    
            
            $sql = 'SELECT g.name AS group, c.id, c.name AS capability, cdt.name AS data_type,
                    value_string, value_number, value_boolean, value_color 
                FROM capabilities c, groups g, device_capabilities dc, capability_data_types cdt
                WHERE dc.wurfl_device_id = ?
                    AND dc.capability_id = c.id
                    AND c.group_id = g.id
                    AND c.capability_data_type_id = cdt.id
                ORDER BY g.name, c.name
            ';
            
            $dbh = new PDO("mysql:dbname=" . DB_DATABASE .";host=" . DB_HOST, DB_USERNAME, DB_PASSWORD);
            $sth = $dbh->prepare($sql);
            $sth->bindParam(1, $id, PDO::PARAM_INT);
            $sth->execute(); 
            
            
            while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                                
                switch($row['data_type'])
                {
                    
                    case 'number' :
                        $value = $row['value_number'];
                        break;
                    
                    case 'date' :
                    case 'string' :
                        $value = $row['value_string'];
                        break;
                    
                    case 'color' :
                        $value = $row['value_color'];
                        break;
                    
                    case 'boolean' :
                        $value = ($row['value_boolean'] == 't') ? 'true' : 'false';
                        break;
                    
                }
                
                $device['result']['keys'][] = $row['id'];
                $device['result']['capabilities'][ $row['id'] ] = array(
                                        
                                        'name' => $row['group'] . ' - ' . $row['capability'],
                                        'value' => $value
                                              
                                              );
            }

        }
        catch (PDOException $e) {
            $device['error'] =  ' SQL Error: ' . $e->getMessage();
        }        

    }
    
    return $device;
}



/**
 * getPdoType
 *
 * @param   ambiguous   $value
 * @return  int         PDO::PARAM_INT or PDO::PARAM_STR
 */
function getPdoType($value)
{
    return (is_numeric($value)) ? PDO::PARAM_INT : PDO::PARAM_STR;    
}

