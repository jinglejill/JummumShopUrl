<?php
    include_once("dbConnect.php");
    setConnectionValue($_POST["dbName"]);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    $dbName = $_POST["dbName"];
    

    if(isset($_POST["turnLight"]))
    {
        $turnLight = $_POST["turnLight"];
    }

    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    

    $sql = "select * from FFD.Branch where DbName = '$dbName'";
    $selectedRow = getSelectedRow($sql);
    $urlNoti = $selectedRow[0]["UrlNoti"];
    $alarmShop = $selectedRow[0]["AlarmShop"];
    if($turnLight == "on")
    {
        if($alarmShop == 1)
        {
            alarmShop($urlNoti);
        }
    }
    else
    {
        if($alarmShop == 1)
        {
            alarmShopOff($urlNoti);
        }
    }
    
    

   
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql);
    echo json_encode($response);
    exit();
?>
