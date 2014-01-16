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
  
  define('CACHE_FILE', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'cache.php');


  require_once 'GoogleTranslate.php';

  /**
   * @param $file SplFileInfo Source code file object
   */
  function deMoonSpeak($file, $area = '') {
    // counters
    static $t_file = 0, $c_file = 0;
    static $t_translate = 0, $c_translate = 0;
    static $t_bytes = 0, $c_bytes = 0;

    /**
     * @var $cache array Simple file cache
     */
    static $cache = array();

    if (empty($cache) and file_exists(CACHE_FILE)) {
      $cache = include CACHE_FILE;
    }

    /**
     * @var $original string Original source code
     */
    $original = file_get_contents($file->getPathname());

    $encoding = mb_detect_encoding($original);
    mb_internal_encoding($encoding);

    $t_file++;

    $source = '';

    if (4 == count($area = explode(':', $area))) {
      list($from_line, $from_column, $to_line, $to_column) = $area;
      $lines = file($file->getPathname());
      $source = '';
      if ($from_line > count($lines) || $from_line >= $to_line && $from_column >= $to_column) {
      } else do {
        if ($from_line == $to_line) {
          $source .= mb_substr($lines[$from_line - 1], $from_column - 1, $to_column - $from_column);
          break;
        }
        if ($from_line < $to_line) {
          $source .= $lines[$from_line - 1];
          $from_line++;
        }
        if ($from_line == $to_line) {
          $source .= mb_substr($lines[$from_line - 1], 0, $to_column - $from_column);
          break;
        }
      } while (true);
    }

    if (0 < mb_strlen($source)) {
      if (isset($cache[$source])) {
        $translation = $cache[$source];
      }
      else {
        $translation = GoogleTranslate::translate($source, 'zh-CN', 'en');
        if (empty($translation)) {
          exit('GoogleTranslate returned empty string!');
        }
        $cache[$source] = $translation;
      }
      $original = str_replace($source, $translation, $original);
      echo "{$file->getFilename()}: translated {$source} to {$translation}.\n";
      file_put_contents($file->getPathname(), $original);
    }

    elseif (preg_match_all(MS_COMMENT_REGEXP, $original, $matches)) {
      $c_file++;
      foreach ($matches[1] as $source) {
        if (preg_match(CN_SYMBOLS_REGEXP, $source) > 0) {
          $t_translate++;
          $t_bytes += strlen($source);
          if (isset($cache[$source])) {
            $translation = $cache[$source];
          }
          else {
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
      file_put_contents(CACHE_FILE, "<?php\n\nreturn " . var_export($cache, TRUE) . ";");
    }
  }

  $path = $argv[1];
  $area = isset($argv[2]) ? $argv[2] : '';

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
        deMoonSpeak($file, $area);
      }
    }
  }
  elseif (is_file($path)) {

    $file = new SplFileInfo($path);
    deMoonSpeak($file, $area);

  } else {
    exit('Must be path or file');
  }



