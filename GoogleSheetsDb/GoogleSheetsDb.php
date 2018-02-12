<?php

namespace GoogleSheetsDb;

class GoogleSheetsDb
{

  protected $googlePageId;
  protected $tables;

  /**
   * GoogleSheetsDb constructor.
   *
   * @param $spreadsheetUrl
   */
  function __construct($spreadsheetUrl)
  {
    $this->googlePageId = $this->makeGoogleIdFromUrl($spreadsheetUrl);
    $this->loadTables();
  }

  /**
   *
   */
  protected function loadTables()
  {
    //Get all worksheets in the spreadsheet
    $url = "https://spreadsheets.google.com/feeds/worksheets/" . $this->googlePageId . "/public/full?alt=json";
    $json = file_get_contents($url);
    $jsonDecoded = json_decode($json, true);
    //the useful rows
    $arrayWorksheets = $jsonDecoded['feed']['entry'];

    $worksheets = [];
    foreach ($arrayWorksheets as $worksheet) {
      $worksheetTitle = $worksheet['title']['$t'];
      $url = $worksheet['link'][0]['href'] . "?alt=json"; //select the first option (xml), but ask specifically for the json format

      $worksheets[] = [
        'title' => $worksheetTitle,
        'url'   => $url,
      ];
    }
    $this->tables = $worksheets;
  }


  /**
   * @param $title
   *
   * @return array|null
   */
  public function getTableByTitle($title)
  {
    //loop through all the tables, and check if the requested title matches one.
    for ($i = 0; $i < count($this->tables); $i++) {

      $table = $this->tables[$i];
      if ($table['title'] == $title) {
        $result = null;
        $json = file_get_contents($table['url']);
        $jsonDecoded = json_decode($json, true);

        //the sorta useful bits
        $entries = $jsonDecoded['feed']['entry'];

        //Match found...

        foreach ($entries as $rows) {

          $return_row = [];

          foreach ($rows as $key => $value) {
            if (!isset($value['$t'])) {
              continue;
            } //No data for this row. Skipping.

            $value = $value['$t'];
            //google uses the dollar sign to delineate the first row of headings

            if (strpos($key, '$') > 0) { //if theres a dollar sign, it is one of our defined headings.

              $key = substr($key, 4); //chop off the first four characters ' gsx$ ' to define the key

              $return_row += [
                $key => $value,
              ];
            }
          }
          $result[] = $return_row;
        }
        return $result;
      }
    }
  }

  /**
   * @return mixed
   */
  public function listTables()
  {
    return $this->tables;
  }

  /**
   * @param $key
   *
   * @return mixed|string
   */
  public function makeHumanReadableKey($key)
  {
    $key = str_replace("-", " ", $key);
    $key = ucwords($key);
    return $key;
  }


  /**
   * @param $spreadsheetUrl
   *
   * @return string
   */
  public function makeGoogleIdFromUrl($spreadsheetUrl)
  {
    $strPos = strpos($spreadsheetUrl, "/d/") + 3;   //Remove everything from the front of the ID
    $spreadsheetUrl = substr($spreadsheetUrl, $strPos);
    return  strstr($spreadsheetUrl, "/", true); //Return everything before the first remaining / character
  }

  /**
   * @return string
   */
  public function getGooglePageId()
  {
    return $this->googlePageId;
  }

  /**
   * @param string $googlePageId
   */
  public function setGooglePageId($googlePageId)
  {
    $this->googlePageId = $googlePageId;
  }

  /**
   * @param mixed $tables
   *
   * @return GoogleSheetsDb
   */
  public function setTables($tables)
  {
    $this->tables = $tables;
    return $this;
  }

}