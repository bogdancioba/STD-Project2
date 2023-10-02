<?php
session_start(); // leg cu php
ini_set('display_errors', 1); // am avut erori
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$formResult = $_SESSION['formResult'] ?? null;

include 'process.php'; // Include process.php here
?>

<!DOCTYPE html>
<html>
<head>
    <title>Form Recognizer</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url('back.jpg');
            background-size: cover;
            background-repeat: no-repeat;
        }


        .container {
            text-align: center;
            padding: 40px;
            background-color: #2c3e50;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            color: #ecf0f1;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #3498db;
        }

        input[type="file"] {
            display: block;
            margin: 0 auto 20px;
            padding: 10px;
            border: none;
            background-color: #3498db;
            color: #ecf0f1;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"] {
            padding: 10px 20px;
            border: none;
            background-color: #e74c3c;
            color: #ecf0f1;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #c0392b;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            color:#ecf0f1;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            color:#ecf0f1;
        }

        th {
            background-color: #3498db;
            color: #ecf0f1;
        }
 
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload your form</h1>
        <form action="index.php" method="post" enctype="multipart/form-data">
            <input type="file" name="fileToUpload" id="fileToUpload">
            <input type="submit" value="Find results" name="submit">
        </form>
    </div>

<?php

set_time_limit(300);  // Increase time limit to 5 minutes - timp sa faca ambele request uri


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//autoload cu site ul
require 'vendor/autoload.php'; // sdk

use MicrosoftAzure\Storage\Blob\BlobRestProxy; //sdk
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException; //sdk
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions; //sdk

//storage account
$connectionString = 'DefaultEndpointsProtocol=https;AccountName=formrecstann;AccountKey=zkSKj7faOCtJDhdBz881LouMaXzTxOetUuvmfV0+dSY58gJhJ3OZqVgeQvvpPheaIVmZHgttUuxW+AStg7xE/A==;EndpointSuffix=core.windows.net';
//conexiunea cu blobul din storage account
$blobClient = BlobRestProxy::createBlobService($connectionString);

$containerName = 'formrecbs';

$formResult = null;

if (isset($_POST['submit'])) { //daca vede o metoda de post de tip submit face ceva
    $file = $_FILES['fileToUpload']; // ia fisierele din upload
    $blobName = $file['name']; // numele din fisier



    try {
        $content = fopen($file['tmp_name'], "r");
        $options = new CreateBlockBlobOptions();
        
        $blobClient->createBlockBlob($containerName, $blobName, $content, $options); // deschide efectiv blob ul

        $formUrl = $blobClient->getBlobUrl($containerName, $blobName); // ia link ul catre unde stocheaza info in blob

        $subscriptionKey = '8d6e887c9a3c4280a7a02450790e6a84'; //cheia pt form rec
        //folosesc un mod predefinit pentru recunoasterea documentelor
        $endpoint = 'https://formrecappa.cognitiveservices.azure.com/formrecognizer/documentModels/prebuilt-layout:analyze?api-version=2022-08-31';//endpoint catre procesul de recunoastrere
        
        //curl = comanda in cmd

        $requestBody = [
            'urlSource' => $formUrl // ala pt blob
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Ocp-Apim-Subscription-Key: ' . $subscriptionKey //adaug in parametru cheia pt formrec
        ];
        
        $curl = curl_init($endpoint); //initializeaza crearea de curl

        //setopt = set option, aleg parametrii comenzii
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($requestBody)); // adaug url ul
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); //adaug header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //se pune mereu
        curl_setopt($curl, CURLOPT_HEADER, true); //se pune mereu
        


        $response = curl_exec($curl); // se executa curl ul
        echo $response;

        
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE); //dupa ce primesc info de la curl vad cat de mare e header ul
       //iau info din primul curl pt al doilea req
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        //nu putem face direct req pt form rec, deoarece avem de primul arg din op loc, care e raspunsul din json ul de la primul req
        preg_match("/operation-location: (.*)\r\n/i", $header, $matches);
        $operationLocation = trim($matches[1]);
//testez
        if ($operationLocation) {
            curl_close($curl); //inchidem primul curl

            sleep(10); // astept sa incarce toate info

            $curl = curl_init($operationLocation); //aici fac noua comanda curl in fct de rasp de la op loc
            $getHeaders = [
                'Ocp-Apim-Subscription-Key: ' . $subscriptionKey
            ];
            

            curl_setopt($curl, CURLOPT_HTTPHEADER, $getHeaders);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_VERBOSE, true);
            
            $formResult = curl_exec($curl);

            // conectarea cu server db
            $serverName = 'tcp:anam2308.database.windows.net,1433';
            $databaseName = 'formrecdbann';
            $username = 'ana.m';
            $password = 'tema3std#';

            $conn = new PDO("sqlsrv:server = $serverName; Database = $databaseName", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//rezultatul dupa curl exec vine mereu in json, pt a lua mai usor datele, facem o decodificare si fac vector cu toate info din el
            try {
            $formResultArray = json_decode($formResult, true);

            // Extract the specific data you want to store ==== iau ce informatii am nev din json
            $status = $formResultArray['status'];
            $createdDateTime = $formResultArray['createdDateTime'];
            $lastUpdatedDateTime = $formResultArray['lastUpdatedDateTime'];
            $content = $formResultArray['analyzeResult']['content'];
            
            // Prepare an SQL statement for inserting the data
            $stmt = $conn->prepare("INSERT INTO Metadata (fisier, link, data, analiza) VALUES (?, ?, ?, ?)"); 
            // Create a DateTime object with the current date and time 
            $dateTime = new DateTime(); 
            // Add 3 hours to the DateTime object 
            $dateTime->modify('+3 hours'); 
            // Convert the DateTime object back to a string in the desired format 
            $dateString = $dateTime->format('Y-m-d H:i:s'); 
            // Bind the parameters to the SQL query and execute it 
            $stmt->execute([$blobName, $formUrl, $dateString, $content]); // aici se face efectiv insert ul pregatit mai sus
            
                // Now the processing result is saved in the database
            } catch (PDOException $e) {
                die("Error inserting into SQL Server: " . $e->getMessage());
            }

            //eroare daca curl ul are erori, daca nu se salveaza rezultatul intr o variabila globala
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

        <h2>Processing Results</h2>
        <div id="results">
            <?php
            // Display the form processing result
            if (!empty($_SESSION['formResult'])) {
                $formResultArray = json_decode($_SESSION['formResult'], true);

                // Check if the 'content' key exists in the result array
                if (isset($formResultArray['analyzeResult']['content'])) {
                    $content = $formResultArray['analyzeResult']['content'];

                    echo "<table>";
                    echo "<tr><th>Content</th></tr>";
                    echo "<tr><td>$content</td></tr>";
                    echo "</table>";
                } else {
                    echo "No processing results available.";
                }
            } else {
                echo "No processing results available.";
            }
            ?>
        </div>

        <h2>Action History</h2>
        <div>
            <table>
                 <tr>
                    <th>Name File</th>
                    <th>Link</th>
                    <th>Date</th>
                    <th>Analysis Result</th>
                </tr>
            <?php echo $processResult; ?>
            </table>
</div>



</body>
</html>
