<?php

// libhputil must be included here for this file to work.

// dot
require "HWOPDotBlockRule.php";

// latex
require "HWOPRuleLatex.php";
require "HWOPRuleImage.php";
require "HWOPMultilineLatexBlockRule.php";

// customs.
require "HWOPSlug.php";
require "CustomHeaderBlockRule.php";
require "HWOPCopyrightBlockRule.php";
require "HWOPRuleLink.php";

class HWOPMarkupEngine {

  private $engine;

  // fullRender will use these.
  private $html, $options, $text;
  private $inputPartition, $outputPartition;

  public function __construct() {
    $engine = new PhutilRemarkupEngine();

    $engine->setConfig('preserve-linebreaks', true);
    $engine->setConfig('pygments.enabled', true);
    $engine->setConfig('uri.allowed-protocols',
      array('http' => true, 'https' => true)
    );
    $engine->setConfig('header.generate-toc', true);
    $engine->setConfig(
      'syntax-highlighter.engine', 'PhutilDefaultSyntaxHighlighterEngine'
    );


    $rules = array();
    $rules[] = new PhutilRemarkupRuleEscapeRemarkup();
    $rules[] = new PhutilRemarkupRuleMonospace();
    $rules[] = new PhutilRemarkupRuleDocumentLink();
    $rules[] = new PhutilRemarkupRuleHyperlink();
    $rules[] = new HWOPRuleLink();
    $rules[] = new HWOPRuleLatex();
    $rules[] = new HWOPRuleImage();

    // first dot, than latex.

    $rules[] = new PhutilRemarkupRuleBold();
    $rules[] = new PhutilRemarkupRuleItalic();
    $rules[] = new PhutilRemarkupRuleDel();

    $blocks = array();
    $blocks[] = new HWOPDotBlockRule();
    $blocks[] = new HWOPMultilineLatexBlockRule();
    $blocks[] = new HWOPCopyrightBlockRule();

    $blocks[] = new PhutilRemarkupEngineRemarkupQuotesBlockRule();
    $blocks[] = new PhutilRemarkupEngineRemarkupLiteralBlockRule();
    $blocks[] = new CustomHeaderBlockRule();
    $blocks[] = new PhutilRemarkupEngineRemarkupListBlockRule();
    $blocks[] = new PhutilRemarkupEngineRemarkupCodeBlockRule();
    $blocks[] = new PhutilRemarkupEngineRemarkupNoteBlockRule();
    $blocks[] = new PhutilRemarkupEngineRemarkupTableBlockRule();
    $blocks[] = new PhutilRemarkupEngineRemarkupSimpleTableBlockRule();
    $blocks[] = new PhutilRemarkupEngineRemarkupDefaultBlockRule();

    foreach ($blocks as $block) {
      if ($block instanceof PhutilRemarkupEngineRemarkupLiteralBlockRule) {
        $literal_rules = array();
        $literal_rules[] = new PhutilRemarkupRuleLinebreaks();
        $block->setMarkupRules($literal_rules);
      } else if (!($block instanceof PhutilRemarkupEngineRemarkupCodeBlockRule)) {
        $block->setMarkupRules($rules);
      }
    }

    $engine->setBlockRules($blocks);

    $this->engine = $engine;
  }

  public function render($text) {
    return $this->engine->markupText($text);
  }

  public function fullRender($text, $options) {
    $sol = $this->engine->markupText($text);
    $this->html = $sol;
    $this->text = $text;
    $this->options = (is_array($options) ? $options : array());
    $this->calcPartitions();

    return array(
      "html" => "$sol",
      "metadata" => (array(
        "headers.toc" => json_encode(
          $this->readHeadersMetadata()
        ),
        "links" => json_encode(
          $this->engine->getTextMetadata("links", array())
        ),
        "copyright" => json_encode(
          $this->engine->getTextMetadata("copyright", false)
        ),
        "input.partition" => json_encode(
          $this->inputPartition
        ),
        "output.partition" => json_encode(
          $this->outputPartition
        ),
      ))
    );
  }

  private function readHeadersMetadata() {
    $sol = $this->engine->getTextMetadata("headers.toc");
    // convert safeHTML object to string vefore JSON encoding.
    if (is_array($sol)) {
      foreach ($sol as &$v) {
        $v[1] = "${v[1]}";
      }
    } else {
      $sol = array();
    }
    return $sol;
  }

