<?php
  /**
   * Created by PhpStorm.
   * User: kusaku
   * Date: 11.01.14
   * Time: 2:38
   */

  /**
   * Class GoogleTranslate
   * TODO: add language detection service
   */
  class GoogleTranslate {

    private static $APIKey = 'AIzaSyCkBV8HsNOGkqQ81ZIpiYQrZxKwDH1CqtQ';

    /**
     * @var string
     */
    private static $serviceURL = 'https://www.googleapis.com/language/translate/v2';

    /**
     * @param array $params
     * @return mixed
     */
    public static final function queryService(array $params) {
      $params['key'] = self::$APIKey;
      $queryString = http_build_query($params);
      $curl = curl_init(self::$serviceURL . "?" . $queryString);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($curl, CURLOPT_HEADER, FALSE);
      $json = curl_exec($curl);
      sleep(1);
      return json_decode($json);
    }

    /**
     * @param string $text
     * @param string $from
     * @param string $to
     * @return string
     */
    public static function translate($text = '', $from = 'en', $to = 'en') {
      $params = array(
        'q'      => $text,
        'source' => $from,
        'target' => $to,
      );
      $translation = self::queryService($params);
      if (isset($translation->data->translations)) {
        return $translation->data->translations[0]->translatedText;
      }
      else {
        return '';
      }
    }
  }

