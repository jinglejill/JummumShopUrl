<?php
    include_once("dbConnect.php");
    setConnectionValue("JUMMUM4");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    

    if(isset($_POST["branchID"]) && isset($_POST["receiptID"]) && isset($_POST["status"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $branchID = $_POST["branchID"];
        $receiptID = $_POST["receiptID"];
        $status = $_POST["status"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
        
        
        
        $modifiedDeviceToken = $_POST["modifiedDeviceToken"];
        
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    //*****ลูกค้ากดยกเลิก หรือส่งคำร้อง
    //2->7
    //5,6->8
    //7->9
    //8->10
    //*****ร้านค้ากดให้ลูกค้า -> ยกเลิก หรือส่งคำร้อง
    //2->9
    //5,6->10
    if($status == 9)
    {
        $sql = "select * from receipt where receiptID = '$receiptID' and status in ('2','7')";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow) == 0)
        {
            $alreadyDone = 1;
        }
        
        $msg = "Order cancelled";
        $category = "clear";
    }
    else if($staus == 10)
    {
        $sql = "select * from receipt where receiptID = '$receiptID' and status in ('5','6','8')";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow) == 0)
        {
            $alreadyDone = 1;
        }
        
        $msg = "Order dispute finished";
        $category = "clear";
    }
    else if($staus == 11)
    {
        $sql = "select * from receipt where receiptID = '$receiptID' and status in ('9','10')";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow) == 0)
        {
            $alreadyDone = 1;
        }
        
        $msg = "Negotiate request";
        $category = "cancelOrder";
    }
    else if($staus == 14)
    {
        $sql = "select * from receipt where receiptID = '$receiptID' and status = '13'";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow) == 0)
        {
            $alreadyDone = 1;
        }
        
        $msg = "Order dispute finished";
        $category = "clear";
    }

    writeToLog("alreadyDone: " . $alreadyDone);
    if(!$alreadyDone)
    {
        $sql = "update receipt set status = '$status', statusRoute = concat(statusRoute,',','$status'), modifiedUser = '$modifiedUser', modifiedDate = '$modifiedDate' where receiptID = '$receiptID'";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
            //        putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
    }
    
    
    
//    2,5,7,8,13
    $sql = "select receipt.* from receipt where receipt.branchID = '$branchID' and status in (2,5,7,8,13)";
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow)==0)
    {
        $sql = "select UrlNoti,AlarmShop from OM.branch where branchID = '$branchID'";
        $selectedRow = getSelectedRow($sql);
        $urlNoti = $selectedRow[0]["UrlNoti"];
        $alarmShop = $selectedRow[0]["AlarmShop"];
        if($alarmShop == 1)
        {
            //alarmShopOff
            //query statement
            $ledStatus = 0;
            $sql = "update OM.Branch set LedStatus = '$ledStatus', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where branchID = '$branchID';";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                //        putAlertToDevice();
                echo json_encode($ret);
                exit();
            }
        }
    }
    
    
    
    
    if($status == 11)
    {
        
        //get pushSync Device in jummum
        $sql = "select * from setting where KeyName = 'DeviceTokenAdmin'";
        $selectedRow = getSelectedRow($sql);
        $pushSyncDeviceTokenAdmin = $selectedRow[0]["Value"];
        $arrPushSyncDeviceTokenAdmin = array();
        array_push($arrPushSyncDeviceTokenAdmin,$pushSyncDeviceTokenAdmin);
        sendPushNotificationToDeviceWithPath($arrPushSyncDeviceTokenAdmin,'./../../JMM/JUMMUM4/','jill','negotiation arrive!',0,0,1);
        
        
        //alarm admin
        $sql = "select * from setting where keyName = 'AlarmAdmin'";
        $selectedRow = getSelectedRow($sql);
        $alarmAdmin = $selectedRow[0]["Value"];
        if(intval($alarmAdmin) == 1)
        {
            //alarmAdmin
            //query statement
            $ledStatus = 1;
            $sql = "update Setting set Value = '$ledStatus', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where KeyName = 'LedStatus';";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                //        putAlertToDevice();
                echo json_encode($ret);
                exit();
            }
        }
    }
    
    
    //push sync to other device
    $pushSyncDeviceTokenReceiveOrder = array();
    $sql = "select * from OM.device left join OM.Branch on OM.device.DbName = OM.Branch.DbName where branchID = '$branchID';";
    $selectedRow = getSelectedRow($sql);
    for($i=0; $i<sizeof($selectedRow); $i++)
    {
        $deviceToken = $selectedRow[$i]["DeviceToken"];
        array_push($pushSyncDeviceTokenReceiveOrder,$deviceToken);
    }
    
    sendPushNotificationToDeviceWithPath($pushSyncDeviceTokenReceiveOrder,'./','jill',$msg,$receiptID,$category,1);
    
    
    
    //dataJson
    $sql = "select * from Receipt where receiptID = '$receiptID';";
    $dataJson = executeMultiQueryArray($sql);
    
    
    
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'Receipt', 'dataJson' => $dataJson);
    echo json_encode($response);
    exit();
?>
