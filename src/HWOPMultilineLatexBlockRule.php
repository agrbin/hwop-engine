<?php

final class HWOPMultilineLatexBlockRule
  extends PhutilRemarkupEngineBlockRule {

  public function getMatchingLineCount(array $lines, $cursor) {
    $num_lines = 0;

    if (trim($lines[$cursor]) == "$$") {
      $num_lines++;
      $cursor++;
      while (isset($lines[$cursor])) {
        $t = trim($lines[$cursor]);
        $lt = trim($lines[$cursor - 1]);
        if ($t && ($num_lines == 1 || $lt != "$$")) {
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
    $text = trim(substr($text, 3, strlen($text) - 6));
    return $this->getEngine()->storeText(
      HWOPRuleLatex::doIt("display", $text)
    );
  }

}

