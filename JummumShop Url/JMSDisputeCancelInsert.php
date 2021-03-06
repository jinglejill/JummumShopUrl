<?php
    include_once("dbConnect.php");
    setConnectionValue("JUMMUM4");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    

    if(isset($_POST["branchID"]))
    {
        $branchID = $_POST["branchID"];
    }
    if(isset($_POST["disputeID"]) && isset($_POST["receiptID"]) && isset($_POST["disputeReasonID"]) && isset($_POST["refundAmount"]) && isset($_POST["detail"]) && isset($_POST["phoneNo"]) && isset($_POST["type"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $disputeID = $_POST["disputeID"];
        $receiptID = $_POST["receiptID"];
        $disputeReasonID = $_POST["disputeReasonID"];
        $refundAmount = $_POST["refundAmount"];
        $detail = $_POST["detail"];
        $phoneNo = $_POST["phoneNo"];
        $type = $_POST["type"];
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
    
    
    
    $sql = "select * from receipt where receiptID = '$receiptID';";
    $selectedRow = getSelectedRow($sql);
    $receiptStatus = $selectedRow[0]["Status"];
    if($receiptStatus == 2)
    {
        //dispute
        //query statement
        $sql = "INSERT INTO Dispute(ReceiptID, DisputeReasonID, RefundAmount, Detail, PhoneNo, Type, ModifiedUser, ModifiedDate) VALUES ('$receiptID', '$disputeReasonID', '$refundAmount', '$detail', '$phoneNo', '$type', '$modifiedUser', '$modifiedDate')";
        $ret = doQueryTask($sql);
        $disputeID = mysqli_insert_id($con);
        if($ret != "")
        {
            mysqli_rollback($con);
            //        putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
        $status = 9;
        
        
        
        
        //receipt
        $sql = "update receipt set status = '$status',statusRoute=concat(statusRoute,',','$status'), modifiedUser = '$modifiedUser', modifiedDate = '$modifiedDate' where receiptID = '$receiptID'";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
            //        putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
        
        
        
        
        
        
        
        //do script successful
        mysqli_commit($con);
        
        
        /* execute multi query */
        $sql = "select * from receipt where receiptID = '$receiptID';";
        $sql .= "Select * from Dispute where receiptID = '$receiptID' and disputeID = '$disputeID';";
        $dataJson = executeMultiQueryArray($sql);
        
        
        

        //push sync to other device
        $pushSyncDeviceTokenReceiveOrder = array();
        $sql = "select * from OM.device left join OM.Branch on OM.device.DbName = OM.Branch.DbName where branchID = '$branchID';";
        $selectedRow = getSelectedRow($sql);
        for($i=0; $i<sizeof($selectedRow); $i++)
        {
            $deviceToken = $selectedRow[$i]["DeviceToken"];
            array_push($pushSyncDeviceTokenReceiveOrder,$deviceToken);
        }
        
        $msg = "Order cancelled";
        $category = "clear";
        sendPushNotificationToDeviceWithPath($pushSyncDeviceTokenReceiveOrder,'./','jill',$msg,$receiptID,$category,1);
    }
    else
    {
        mysqli_commit($con);
        
        
        /* execute multi query */
        $sql = "select * from receipt where receiptID = '$receiptID';";
        $sql .= "select * from dispute where 0";
        $dataJson = executeMultiQueryArray($sql);
    }
    
    
    
    
    
    
    mysqli_close($con);
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'Receipt', 'dataJson' => $dataJson);
    echo json_encode($response);
    exit();
?>
