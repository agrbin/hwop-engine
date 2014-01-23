<?php

/**
 * When you use
 *
 * !!!
 * Anton Grbin
 * !!!
 *
 * Pattern in HWOP markup, you will render a copyright notice from
 * creative-commons.
 *
 * @group markup
 */
final class HWOPCopyrightBlockRule
  extends PhutilRemarkupEngineBlockRule {

  public function getMatchingLineCount(array $lines, $cursor) {
    $num_lines = 0;

    if (preg_match("/^!!!$/", $lines[$cursor])) {
      $num_lines++;
      $cursor++;
      while (isset($lines[$cursor])) {
        if ($num_lines == 1 || trim($lines[$cursor-1]) != "!!!") {
          $num_lines++;
          $cursor++;
          continue;
        }
        break;
      }
    }

    return $num_lines;
  }

  public function shouldMergeBlocks() {
    return true;
  }

  public function markupText($text) {
    $text = str_replace("!!!", "", $text);
    if (!$text) {
      return null;
    }
    $this->getEngine()->setTextMetadata("copyright", $text);

    $who = nl2br(phutil_tag(
      'span',
      array(
        'style' => 'border:0',
      ),
      $text
    ));

    $text = phutil_safe_html('
    <div style="padding-top: 5px; margin-top: 50px;
      border-top: 1px #ccc solid;text-align:right;margin-left:auto;">
      &copy;&nbsp;' .$who. '
      <a
        rel="license"
        style="display:block;"
        href="http://creativecommons.org/licenses/by-sa/3.0/hr/deed.en_US"
      >
        <img
          alt="Creative Commons License"
          style="margin-left: auto; margin-top: 10px; margin-bottom: 10px;"
          src="/img/cc_88x31.png"
          width="88px" height="31px"/>
      </a>
      <p>
        Ovaj članak objavljen je pod<br/>
        <a
          rel="license"
          href="http://creativecommons.org/licenses/by-sa/3.0/hr/deed.en_US"
        >
          Creative Commons Attribution-ShareAlike 3.0 Croatia License
        </a>
      </p>
    </div>
    ');

    return $this->getEngine()->storeText($text);
  }

}
