<?php

require '../config/init.php';


if(isset($_POST['output_type']) && isset($_POST['output_data']))
{
    if(isset($_SESSION['last_seen']) && ($_SESSION['last_seen'] > strtotime("-1 day")))
    {
        
        if($_POST['output_type'] == 'csv')
        {
            header('Content-type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename=wurflResult.csv');
        }
        
        $od = $_POST['output_data'];
        if(get_magic_quotes_gpc())
        {
            $od = stripslashes($od);
        }
        
        print $od;


    }

}