  // line i level.
  private function calcPartitions() {
    // get the data
    $headers = $this->engine->getTextMetadata("headers.toc");
    $partition = $this->engine->getTextMetadata("partition", array());

    // get the options
    $partition_depth = json_decode(idx($this->options,
          'partition.depth', "2"));
    $check_first = json_decode(idx($this->options,
          'lint.check_first_paragraph', "true"));
    $min_header_level = json_decode(idx($this->options,
          'lint.min_header_level', "1"));

    // check the options
    if (!is_bool($check_first)) {
      throw new Exception(
            "invalid lint.check_first_paragraph $check_first");
    }
    if ($partition_depth < 1 || $partition_depth > 5) {
      throw new Exception(
            "invalid partition.depth $partition_depth");
    }
    if ($min_header_level < 1 || $min_header_level > 5) {
      throw new Exception(
            "invalid min_header_level $min_header_level");
    }

    // do the math.
    $lines = explode("\n", $this->text);
    $partitions = array();
    $level_ones = 0;
    $last_level = -1;
    $offset = 0;
    $out_offset = 0;

    foreach ($partition as $index => $header) {
      list($line, $level, $mark) = $header;

      // check for min header level
      if ($level < $min_header_level) {
        throw new Exception(
          "header $line is of level $level which is less than"
          . " min_header_level provided with options"
        );
      }

      // check for first paragraph
      if (!$index && $level != 1 && $check_first) {
        throw new Exception(
          "first paragraph must be = * ="
        );
      }
      $level_ones += ($level == 1);

      // header sigurno pocinje na pocetku linije.
      if ($level <= $partition_depth) {
        // dodaj u particiju inputa od offset-a do dijela di pocinje ovaj line
        $line_start = mb_strpos($this->text, $line, $offset);

        // na pocetku se mora nalaziti header.
        if ($index == 0 && $line_start != 0) {
          throw new Exception(
            "text must begin with partitionable header"
          );
        }

        // add partition if it has size (first one won't have)
        if ($offset != $line_start) {
          $mark_pos = mb_strpos($this->html, $mark, $out_offset);
          if ($mark_pos === false) {
            throw new Exception(
              "internal error. partition mark not found."
            );
          }
          // write down the partition.
          $this->inputPartition[] = array($last_level, $offset, $line_start);
          $this->outputPartition[] = array($last_level, $out_offset, $mark_pos);
          $offset = $line_start;
          $out_offset = $mark_pos;
        }
        $last_level = $level;
      }
    }

    // the eeeeend!
    $this->inputPartition[] =
      array($last_level, $offset, mb_strlen($this->text));
    $this->outputPartition[] =
      array($last_level, $out_offset, mb_strlen($this->html));

    if ($level_ones != 1 && $check_first) {
      throw new Exception("document must have exactly one = * =");
    }
  }

  /*
   * paragraphPartition will use $sol structure as exposed in render
   * function to partition text by paragraphs.
   *
   * it will lint the text so the following holds:
   *  - first line in text must be 1st header (= * =)
   *  - there must be only one 1st header in the document
   *
   * partition will be done by 2st headers (== * ==) and text
   * between 1st header and 2st header will be first partition. every
   * partiton have distinct id.
   *
   * returned structure looks like this:
   *
   * array(
   *  0 => ..partition source..
   *  1 => ...
   * ).
   *
   * concating text's from every partition will produce the input text
   * with +/- 1 newline at the end or begining.
   */
  private function paragraphPartition(&$sol) {
    $src = $sol["meta"]["source"];
    $lines = explode("\n", $src); $ptr = 0;
    $partitions = array(); $nptr = -1;
    $stack = array();
    $level_ones = 0;

    foreach ($sol["meta"]["partition"] as $index => $header) {
      $id = idx(array_keys($sol["meta"]["headers.toc"]), $index);
      list($src, $level) = $header;
      if (!count($stack) && $level != 1) {
        throw new Exception(
          "first paragraph must be = * ="
        );
      }
      $level_ones += ($level == 1);
      while (count($stack) && last($stack) > $level)
        array_pop($stack);
      $stack[] = $level;
      if ($level < 3) {
        while (trim($src) !== trim($lines[$ptr])) {
          if ($nptr == -1) {
            throw new Exception(
              "first line in text must be = * ="
            );
          }
          $partitions[$nptr] .= ($lines[$ptr++] . "\n");
        }
        $partitions[++$nptr] = "";
      }
    }
    while ($ptr < count($lines)) {
      $partitions[$nptr] .= ($lines[$ptr++] . "\n");
    }
    if ($level_ones != 1) {
      throw new Exception(
        "document must have exactly one = * ="
      );
    }
    $sol["meta"]["partition"] = $partitions;
  }
}

