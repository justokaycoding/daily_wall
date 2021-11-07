<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class dw_sql{

  function __construct(){
  }

  public function createTable(){
    //creates wp_dailyWall in Database
    global $wpdb;
    $tablename = $wpdb->prefix . "dailyWall";

    $sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
            `word` varchar(250) NOT NULL,
            `wCount` int(11) NOT NULL,
            `wDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
          ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
          ";

    $wpdb->query( $sql );

    $sql = "ALTER TABLE `$tablename`
            ADD UNIQUE KEY `word` (`word`);";

    $wpdb->query( $sql );
  }

  public function addToTable($word){
    // Addes input from user to database
    if(strlen($word) == 0) return;

    global $wpdb;
    $tablename = $wpdb->prefix . "dailyWall";
    $word = $this->wordClean($word);
    $word = $this->wordSanitize($word);

    if(strlen($word) == 0) return;

    $sql = "INSERT INTO $tablename (`word`, `wCount`, `wDate`) VALUES ('$word', '1' , CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE wCount=wCount + 1;
           ";

    $wpdb->query( $sql );
  }

  public function getData($orderBy = 'wDate', $sortBy = 'Asc'){
    // gets data from the wp_dailyWall
    global $wpdb;
    $tablename = $wpdb->prefix . "dailyWall";
    $sql = "SELECT * FROM $tablename ORDER BY $orderBy $sortBy";
    $results = $wpdb->get_results( $sql );
    $results = !empty($results) ? $results : '';

    if(empty($results)){
      $this->addToTable("John Was Here");
      $this->getData();
    }

    return $results;
  }

  public function wordClean($word){
    //removes curse words
    $filePath = plugin_dir_path( __FILE__ ).'badwords.txt';
    $badwordsFile = file_get_contents($filePath);
    $pattern = "/$word/i";
    if(preg_match($pattern, $badwordsFile)){
      $length = strlen($word);
      $newWord = substr($word, 0, 1) . str_repeat('*', $length - 1);
    } else{
      $newWord = $word;
    }
    return $newWord;
  }

  public function wordSanitize($word){
    //sanitizes word for the DataBase
    $word = addslashes(strtolower(htmlspecialchars(trim(strip_tags($word)))));
    return $word;
  }

  public function deleteData(){
    //deletes data based on the time based
    //on the options vaule
    global $wpdb;
    $options = unserialize(get_option( 'dw_settingsPage' ));
    $nthdays = $options['dw_deleteIN'];
    $tablename = $wpdb->prefix . "dailyWall";

    $sql = "DELETE FROM $tablename
            WHERE (SELECT CURRENT_TIMESTAMP) > DATE_ADD(`wDate`, INTERVAL $nthdays DAY)";
    $wpdb->query( $sql );
  }

  public function dropTable(){
    // drops table
    global $wpdb;
    $tablename = $wpdb->prefix . "dailyWall";
    $sql = "DROP TABLE IF EXISTS $tablename;";
    $wpdb->query( $sql );
  }
}
