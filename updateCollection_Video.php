<?php

error_reporting(0);

function connectDb() {
    // Create connection
    $conn = new mysqli('localhost', 'root', '123456', 'V4');
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 
    echo "Connected successfully";
    return $conn;
}

function closeDb($conn){
    $conn->close();
}

$conn = connectDb();

echo '<hr>';

function findVideoIdByFilename($conn, $Filename, $Tag) {
    $sql = "SELECT Id, Title, SUBSTRING(JSON_UNQUOTE(Ext->'$.video_source'), 9) as `video_source` FROM V4.videos WHERE Filename='video___$Filename' LIMIT 10;";

    $result = $conn->query($sql);

    $videosId = array();

    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            //echo "id: " . $row["Id"]. " - Title: " . $row["Title"]. " " . $row["video_source"]. "<br>";
            array_push($videosId, $row["Id"]);
        }
        return $videosId[0];
    } else {
        // echo "findVideoIdByFilename: $Filename ( $Tag )";
        return NULL;
    }
}

// test only
// $videosId = findVideoIdByFilename($conn,"t91Cgg1oKZs");
// foreach ($videosId as $key => $videoId) {
//     echo $videoId;
// }

function findVideoIdByTitleAndArtist($conn, $title, $artist) {
    $sql = "SELECT Id, Title, SUBSTRING(JSON_UNQUOTE(Ext->'$.video_source'), 9) as `video_source` FROM V4.videos WHERE Title='$title' AND Artist='$artist' LIMIT 10;";

    $result = $conn->query($sql);

    $videosId = array();

    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            //echo "id: " . $row["Id"]. " - Title: " . $row["Title"]. " " . $row["video_source"]. "<br>";
            array_push($videosId, $row["Id"]);
        }
        return $videosId[0];
    } else {
        // echo "findVideoIdByTitleAndArtist: $title / $artist<br>";
        return NULL;
    }
}

// test only
// $videosId = findVideoIdByTitleAndArtist($conn,"The Great Divide","Reba McEntire");
// foreach ($videosId as $key => $videoId) {
//     echo $videoId;
// }

function findCollectionIdByName($conn, $collectName){
    $sql = "SELECT Id, Title FROM V4.collections WHERE Title='$collectName' LIMIT 10;";

    $result = $conn->query($sql);

    $Ids = array();

    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            array_push($Ids, $row["Id"]);
        }
        return $Ids[0];
    } else {
        return NULL;
    }
}

function findTagLineIdByTitle($conn, $tagLine){
    $sql = "SELECT Id, Caption FROM V4.taglines WHERE Caption='$tagLine' LIMIT 10;";

    $result = $conn->query($sql);

    $Ids = array();

    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            array_push($Ids, $row["Id"]);
        }
        return $Ids[0];
    } else {
        return NULL;
    }
}

function findGenreByTitle($conn, $input){
    $title = $input;
    
    if($title == 'Alternative/R&B'){
        $title = 'Alternative R&B';
    }
    if($title == 'Classic/R&B'){
        $title = 'Classic R&B';
    }
    if($title == 'Folks'){
        $title = 'Folk';
    }

    $sql = "SELECT Id, Title FROM V4.genres WHERE Title='$title' LIMIT 10;";
    // echo $sql;

    $result = $conn->query($sql);

    $Ids = array();

    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            array_push($Ids, $row["Id"]);
        }
        return $Ids[0];
    } else {
        // echo "not found genre with title: ";
        // echo $title;
        // newLine();
        return NULL;
    }
}

function checkVideoIdExisted($conn, $videoId, $genreId) {
    $sql = "SELECT VideoId, GenreId FROM V4.video_genre WHERE VideoId='$videoId' AND GenreId='$genreId' LIMIT 10;";

    $result = $conn->query($sql);

    $Ids = array();

    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            array_push($Ids, $row["VideoId"]);
        }
        return 1;
    } else {
        return 0;
    }
}

function findVideoFileNameFromUrl($url){
    $parts = parse_url($url);
    parse_str($parts['query'], $query);
    return $query['v'];
}

function newLine(){
    echo "<br>";
}

// Read file CSV
function readCSVfile($fileName, $delimiter = ','){
    if (!file_exists($fileName) || !is_readable($fileName)) {
        return false;
    }
    $header = null;
    $dataFileCSV = [];
    if (($handle = fopen($fileName, 'r')) !== false) {
        while (($row = fgetcsv($handle, 1500, $delimiter)) !== false) {
            if (!$header) {
                $header = $row;
            } else {
                try {
                    $rowData = array_combine($header, $row);

                    $dataFileCSV[] = $rowData;
                } catch (Exception $e) {
                    print_r($row);
                    echo $e->getMessage();
                    exit;
                }
            }
        }
        fclose($handle);
    }
    return $dataFileCSV;
}

function saveToFile($fileName, $content) {
    $myfile = fopen("output/".$fileName, "w") or die("Unable to open file!");
    fwrite($myfile, $content);
    fclose($myfile);

    echo "save to file:";
    echo $fileName;
    newLine();
}

