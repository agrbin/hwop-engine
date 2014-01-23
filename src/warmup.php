<?php

function test_dot_available() {
  $a = DotRenderer::render("graph a {a -- b;}");
  if (!strlen($a)) {
    echo (
      "dot renderer doesn't work. make sure you have graphviz on your system.\n"
    );
    exit(1);
  }
}

function test_svgtex_available() {
  $a = LatexRenderer::render("inline", "2^x");
  if (!strlen($a)) {
    echo (
      "latex renderer doesn't work. make sure you have svgtex and phantomjs
      configured correctly.\n"
    );
    exit(1);
  }
}

function test_whole_markup() {
  $engine = new HWOPMarkupEngine();
  $a = $engine->render(<<<EOF
== test ==

This is exponential: $2^y$ !
.. And this graph is a chain:

this should be multiline latex
$$
2^3 + 6
$$

graph {
a -- b -- c -- d;
}

!!!
  Anton Grbin
!!!
EOF
  );
  file_put_contents("/home/agrbin/a.html", $a);
}

function test_all() {
  echo "testing dot support.. ";
  test_dot_available();
  echo "[OK]\n";
  echo "testing latex support.. ";
  test_svgtex_available();
  echo "[OK]\n";

  echo "testing libhputil markup impl... ";
  test_whole_markup();
  echo "[OK]\n";
}


