<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$formResult = $_SESSION['formResult'] ?? null;

include 'process.php'; // Include process.php here
?>

<!DOCTYPE html>
<html>
<head>
    <title>Încărcare fișier și procesare Form Recognizer</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(45deg, #6ab7a8, #3b8d99);
            min-height: 100vh;
            padding: 50px 0;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center; /* Center the boxes horizontally */
        }
        body > * {
            margin: 20px 0;
        }

        h1, h2 {
            color: #fff;
            padding: 10px 0;
        }

        form, #results, table {
            max-width: 800px;
            width: 100%;
            background-color: #ffffff;
            margin: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        input[type="file"] {
            display: none;
        }

        label {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"] {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        a {
            color: #007BFF;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
        .section-box {
            background-color: rgba(255, 255, 255, 0.1); /* Semi-transparent white for a modern look */
            padding: 20px;
            border-radius: 10px; /* Rounded corners */
            margin: 20px 0; /* Space between boxes */
            max-width: 800px; /* Max width of boxes */
            width: 90%; /* Relative width to parent, but will not exceed max-width */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
        }
    </style>
</head>
<body>
    <h1>Încarcă un fișier pentru procesare</h1>
    <div class="section-box">
    <form action="index.php" method="post" enctype="multipart/form-data">
        <label for="fileToUpload">Alege fișier</label>
        <input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="Încarcă fișier" name="submit">
    </form>
    </div>


<?php

set_time_limit(300);  // Increase time limit to 5 minutes


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require '../vendor/autoload.php';

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;

$connectionString = 'DefaultEndpointsProtocol=https;AccountName=sagabriel;AccountKey=h/rjLnAsSYuagLJTdJ3HAJkR+z8Rq40kY7Emk6eV/SjARVKYGMu8EeyFD5Z6EJy6MAbPaVtwa9SW+AStPJnMcQ==;EndpointSuffix=core.windows.net';
$blobClient = BlobRestProxy::createBlobService($connectionString);

$containerName = 'blobgabi';

$formResult = null;

if (isset($_POST['submit'])) {
    $file = $_FILES['fileToUpload'];
    $blobName = $file['name'];



    try {
        $content = fopen($file['tmp_name'], "r");
        $options = new CreateBlockBlobOptions();
        
        $blobClient->createBlockBlob($containerName, $blobName, $content, $options);

        $formUrl = $blobClient->getBlobUrl($containerName, $blobName);

        $subscriptionKey = '37ed828a168e4d3ab0f8bd069bddbe24';
        $endpoint = 'https://formgabriel.cognitiveservices.azure.com/formrecognizer/documentModels/prebuilt-layout:analyze?api-version=2022-08-31';
        
        $requestBody = [
            'urlSource' => $formUrl
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Ocp-Apim-Subscription-Key: ' . $subscriptionKey
        ];
        
        $curl = curl_init($endpoint);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        


        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        preg_match("/operation-location: (.*)\r\n/i", $header, $matches);
        $operationLocation = trim($matches[1]);

        if ($operationLocation) {
            curl_close($curl);

            sleep(10);

            $curl = curl_init($operationLocation);
            $getHeaders = [
                'Ocp-Apim-Subscription-Key: ' . $subscriptionKey
            ];
            

            curl_setopt($curl, CURLOPT_HTTPHEADER, $getHeaders);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_VERBOSE, true);
            
            $formResult = curl_exec($curl);

            $serverName = 'tcp:gabrielsv.database.windows.net,1433';  
            $databaseName = 'gabrieldb';  
            $username = 'gabriel';  
            $password = 'std-1234'; 

            $conn = new PDO("sqlsrv:server = $serverName; Database = $databaseName", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            try {
            $formResultArray = json_decode($formResult, true);

            // Extract the specific data you want to store
            $status = $formResultArray['status'];
            $createdDateTime = $formResultArray['createdDateTime'];
            $lastUpdatedDateTime = $formResultArray['lastUpdatedDateTime'];
            $content = $formResultArray['analyzeResult']['content'];
            
            // Prepare an SQL statement for inserting the data
            $stmt = $conn->prepare("INSERT INTO Date (fisier, link, data, rezultat) VALUES (?, ?, ?, ?)");

            // Bind the parameters to the SQL query and execute it
            $stmt->execute([$blobName, $formUrl, date('Y-m-d H:i:s'), $content]);
            
                // Now the processing result is saved in the database
            } catch (PDOException $e) {
                die("Error inserting into SQL Server: " . $e->getMessage());
            }

            
            if (curl_errno($curl)) {
                echo 'Curl Error: ' . curl_error($curl);
            } else {
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
                if ($httpCode !== 200) {
                    echo 'HTTP Response Error: Unexpected HTTP Code - ' . $httpCode;
                } elseif (empty($formResult)) {
                    echo 'Error: Response body is empty';
                } else {
                    // Store the form processing result in a session variable
                    $_SESSION['formResult'] = $formResult;
                }
            }
            
            curl_close($curl);
        } else {
            echo 'No operation-location found in headers';
        }
    } catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code . ": " . $error_message . "<br />";
    } catch (Exception $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code . ": " . $error_message . "<br />";
    }
}


?>

<h2>Rezultate procesare</h2>
<div class="section-box">
<div id="results">
    <?php
    // Display the form processing result
    if (!empty($_SESSION['formResult'])) {
        $formResultArray = json_decode($_SESSION['formResult'], true);

        // Check if the 'content' key exists in the result array
        if (isset($formResultArray['analyzeResult']['content'])) {
            $content = $formResultArray['analyzeResult']['content'];
            echo $content;
        } else {
            echo "Niciun rezultat de procesare disponibil.";
        }
    } else {
        echo "Niciun rezultat de procesare disponibil.";
    }
    ?>
</div>
</div>


<h2>Istoric acțiuni</h2>
<div class="section-box">
<table>
        <?php echo $processResult; ?>
</table>
</div>
</body>
</html>