function checkVideoMarkToInsert($videoId, $genreId){
    foreach ($GLOBALS['newVideoIdsToInsert'] as $key => $value) {
        if($value == $videoId.$genreId){
            return true;
        }
    }
    array_push($GLOBALS['newVideoIdsToInsert'], $videoId.$genreId);
    return false;
}

function replaceSpecialCharacter($input){
    $output = str_replace("'", "\'", $input);   // My's song -> My\'s song
    // $output = str_replace("/", "\/", $output);   // Classic\R&B -> Classic\/R&B
    return $output;
}

function randomDate($input){
    $date = date_create($input);
    $hour = rand(10, 23);
    $minute = rand(1, 59);
    return $date->format("Y-m-d $hour:$minute:00");
}

// GLOBAL varialbes
$GLOBALS['newVideoIdsToInsert'] = array();
$collection_date = [];

$data_C_All = readCSVfile('csv/C_Nov_29_30_13456789_12_13_2016_DB-INSERTED.csv');

$newCollectionVideoToInsert="";
$collectionOrTagLineNotFound = "";

$newVideoToInsert = "";
$videosNotFound = "";
$duplicatedVideos = "";
$genreNotFound = "";
$duplicatedVideoCsv = "";

foreach ($data_C_All as $key => $video) {
    // test only
    // if($key > 10){
    //     break;
    // }

    $SubGenre = $video['Sub Genre'];
    $ArtistName = replaceSpecialCharacter($video['Artist Name']);
    $SongName = replaceSpecialCharacter($video['Song Name']);
    $SongDate = $video['SongDate'].' 12:00:00';
    $SubGenre1 = replaceSpecialCharacter($video['Sub Genre 1']);
    $SubGenre2 = replaceSpecialCharacter($video['Sub Genre 2']);
    $YouTubeUrl = $video['YouTube URL'];

    $CollectionName = replaceSpecialCharacter($video['CollectionName']);
    $TagLine = replaceSpecialCharacter($video['Tagline']);
    $DatePublish = $video['Date'];
    
    if($YouTubeUrl == NULL){
        $YouTubeUrl = $video['URL'];
    }

    $VideoFileName = findVideoFileNameFromUrl($YouTubeUrl);

    $videoIdDb = findVideoIdByFilename($conn, $VideoFileName, $YouTubeUrl);

    if($videoIdDb == NULL){
        $videoIdDb = findVideoIdByTitleAndArtist($conn, $SongName, $ArtistName);
    }

    if($videoIdDb == NULL){
        echo 'video not found: '.$SongName."-".$ArtistName."-".$YouTubeUrl.'<br>';
        $videosNotFound = $videosNotFound.$SongName."-".$ArtistName."-".$YouTubeUrl."\n";
        continue;
    }

    // Collection Video
    if($CollectionName != NULL){
        $collectionId = findCollectionIdByName($conn, $CollectionName);
        $tagLineId = findTagLineIdByTitle($conn, $TagLine);
        $date2 = $DatePublish;

        // auto time substract 2 hours
        if(isset($collection_date[$CollectionName])){
            $date = date_create($collection_date[ $CollectionName ]);
            $date->modify("- 2 hour 00 minutes 00 seconds");
            $date2 = $date->format('Y-m-d H:i:s');
            $collection_date[ $CollectionName ] = $date2;
        }else{
            $date = date_create($DatePublish);
            $hour = rand(10, 23);
            $minute = rand(1, 59);
            $date2 = $date->format("Y-m-d $hour:$minute:00");
            $collection_date[ $CollectionName ] = $date2;
        }

        if($collectionId != NULL && $tagLineId != NULL) {
            $newCollectionVideoToInsert = $newCollectionVideoToInsert."INSERT INTO `v4`.`collection_video`(`Id`, `CollectionId`, `VideoId`, `Taglines`, `Ext`) VALUES (nextval_ex('collection_video'),'$collectionId','$videoIdDb', '[$tagLineId]', '{\"Rank\": 6, \"published_at\":\"$date2\"}');"."\n";
        }else{
            if($collectionId == NULL){
                echo "collection not found: <strong> $CollectionName </strong>";
                $collectionOrTagLineNotFound = $collectionOrTagLineNotFound."collection not found: $CollectionName";
            }else{
                echo "collection: <strong> $CollectionName </strong>";
                $collectionOrTagLineNotFound = $collectionOrTagLineNotFound."collection: $CollectionName";
            }

            if($tagLineId == NULL){
                echo " tagline not found: <strong> $TagLine </strong>";
                $collectionOrTagLineNotFound = $collectionOrTagLineNotFound." tagline not found: $TagLine";
            }else{
                echo " tagline <strong> $TagLine </strong>";
                $collectionOrTagLineNotFound = $collectionOrTagLineNotFound." tagline $TagLine";
            }

            echo " videoId: $videoIdDb url: $YouTubeUrl";
            newLine();

            $collectionOrTagLineNotFound = $collectionOrTagLineNotFound." videoId: $videoIdDb url: $YouTubeUrl"."\n";
        }
    }
    
}

echo '<hr>';

saveToFile("new_collection_video_to_insert.sql",$newCollectionVideoToInsert);
saveToFile("collection_or_tagline_not_found.txt",$collectionOrTagLineNotFound);

//closeDb on completed
closeDb($conn);

