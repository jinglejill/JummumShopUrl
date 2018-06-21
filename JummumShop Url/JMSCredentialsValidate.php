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
//
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
//
//    function doQueryTask($sql)
//    {
//        global $con;
//        $user = $_POST["username"];
//        $res = mysqli_query($con,$sql);
//        if(!$res)
//        {
//            $error = "query fail, sql: " . $sql . ", modified user: " . $user . " error: " . mysqli_error($con);
//            writeToLog($error);
//            $response = array('status' => $error);
//            return $response;
//        }
//        else
//        {
//            writeToLog("query success, sql: " . $sql . ", modified user: " . $user);
//        }
//        return "";
//    }
//
//    function executeMultiQueryArrayCredentials($sql)
//    {
//        global $con;
//        if (mysqli_multi_query($con, $sql)) {
//            $arrOfTableArray = array();
//            $resultArray = array();
//            do {
//                /* store first result set */
//                if ($result = mysqli_store_result($con)) {
//                    while ($row = mysqli_fetch_object($result)) {
//                        array_push($resultArray, $row);
//                    }
//                    array_push($arrOfTableArray,$resultArray);
//                    $resultArray = [];
//                    mysqli_free_result($result);
//                }
//                if(!mysqli_more_results($con))
//                {
//                    break;
//                }
//            } while (mysqli_next_result($con));
//
//            return $arrOfTableArray;
//        }
//        return "";
//    }
    
    
    
    if (isset ($_POST["username"]) && isset ($_POST["password"]))
    {
        $username = $_POST["username"];
        $password = $_POST["password"];
        $deviceToken = $_POST["modifiedDeviceToken"];
    }
    else
    {
        $username = "test";
        $password = "test";
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
    
    
    
    //เช็คว่ามี username นี้มั๊ย -> ไม่มี alert -> username is invalid
    //เช็คว่ามี password ถูกต้องมั๊ย -> ไม่มี alert -> password is invalid
    //เช็คว่า expire หรือยัง
    //ยังไม่ expire -> insert device with this credentials
    //ถ้า else ให้ status => 2 alert -> application is expire, please contact administrator
    //2 tables นี้ ไม่ได้ใช้ในฝั่ง app เลย ใช้เพียงเช็คตอน first setup เท่านั้น
    
    $sql = "select * from Credentials where `Username` = '$username'";
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow) == 0)
    {
        writeToLog("query commit");
        mysqli_commit($con);
        mysqli_close($con);
        $response = array('status' => '2', 'sql' => $sql, 'msg' => 'Username is invalid');
        
        
        echo json_encode($response);
        exit();
    }
    
    
    $credentialsID = $selectedRow[0]["CredentialsID"];
    $strExpiredDate = $selectedRow[0]["ExpiredDate"];
    $credentialPassword = $selectedRow[0]["Password"];
    //----
    
    
    if($password != $credentialPassword)
    {
        writeToLog("query commit");
        mysqli_commit($con);
        mysqli_close($con);
        $response = array('status' => '2', 'sql' => $sql, 'msg' => 'Password is invalid');
        
        
        echo json_encode($response);
        exit();
    }
    
    
    
    $expiredDate = date('Y-m-d H:i:s',strtotime($strExpiredDate));
    $currentDate = date('Y-m-d H:i:s');
    writeToLog("expired date: " . $expiredDate);
    writeToLog("current date: " . $currentDate);
    if($currentDate >= $expiredDate)
    {
        writeToLog("query commit");
        mysqli_commit($con);
        mysqli_close($con);
        $response = array('status' => '2', 'sql' => $sql, 'msg' => 'Application is expired, please contact administrator');
        
        
        echo json_encode($response);
        exit();
    }
    //------
    
    
    
    $sql = "insert into `CredentialsDevice` (`CredentialsID`,`DeviceToken`) values ($credentialsID, '$deviceToken')";
    $ret = doQueryTask($sql);
    if($ret != "")
    {
        // Rollback transaction
        mysqli_rollback($con);
        mysqli_close($con);
//        putAlertToDevice($_POST["modifiedUser"]);
        echo json_encode($ret);
        exit();
    }
    
    
    
    
    
    
    
    
    //do script successful
    writeToLog("query commit, file: " . basename(__FILE__));
    mysqli_commit($con);
    mysqli_close($con);
    $response = array('status' => '1', 'sql' => $sql);
    
    
    echo json_encode($response);
    exit();
    ?>
