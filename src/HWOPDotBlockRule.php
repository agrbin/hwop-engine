<?php

require "DotRenderer.php";

final class HWOPDotBlockRule
  extends PhutilRemarkupEngineBlockRule {

  public function getMatchingLineCount(array $lines, $cursor) {
    $num_lines = 0;

    if (preg_match("/^(graph|digraph)([ a-zA-Z0-9_]+)?{/", $lines[$cursor])) {
      $num_lines++;
      $cursor++;
      while (isset($lines[$cursor])) {
        if (trim($lines[$cursor-1]) != "}") {
          $num_lines++;
          $cursor++;
          continue;
        }
        break;
      }
    }

    return $num_lines;
  }

  public function markupText($text) {
    $cache = new PhutilKeyValueCacheAPC();
    if (($img = $cache->getKey($text)) === null) {
      $img = DotRenderer::render($text);
      $cache->setKey($text, $img, 300);
    }
    $img = phutil_safe_html($img);

    $embed = phutil_tag(
      'div',
      array(
        'style' => "text-align: center;"
      ),
      $img
    );

    return $this->getEngine()->storeText($embed);
  }

}
