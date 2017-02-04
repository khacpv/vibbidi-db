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

// GLOBAL varialbes
$GLOBALS['newVideoIdsToInsert'] = array();

$data_C_All = readCSVfile('csv/C_Nov_29_30_13456789_12_13_2016_DB-INSERTED.csv');

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

    // Subgenre 1
    if($SubGenre1 != NULL) {
        $genreIdDb1 = findGenreByTitle($conn, trim($SubGenre1));

        if($genreIdDb1 != NULL){
            $isExisted1 = checkVideoIdExisted($conn, $videoIdDb, $genreIdDb1);
            if(!$isExisted1){
                if(!checkVideoMarkToInsert($videoIdDb, $genreIdDb1)){
                    $newVideoToInsert = $newVideoToInsert."INSERT INTO `V4`.`video_genre` (`VideoId`, `GenreId`, `Category`, `ReleasedAt`) VALUES ('$videoIdDb', '$genreIdDb1', 'classic', '$SongDate');\n";
                }else{
                    echo 'duplicated video in csv: videoId: '.$videoIdDb.' genreId: '.$genreIdDb1;
                    newLine();
                    $duplicatedVideoCsv = $duplicatedVideoCsv."duplicate: ('$videoIdDb', '$genreIdDb1', 'classic', '$SongDate');\n";
                }
            }else{
                $duplicatedVideos = $duplicatedVideos.$SongName."-".$ArtistName."-".$YouTubeUrl."\n";
            }
        }else{
            echo 'genre not found: '.$SubGenre1;
            newLine();
            $genreNotFound = $genreNotFound.$SubGenre1." - Song: ".$SongName."- Artist: ".$ArtistName."- url: ".$YouTubeUrl."\n";
        }
        
    }

    // Subgenre 2
    if($SubGenre2 != NULL) {
        $genreIdDb2 = findGenreByTitle($conn, trim($SubGenre2));

        if($genreIdDb2 != NULL){
            $isExisted2 = checkVideoIdExisted($conn, $videoIdDb, $genreIdDb2);
            if(!$isExisted2){
                if(!checkVideoMarkToInsert($videoIdDb, $genreIdDb2)){
                    $newVideoToInsert = $newVideoToInsert."INSERT INTO `V4`.`video_genre` (`VideoId`, `GenreId`, `Category`, `ReleasedAt`) VALUES ('$videoIdDb', '$genreIdDb2', 'classic', '$SongDate');\n";
                }else{
                    echo 'duplicated video in csv: videoId: '.$videoIdDb.' genreId: '.$genreIdDb2;
                    newLine();
                    $duplicatedVideoCsv = $duplicatedVideoCsv."duplicate: ('$videoIdDb', '$genreIdDb2', 'classic', '$SongDate');\n";
                }
            }else{
                $duplicatedVideos = $duplicatedVideos.$SongName."-".$ArtistName."-".$YouTubeUrl."\n";
            }
        }else{
            echo 'genre not found: '.$SubGenre2;
            newLine();
            $genreNotFound = $genreNotFound.$SubGenre2." - Song name: ".$SongName."- artist name: ".$ArtistName."- url: ".$YouTubeUrl."\n";
        }
    }
}

echo '<hr>';

saveToFile("new_video_to_insert.sql",$newVideoToInsert);
saveToFile("video_not_found.txt",$videosNotFound);
saveToFile("duplicated_videos.txt",$duplicatedVideos);
saveToFile("genre_not_found.txt",$genreNotFound);
saveToFile("duplicated_video_in_csv.txt",$duplicatedVideoCsv);

//closeDb on completed
closeDb($conn);

