<?php
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
// Read file Bot(with SubGenre)_1130_2016.csv
$data_Genre_SubGenre = readCSVfile('Bot(with SubGenre)_1130_2016.csv');
echo '<pre>';
print_r($data_Genre_SubGenre);
echo '</pre>';

// Read file Genre-list.csv
$data_Genre = readCSVfile('Genre-list.csv');
echo '<pre>';
print_r($data_Genre);
echo '</pre>';

// Get GenreID from GenreName
function getGenreIdByGenreName($genreTitle, $data_Genres) {
    foreach( $data_Genres as $key => $genre ) {
        if( $genre['Genre'] == $genreTitle ) {
            return $genre['Id'];
        }
    }
}
// insert Subgenre
foreach($data_Genre_SubGenre as $key => $Genres )  {
    $genreParentID = getGenreIdByGenreName($Genres['Genre'],  $data_Genre);
    $query = "INSERT INTO `v4`.`genres`(`Id`, `ParentId`, `Title`) VALUES (nextval_ex('genres'), '$genreParentID', '" . $Genres['SubGenre'] . "');";
    echo $query;
    echo '<br>';
}

?>