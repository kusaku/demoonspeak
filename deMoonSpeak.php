<?php
  /**
   * Created by PhpStorm.
   * User: kusaku
   * Date: 11.01.14
   * Time: 2:45
   */

  /**
   * http://www.unicodemap.org/
   * main part - 0x4E00 - 0x9FFF : CJK Unified Ideographs
   * using extended range 0x2E80 - 0xFFFF
   */

  define('CN_SYMBOLS', '\x{2E80}-\x{FFFF}');
  define('EN_SYMBOLS', 'A-Za-z@$0-9+\-*=~<|>.:,;!?\t ');
  define('MS_SYMBOLS', CN_SYMBOLS.EN_SYMBOLS);
  define('CN_SYMBOLS_REGEXP', '/['.CN_SYMBOLS.']+/u');

  #                             main unicode regexp pattern structure
  #                             (?|_______inline_comments_pattern________|_____________________block_comments_pattern______________________)
  #                             ___________________(_____chinese_____)___|_______________________(_____chinese_____)________________________
  #                             (?|(?:__|______)___(___$matches[1]___)___|_______________________(___$matches[1]___)_______________________)
  define('MS_COMMENT_REGEXP', '/(?|(?:#+|\/{2,})\s*(['.MS_SYMBOLS.']+)\s*|\/\*['.EN_SYMBOLS.'\n]*(['.MS_SYMBOLS.']+)['.EN_SYMBOLS.'\n]*\*\/)/u');


  require_once 'GoogleTranslate.php';

  /**
   * @param $file SplFileInfo Source code file object
   */
  function deMoonSpeak($file) {
    // counters
    static $t_file = 0, $c_file = 0;
    static $t_translate = 0, $c_translate = 0;
    static $t_bytes = 0, $c_bytes = 0;

    /**
     * @var $cache array Simple file cache
     */
    static $cache = array();

    if (empty($cache) and file_exists('cache.php')) {
      $cache = include 'cache.php';
    }

    /**
     * @var $original string Original source code
     */
    $original = file_get_contents($file->getPathname());

    $t_file++;

    if (preg_match_all(MS_COMMENT_REGEXP, $original, $matches)) {

      $c_file++;

      foreach ($matches[1] as $source) {
        if (preg_match(CN_SYMBOLS_REGEXP, $source) > 0) {

          $t_translate++;
          $t_bytes += strlen($source);

          if (isset($cache[$source])) {
            $translation = $cache[$source];
          } else {
            $translation = GoogleTranslate::translate($source, 'zh-CN', 'en');

            if (empty($translation)) {
              exit('GoogleTranslate returned empty string!');
            }

            $cache[$source] = $translation;
            $c_translate++;
            $c_bytes += strlen($source);
          }

          $original = str_replace($source, $translation, $original);

          echo "[{$c_file}/{$t_file}:{$c_translate}/{$t_translate}({$c_bytes}/{$t_bytes})] {$file->getFilename()}: translated {$source} to {$translation}.\n";
        }
      }

      file_put_contents($file->getPathname(), $original);
    }

    if (!empty($cache)) {
      file_put_contents('cache.php', "<?php\n\nreturn " . var_export($cache, TRUE) . ";");
    }
  }

  $path = $argv[1];

  if (is_dir($path)) {

    // using SPL

    /**
     * @var $rdi RecursiveDirectoryIterator
     */
    $rdi = new RecursiveDirectoryIterator($argv[1]);

    /**
     * @var $rii RecursiveIteratorIterator
     */
    $rii = new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::SELF_FIRST);

    //TODO: refactor file processing bia SPL
    /**
     * @var $file SplFileInfo
     */

    foreach ($rii as $file) {
      //TODO: wrap $file into RecursiveRegexIterator and use MIME type
      if (!$file->isDir() and $file->isWritable() and preg_match('/.+\.(?:php|txt|tpl|htm|html|js|css)/i', $file->getFilename())) {
        deMoonSpeak($file);
      }
    }
  }
  elseif (is_file($path)) {

    $file = new SplFileInfo($path);
    deMoonSpeak($file);

  } else {
    exit('Must be path or file');
  }



