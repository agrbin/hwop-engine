<?php

class DotRenderer {

  static private $instance = null;

  public static function render($src) {
    if (self::$instance === null) {
      self::$instance = new DotRenderer();
    }
    return self::$instance->doRender($src);
  }

  private function doRender($src) {
    $pipes = null;
    $process = proc_open(
      "dot -Tsvg -Nfontsize=8 -Nfontname='Helvetica Neue' -Efontsize=8",
      array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
      ),
      $pipes
    );
    if (is_resource($process)) {
      $this->my_fwrite($pipes[0], $src);

      $bin_out = "";
      $this->my_fread($pipes[1], $bin_out);
      $lines = explode("\n", $bin_out);
      $sol = "";
      $moze = false;
      foreach ($lines as $l) {
        if (substr($l, 0, 4) == "<svg") $moze = 1;
        if ($moze) $sol .= $l;
      }
      $bin_out = $sol;
      $tmp = "";
      $this->my_fread($pipes[2], $tmp);
      if (proc_close($process)) {
        $bin_out = $tmp;
      }
    } else {
      $bin_out = "PHP-dot: checker proc_open failed.";
      proc_close($process);
    }
    return $bin_out;
  }

  /**
   * read up everything in this file to string.
   * close the file afterwards.
   */
  private function my_fread($handle, &$target) {
    while (!feof($handle)) {
      $target .= fread($handle, 8192);
    }
    fclose($handle);
  }

  /**
   * write up the whole string into the file handle.
   * close that file handle afterwards.
   */
  private function my_fwrite($handle, $target) {
    fwrite($handle, $target, mb_strlen($target));
    fclose($handle);
  }

};

