<?php
    include_once("dbConnect.php");
    setConnectionValue($_POST["dbName"]);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["username"]) && isset($_POST["password"]))
    {
        $username = $_POST["username"];
        $password = $_POST["password"];
    }
    if(isset($_POST["logInID"]) && isset($_POST["username"]) && isset($_POST["status"]) && isset($_POST["deviceToken"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $logInID = $_POST["logInID"];
        $username = $_POST["username"];
        $status = $_POST["status"];
        $deviceToken = $_POST["deviceToken"];
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
    
    
    
    
    $sql = "select * from UserAccount where username = '$username' and password = '$password'";
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow)==0)
    {
        //download UserAccount
        $sql = "select * from UserAccount where 0";
        writeToLog("sql = " . $sql);
        
        
        /* execute multi query */
        $dataJson = executeMultiQueryArray($sql);
        
        
        
        writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('status' => '1', 'sql' => $sql , 'tableName' => 'UserAccountValidate', 'dataJson' => $dataJson);
        echo json_encode($response);
        exit();
    }
    
    
    
    
    
    //login--------------------
    //query statement
    $sql = "INSERT INTO LogIn(Username, Status, DeviceToken, ModifiedUser, ModifiedDate) VALUES ('$username', '$status', '$deviceToken', '$modifiedUser', '$modifiedDate')";
    $ret = doQueryTask($sql);
    if($ret != "")
    {
        mysqli_rollback($con);
//        putAlertToDevice();
        echo json_encode($ret);
        exit();
    }
    //-----
    
    
    
    

    //userAccount
    $sql = "select *, 1 IdInserted from UserAccount where username = '$username' and password = '$password';";
    $selectedRow = getSelectedRow($sql);
    $userAccountID = $selectedRow[0]["UserAccountID"];
    $sqlAll = $sql;
    
    
    
    //receipt
    $sql = "select * from receipt where memberID = '$userAccountID' order by receipt.ReceiptDate DESC, receipt.ReceiptID DESC limit 10;";
    $selectedRow = getSelectedRow($sql);
    
    
    $receiptIDList = array();
    for($i=0; $i<sizeof($selectedRow); $i++)
    {
        array_push($receiptIDList,$selectedRow[$i]["ReceiptID"]);
    }
    if(sizeof($receiptIDList) > 0)
    {
        $receiptIDListInText = $receiptIDList[0];
        for($i=1; $i<sizeof($receiptIDList); $i++)
        {
            $receiptIDListInText .= "," . $receiptIDList[$i];
        }
    }
    $sqlAll .= $sql;
    
    
    
    //orderTaking
    $sql = "select * from OrderTaking where receiptID in ($receiptIDListInText);";
    $selectedRow = getSelectedRow($sql);
    $sqlAll .= $sql;
    
    
    //menu
    if(sizeof($selectedRow)>0)
    {
        
        $menuID = $selectedRow[0]["MenuID"];
        $branchID = $selectedRow[0]["BranchID"];
        $sql2 = "select * from OM.branch where branchID = '$branchID'";
        $selectedRow2 = getSelectedRow($sql2);
        $eachDbName = $selectedRow2[0]["DbName"];
        $mainBranchID = $selectedRow2[0]["MainBranchID"];
        if($branchID == $mainBranchID)
        {
            $sql = "select '$mainBranchID' BranchID, Menu.* from $eachDbName.Menu where menuID = '$menuID'";
        }
        else
        {
            $sql2 = "select * from OM.branch where branchID = '$mainBranchID'";
            $selectedRow2 = getSelectedRow($sql2);
            $eachDbName = $selectedRow2[0]["DbName"];
            $mainBranchID = $selectedRow2[0]["MainBranchID"];
            $sql = "select '$mainBranchID' BranchID, Menu.* from $eachDbName.Menu where menuID = '$menuID'";
        }
        for($i=1; $i<sizeof($selectedRow); $i++)
        {
            $menuID = $selectedRow[$i]["MenuID"];
            $branchID = $selectedRow[$i]["BranchID"];
            $sql2 = "select * from OM.branch where branchID = '$branchID'";
            $selectedRow2 = getSelectedRow($sql2);
            $eachDbName = $selectedRow2[0]["DbName"];
            $mainBranchID = $selectedRow2[0]["MainBranchID"];
            if($branchID == $mainBranchID)
            {
                $sql .= " union select '$mainBranchID' BranchID, Menu.* from $eachDbName.Menu where menuID = '$menuID'";
            }
            else
            {
                $sql2 = "select * from OM.branch where branchID = '$mainBranchID'";
                $selectedRow2 = getSelectedRow($sql2);
                $eachDbName = $selectedRow2[0]["DbName"];
                $mainBranchID = $selectedRow2[0]["MainBranchID"];
                $sql .= " union select '$mainBranchID' BranchID, Menu.* from $eachDbName.Menu where menuID = '$menuID'";
            }
        }
        $sql .= ";";
    }
    $sqlAll .= $sql;
    
    
    
    //orderNote
    $sql = "select * from OrderNote where orderTakingID in (select orderTakingID from OrderTaking where receiptID in ($receiptIDListInText));";
    $sqlAll .= $sql;
    
    
    
    //note
    $sql = "select * from note where noteID in (select NoteID from OrderNote where orderTakingID in (select orderTakingID from OrderTaking where receiptID in ($receiptIDListInText)));";
    $sqlAll .= $sql;
    
    
    
    //noteType
    $sql = "select distinct noteType.* from note left join noteType on note.noteTypeID = noteType.noteTypeID where noteID in (select NoteID from OrderNote where orderTakingID in (select orderTakingID from OrderTaking where receiptID in ($receiptIDListInText)));";
    $sqlAll .= $sql;
    
    
    
    
    
    /* execute multi query */
    $dataJson = executeMultiQueryArray($sqlAll);
    
    
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'UserAccountValidate', dataJson => $dataJson);
    echo json_encode($response);
    exit();
?>
