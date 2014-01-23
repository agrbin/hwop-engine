<?php

require "LatexRenderer.php";

final class HWOPRuleLatex
  extends PhutilRemarkupRule {

    // $..$ is latex. first char is not space and last char is not space.
    // after last $ we must have space.
    // these rules are to avoid opening latex placeholders
    // by mistake.

  public function apply($text) {
    return preg_replace_callback(
      '/\$([^[:blank:]]|[^[:blank:]](.*?)[^[:blank:]])\$(\s(.)|\s*$)/u',
      array($this, 'markupInlineLatex'),
      $text
    );
  }

  public static function doIt($type, $text, $next_char = '') {

    $cache = new PhutilKeyValueCacheAPC();
    // in php.ini apc.enable_cli must be set.
    $key = $type.$text;
    if (($img = $cache->getKey($key)) === null) {
      $img = LatexRenderer::render($text, $type);
      $cache->setKey($key, $img, 300);
    }
    $img = phutil_safe_html($img);

    $interpunkcije = ".,:(){}?!";
    if ($next_char !== '' && (strpos($interpunkcije, $next_char) === false)) {
      $razmak = " ";
    } else {
      $razmak = "";
    }

    if ($type == 'inline') {
      $embed = phutil_tag(
        'span',
        array(
          'style' => "font-size:100%; display:inline-block;"
        ),
        $img
      );
    } else {
      $embed = phutil_tag(
        'div',
        array(
          'style' => "text-align: center;"
        ),
        $img
      );
    }

    $embed = phutil_safe_html($embed
      . phutil_escape_html($razmak.$next_char));

    return $embed;
  }

  public function markupInlineLatex($matches) {
    return $this->getEngine()->storeText(
      self::doIt("inline", $matches[1], idx($matches, 4, '')));
  }

}
