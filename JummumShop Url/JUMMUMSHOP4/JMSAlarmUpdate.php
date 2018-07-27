<?php
    include_once("dbConnect.php");
    setConnectionValue("FFD");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    

    if(isset($_POST["branchID"]) && isset($_POST["alarmShop"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $branchID = $_POST["branchID"];
        $alarmShop = $_POST["alarmShop"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    

    
    $sql = "update branch set alarmShop = '$alarmShop', modifiedUser = '$modifiedUser', modifiedDate = '$modifiedDate' where branchID = '$branchID'";
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
    mysqli_close($con);
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql);
    echo json_encode($response);
    exit();
?>
