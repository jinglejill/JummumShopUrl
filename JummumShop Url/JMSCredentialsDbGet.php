<?php
    include_once("dbConnect.php");//
    setConnectionValue('FFD');
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
//    function printAllPostCredential()
//    {
//        global $con;
//        $paramAndValue;
//        $i = 0;
//        foreach ($_POST as $param_name => $param_val)
//        {
//            if($i == 0)
//            {
//                $paramAndValue = "Param=Value: ";
//            }
//            $paramAndValue .= "$param_name=$param_val&";
//            $_POST['$param_name'] = mysqli_real_escape_string($con,$param_val);
//            $i++;
//        }
//
//        if(sizeof($_POST) > 0)
//        {
//            writeToLog($paramAndValue);
//        }
//    }
//
//    function writeToLog($message)
//    {
//        global $globalDBName;
//        $year = date("Y");
//        $month = date("m");
//        $day = date("d");
//        $path = './CredentialTransactionLog/';
//        $file = 'transactionLog' . $year . $month . $day . '.log';
//
//
//        if (!file_exists($path)) {
//            mkdir($path, 0777, true);
//        }
//        $path = $path . $file;
//
//
//
//        if ($fp = fopen($path, 'at'))
//        {
//            fwrite($fp, date('c') . ' ' . $message . PHP_EOL);
//            fclose($fp);
//        }
//    }
//
//    function getSelectedRow($sql)
//    {
//        global $con;
//        if ($result = mysqli_query($con, $sql))
//        {
//            $resultArray = array();
//            $tempArray = array();
//
//            while($row = mysqli_fetch_array($result))
//            {
//                $tempArray = $row;
//                array_push($resultArray, $tempArray);
//            }
//            mysqli_free_result($result);
//        }
//        if(sizeof($resultArray) == 0)
//        {
//            $error = "query: selected row count = 0, sql: " . $sql . ", modified user: " . $username;
//            writeToLog($error);
//        }
//        else
//        {
//            writeToLog("query success, sql: " . $sql . ", modified user: " . $username);
//        }
//
//        return $resultArray;
//    }
    
    
    
    if (isset ($_POST["username"]))
    {
        $username = $_POST["username"];
        $deviceToken = $_POST["modifiedDeviceToken"];
    }
    else
    {
        $username = "MAMARIN7";
        $deviceToken = "test";
    }
    
    
    writeToLog("device token: " . $deviceToken);
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    
    
    $sql = "select * from credentials where username = '" . $username . "'";
    $selectedRow = getSelectedRow($sql);
    $credentialsID = $selectedRow[0]["CredentialsID"];
    
    
    $sql = "select credentialsdb.*,branch.BranchID,branch.Name from credentialsdb left join branch on credentialsdb.dbName = branch.dbName  where credentialsdb.credentialsID = $credentialsID and (branch.status = 1 or branch.status = 2)";
    $jsonEncode = executeMultiQuery($sql);
    echo $jsonEncode;
    
    
    
    // Close connections
    mysqli_close($con);
    
    ?>
