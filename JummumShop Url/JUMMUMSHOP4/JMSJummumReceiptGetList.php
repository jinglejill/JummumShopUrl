<?php
    include_once("dbConnect.php");
    setConnectionValue("JUMMUM4");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    

    if(isset($_POST["receiptID"]) && isset($_POST["branchID"]))
    {
        $receiptID = $_POST["receiptID"];
        $branchID = $_POST["branchID"];
    }
    else
    {
        $receiptID = $_GET["receiptID"];
        $branchID = $_GET["branchID"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    $sql = "select '$branchID' BranchID, Receipt.*, 1 IdInserted from Receipt where ReceiptID = '$receiptID';";
    $sql .= "select '$branchID' BranchID, OrderTaking.*, 1 IdInserted from OrderTaking where ReceiptID = '$receiptID';";
    $sql .= "select '$branchID' BranchID, OrderNote.*, 1 IdInserted from OrderNote where OrderTakingID in (select orderTakingID from OrderTaking where ReceiptID = '$receiptID');";
    $sql .= "select Dispute.*, 1 IdInserted from Dispute where ReceiptID = '$receiptID';";
    writeToLog($sql);
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQuery($sql);
    echo $jsonEncode;
    
    
    
    // Close connections
    mysqli_close($con);
?>
